<?php

namespace Database\Seeders;

use App\Models\AuditMandate;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Entity;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Totp;
use Illuminate\Database\Seeder;

/** Master data: entitas, departemen, pengguna demo, taksonomi, retensi & mandat auditor — kasus Grand Pasundan Convention Hotel. */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $gpch = Entity::create(['code' => 'GPCH', 'name' => 'PT Grand Pasundan Convention Hotel', 'address' => 'Jl. Peta No. 147–149, Bandung, Jawa Barat']);
        $gbk = Entity::create(['code' => 'GBK', 'name' => 'GBK — Entitas Afiliasi Grup', 'address' => 'Bandung, Jawa Barat']);

        $deps = [];
        foreach ([
            'BOD' => 'Direksi & General Manager', 'CORSEC' => 'Corporate Secretary', 'LEG' => 'Legal & Compliance',
            'FIN' => 'Finance & Accounting', 'HRD' => 'Human Resources', 'ENG' => 'Engineering', 'FO' => 'Front Office',
            'FNB' => 'Food & Beverage', 'HK' => 'Housekeeping', 'SM' => 'Sales & Marketing', 'IT' => 'Information Technology',
            'SEC' => 'Security & Safety',
        ] as $code => $name) {
            $deps[$code] = Department::create(['entity_id' => $gpch->id, 'code' => $code, 'name' => $name]);
        }
        $deps['GBK-BOD'] = Department::create(['entity_id' => $gbk->id, 'code' => 'BOD', 'name' => 'Direksi GBK']);
        $deps['GBK-FIN'] = Department::create(['entity_id' => $gbk->id, 'code' => 'FIN', 'name' => 'Keuangan GBK']);

        $users = [
            // key, nama, email, jabatan, peran, departemen, kepala?, ekstra
            ['owner', 'Ir. H. Dadang Suryana', 'owner@gpch.test', 'Owner / Komisaris Utama', 'owner', 'BOD', false],
            ['gm', 'Rina Kartikasari', 'gm@gpch.test', 'General Manager', 'bod_gm', 'BOD', true],
            ['director', 'Budi Santoso', 'direktur@gpch.test', 'Direktur Operasional', 'bod_gm', 'BOD', false],
            ['doccon', 'Siti Nurhaliza', 'corsec@gpch.test', 'Corporate Secretary & Document Controller', 'doc_controller', 'CORSEC', true],
            ['legal', 'Andi Pratama, S.H.', 'legal@gpch.test', 'Legal & Compliance Manager', 'legal', 'LEG', true],
            ['finance', 'Dewi Lestari, S.E.', 'finance@gpch.test', 'Finance & Tax Manager', 'finance', 'FIN', true],
            ['hr', 'Yusuf Maulana', 'hr@gpch.test', 'HR Manager', 'hr', 'HRD', true],
            ['eng', 'Agus Setiawan', 'engineering@gpch.test', 'Chief Engineer', 'dept_head', 'ENG', true],
            ['fo', 'Maya Anggraini', 'fo@gpch.test', 'Front Office Manager', 'dept_head', 'FO', true],
            ['fnb', 'Hendra Wijaya', 'fnb@gpch.test', 'F&B Manager', 'dept_head', 'FNB', true],
            ['hk', 'Ratna Sari', 'hk@gpch.test', 'Executive Housekeeper', 'dept_head', 'HK', true],
            ['sales', 'Lina Marlina', 'sales@gpch.test', 'Director of Sales & Marketing', 'dept_head', 'SM', true],
            ['security', 'Bambang Sutrisno', 'security@gpch.test', 'Chief Security & Safety', 'dept_head', 'SEC', true],
            ['engstaff', 'Rizky Ramadhan', 'rizky@gpch.test', 'Engineering Supervisor', 'staff', 'ENG', false],
            ['salesstaff', 'Putri Ayu Lestari', 'putri@gpch.test', 'Sales Executive', 'staff', 'SM', false],
            ['fostaff', 'Dimas Prakoso', 'dimas@gpch.test', 'Guest Service Agent', 'staff', 'FO', false],
            ['hkstaff', 'Nurul Hidayah', 'nurul@gpch.test', 'Room Attendant Supervisor', 'staff', 'HK', false],
            ['it', 'Fajar Nugroho', 'it@gpch.test', 'IT Manager (Admin Sistem)', 'it_admin', 'IT', true],
            ['auditor', 'Drs. Hartono Wibisono, Ak.', 'auditor@kap-mitra.test', 'Auditor Eksternal — KAP Mitra Audit', 'auditor', 'FIN', false],
            ['auditor_old', 'Sri Wahyuni', 'sri.audit@gpch.test', 'Internal Auditor (penugasan selesai)', 'auditor', 'FIN', false],
            ['gbk', 'Teguh Hidayat', 'teguh@gbk.test', 'Finance Officer GBK', 'finance', 'GBK-FIN', false],
            ['svc_asset', 'Service — GPCH Asset Care', 'svc-assetcare@gpch.test', 'Service account integrasi', 'service', 'IT', false],
            ['svc_crm', 'Service — CRM GPCH', 'svc-crm@gpch.test', 'Service account integrasi', 'service', 'IT', false],
        ];

        $u = [];
        foreach ($users as [$key, $name, $email, $pos, $role, $dep, $head]) {
            $d = $deps[$dep];
            $u[$key] = User::create([
                'name' => $name, 'email' => $email, 'password' => 'password', 'position' => $pos, 'role' => $role,
                'entity_id' => $d->entity_id, 'department_id' => $d->id, 'is_active' => true,
                'mfa_secret' => Totp::generateSecret(), 'email_verified_at' => now(),
                'can_bulk_export' => in_array($key, ['doccon', 'gm']),
                'access_expires_at' => match ($key) { 'auditor' => today()->addDays(20)->endOfDay(), 'auditor_old' => today()->subDays(10), default => null },
                'last_login_at' => in_array($role, ['service']) ? null : now()->subHours(rand(1, 72)),
            ]);
            if ($head) $d->update(['head_user_id' => $u[$key]->id]);
        }
        $deps['GBK-BOD']->update(['head_user_id' => $u['director']->id]);
        $deps['GBK-FIN']->update(['head_user_id' => $u['gbk']->id]);
        $u['svc_asset']->update(['api_token' => hash('sha256', 'demo-assetcare-token-0123456789')]);

        // ---------- Jadwal retensi ----------
        $r = [];
        foreach ([
            ['RET-PERM-LEG', 'Legalitas korporasi (akta, NIB)', 'Legal', 'effective', null, 'permanent', 'UU 8/1997 Dokumen Perusahaan; UU 40/2007 PT — dokumen pendirian disimpan permanen.'],
            ['RET-IZN-10', 'Perizinan & sertifikat', 'Legal', 'expiry', 10, 'destroy', 'Disimpan 10 tahun sejak berakhir sebagai bukti kepatuhan.'],
            ['RET-KON-10', 'Kontrak & perjanjian', 'Legal', 'expiry', 10, 'destroy', 'UU 8/1997 Ps. 11 — bukti hak & kewajiban 10 tahun.'],
            ['RET-POL-10', 'Kebijakan perusahaan', 'Kebijakan', 'superseded', 10, 'review', 'Disimpan 10 tahun setelah dicabut, lalu ditinjau.'],
            ['RET-SOP-5', 'SOP & instruksi kerja', 'SOP', 'superseded', 5, 'destroy', 'Versi tidak berlaku disimpan 5 tahun.'],
            ['RET-ORG-PERM', 'Keputusan direksi, risalah & organisasi', 'Board', 'effective', null, 'permanent', 'Arsip vital tata kelola — permanen.'],
            ['RET-FIN-10', 'Dokumen keuangan & perpajakan', 'Finance', 'effective', 10, 'destroy', 'UU 8/1997; UU KUP Ps. 28 ayat (11) — 10 tahun.'],
            ['RET-MKT-KON-5', 'Kontrak korporat & proposal', 'Marketing', 'expiry', 5, 'destroy', 'Kebutuhan bisnis — 5 tahun sejak berakhir.'],
            ['RET-MKT-3', 'Materi & laporan kampanye', 'Marketing', 'effective', 3, 'destroy', 'Kebutuhan bisnis — 3 tahun.'],
            ['RET-HR-10', 'Dokumen SDM & personel', 'HR', 'effective', 10, 'destroy', 'UU 13/2003 & UU 27/2022 PDP — minimisasi data.'],
            ['RET-OPS-5', 'Teknik, aset & operasional', 'Operasional', 'superseded', 5, 'destroy', 'Kebutuhan operasional — 5 tahun setelah tidak berlaku.'],
        ] as [$code, $name, $cat, $trig, $years, $act, $basis]) {
            $r[$code] = RetentionPolicy::create(['code' => $code, 'name' => $name, 'category' => $cat, 'trigger' => $trig,
                'period_years' => $years, 'disposal_action' => $act, 'legal_basis' => $basis]);
        }

        $c = [];
        foreach ([
            ['LEG-PER', 'Perizinan & Legalitas', 'legal', 'RET-IZN-10'], ['LEG-KON', 'Kontrak & Perjanjian', 'legal', 'RET-KON-10'],
            ['POL', 'Kebijakan Perusahaan', 'policy', 'RET-POL-10'], ['SOP', 'SOP & Instruksi Kerja', 'policy', 'RET-SOP-5'],
            ['ORG', 'Struktur Organisasi & Peraturan', 'policy', 'RET-ORG-PERM'], ['BRD', 'Risalah & Keputusan Direksi', 'board', 'RET-ORG-PERM'],
            ['FIN', 'Laporan Keuangan', 'finance', 'RET-FIN-10'], ['TAX', 'Perpajakan', 'finance', 'RET-FIN-10'],
            ['BUD', 'Anggaran & Evaluasi Kinerja', 'finance', 'RET-FIN-10'], ['MKT-KON', 'Kontrak Korporat & Proposal', 'marketing', 'RET-MKT-KON-5'],
            ['MKT-CMP', 'Kampanye & Evaluasi Marketing', 'marketing', 'RET-MKT-3'], ['HR-DOC', 'Dokumen SDM & Personel', 'hr', 'RET-HR-10'],
            ['OPS', 'Teknik, Aset & Operasional', 'operations', 'RET-OPS-5'],
        ] as [$code, $name, $mod, $ret]) {
            $c[$code] = DocumentCategory::create(['code' => $code, 'name' => $name, 'module' => $mod, 'retention_policy_id' => $r[$ret]->id]);
        }

        foreach ([
            // code, nama, kategori, expiry, review, material, ack, siklus review (bulan)
            ['AKT', 'Akta Pendirian / Perubahan', 'LEG-PER', false, false, true, false, null],
            ['NIB', 'Nomor Induk Berusaha', 'LEG-PER', false, false, false, false, null],
            ['IZN', 'Izin / Sertifikat Standar Usaha', 'LEG-PER', true, false, false, false, null],
            ['SLF', 'Sertifikat Laik Fungsi', 'LEG-PER', true, false, false, false, null],
            ['SRT', 'Sertifikat (Halal, K3, CHSE, Proteksi)', 'LEG-PER', true, false, false, false, null],
            ['KTR', 'Kontrak Vendor / Sewa', 'LEG-KON', true, false, true, false, null],
            ['NDA', 'Perjanjian Kerahasiaan', 'LEG-KON', true, false, true, false, null],
            ['KBJ', 'Kebijakan', 'POL', false, true, true, true, 24],
            ['SOP', 'Standard Operating Procedure', 'SOP', false, true, false, true, 12],
            ['IK', 'Instruksi Kerja', 'SOP', false, true, false, false, 24],
            ['ORG', 'Struktur Organisasi', 'ORG', false, true, false, false, 12],
            ['PP', 'Peraturan Perusahaan', 'ORG', true, false, true, true, null],
            ['NOT', 'Notulen / Risalah Rapat', 'BRD', false, false, false, false, null],
            ['SKD', 'Surat Keputusan Direksi', 'BRD', false, false, true, false, null],
            ['KJS', 'Kajian Strategis', 'BRD', false, false, true, false, null],
            ['LK', 'Laporan Keuangan', 'FIN', false, false, true, false, null],
            ['SPT', 'SPT / Laporan Pajak', 'TAX', false, false, false, false, null],
            ['ANG', 'Anggaran (RKAP)', 'BUD', false, false, true, false, null],
            ['EVL', 'Laporan Evaluasi Kinerja', 'BUD', false, false, false, false, null],
            ['KKP', 'Kontrak Korporat', 'MKT-KON', true, false, true, false, null],
            ['PRP', 'Proposal Penjualan', 'MKT-KON', false, false, false, false, null],
            ['KMP', 'Materi & Laporan Kampanye', 'MKT-CMP', false, false, false, false, null],
            ['PDM', 'Pedoman SDM', 'HR-DOC', false, true, true, false, 24],
            ['PRS', 'Dokumen Personel', 'HR-DOC', false, false, false, false, null],
            ['GRS', 'Garansi & Manual Teknis', 'OPS', false, false, false, false, null],
            ['SKL', 'Sertifikat Kalibrasi / Inspeksi', 'OPS', true, false, false, false, null],
            ['GBR', 'Gambar Teknis', 'OPS', false, false, false, false, null],
            ['LOP', 'Laporan Operasional', 'OPS', false, false, false, false, null],
        ] as [$code, $name, $cat, $exp, $rev, $mat, $ack, $cycle]) {
            DocumentType::create(['code' => $code, 'name' => $name, 'category_id' => $c[$cat]->id, 'requires_expiry' => $exp,
                'requires_review' => $rev, 'is_material' => $mat, 'requires_acknowledgement' => $ack, 'review_cycle_months' => $cycle]);
        }

        // ---------- Mandat auditor ----------
        AuditMandate::create([
            'auditor_id' => $u['auditor']->id, 'mandate_number' => 'MND/KAP/2026/07', 'purpose' => 'Audit laporan keuangan tahun buku 2026 (interim)',
            'category_ids' => [$c['FIN']->id, $c['TAX']->id, $c['BUD']->id, $c['LEG-KON']->id], 'department_ids' => null,
            'starts_at' => today()->subDays(10), 'ends_at' => today()->addDays(20), 'granted_by' => $u['gm']->id,
        ]);
        AuditMandate::create([
            'auditor_id' => $u['auditor_old']->id, 'mandate_number' => 'MND/IA/2026/03', 'purpose' => 'Audit internal kepatuhan perizinan',
            'category_ids' => [$c['LEG-PER']->id], 'department_ids' => null,
            'starts_at' => today()->subDays(60), 'ends_at' => today()->subDays(10), 'granted_by' => $u['gm']->id,
        ]);
    }
}
