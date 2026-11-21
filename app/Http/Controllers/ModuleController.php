<?php

namespace App\Http\Controllers;

use App\Models\Acknowledgement;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\DocumentFinder;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /** M02 Legal & compliance: register perizinan & kontrak dengan pengingat 90/60/30 hari. */
    public function legal(Request $request, DocumentFinder $finder)
    {
        $docs = $finder->search($request->user(), ['module' => 'legal'])->where('can_open', true)
            ->whereIn('status', ['published', 'approved', 'under_review', 'draft', 'submitted']);
        $withExpiry = $docs->whereNotNull('expires_at')->sortBy('expires_at');
        $permits = $withExpiry->filter(fn ($d) => $d->category->code === 'LEG-PER');
        $contracts = $withExpiry->filter(fn ($d) => $d->category->code === 'LEG-KON');
        $foundation = $docs->whereNull('expires_at')->filter(fn ($d) => $d->category->code === 'LEG-PER');
        $stats = [
            'expired' => $withExpiry->filter(fn ($d) => $d->daysToExpiry() < 0)->count(),
            'd30' => $withExpiry->filter(fn ($d) => $d->daysToExpiry() >= 0 && $d->daysToExpiry() <= 30)->count(),
            'd60' => $withExpiry->filter(fn ($d) => $d->daysToExpiry() > 30 && $d->daysToExpiry() <= 60)->count(),
            'd90' => $withExpiry->filter(fn ($d) => $d->daysToExpiry() > 60 && $d->daysToExpiry() <= 90)->count(),
        ];
        return view('modules.legal', compact('permits', 'contracts', 'foundation', 'stats'));
    }

    /** M03 Policy, SOP & organisasi: daftar berlaku + status acknowledgement. */
    public function policies(Request $request, DocumentFinder $finder)
    {
        $docs = $finder->search($request->user(), ['module' => 'policy'])->where('can_open', true)->where('status', 'published');
        $ackIds = Acknowledgement::where('user_id', $request->user()->id)->pluck('document_version_id')->all();
        $totalUsers = User::whereNotIn('role', ['service', 'auditor'])->where('is_active', true)->count();
        $ackCounts = Acknowledgement::selectRaw('document_version_id, count(*) c')->groupBy('document_version_id')->pluck('c', 'document_version_id');
        return view('modules.policies', compact('docs', 'ackIds', 'totalUsers', 'ackCounts'));
    }

    public function acknowledgements(Request $request, Document $document)
    {
        $this->authorizeDocument($document);
        abort_unless($request->user()->hasRole('doc_controller', 'hr', 'bod_gm') || $document->owner_id === $request->user()->id || $document->department?->head_user_id === $request->user()->id, 403);
        $acks = Acknowledgement::with('user.department')->where('document_version_id', $document->current_version_id)->get()->keyBy('user_id');
        $users = User::with('department')->whereNotIn('role', ['service', 'auditor'])->where('is_active', true)->orderBy('department_id')->orderBy('name')->get();
        return view('modules.acknowledgements', compact('document', 'acks', 'users'));
    }

    /** M05 Finance & tax, M06 Marketing & customer, HR, Operasional — arsip per modul dengan tautan sistem sumber. */
    public function module(Request $request, string $module, DocumentFinder $finder)
    {
        abort_unless(array_key_exists($module, DocumentCategory::MODULES), 404);
        $docs = $finder->search($request->user(), ['module' => $module] + $request->only(['q', 'category_id', 'status']));
        $categories = DocumentCategory::where('module', $module)->withCount('types')->get();
        $documents = DocumentFinder::paginate($docs, 20);
        $notes = [
            'finance' => 'Data finansial utama tetap di VHP / sistem keuangan. SENTRA menyimpan laporan final yang sah, bukti pendukung, pajak, anggaran dan evaluasi — dengan tautan ke record VHP & Ruci BSC.',
            'marketing' => 'Data customer master tetap di CRM GPCH. SENTRA menyimpan kontrak korporat, proposal, kampanye dan evaluasi — dengan tautan ke akun CRM.',
            'hr' => 'Dokumen personel bersifat terbatas dan tunduk pada UU Pelindungan Data Pribadi; akses berdasarkan need-to-know.',
            'operations' => 'Dokumen teknis, garansi dan sertifikat aset ditautkan ke GPCH Asset Care tanpa menyalin data inventaris.',
            'board' => 'Risalah dan keputusan Direksi dengan akses khusus; tindak lanjut dipantau di menu Rapat & Action Item.',
            'legal' => 'Lihat juga Register Legal untuk pemantauan masa berlaku.',
            'policy' => 'Lihat juga Pusat Kebijakan & SOP untuk status acknowledgement karyawan.',
        ];
        return view('modules.show', ['module' => $module, 'title' => DocumentCategory::MODULES[$module], 'documents' => $documents,
            'categories' => $categories, 'note' => $notes[$module] ?? null, 'filters' => $request->only(['q', 'category_id', 'status'])]);
    }
}
