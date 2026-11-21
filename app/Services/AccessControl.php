<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAcl;
use App\Models\User;
use App\Models\WorkflowStep;

/**
 * Keputusan izin SRS §5: RBAC + ABAC + ACL per dokumen.
 * izin = peran + unit organisasi + entitas hukum + klasifikasi + kepemilikan + status dokumen + masa berlaku akses.
 * Prinsip: default deny, least privilege, deny mengalahkan allow,
 * administrator teknis tidak otomatis memiliki hak baca.
 */
class AccessControl
{
    private array $cache = [];

    /** @return array{0: bool, 1: string} */
    public function decide(User $u, Document $d, string $action = 'view'): array
    {
        $key = $u->id.':'.$d->id.':'.$action;
        return $this->cache[$key] ??= $this->evaluate($u, $d, $action);
    }

    public function can(User $u, Document $d, string $action = 'view'): bool
    {
        return $this->decide($u, $d, $action)[0];
    }

    private function evaluate(User $u, Document $d, string $action): array
    {
        if (! $u->is_active) return [false, 'Akun tidak aktif.'];
        if ($u->isAccessExpired()) return [false, 'Masa berlaku akses akun Anda telah berakhir.'];
        if ($u->role === 'service') return [false, 'Service account hanya melalui API.'];

        $acl = $this->activeAcl($u, $d);

        // 1. Deny mengalahkan allow
        if ($acl->contains('permission', 'deny')) return [false, 'Akses Anda ke dokumen ini dibatasi secara eksplisit (deny).'];

        if ($d->status === 'disposed') {
            return $action === 'view' && $u->hasRole('doc_controller', 'legal', 'auditor', 'bod_gm')
                ? [true, 'Metadata tombstone dokumen musnah.']
                : [false, 'Dokumen telah dimusnahkan; hanya metadata dan berita acara yang tersisa.'];
        }

        $view = $this->canViewContent($u, $d, $acl);
        if (! $view[0]) return $view;

        return match ($action) {
            'view' => $view,
            'download' => $this->canDownload($u, $d, $acl),
            'edit' => $this->canEdit($u, $d, $acl),
            default => [false, 'Aksi tidak dikenal.'],
        };
    }

    private function canViewContent(User $u, Document $d, $acl): array
    {
        $isOwner = $d->owner_id === $u->id || $d->created_by === $u->id;
        if ($isOwner) return [true, 'Pemilik/pembuat dokumen.'];

        if ($this->isWorkflowParticipant($u, $d)) return [true, 'Peserta alur review/persetujuan.'];

        if ($acl->whereIn('permission', ['view', 'download', 'edit'])->isNotEmpty()) {
            return [true, 'Hak akses eksplisit (ACL).'];
        }

        // Sangat Rahasia: whitelist individu saja
        if ($d->classification === 'strictly_confidential') {
            return [false, 'Dokumen Sangat Rahasia hanya untuk individu dalam whitelist.'];
        }

        // Dokumen yang belum sah hanya untuk pemilik, peserta workflow, dan Document Controller
        if (! in_array($d->status, Document::READABLE_STATUSES)) {
            if ($u->hasRole('doc_controller')) return [true, 'Document Controller (pengelolaan publikasi).'];
            return [false, 'Dokumen belum disahkan; belum dapat dibaca umum.'];
        }

        // Auditor: read-only berdasarkan mandat yang masih berlaku
        if ($u->hasRole('auditor')) {
            foreach ($u->mandates as $m) {
                if ($m->covers($d)) return [true, 'Mandat audit '.$m->mandate_number.' (s.d. '.$m->ends_at->format('d/m/Y').').'];
            }
            return [false, 'Di luar cakupan mandat audit Anda.'];
        }

        $sameEntity = $u->entity_id === $d->entity_id;
        $strategic = $u->hasRole('owner', 'bod_gm');

        return match ($d->classification) {
            'public_internal' => ($sameEntity || $strategic)
                ? [true, 'Publik internal untuk pegawai entitas.']
                : [false, 'Dokumen milik entitas lain.'],
            'department' => (($sameEntity && $u->department_id === $d->department_id) || ($strategic && $u->hasRole('bod_gm')) || ($u->hasRole('owner')) || $this->isFunctionalOwner($u, $d))
                ? [true, 'Terbatas departemen: anggota departemen/atasan berwenang.']
                : [false, 'Dokumen terbatas untuk departemen '.$d->department?->name.'.'],
            'confidential' => ($this->isHeadOf($u, $d) || $u->hasRole('bod_gm') || $this->isFunctionalOwner($u, $d))
                ? [true, 'Rahasia: kepala departemen/BOD/fungsi berwenang.']
                : [false, 'Dokumen Rahasia: akses need-to-know melalui ACL. Ajukan permintaan akses.'],
            default => [false, 'Klasifikasi tidak dikenal.'],
        };
    }

    private function canDownload(User $u, Document $d, $acl): array
    {
        if ($d->classification === 'strictly_confidential' && ! $acl->contains('permission', 'download') && $d->owner_id !== $u->id) {
            return [false, 'Unduhan dokumen Sangat Rahasia dibatasi (hanya preview ber-watermark).'];
        }
        if ($acl->isNotEmpty() && $acl->every(fn ($a) => $a->permission === 'view')
            && $d->owner_id !== $u->id && ! $this->isHeadOf($u, $d)) {
            return [false, 'Hak akses Anda hanya untuk melihat (view-only).'];
        }
        return [true, 'Unduhan diizinkan dan dicatat.'];
    }

    private function canEdit(User $u, Document $d, $acl): array
    {
        if (! in_array($d->status, array_merge(Document::EDITABLE_STATUSES, ['published']))) {
            return [false, 'Dokumen sedang dalam proses atau sudah final.'];
        }
        if ($d->checked_out_by && $d->checked_out_by !== $u->id) {
            return [false, 'Dokumen sedang di-check-out oleh '.$d->checkedOutBy?->name.'.'];
        }
        if ($u->hasRole('auditor')) return [false, 'Auditor hanya read-only.'];
        if ($d->owner_id === $u->id || $d->created_by === $u->id || $u->hasRole('doc_controller')
            || $acl->contains('permission', 'edit') || $this->isHeadOf($u, $d)) {
            return [true, 'Boleh mengedit.'];
        }
        return [false, 'Hanya pemilik, kepala departemen, atau Document Controller yang dapat mengedit.'];
    }

    public function activeAcl(User $u, Document $d)
    {
        return $d->acl->filter(function (DocumentAcl $a) use ($u) {
            if (! $a->isActive()) return false;
            return ($a->subject_type === 'user' && (int) $a->subject_id === $u->id)
                || ($a->subject_type === 'department' && (int) $a->subject_id === (int) $u->department_id)
                || ($a->subject_type === 'role' && $a->subject_id === $u->role);
        });
    }

    private function isHeadOf(User $u, Document $d): bool
    {
        return $d->department && $d->department->head_user_id === $u->id;
    }

    /** Fungsi pemilik domain: Legal untuk modul legal, Finance untuk finance, HR untuk dokumen SDM. */
    private function isFunctionalOwner(User $u, Document $d): bool
    {
        $module = $d->category?->module;
        return ($u->hasRole('legal') && $module === 'legal')
            || ($u->hasRole('finance') && $module === 'finance')
            || ($u->hasRole('hr') && $module === 'hr');
    }

    private function isWorkflowParticipant(User $u, Document $d): bool
    {
        return WorkflowStep::whereHas('instance', fn ($q) => $q->where('document_id', $d->id))
            ->where(fn ($q) => $q->where('assignee_id', $u->id)->orWhere('delegated_to_id', $u->id))
            ->exists();
    }

    /** Apakah judul/metadata boleh tampil di hasil pencarian (tanpa membuka isi). AC-02 */
    public function canDiscover(User $u, Document $d): bool
    {
        if ($this->can($u, $d)) return true;
        if ($d->status === 'disposed') return false;
        if ($d->classification === 'strictly_confidential') return false; // tidak muncul sama sekali
        if (! in_array($d->status, Document::READABLE_STATUSES)) return false;
        if ($this->activeAcl($u, $d)->contains('permission', 'deny')) return false;
        return $u->entity_id === $d->entity_id || $u->hasRole('owner', 'bod_gm');
    }

    /** Pemeriksa pemisahan tugas: pembuat tidak boleh menyetujui dokumen material miliknya. */
    public function violatesSeparationOfDuties(User $u, Document $d): bool
    {
        return $d->type?->is_material && ($d->created_by === $u->id || $d->owner_id === $u->id);
    }
}
