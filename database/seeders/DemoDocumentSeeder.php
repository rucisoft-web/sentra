<?php

namespace Database\Seeders;

use App\Models\AccessRequest;
use App\Models\Acknowledgement;
use App\Models\ActionItem;
use App\Models\AppNotification;
use App\Models\AuditEvent;
use App\Models\Department;
use App\Models\DisposalRequest;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Entity;
use App\Models\LegalHold;
use App\Models\Meeting;
use App\Models\RetentionPolicy;
use App\Models\SavedSearch;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Services\AccessControl;
use App\Services\AuditLogger;
use App\Services\DocumentNumbering;
use App\Services\DocumentStorage;
use App\Services\SimplePdf;
use App\Services\WorkflowService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/** Data demo lengkap Grand Pasundan Convention Hotel. */
class DemoDocumentSeeder extends Seeder
{
    private array $u = [];
    private array $dep = [];
    private array $d = [];
    private WorkflowService $wf;

    public function run(): void
    {
        $this->wf = app(WorkflowService::class);
        $this->u = User::all()->keyBy(fn ($x) => explode('@', $x->email)[0])->all();
        $this->dep = Department::with('entity')->get()->keyBy(fn ($x) => ($x->entity->code === 'GBK' ? 'GBK-' : '').$x->code)->all();
        $U = fn ($k) => $this->u[$k];

        $T = today();
        $gm = 'gm'; $dir = 'direktur'; $dc = 'corsec'; $lg = 'legal'; $fin = 'finance'; $hr = 'hr'; $eng = 'engineering';
        $fo = 'fo'; $fnb = 'fnb'; $hk = 'hk'; $sm = 'sales'; $sec = 'security'; $it = 'it';

        // ====================== LEGAL — PERIZINAN ======================
        $this->d['akta'] = $this->published('Akta Pendirian PT Grand Pasundan Convention Hotel', 'LEG', 'AKT', 'confidential', $lg, $dc,
            '2009-03-12', ['desc' => 'Akta pendirian perseroan No. 27 tanggal 12 Maret 2009 beserta pengesahan Kemenkumham. Arsip vital, disimpan permanen.',
                'retention' => 'RET-PERM-LEG', 'tags' => 'akta, pendirian, vital, kemenkumham', 'counterparty' => 'Notaris Hj. R. Ratna Komala, S.H., M.Kn.',
                'body' => ['Akta Nomor 27 tanggal 12 Maret 2009.', 'Pengesahan badan hukum: AHU-11234.AH.01.01 Tahun 2009.', 'Modal dasar dan susunan pemegang saham sebagaimana tercantum.']]);
        $this->d['akta_ubah'] = $this->published('Akta Perubahan Anggaran Dasar — Peningkatan Modal 2024', 'LEG', 'AKT', 'confidential', $lg, $dc,
            '2024-06-20', ['desc' => 'Akta perubahan No. 14 tanggal 20 Juni 2024 tentang peningkatan modal ditempatkan dan perubahan susunan Direksi.',
                'retention' => 'RET-PERM-LEG', 'tags' => 'akta, perubahan, modal, direksi', 'relations' => [['amends', 'akta']]]);
        $this->d['nib'] = $this->published('Nomor Induk Berusaha (NIB) — Penyediaan Akomodasi Hotel Bintang', 'LEG', 'NIB', 'public_internal', $lg, $dc,
            '2021-04-05', ['desc' => 'NIB 9120001234567 melalui OSS RBA untuk KBLI 55110 Hotel Bintang.', 'retention' => 'RET-PERM-LEG', 'tags' => 'nib, oss, kbli 55110']);
        $this->d['ssu'] = $this->published('Sertifikat Standar Usaha Hotel Bintang 4', 'LEG', 'IZN', 'department', $lg, $dc,
            '2021-12-01', ['expires' => $T->copy()->addDays(85), 'desc' => 'Perizinan berusaha berbasis risiko untuk klasifikasi Hotel Bintang 4 — wajib diperpanjang sebelum berakhir.',
                'counterparty' => 'Kementerian Pariwisata & Ekonomi Kreatif / OSS', 'obligations' => 'Pemenuhan standar usaha & penilaian ulang klasifikasi bintang.', 'tags' => 'perizinan, bintang 4, oss']);
        $this->d['slf'] = $this->published('Sertifikat Laik Fungsi (SLF) Gedung Utama Hotel', 'ENG', 'SLF', 'department', $eng, $lg,
            '2021-12-15', ['expires' => $T->copy()->addDays(25), 'desc' => 'SLF bangunan gedung utama 12 lantai, ballroom dan basement. Perpanjangan memerlukan kajian teknis bangunan oleh pengkaji teknis bersertifikat.',
                'counterparty' => 'DPMPTSP Kota Bandung', 'obligations' => 'Pemeriksaan berkala kelaikan fungsi; laporan pengkaji teknis wajib dilampirkan pada permohonan perpanjangan.',
                'tags' => 'slf, bangunan, pbg, kritis', 'links' => [['asset_care', 'AST-BLD-MAIN-01', 'sertifikat bangunan']]]);
        $this->d['halal_old'] = $this->published('Sertifikat Halal Dapur Utama & Banquet 2022', 'FNB', 'SRT', 'public_internal', $fnb, $lg,
            '2022-09-01', ['expires' => $T->copy()->subDays(40), 'desc' => 'Sertifikat halal periode 2022–2026 (sudah diganti dengan sertifikat baru).', 'counterparty' => 'BPJPH Kementerian Agama', 'tags' => 'halal, dapur, banquet']);
        $this->d['halal'] = $this->published('Sertifikat Halal Dapur Utama & Banquet 2026–2030', 'FNB', 'SRT', 'public_internal', $fnb, $lg,
            $T->copy()->subDays(50)->toDateString(), ['expires' => $T->copy()->addYears(4)->subDays(50), 'desc' => 'Sertifikat halal perpanjangan untuk dapur utama, pastry dan banquet kitchen.',
                'counterparty' => 'BPJPH Kementerian Agama', 'tags' => 'halal, dapur, banquet, perpanjangan', 'relations' => [['renewal_of', 'halal_old']]]);
        $this->archive('halal_old', 'halal', $T->copy()->subDays(50));
        $this->d['b3'] = $this->published('Persetujuan Teknis Pengelolaan Limbah B3', 'ENG', 'IZN', 'department', $eng, $lg,
            '2021-01-20', ['expires' => $T->copy()->addDays(55), 'desc' => 'Persetujuan teknis penyimpanan sementara limbah B3 (oli bekas, lampu TL, aki) dan kewajiban pelaporan triwulanan.',
                'counterparty' => 'Dinas Lingkungan Hidup Kota Bandung', 'obligations' => 'Laporan neraca limbah B3 per triwulan; manifest elektronik setiap pengangkutan.', 'tags' => 'limbah b3, lingkungan, dlh']);
        $this->d['lift'] = $this->published('Sertifikat Riksa Uji Lift Penumpang P1–P4 & Lift Barang', 'ENG', 'SRT', 'department', $eng, $lg,
            '2025-11-05', ['expires' => $T->copy()->subDays(12), 'desc' => 'Surat keterangan memenuhi syarat K3 pesawat lift hasil pemeriksaan & pengujian PJK3. SUDAH LEWAT MASA BERLAKU — perlu tindak lanjut segera.',
                'counterparty' => 'Disnakertrans Prov. Jawa Barat / PJK3 PT Riksa Prima', 'obligations' => 'Riksa uji ulang tahunan sesuai Permenaker 6/2017.',
                'tags' => 'k3, lift, riksa uji, kritis', 'links' => [['asset_care', 'AST-ENG-LFT-01', 'sertifikat K3']]]);
        $this->d['chse'] = $this->published('Sertifikat CHSE (Cleanliness, Health, Safety & Environment)', 'HK', 'SRT', 'public_internal', $hk, $lg,
            '2025-12-10', ['expires' => $T->copy()->addDays(400), 'desc' => 'Sertifikasi CHSE untuk usaha hotel.', 'counterparty' => 'Lembaga Sertifikasi CHSE', 'tags' => 'chse, kebersihan, kesehatan']);
        $this->d['reklame'] = $this->published('Izin Penyelenggaraan Reklame Billboard Jl. Peta', 'SM', 'IZN', 'department', $sm, $lg,
            '2025-04-18', ['expires' => $T->copy()->addDays(150), 'desc' => 'Izin reklame billboard 4x8 m di area depan hotel.', 'counterparty' => 'Bapenda Kota Bandung', 'tags' => 'reklame, billboard, pajak reklame']);
        $this->d['proteksi'] = $this->published('Sertifikat Keselamatan Kebakaran Gedung', 'SEC', 'SRT', 'department', $sec, $lg,
            '2025-02-01', ['expires' => $T->copy()->addDays(70), 'desc' => 'Rekomendasi & sertifikat keselamatan kebakaran (sistem sprinkler, hydrant, fire alarm) dari Dinas Pemadam Kebakaran.',
                'counterparty' => 'Diskar PB Kota Bandung', 'obligations' => 'Uji fungsi sistem proteksi kebakaran tiap 6 bulan; simulasi evakuasi tahunan.', 'tags' => 'kebakaran, sprinkler, hydrant, k3']);

        // ====================== LEGAL — KONTRAK ======================
        $this->d['parkir'] = $this->published('Perjanjian Sewa Lahan Parkir Basement & Valet', 'LEG', 'KTR', 'confidential', $lg, $dc,
            '2024-07-01', ['expires' => $T->copy()->addDays(200), 'value' => 1800000000, 'desc' => 'Perjanjian kerja sama pengelolaan parkir basement (220 slot) dan layanan valet. Sedang dalam sengketa tagihan bagi hasil.',
                'counterparty' => 'PT Pasundan Parkir Mandiri', 'obligations' => 'Bagi hasil 35% pendapatan parkir, laporan bulanan, asuransi kendaraan.', 'tags' => 'parkir, sewa, sengketa']);
        $this->d['laundry'] = $this->published('Kontrak Jasa Laundry Linen & Seragam 2025–2026', 'HK', 'KTR', 'confidential', $hk, $lg,
            '2025-01-01', ['expires' => $T->copy()->addDays(40), 'value' => 960000000, 'desc' => 'Kontrak jasa pencucian linen kamar, F&B dan seragam karyawan.',
                'counterparty' => 'PT Bersih Cemerlang Abadi', 'obligations' => 'SLA pengembalian 24 jam; denda 2% per hari keterlambatan; laporan kerusakan linen bulanan.', 'tags' => 'laundry, linen, vendor']);
        $this->d['lift_kontrak'] = $this->published('Kontrak Pemeliharaan Lift & Eskalator', 'ENG', 'KTR', 'confidential', $eng, $lg,
            '2025-09-01', ['expires' => $T->copy()->addDays(300), 'value' => 420000000, 'desc' => 'Pemeliharaan preventif bulanan dan perbaikan 24/7 untuk 5 unit lift dan 2 eskalator.',
                'counterparty' => 'PT Elevasi Teknik Nusantara', 'obligations' => 'Kunjungan preventif bulanan; respon gangguan maks. 2 jam.', 'tags' => 'lift, eskalator, maintenance',
                'links' => [['asset_care', 'AST-ENG-LFT-01', 'kontrak pemeliharaan']]]);
        $this->d['limbah_kontrak'] = $this->published('Perjanjian Pengangkutan & Pengolahan Limbah B3', 'ENG', 'KTR', 'confidential', $eng, $lg,
            '2024-12-20', ['expires' => $T->copy()->addDays(28), 'value' => 96000000, 'desc' => 'Pengangkutan limbah B3 oleh transporter berizin dan pengolahan di fasilitas berizin KLHK.',
                'counterparty' => 'PT Hijau Lestari Environment', 'obligations' => 'Manifest elektronik setiap pengangkutan.', 'tags' => 'limbah b3, transporter',
                'relations' => [['references', 'b3']]]);
        $this->d['pest'] = $this->published('Kontrak Pest Control Terpadu', 'HK', 'KTR', 'confidential', $hk, $lg,
            '2025-02-01', ['expires' => $T->copy()->addDays(75), 'value' => 84000000, 'desc' => 'Layanan pengendalian hama terpadu area kamar, dapur dan public area.', 'counterparty' => 'PT Prima Hygiene Indonesia', 'tags' => 'pest control, hama, vendor']);
        $this->d['nda'] = $this->published('Perjanjian Kerahasiaan dengan Konsultan Transaksi', 'BOD', 'NDA', 'strictly_confidential', $gm, $lg,
            $T->copy()->subDays(70)->toDateString(), ['expires' => $T->copy()->addDays(295), 'desc' => 'NDA terkait kajian transaksi strategis. Akses whitelist individu.',
                'counterparty' => 'Konsultan Transaksi (dirahasiakan)', 'tags' => 'nda, kerahasiaan, strategis',
                'acl' => [['user', 'direktur', 'download'], ['user', 'legal', 'view'], ['user', 'owner', 'view']]]);

        // ====================== KEBIJAKAN, SOP & ORGANISASI ======================
        $this->d['org'] = $this->published('Struktur Organisasi Grand Pasundan Convention Hotel 2026', 'HRD', 'ORG', 'public_internal', $hr, $hr,
            '2026-01-02', ['desc' => 'Bagan organisasi berlaku per 2 Januari 2026, termasuk garis pelaporan departemen dan jabatan kunci.', 'tags' => 'organisasi, struktur, bagan', 'approver' => $gm]);
        $this->d['kbj_arsip'] = $this->published('Kebijakan Tata Kelola Dokumen & Arsip Korporasi', 'CORSEC', 'KBJ', 'public_internal', $dc, $dc,
            $T->copy()->subDays(20)->toDateString(), ['desc' => 'Kebijakan pengelolaan dokumen resmi: klasifikasi, penomoran, workflow pengesahan, retensi, legal hold dan pemusnahan melalui SENTRA.',
                'tags' => 'kebijakan, arsip, sentra, tata kelola', 'body' => ['Seluruh dokumen resmi wajib didaftarkan di SENTRA dengan metadata, pemilik dan klasifikasi.', 'File yang diunggah tidak otomatis menjadi dokumen sah sebelum disahkan.', 'Pemusnahan arsip hanya melalui jadwal retensi, verifikasi legal hold, persetujuan ganda dan berita acara.']]);
        $this->d['sop_fire'] = $this->published('SOP Penanggulangan Keadaan Darurat Kebakaran & Evakuasi', 'SEC', 'SOP', 'public_internal', $sec, $sec,
            $T->copy()->subMonths(4)->toDateString(), ['versions' => [[1, 0, '2022-03-01', 'Versi awal'], [2, 0, '2024-05-10', 'Penyesuaian titik kumpul baru'], [3, 0, null, 'Penambahan prosedur evakuasi tamu disabilitas & lift kebakaran']],
                'desc' => 'Prosedur tanggap darurat kebakaran, alarm, komunikasi, evakuasi tamu & karyawan, titik kumpul dan pelaporan.', 'tags' => 'kebakaran, evakuasi, darurat, k3',
                'body' => ['## Tujuan', 'Menjamin keselamatan tamu, karyawan dan aset saat terjadi kebakaran.', '## Prosedur', '1. Saat alarm berbunyi, Operator PABX mengumumkan kode merah.', '2. Fire squad lantai menuju lokasi dengan APAR terdekat.', '3. Evakuasi melalui tangga darurat; lift kebakaran hanya untuk petugas.', '4. Titik kumpul utama: halaman parkir timur.', '5. Tamu disabilitas dibantu buddy yang ditunjuk Duty Manager.']]);
        $this->d['sop_checkin'] = $this->published('SOP Check-in & Check-out Tamu', 'FO', 'SOP', 'department', $fo, $fo,
            '2025-08-01', ['versions' => [[1, 0, '2023-02-01', 'Versi awal'], [2, 0, null, 'Integrasi e-KTP reader & pembayaran QRIS']], 'review' => $T->copy()->subDays(20),
                'desc' => 'Prosedur registrasi, verifikasi identitas, deposit, penyerahan kunci, dan proses check-out termasuk settlement folio di VHP.', 'tags' => 'front office, check-in, check-out, vhp']);
        $this->d['sop_hk'] = $this->published('SOP Pembersihan Kamar Standar (Room Make-up)', 'HK', 'SOP', 'department', $hk, $hk,
            '2026-03-01', ['versions' => [[1, 0, '2021-06-01', 'Versi awal'], [2, 0, '2023-09-01', 'Protokol CHSE'], [3, 0, null, 'Penyesuaian standar amenities ramah lingkungan']],
                'desc' => 'Urutan kerja pembersihan kamar, standar waktu 25 menit/kamar, checklist kamar dan pelaporan lost & found.', 'tags' => 'housekeeping, kamar, chse']);
        $this->d['sop_haccp'] = $this->published('SOP Food Safety & HACCP Dapur Utama', 'FNB', 'SOP', 'department', $fnb, $fnb,
            '2025-11-25', ['versions' => [[1, 0, '2023-11-20', 'Versi awal'], [2, 0, null, 'Penambahan CCP blast chiller']], 'review' => $T->copy()->addDays(10),
                'desc' => 'Titik kendali kritis (CCP) penerimaan, penyimpanan, pengolahan dan penyajian makanan.', 'tags' => 'haccp, food safety, dapur, halal']);
        $this->d['sop_komplain'] = $this->published('SOP Penanganan Komplain Tamu (LEARN)', 'FO', 'SOP', 'public_internal', $fo, $fo,
            '2026-02-15', ['desc' => 'Metode LEARN (Listen, Empathize, Apologize, Resolve, Notify) dan eskalasi ke Duty Manager.', 'tags' => 'komplain, tamu, service recovery']);
        $this->d['ik_loto'] = $this->published('Instruksi Kerja Lockout/Tagout Panel Listrik', 'ENG', 'IK', 'department', $eng, $eng,
            '2025-06-10', ['desc' => 'Langkah isolasi energi sebelum pekerjaan perawatan panel listrik dan mesin.', 'tags' => 'loto, listrik, k3, engineering', 'approver' => $gm]);
        $this->d['pp'] = $this->published('Peraturan Perusahaan 2025–2027', 'HRD', 'PP', 'public_internal', $hr, $hr,
            '2025-03-01', ['expires' => Carbon::parse('2027-02-28'), 'desc' => 'Peraturan perusahaan yang disahkan Disnaker: hak & kewajiban, jam kerja, cuti, disiplin.', 'tags' => 'peraturan perusahaan, sdm, disnaker']);
        $this->d['kbj_it'] = $this->published('Kebijakan Keamanan Informasi & Penggunaan TI', 'IT', 'KBJ', 'public_internal', $it, $it,
            '2024-09-01', ['review' => $T->copy()->subDays(45), 'desc' => 'Ketentuan kata sandi, MFA, penggunaan perangkat, email, klasifikasi informasi dan pelaporan insiden.', 'tags' => 'keamanan informasi, ti, mfa, insiden']);
        $this->d['sop_kas'] = $this->published('SOP Pengelolaan Kas Kecil (Petty Cash)', 'FIN', 'SOP', 'department', $fin, $fin,
            '2025-10-01', ['desc' => 'Batas kas kecil, otorisasi, bukti pengeluaran dan rekonsiliasi mingguan.', 'tags' => 'kas kecil, keuangan, otorisasi']);
        $this->d['sop_chiller'] = $this->published('SOP Pemeliharaan Chiller & AHU', 'ENG', 'SOP', 'department', $eng, 'rizky',
            '2025-05-01', ['desc' => 'Jadwal dan langkah pemeliharaan preventif chiller sentrifugal, cooling tower dan AHU.', 'tags' => 'chiller, ahu, hvac, preventive',
                'links' => [['asset_care', 'AST-ENG-CHL-01', 'SOP pemeliharaan']], 'approver' => $gm]);

        // ====================== BOARD ======================
        $this->d['rups'] = $this->published('Risalah RUPS Tahunan 2026', 'BOD', 'NOT', 'confidential', $dc, $dc,
            '2026-05-28', ['desc' => 'Risalah Rapat Umum Pemegang Saham Tahunan tahun buku 2025.', 'tags' => 'rups, risalah, pemegang saham', 'approver' => $gm,
                'acl' => [['user', 'owner', 'download']]]);
        $this->d['notulen_okt'] = $this->published('Notulen Rapat Direksi Oktober 2026', 'BOD', 'NOT', 'confidential', $dc, $dc,
            '2026-10-15', ['desc' => 'Notulen rapat direksi bulanan: renovasi ballroom, perpanjangan SLF, re-sertifikasi lift, implementasi SENTRA.', 'tags' => 'rapat direksi, notulen, ballroom, slf', 'approver' => $gm]);
        $this->d['skd_ballroom'] = $this->published('Keputusan Direksi tentang Renovasi Ballroom Pasundan', 'BOD', 'SKD', 'confidential', $gm, $dc,
            '2026-10-20', ['desc' => 'SK Direksi No. 014/SKD/X/2026: persetujuan renovasi ballroom dengan anggaran maksimal Rp 6,5 miliar, pelaksanaan Januari–April 2027.',
                'tags' => 'keputusan direksi, ballroom, renovasi, capex', 'approver' => $dir, 'relations' => [['references', 'notulen_okt']]]);
        $this->d['skd_dc'] = $this->published('Keputusan Direksi tentang Penunjukan Document Controller & Implementasi SENTRA', 'BOD', 'SKD', 'public_internal', $gm, $dc,
            '2026-11-12', ['desc' => 'Penunjukan Corporate Secretary sebagai Document Controller dan arahan Owner 12 November 2026 tentang pusat arsip korporasi.', 'tags' => 'sentra, document controller, arsip', 'approver' => $dir]);
        $this->d['notulen_mgmt'] = $this->published('Notulen Rapat Manajemen Mingguan 16 November 2026', 'BOD', 'NOT', 'department', $dc, $dc,
            $T->copy()->subDays(5)->toDateString(), ['desc' => 'Rapat manajemen: target okupansi Desember, komplain AC lantai 7, review SOP, audit hak akses.', 'tags' => 'rapat manajemen, okupansi', 'approver' => $gm,
                'acl' => [['role', 'dept_head', 'view']]]);
        $this->d['akuisisi'] = $this->published('Kajian Strategis Rencana Akuisisi Hotel di Kawasan Lembang', 'BOD', 'KJS', 'strictly_confidential', $dir, $dir,
            $T->copy()->subDays(30)->toDateString(), ['desc' => 'Kajian awal valuasi, sinergi dan risiko rencana akuisisi properti hotel resort. SANGAT RAHASIA.', 'tags' => 'akuisisi, strategis, valuasi', 'approver' => $gm,
                'acl' => [['user', 'gm', 'download'], ['user', 'owner', 'view']]]);

        // ====================== FINANCE & TAX ======================
        $this->d['lk2025'] = $this->published('Laporan Keuangan Audited Tahun Buku 2025', 'FIN', 'LK', 'confidential', $fin, $fin,
            '2026-04-15', ['desc' => 'Laporan keuangan audited dengan opini Wajar Tanpa Modifikasian.', 'tags' => 'laporan keuangan, audited, wtm', 'approver' => $dir,
                'links' => [['vhp', 'VHP-FIN-2025-FY', 'laporan final'], ['ruci_bsc', 'BSC-FIN-2025', 'bukti KPI keuangan']]]);
        $this->d['lkq3'] = $this->published('Laporan Keuangan Triwulan III 2026 (Unaudited)', 'FIN', 'LK', 'confidential', $fin, $fin,
            '2026-10-25', ['desc' => 'Laporan posisi keuangan, laba rugi dan arus kas Q3 2026. Okupansi rata-rata 71%, GOP 34%.', 'tags' => 'laporan keuangan, q3, okupansi, gop', 'approver' => $dir,
                'links' => [['vhp', 'VHP-FIN-2026-Q3', 'laporan final']]]);
        $this->d['spt2025'] = $this->published('SPT Tahunan PPh Badan Tahun Pajak 2025', 'FIN', 'SPT', 'confidential', $fin, $fin,
            '2026-04-28', ['desc' => 'SPT Tahunan PPh Badan beserta lampiran dan bukti penerimaan elektronik.', 'tags' => 'spt, pph badan, pajak', 'approver' => $dir]);
        $this->d['ppn_okt'] = $this->published('SPT Masa PPN Oktober 2026', 'FIN', 'SPT', 'confidential', $fin, $fin,
            '2026-11-15', ['desc' => 'SPT Masa PPN dan bukti setor.', 'tags' => 'spt masa, ppn, pajak', 'approver' => $dir]);
        $this->d['pb1'] = $this->published('Laporan Pajak Hotel & Restoran (PB1) Oktober 2026', 'FIN', 'SPT', 'department', $fin, $fin,
            '2026-11-10', ['desc' => 'Pelaporan pajak daerah PB1 hotel & restoran ke Bapenda.', 'tags' => 'pb1, pajak daerah, bapenda', 'approver' => $dir]);
        $this->d['evl_s1'] = $this->published('Evaluasi Kinerja Keuangan & KPI Semester I 2026', 'FIN', 'EVL', 'confidential', $fin, $fin,
            '2026-07-20', ['desc' => 'Evaluasi capaian target pendapatan, GOP, cost per occupied room dan KPI departemen.', 'tags' => 'evaluasi, kpi, bsc, semester', 'approver' => $gm,
                'links' => [['ruci_bsc', 'BSC-KPI-2026-S1', 'bukti evaluasi KPI']]]);

        // ====================== MARKETING ======================
        $this->d['kk_bank'] = $this->published('Kontrak Korporat Bank Parahyangan Sejahtera — Corporate Rate 2026', 'SM', 'KKP', 'confidential', $sm, 'putri',
            '2026-01-01', ['expires' => $T->copy()->addDays(40), 'value' => 2400000000, 'desc' => 'Corporate rate kamar dan paket meeting untuk seluruh kantor wilayah Jawa Barat.',
                'counterparty' => 'PT Bank Parahyangan Sejahtera', 'obligations' => 'Allotment 20 kamar/hari; rate Rp 850.000 net; pembayaran 30 hari.', 'tags' => 'kontrak korporat, corporate rate, bank',
                'links' => [['crm_gpch', 'CRM-ACC-0042', 'kontrak akun korporat']]]);
        $this->d['kk_farmasi'] = $this->published('Kontrak Korporat PT Sinar Farmasi Nusantara — Paket MICE', 'SM', 'KKP', 'confidential', $sm, 'putri',
            '2026-05-01', ['expires' => $T->copy()->addDays(160), 'value' => 1350000000, 'desc' => 'Paket MICE untuk 12 kegiatan pelatihan medical representative per tahun.',
                'counterparty' => 'PT Sinar Farmasi Nusantara', 'tags' => 'kontrak korporat, mice, farmasi', 'links' => [['crm_gpch', 'CRM-ACC-0117', 'kontrak akun korporat']]]);
        $this->d['kmp_staycation'] = $this->published('Laporan Evaluasi Kampanye "Pasundan Staycation" Q2 2026', 'SM', 'KMP', 'department', $sm, 'putri',
            '2026-07-10', ['desc' => 'Evaluasi kampanye digital: 1.240 room nights, ROAS 6,2x, konversi website 2,8%.', 'tags' => 'kampanye, staycation, digital, evaluasi',
                'links' => [['crm_gpch', 'CRM-CMP-2026-02', 'laporan kampanye'], ['ruci_bsc', 'BSC-MKT-2026-Q2', 'bukti KPI marketing']]]);

        // ====================== SDM ======================
        $this->d['remunerasi'] = $this->published('Pedoman Remunerasi & Grading Jabatan 2026', 'HRD', 'PDM', 'confidential', $hr, $hr,
            '2026-01-15', ['desc' => 'Struktur skala upah, grading jabatan dan komponen tunjangan.', 'tags' => 'remunerasi, grading, upah', 'approver' => $gm,
                'acl' => [['user', 'rizky', 'deny']]]);
        $this->d['pkwt'] = $this->published('Kontrak Kerja PKWT Karyawan Batch Oktober 2026', 'HRD', 'PRS', 'confidential', $hr, $hr,
            '2026-10-01', ['desc' => 'Kumpulan perjanjian kerja waktu tertentu 18 karyawan baru (data pribadi — UU PDP).', 'tags' => 'pkwt, kontrak kerja, data pribadi', 'approver' => $gm]);
        $this->d['mcu'] = $this->published('Rekapitulasi Hasil Medical Check-up Karyawan 2026', 'HRD', 'PRS', 'strictly_confidential', $hr, $hr,
            '2026-08-30', ['desc' => 'Data kesehatan karyawan (data pribadi spesifik). Whitelist HR & dokter perusahaan.', 'tags' => 'mcu, kesehatan, data sensitif', 'approver' => $gm,
                'acl' => [['user', 'it', 'deny']]]);

        // ====================== OPERASIONAL & ASET ======================
        $this->d['garansi_chiller'] = $this->published('Garansi & Manual Teknis Chiller Sentrifugal Unit 1', 'ENG', 'GRS', 'department', $eng, 'rizky',
            '2024-02-01', ['desc' => 'Kartu garansi 5 tahun kompresor, manual operasi dan perawatan chiller unit 1.', 'tags' => 'chiller, garansi, manual', 'approver' => $gm,
                'links' => [['asset_care', 'AST-ENG-CHL-01', 'garansi'], ['asset_care', 'AST-ENG-CHL-01', 'manual teknis']]]);
        $this->d['kalibrasi'] = $this->published('Sertifikat Kalibrasi Timbangan Dapur & Receiving 2026', 'FNB', 'SKL', 'department', $fnb, $fnb,
            '2026-03-20', ['expires' => $T->copy()->addDays(120), 'desc' => 'Sertifikat kalibrasi 6 unit timbangan digital dapur dan area receiving.', 'tags' => 'kalibrasi, timbangan, receiving', 'approver' => $gm,
                'links' => [['asset_care', 'AST-FNB-SCL-03', 'sertifikat kalibrasi']]]);
        $this->d['genset'] = $this->published('Laporan Inspeksi & Uji Beban Genset 2026', 'ENG', 'LOP', 'department', $eng, 'rizky',
            '2026-09-12', ['desc' => 'Uji beban 1000 kVA selama 2 jam, hasil memenuhi syarat.', 'tags' => 'genset, uji beban, listrik', 'approver' => $gm,
                'links' => [['asset_care', 'AST-ENG-GEN-01', 'laporan inspeksi']]]);
        $this->d['asbuilt'] = $this->published('As-built Drawing Ballroom Pasundan', 'ENG', 'GBR', 'department', $eng, $eng,
            '2019-08-01', ['desc' => 'Gambar as-built arsitektur, MEP dan struktur ballroom (acuan renovasi 2027).', 'tags' => 'gambar teknis, ballroom, as-built', 'approver' => $gm,
                'relations' => [['references', 'skd_ballroom']]]);

        // ====================== ENTITAS GBK ======================
        $this->d['gbk_akta'] = $this->published('Akta Pendirian GBK', 'GBK-BOD', 'AKT', 'confidential', 'direktur', 'direktur',
            '2015-05-05', ['desc' => 'Akta pendirian entitas afiliasi GBK.', 'retention' => 'RET-PERM-LEG', 'tags' => 'akta, gbk', 'approver' => $gm]);
        $this->d['gbk_lk'] = $this->published('Laporan Keuangan GBK Tahun Buku 2025', 'GBK-FIN', 'LK', 'department', 'teguh', 'teguh',
            '2026-04-20', ['desc' => 'Laporan keuangan entitas GBK — terpisah dari GPCH (isolasi entitas).', 'tags' => 'laporan keuangan, gbk', 'approver' => $dir]);

        // ====================== ARSIP INAKTIF, RETENSI & PEMUSNAHAN ======================
        $this->d['ramadan2019'] = $this->published('Materi Kampanye Ramadan 2019 "Berkah di Pasundan"', 'SM', 'KMP', 'department', $sm, $sm,
            '2019-04-20', ['desc' => 'Materi promosi & laporan hasil kampanye Ramadan 2019.', 'tags' => 'kampanye, ramadan, 2019', 'approver' => $gm, 'at' => '2019-04-15']);
        $this->d['loyalty2019'] = $this->published('Data Peserta Program Loyalitas "Pasundan Rewards" 2019', 'SM', 'KMP', 'confidential', $sm, $sm,
            '2019-01-10', ['desc' => 'Daftar anggota program loyalitas 2019 (berisi data pribadi). Terkait pengaduan pelindungan data pribadi.', 'tags' => 'loyalitas, data pribadi, pdp', 'approver' => $gm, 'at' => '2019-01-05']);
        $this->d['nightaudit2018'] = $this->published('Laporan Harian Night Audit Tahun 2018', 'FO', 'LOP', 'department', $fo, $fo,
            '2018-12-31', ['desc' => 'Kompilasi laporan night audit harian tahun 2018.', 'tags' => 'night audit, laporan harian, 2018', 'approver' => $gm, 'at' => '2018-12-20']);
        $this->d['ny2018'] = $this->published('Kampanye Malam Tahun Baru 2018 "Pasundan Countdown"', 'SM', 'KMP', 'public_internal', $sm, $sm,
            '2017-12-01', ['desc' => 'Materi promosi dan laporan penjualan paket malam tahun baru 2018.', 'tags' => 'kampanye, tahun baru, 2018', 'approver' => $gm, 'at' => '2017-11-25']);
        foreach (['ramadan2019', 'loyalty2019', 'nightaudit2018', 'ny2018'] as $k) $this->archive($k, null, Carbon::parse('2021-01-15'));

        // tandai semua notifikasi historis sebagai sudah dibaca
        AppNotification::query()->update(['read_at' => now()]);

        // ====================== WORKFLOW BERJALAN ======================
        // 1) SOP VIP: reviewer selesai, menunggu pengesahan GM (demo MFA)
        $vip = $this->draft('SOP Check-in Tamu VIP & Protokoler Kenegaraan', 'FO', 'SOP', 'department', $fo, $fo,
            ['desc' => 'Prosedur penyambutan tamu VIP/VVIP, koordinasi protokoler, pengamanan lantai eksekutif dan amenities khusus.', 'tags' => 'vip, protokoler, front office', 'at' => $T->copy()->subDays(4),
                'versions' => [[0, 1, 'Draft awal'], [1, 0, 'Revisi sesuai masukan Security']]]);
        $wf = $this->wf->submit($vip, $vip->versions()->first(), $U($fo), [$U($sec)->id, $U($dc)->id], $U($gm)->id, 'serial', 3, 'Mohon review — dipakai untuk kunjungan delegasi KAA bulan depan.', $T->copy()->subDays(3)->setTime(9, 10));
        $this->decideStep($wf, 0, 'approved', 'Prosedur pengamanan sudah sesuai. Tambahkan nomor darurat Polrestabes di lampiran.', $T->copy()->subDays(2)->setTime(14, 5));
        $this->decideStep($wf, 1, 'approved', 'Format dan penomoran sesuai pedoman.', $T->copy()->subDays(1)->setTime(10, 30));

        // 2) Kebijakan anti-gratifikasi: menunggu review Legal
        $grat = $this->draft('Kebijakan Anti-Gratifikasi & Benturan Kepentingan', 'CORSEC', 'KBJ', 'public_internal', $dc, $dc,
            ['desc' => 'Larangan penerimaan gratifikasi, batas nilai cendera mata, kewajiban pelaporan dan pengelolaan benturan kepentingan.', 'tags' => 'gratifikasi, etika, benturan kepentingan', 'at' => $T->copy()->subDays(2)]);
        $this->wf->submit($grat, $grat->versions()->first(), $U($dc), [$U($lg)->id, $U($hr)->id], $U($gm)->id, 'parallel', 5, 'Review paralel Legal & HR.', $T->copy()->subDays(1)->setTime(8, 45));

        // 3) Anggaran 2027: GM sudah review, menunggu Direktur — lewat SLA (eskalasi)
        $rkap = $this->draft('Rencana Kerja & Anggaran Perusahaan (RKAP) 2027', 'FIN', 'ANG', 'confidential', $fin, $fin,
            ['desc' => 'Target pendapatan Rp 148 M, okupansi 74%, capex renovasi ballroom Rp 6,5 M, rencana tenaga kerja.', 'tags' => 'rkap, anggaran, 2027, capex', 'at' => $T->copy()->subDays(9),
                'versions' => [[0, 1, 'Draft awal'], [0, 2, 'Penyesuaian asumsi okupansi'], [1, 0, 'Final untuk persetujuan direksi']]]);
        $wf = $this->wf->submit($rkap, $rkap->versions()->first(), $U($fin), [$U($gm)->id], $U($dir)->id, 'serial', 3, 'Mohon persetujuan sebelum RUPS-LB.', $T->copy()->subDays(7)->setTime(11, 0));
        $this->decideStep($wf, 0, 'approved', 'Asumsi okupansi realistis. Lanjut ke Direktur.', $T->copy()->subDays(5)->setTime(16, 20));

        // 4) SOP Chiller revisi v1.1: dikembalikan untuk revisi
        $chiller = $this->d['sop_chiller'];
        $v = $this->addVersion($chiller, 1, 1, 'Penambahan prosedur water treatment cooling tower', 'rizky', $T->copy()->subDays(6));
        $wf = $this->wf->submit($chiller, $v, $U('rizky'), [$U($eng)->id], $U($gm)->id, 'serial', 3, null, $T->copy()->subDays(6)->setTime(10, 0));
        $this->decideStep($wf, 0, 'revision', "Lengkapi parameter kualitas air (pH, TDS, conductivity) dan frekuensi blowdown.\nLampirkan form checklist harian.", $T->copy()->subDays(4)->setTime(13, 15));

        // 5) Kontrak laundry 2027–2028: disahkan, menunggu publikasi oleh Document Controller
        $laundry2 = $this->draft('Kontrak Jasa Laundry Linen & Seragam 2027–2028', 'HK', 'KTR', 'confidential', $hk, $lg,
            ['desc' => 'Perpanjangan kontrak laundry dengan penyesuaian harga 4% dan SLA 18 jam.', 'tags' => 'laundry, linen, vendor, perpanjangan', 'at' => $T->copy()->subDays(8),
                'expires' => Carbon::parse('2028-12-31'), 'counterparty' => 'PT Bersih Cemerlang Abadi', 'value' => 1010000000, 'relations' => [['renewal_of', 'laundry']]]);
        $wf = $this->wf->submit($laundry2, $laundry2->versions()->first(), $U($lg), [$U($fin)->id], $U($gm)->id, 'serial', 3, null, $T->copy()->subDays(6)->setTime(9, 0));
        $this->decideStep($wf, 0, 'approved', 'Nilai sesuai RKAP 2027.', $T->copy()->subDays(5)->setTime(10, 0));
        $this->decideStep($wf, 1, 'approved', 'Disetujui.', $T->copy()->subDays(2)->setTime(15, 40), true);

        // 6) Proposal MICE ditolak
        $mice = $this->draft('Proposal Paket MICE & Wedding 2027', 'SM', 'PRP', 'department', $sm, 'putri',
            ['desc' => 'Usulan paket full-board meeting dan wedding package 2027.', 'tags' => 'proposal, mice, wedding', 'at' => $T->copy()->subDays(12)]);
        $wf = $this->wf->submit($mice, $mice->versions()->first(), $U('putri'), [$U($sm)->id], $U($gm)->id, 'serial', 3, null, $T->copy()->subDays(11)->setTime(9, 0));
        $this->decideStep($wf, 0, 'approved', 'OK dari sisi pasar.', $T->copy()->subDays(10)->setTime(9, 0));
        $this->decideStep($wf, 1, 'rejected', 'Harga paket wedding di bawah HPP F&B. Susun ulang struktur harga bersama Finance.', $T->copy()->subDays(9)->setTime(11, 0), true);

        // 7) Draft biasa
        $this->draft('SOP Penerimaan & Pemeriksaan Barang (Receiving)', 'FNB', 'SOP', 'department', $fnb, $fnb,
            ['desc' => 'Prosedur penerimaan barang F&B: verifikasi PO, suhu, kualitas, timbangan terkalibrasi.', 'tags' => 'receiving, purchasing, f&b', 'at' => $T->copy()->subDays(1)]);
        $this->draft('Rencana Pelatihan Karyawan Tahunan 2027', 'HRD', 'PDM', 'department', $hr, $hr,
            ['desc' => 'Rencana pelatihan per departemen, sertifikasi kompetensi dan anggaran pelatihan.', 'tags' => 'pelatihan, training, kompetensi', 'at' => $T->copy()->subDays(3)]);

        // Check-out contoh
        $this->d['kbj_it']->update(['checked_out_by' => $U($it)->id, 'checked_out_at' => $T->copy()->subDay()->setTime(15, 0)]);

        // ====================== RAPAT & ACTION ITEM ======================
        $this->meetings($T);

        // ====================== LEGAL HOLD & PEMUSNAHAN ======================
        $this->holdsAndDisposals($T);

        // ====================== AKUISISI AKSES, ACK, PENCARIAN TERSIMPAN ======================
        $this->accessAndAcks($T);

        // ====================== AKTIVITAS AUDIT 30 HARI ======================
        $this->activityNoise($T);

        // jalankan job terjadwal sekali agar pengingat & eskalasi terlihat
        foreach (Document::all() as $doc) { $doc->retention_until = $doc->computeRetentionUntil(); $doc->saveQuietly(); }
        Artisan::call('archive:reminders');
        Artisan::call('archive:escalate');
    }

    // =====================================================================
    private function user(string $k): User { return $this->u[$k]; }

    private function pdf(Document $doc, string $versionLabel, array $body = []): string
    {
        $lines = [
            '## Identitas Dokumen',
            'Nomor      : '.$doc->code,
            'Judul      : '.$doc->title,
            'Entitas    : '.$doc->entity->name,
            'Departemen : '.$doc->department->name,
            'Jenis      : '.$doc->type->name,
            'Klasifikasi: '.$doc->classificationLabel(),
            'Versi      : '.$versionLabel,
            'Pemilik    : '.$doc->owner->name,
        ];
        if ($doc->effective_at) $lines[] = 'Efektif    : '.$doc->effective_at->format('d F Y');
        if ($doc->expires_at) $lines[] = 'Berlaku s.d.: '.$doc->expires_at->format('d F Y');
        if ($doc->counterparty) $lines[] = 'Pihak      : '.$doc->counterparty;
        $lines[] = '## Ringkasan';
        $lines[] = (string) $doc->description;
        if ($doc->obligations) { $lines[] = '## Kewajiban'; $lines[] = $doc->obligations; }
        if ($body) { $lines[] = '## Isi Pokok'; array_push($lines, ...$body); }
        $lines[] = '## Catatan';
        $lines[] = 'Dokumen demo SENTRA untuk kasus Grand Pasundan Convention Hotel. Isi bersifat ilustratif.';
        return SimplePdf::make($doc->title, $lines, $doc->code.' '.$versionLabel);
    }

    private function makeDoc(string $title, string $depKey, string $typeCode, string $cls, string $ownerKey, string $creatorKey, array $o): Document
    {
        $dep = $this->dep[$depKey];
        $type = DocumentType::where('code', $typeCode)->with('category')->first();
        $year = isset($o['effective']) ? Carbon::parse($o['effective'])->year : (int) date('Y');
        $at = isset($o['at']) ? Carbon::parse($o['at']) : (isset($o['effective']) ? Carbon::parse($o['effective'])->subDays(10) : now());
        $doc = Document::create([
            'code' => DocumentNumbering::next($dep->entity, $dep, $type, $year),
            'title' => $title, 'description' => $o['desc'] ?? null,
            'entity_id' => $dep->entity_id, 'department_id' => $dep->id, 'category_id' => $type->category_id, 'type_id' => $type->id,
            'classification' => $cls, 'owner_id' => $this->user($ownerKey)->id, 'created_by' => $this->user($creatorKey)->id,
            'status' => 'draft', 'expires_at' => $o['expires'] ?? null, 'review_at' => $o['review'] ?? null,
            'retention_policy_id' => isset($o['retention']) ? RetentionPolicy::where('code', $o['retention'])->value('id') : $type->category->retention_policy_id,
            'counterparty' => $o['counterparty'] ?? null, 'obligations' => $o['obligations'] ?? null, 'contract_value' => $o['value'] ?? null,
            'tags' => $o['tags'] ?? null,
        ]);
        $doc->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        AuditLogger::log('document.created', $doc, 'Dokumen didaftarkan: '.$doc->code, [], $doc->created_by, $at);

        foreach ($o['acl'] ?? [] as [$st, $sid, $perm]) {
            $doc->acl()->create(['subject_type' => $st, 'subject_id' => $st === 'user' ? (string) $this->user($sid)->id : $sid,
                'permission' => $perm, 'granted_by' => $this->user($ownerKey)->id, 'conditions' => $perm === 'deny' ? 'Pembatasan eksplisit (konflik kepentingan / prinsip least privilege)' : 'Whitelist / need-to-know']);
        }
        foreach ($o['links'] ?? [] as [$sys, $rec, $rel]) $doc->links()->create(['related_system' => $sys, 'external_record_id' => $rec, 'relation_type' => $rel]);
        foreach ($o['relations'] ?? [] as [$rel, $key]) $doc->relations()->create(['related_document_id' => $this->d[$key]->id, 'relation_type' => $rel]);
        return $doc->load(['entity', 'department', 'type', 'owner']);
    }

    private function addVersion(Document $doc, int $major, int $minor, string $note, string $uploaderKey, $at, string $status = 'draft', array $body = []): DocumentVersion
    {
        $label = "v{$major}.{$minor}";
        $stored = DocumentStorage::put($doc, $this->pdf($doc, $label, $body));
        $v = $doc->versions()->create($stored + [
            'major' => $major, 'minor' => $minor, 'status' => $status, 'original_name' => \Illuminate\Support\Str::slug($doc->title).'-'.$label.'.pdf',
            'mime' => 'application/pdf', 'change_note' => $note, 'uploaded_by' => $this->user($uploaderKey)->id,
            'content_text' => $doc->title.' '.$doc->description.' '.$doc->obligations.' '.implode(' ', $body),
        ]);
        $v->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        AuditLogger::log('version.uploaded', $doc, "Versi {$label} diunggah", [], $v->uploaded_by, $at, null, $v->checksum);
        return $v;
    }

    /** Dokumen berlaku melalui alur lengkap: draft → review → pengesahan (MFA) → publikasi. */
    private function published(string $title, string $depKey, string $typeCode, string $cls, string $ownerKey, string $creatorKey, string $effective, array $o = []): Document
    {
        $o['effective'] = $effective;
        $doc = $this->makeDoc($title, $depKey, $typeCode, $cls, $ownerKey, $creatorKey, $o);
        $eff = Carbon::parse($effective);
        $body = $o['body'] ?? [];

        $versions = $o['versions'] ?? [[1, 0, null, 'Versi pertama yang disahkan']];
        $last = count($versions) - 1;
        foreach ($versions as $i => [$maj, $min, $date, $note]) {
            $vat = $i === $last ? $eff->copy()->subDays(7)->setTime(9, 0) : Carbon::parse($date)->subDays(5);
            if ($i < $last) {
                $v = $this->addVersion($doc, $maj, $min, $note, $creatorKey, $vat, 'superseded', $body);
                $v->forceFill(['locked' => true, 'approved_by' => $this->user($o['approver'] ?? 'gm')->id, 'approved_at' => Carbon::parse($date)->subDay()])->saveQuietly();
                continue;
            }
            $v = $this->addVersion($doc, $maj, $min, $note, $creatorKey, $vat, 'draft', $body);

            // reviewer: kepala departemen (bukan pembuat/pemilik) atau Document Controller
            $head = $doc->department->head_user_id;
            $reviewer = ($head && ! in_array($head, [$doc->created_by, $doc->owner_id])) ? $head : $this->user('corsec')->id;
            if ($reviewer === $doc->created_by) $reviewer = $this->user('legal')->id;
            $approverKey = $o['approver'] ?? 'gm';
            $approver = $this->user($approverKey);
            if (in_array($approver->id, [$doc->created_by, $doc->owner_id, $reviewer])) $approver = $this->user($approverKey === 'gm' ? 'direktur' : 'gm');

            $submitter = $this->user($creatorKey);
            $wf = $this->wf->submit($doc, $v, $submitter, $reviewer === $submitter->id ? [] : [$reviewer], $approver->id, 'serial', 3, null, $vat->copy()->addHour());
            $late = rand(0, 9) === 0; // ~10% keputusan melewati SLA (untuk KPI)
            foreach ($wf->steps()->get() as $idx => $step) {
                $when = $vat->copy()->addDays($idx + 1)->addHours($late ? 80 : rand(2, 30));
                $this->wf->decide($step->fresh(), User::find($step->assignee_id), 'approved', $idx === 0 && $wf->steps()->count() > 1 ? 'Sudah sesuai.' : 'Disahkan.', $step->stage === 'approval', $when);
            }
            $this->wf->publish($doc->fresh(), $v->fresh(), $this->user('corsec'), $effective, $eff->copy()->setTime(8, 0)->max($vat->copy()->addDays(3)));
        }
        if (isset($o['review'])) $doc->update(['review_at' => $o['review']]);
        $doc->refresh();
        $doc->update(['retention_until' => $doc->computeRetentionUntil()]);
        return $doc;
    }

    private function draft(string $title, string $depKey, string $typeCode, string $cls, string $ownerKey, string $creatorKey, array $o = []): Document
    {
        $doc = $this->makeDoc($title, $depKey, $typeCode, $cls, $ownerKey, $creatorKey, $o);
        $at = Carbon::parse($o['at'] ?? now());
        $versions = $o['versions'] ?? [[0, 1, 'Draft awal']];
        foreach ($versions as $i => [$maj, $min, $note]) {
            $v = $this->addVersion($doc, $maj, $min, $note, $creatorKey, $at->copy()->addHours($i * 5), 'draft');
            if ($i < count($versions) - 1) $v->forceFill(['status' => 'superseded'])->saveQuietly();
        }
        return $doc;
    }

    private function decideStep($wf, int $index, string $decision, string $comment, $at, bool $mfa = false): void
    {
        $step = $wf->steps()->orderBy('sequence')->orderBy('id')->get()[$index];
        $this->wf->decide($step->fresh(), User::find($step->assignee_id), $decision, $comment, $mfa || $step->stage === 'approval', $at);
    }

    private function archive(string $key, ?string $byKey, $at): void
    {
        $doc = $this->d[$key];
        $doc->update(['status' => $byKey ? 'superseded' : 'archived', 'superseded_by_id' => $byKey ? $this->d[$byKey]->id : null, 'archived_at' => $at]);
        if ($byKey) $doc->versions()->where('status', 'final')->update(['status' => 'superseded']);
        $doc->update(['retention_until' => $doc->fresh()->computeRetentionUntil()]);
        AuditLogger::log($byKey ? 'document.superseded' : 'document.archived', $doc, $byKey ? 'Digantikan oleh '.$this->d[$byKey]->code : 'Dipindahkan ke arsip inaktif', [], $this->user('corsec')->id, $at);
    }

    private function meetings(Carbon $T): void
    {
        $g = Entity::where('code', 'GPCH')->first();
        $mk = function ($num, $type, $title, $at, $chair, $min, $cls, $agenda, $att) use ($g) {
            return Meeting::create(['entity_id' => $g->id, 'number' => $num, 'type' => $type, 'title' => $title, 'meeting_at' => $at,
                'location' => $type === 'rups' ? 'Ruang Priangan, Lt. 2' : 'Boardroom Lt. 12', 'chair_id' => $this->user($chair)->id,
                'secretary_id' => $this->user('corsec')->id, 'agenda' => $agenda, 'attendees' => $att, 'classification' => $cls,
                'minutes_document_id' => $this->d[$min]->id]);
        };
        $ai = function (Meeting $m, $dec, $title, $pic, $due, $status = 'open', $evidence = null, $note = null) {
            ActionItem::create(['meeting_id' => $m->id, 'decision_id' => $dec?->id, 'title' => $title, 'pic_id' => $this->user($pic)->id,
                'due_at' => $due, 'status' => $status, 'evidence_document_id' => $evidence ? $this->d[$evidence]->id : null, 'evidence_note' => $note,
                'completed_at' => $status === 'done' ? Carbon::parse($due)->subDays(2) : null]);
        };

        $rups = $mk('RUPS/GPCH/2026/01', 'rups', 'RUPS Tahunan Tahun Buku 2025', Carbon::parse('2026-05-28 10:00'), 'owner', 'rups', 'confidential',
            "1. Persetujuan Laporan Tahunan & pengesahan Laporan Keuangan 2025\n2. Penetapan penggunaan laba bersih\n3. Rencana renovasi ballroom", 'Pemegang saham, Dewan Komisaris, Direksi, Corporate Secretary');
        $d1 = $rups->decisions()->create(['number' => 'K-01', 'decision' => 'Menyetujui Laporan Tahunan dan mengesahkan Laporan Keuangan Audited 2025 serta memberikan pelunasan dan pembebasan tanggung jawab (acquit et de charge) kepada Direksi dan Dewan Komisaris.']);
        $d2 = $rups->decisions()->create(['number' => 'K-02', 'decision' => 'Menetapkan 40% laba bersih sebagai dividen tunai dan 60% sebagai laba ditahan untuk pendanaan capex.']);
        $d3 = $rups->decisions()->create(['number' => 'K-03', 'decision' => 'Menyetujui rencana renovasi Ballroom Pasundan dengan pagu maksimal Rp 6,5 miliar, rincian ditetapkan Direksi.']);
        $ai($rups, $d1, 'Publikasi ringkasan laporan keuangan audited', 'finance', '2026-06-15', 'done', 'lk2025');
        $ai($rups, $d2, 'Pembayaran dividen tunai kepada pemegang saham', 'finance', '2026-07-30', 'done', null, 'Bukti transfer bank tanggal 25/07/2026 tersimpan di arsip Finance.');
        $ai($rups, $d3, 'Tender kontraktor renovasi ballroom', 'engineering', $T->copy()->addDays(20), 'in_progress');

        $okt = $mk('BOD/GPCH/2026/10', 'bod', 'Rapat Direksi Bulanan Oktober 2026', Carbon::parse('2026-10-14 14:00'), 'gm', 'notulen_okt', 'confidential',
            "1. Tindak lanjut renovasi ballroom\n2. Status perizinan kritis (SLF, riksa uji lift)\n3. Implementasi pusat arsip korporasi", 'GM, Direktur Operasional, Corporate Secretary, Legal, Finance, Chief Engineer');
        $k1 = $okt->decisions()->create(['number' => 'K-01', 'decision' => 'Renovasi ballroom dimulai Januari 2027 setelah SK Direksi terbit; ballroom ditutup bertahap.']);
        $k2 = $okt->decisions()->create(['number' => 'K-02', 'decision' => 'Perpanjangan Sertifikat Laik Fungsi wajib diajukan paling lambat 30 hari sebelum berakhir.']);
        $k3 = $okt->decisions()->create(['number' => 'K-03', 'decision' => 'Riksa uji lift harus dijadwalkan ulang segera; lift tanpa sertifikat berlaku tidak boleh dioperasikan untuk tamu setelah tanggal berakhir.']);
        $k4 = $okt->decisions()->create(['number' => 'K-04', 'decision' => 'Implementasi SENTRA sebagai single source of truth dokumen resmi; pilot Legal & SOP Engineering.']);
        $ai($okt, $k1, 'Rencana kerja & jadwal shutdown ballroom', 'engineering', $T->copy()->subDays(6), 'in_progress');
        $ai($okt, $k1, 'Terbitkan SK Direksi renovasi ballroom', 'corsec', '2026-10-20', 'done', 'skd_ballroom');
        $ai($okt, $k2, 'Ajukan permohonan perpanjangan SLF ke DPMPTSP', 'legal', $T->copy()->subDays(3), 'open');
        $ai($okt, $k2, 'Siapkan laporan kajian teknis bangunan untuk SLF', 'engineering', $T->copy()->addDays(5), 'in_progress');
        $ai($okt, $k3, 'Jadwalkan riksa uji lift dengan PJK3', 'engineering', $T->copy()->subDays(15), 'open');
        $ai($okt, $k4, 'Migrasi dokumen prioritas Legal & SOP Engineering ke SENTRA', 'corsec', $T->copy()->addDays(30), 'in_progress');

        $mg = $mk('MGT/GPCH/2026/46', 'management', 'Rapat Manajemen Mingguan — Minggu ke-46', $T->copy()->subDays(5)->setTime(9, 0), 'gm', 'notulen_mgmt', 'department',
            "1. Forecast okupansi Desember\n2. Komplain AC kamar lantai 7\n3. SOP yang melewati jadwal review\n4. Audit hak akses triwulanan", 'GM & seluruh kepala departemen');
        $m1 = $mg->decisions()->create(['number' => 'K-01', 'decision' => 'Target okupansi Desember 82%; luncurkan kampanye Natal & Tahun Baru.']);
        $m2 = $mg->decisions()->create(['number' => 'K-02', 'decision' => 'Perbaikan FCU lantai 7 diprioritaskan dalam 48 jam.']);
        $m3 = $mg->decisions()->create(['number' => 'K-03', 'decision' => 'Seluruh SOP yang melewati tanggal review wajib direvisi dan diajukan melalui SENTRA.']);
        $m4 = $mg->decisions()->create(['number' => 'K-04', 'decision' => 'Lakukan audit hak akses sistem triwulan IV.']);
        $ai($mg, $m1, 'Kampanye Natal & Tahun Baru 2027', 'sales', $T->copy()->addDays(7), 'in_progress');
        $ai($mg, $m2, 'Perbaikan FCU kamar lantai 7', 'engineering', $T->copy()->subDays(3), 'done', null, 'Penggantian motor fan 6 unit FCU, WO #ENG-2611-044 di Asset Care.');
        $ai($mg, $m3, 'Revisi SOP Check-in & Check-out Tamu', 'fo', $T->copy()->addDays(14), 'open');
        $ai($mg, $m3, 'Revisi Kebijakan Keamanan Informasi', 'it', $T->copy()->addDays(10), 'in_progress');
        $ai($mg, $m4, 'Audit hak akses triwulan IV (SENTRA, VHP, email)', 'it', $T->copy()->addDays(10), 'open');

        $aq = $mk('BOD/GPCH/2026/K-03', 'bod', 'Rapat Direksi Khusus — Kajian Transaksi Strategis', $T->copy()->subDays(28)->setTime(16, 0), 'gm', 'akuisisi', 'strictly_confidential',
            "1. Pemaparan kajian awal\n2. Penunjukan konsultan transaksi", 'Owner, GM, Direktur Operasional');
        $q1 = $aq->decisions()->create(['number' => 'K-01', 'decision' => 'Melanjutkan due diligence terbatas dengan konsultan transaksi di bawah NDA.']);
        $ai($aq, $q1, 'Koordinasi due diligence legal (terbatas)', 'direktur', $T->copy()->addDays(25), 'open');
    }

    private function holdsAndDisposals(Carbon $T): void
    {
        $lg = $this->user('legal'); $dc = $this->user('corsec');
        LegalHold::create(['document_id' => $this->d['parkir']->id, 'reason' => 'Sengketa tagihan bagi hasil parkir periode Jan–Agu 2026; dokumen wajib dipertahankan sebagai alat bukti.',
            'case_reference' => 'Somasi No. 12/SOM/IX/2026', 'hold_owner_id' => $lg->id, 'placed_at' => $T->copy()->subDays(48)]);
        AuditLogger::log('hold.placed', $this->d['parkir'], 'Legal hold: sengketa tagihan bagi hasil parkir', [], $lg->id, $T->copy()->subDays(48));
        LegalHold::create(['document_id' => $this->d['loyalty2019']->id, 'reason' => 'Pengaduan pelindungan data pribadi oleh mantan anggota program loyalitas; menunggu klarifikasi.',
            'case_reference' => 'PDP/ADU/2026/031', 'hold_owner_id' => $lg->id, 'placed_at' => $T->copy()->subDays(20)]);
        AuditLogger::log('hold.placed', $this->d['loyalty2019'], 'Legal hold: pengaduan PDP', [], $lg->id, $T->copy()->subDays(20));
        LegalHold::create(['document_id' => $this->d['spt2025']->id, 'reason' => 'Pemeriksaan pajak tahun 2025 oleh KPP Madya Bandung.', 'case_reference' => 'SP2 No. 00045/PJ/2026',
            'hold_owner_id' => $lg->id, 'placed_at' => $T->copy()->subDays(120), 'released_at' => $T->copy()->subDays(35), 'released_by' => $lg->id,
            'release_note' => 'Pemeriksaan selesai, SKP nihil diterima.']);
        AuditLogger::log('disposal.blocked', $this->d['loyalty2019'], 'Usulan pemusnahan diblokir: legal hold aktif', [], $dc->id, $T->copy()->subDays(10));

        // pending persetujuan Legal
        DisposalRequest::create(['document_id' => $this->d['ramadan2019']->id, 'requested_by' => $dc->id, 'method' => 'Penghapusan aman digital',
            'reason' => 'Masa retensi 3 tahun (RET-MKT-3) telah berakhir pada 2022; tidak ada kebutuhan bisnis atau hukum.', 'created_at' => $T->copy()->subDays(3)]);
        $this->d['ramadan2019']->update(['status' => 'eligible_disposal']);
        AuditLogger::log('disposal.requested', $this->d['ramadan2019'], 'Usulan pemusnahan', [], $dc->id, $T->copy()->subDays(3));
        // menunggu persetujuan GM
        DisposalRequest::create(['document_id' => $this->d['nightaudit2018']->id, 'requested_by' => $dc->id, 'method' => 'Pencacahan fisik + penghapusan digital',
            'reason' => 'Retensi operasional 5 tahun berakhir 2023; laporan keuangan terkait sudah final.', 'status' => 'first_approved',
            'first_approver_id' => $lg->id, 'first_approved_at' => $T->copy()->subDays(1), 'created_at' => $T->copy()->subDays(6)]);
        $this->d['nightaudit2018']->update(['status' => 'eligible_disposal']);
        AuditLogger::log('disposal.approved', $this->d['nightaudit2018'], 'Persetujuan pemusnahan tahap 1', [], $lg->id, $T->copy()->subDays(1));

        // telah dieksekusi dengan berita acara
        $doc = $this->d['ny2018'];
        $manifest = [];
        foreach ($doc->versions as $v) {
            $manifest[] = ['version' => $v->label(), 'file' => $v->original_name, 'size' => $v->size, 'sha256' => $v->checksum];
            DocumentStorage::delete($v->object_key);
            $v->forceFill(['status' => 'destroyed', 'object_key' => null, 'content_text' => null])->saveQuietly();
        }
        DisposalRequest::create(['document_id' => $doc->id, 'requested_by' => $dc->id, 'method' => 'Penghapusan aman digital',
            'reason' => 'Retensi 3 tahun berakhir; materi promosi tidak memiliki nilai guna lanjutan.', 'status' => 'executed',
            'first_approver_id' => $lg->id, 'first_approved_at' => $T->copy()->subDays(40), 'second_approver_id' => $this->user('gm')->id,
            'second_approved_at' => $T->copy()->subDays(38), 'executed_at' => $T->copy()->subDays(37), 'executed_by' => $dc->id,
            'certificate_number' => 'BA-MUSNAH/GPCH/'.$T->year.'/001', 'manifest' => $manifest, 'created_at' => $T->copy()->subDays(45)]);
        $doc->update(['status' => 'disposed', 'disposed_at' => $T->copy()->subDays(37)]);
        AuditLogger::log('disposal.executed', $doc, 'Dimusnahkan dengan berita acara BA-MUSNAH/GPCH/'.$T->year.'/001', [], $dc->id, $T->copy()->subDays(37));
    }

    private function accessAndAcks(Carbon $T): void
    {
        AccessRequest::create(['requester_id' => $this->user('dimas')->id, 'document_id' => $this->d['lkq3']->id, 'permission' => 'view',
            'justification' => 'Membutuhkan data okupansi & ADR Q3 untuk bahan presentasi pelatihan upselling Front Office.', 'requested_until' => $T->copy()->addDays(7),
            'created_at' => $T->copy()->subHours(20)]);
        $r = AccessRequest::create(['requester_id' => $this->user('putri')->id, 'document_id' => $this->d['kk_bank']->id, 'permission' => 'download',
            'justification' => 'Menyiapkan draft perpanjangan kontrak korporat 2027 untuk Bank Parahyangan.', 'requested_until' => $T->copy()->addDays(10),
            'approver_id' => $this->user('sales')->id, 'decision' => 'approved', 'decision_note' => 'Disetujui untuk persiapan renewal.', 'decided_at' => $T->copy()->subDays(2), 'created_at' => $T->copy()->subDays(3)]);
        $this->d['kk_bank']->acl()->create(['subject_type' => 'user', 'subject_id' => (string) $this->user('putri')->id, 'permission' => 'download',
            'expires_at' => $T->copy()->addDays(10)->endOfDay(), 'conditions' => 'Permintaan akses #'.$r->id, 'granted_by' => $this->user('sales')->id]);
        AccessRequest::create(['requester_id' => $this->user('rizky')->id, 'document_id' => $this->d['remunerasi']->id, 'permission' => 'view',
            'justification' => 'Ingin mengetahui grading jabatan supervisor.', 'requested_until' => $T->copy()->addDays(5),
            'approver_id' => $this->user('hr')->id, 'decision' => 'rejected', 'decision_note' => 'Informasi grading individu disampaikan melalui atasan langsung.', 'decided_at' => $T->copy()->subDays(6), 'created_at' => $T->copy()->subDays(7)]);
        AccessRequest::create(['requester_id' => $this->user('engineering')->id, 'document_id' => $this->d['parkir']->id, 'permission' => 'view',
            'justification' => 'Perlu klausul tanggung jawab perawatan instalasi basement parkir untuk rencana renovasi.', 'requested_until' => $T->copy()->addDays(14),
            'created_at' => $T->copy()->subHours(6)]);

        // acknowledgement SOP & kebijakan
        $staff = User::whereNotIn('role', ['service', 'auditor'])->where('entity_id', 1)->get();
        foreach ([['sop_fire', 0.75], ['kbj_arsip', 0.35], ['pp', 0.85], ['sop_hk', 0.0], ['sop_komplain', 0.5]] as [$k, $ratio]) {
            $doc = $this->d[$k];
            $pick = $k === 'sop_hk' ? $staff->whereIn('department_id', [$doc->department_id]) : $staff->shuffle()->take((int) round($staff->count() * $ratio));
            foreach ($pick as $usr) {
                Acknowledgement::create(['document_id' => $doc->id, 'document_version_id' => $doc->current_version_id, 'user_id' => $usr->id,
                    'acknowledged_at' => $T->copy()->subDays(rand(1, 25))->setTime(rand(8, 17), rand(0, 59))]);
            }
        }

        SavedSearch::create(['user_id' => $this->user('gm')->id, 'name' => 'Izin & kontrak berakhir ≤ 90 hari', 'query' => ['module' => 'legal', 'expiring' => 90]]);
        SavedSearch::create(['user_id' => $this->user('gm')->id, 'name' => 'Keputusan direksi', 'query' => ['q' => 'keputusan direksi']]);
        SavedSearch::create(['user_id' => $this->user('legal')->id, 'name' => 'Kontrak berlaku', 'query' => ['module' => 'legal', 'status' => 'published', 'q' => 'kontrak']]);
        SavedSearch::create(['user_id' => $this->user('corsec')->id, 'name' => 'Dokumen dalam review', 'query' => ['status' => 'under_review']]);
        SavedSearch::create(['user_id' => $this->user('engineering')->id, 'name' => 'Sertifikat K3', 'query' => ['q' => 'k3']]);
    }

    private function activityNoise(Carbon $T): void
    {
        $acl = app(AccessControl::class);
        $users = User::with('mandates')->whereNotIn('role', ['service'])->where('is_active', true)->get()->reject->isAccessExpired();
        $docs = Document::with(['acl', 'category', 'department', 'currentVersion'])->where('status', 'published')->get();
        $terms = ['sertifikat', 'kontrak', 'sop', 'notulen', 'laporan keuangan', 'slf', 'halal', 'lift', 'anggaran', 'kebakaran', 'remunerasi', 'parkir', 'keputusan direksi', 'k3', 'laundry'];
        $ips = ['10.10.1.', '10.10.2.', '10.10.5.', '192.168.20.'];
        $dev = ['Mozilla/5.0 (Windows NT 10.0) Chrome/131', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1) Safari', 'Mozilla/5.0 (Macintosh) Safari/18', 'Mozilla/5.0 (Linux; Android 15) Chrome/131'];

        $events = [];
        foreach ($users as $usr) {
            $visible = $docs->filter(fn ($d) => $acl->can($usr, $d))->values();
            for ($day = 29; $day >= 0; $day--) {
                if (rand(0, 9) < 4) continue;
                $base = $T->copy()->subDays($day)->setTime(rand(7, 18), rand(0, 59), rand(0, 59));
                if ($base->isFuture()) $base = now()->subMinutes(rand(5, 300));
                $meta = ['_ip' => $ips[array_rand($ips)].rand(10, 250), '_device' => $dev[array_rand($dev)]];
                $events[] = [$base, 'login', $usr->id, null, 'Login berhasil', $meta];
                if ($visible->isEmpty()) continue;
                for ($i = 0; $i < rand(1, 3); $i++) {
                    $doc = $visible->random();
                    $events[] = [$base->copy()->addMinutes(rand(2, 90)), 'document.viewed', $usr->id, $doc, 'Membuka detail dokumen', $meta];
                    if (rand(0, 2) === 0 && $doc->currentVersion) $events[] = [$base->copy()->addMinutes(rand(91, 120)), 'document.previewed', $usr->id, $doc, 'Preview '.$doc->currentVersion->label(), $meta];
                }
                if (rand(0, 2) === 0) {
                    $t = $terms[array_rand($terms)];
                    $events[] = [$base->copy()->addMinutes(rand(1, 60)), 'search', $usr->id, null, 'Pencarian: '.$t, $meta + ['filters' => ['q' => $t], 'results' => rand(1, 14), 'ms' => rand(90, 1400)]];
                }
            }
        }
        // auditor mengunduh beberapa dokumen keuangan (tercatat, AC-06)
        $auditor = User::where('email', 'auditor@kap-mitra.test')->first();
        foreach (['lk2025', 'lkq3', 'spt2025', 'ppn_okt', 'evl_s1'] as $i => $k) {
            $doc = $this->d[$k];
            $events[] = [$T->copy()->subDays(5 - $i)->setTime(10, 15 + $i), 'document.downloaded', $auditor->id, $doc, 'Unduh '.$doc->currentVersion->label().' (mandat audit)', ['_ip' => '103.28.14.77', '_device' => 'Mozilla/5.0 (Windows NT 10.0) Edge/131']];
        }
        // upaya akses tanpa izin berulang (monitoring anomali)
        $dimas = User::where('email', 'dimas@gpch.test')->first();
        foreach (['remunerasi', 'lkq3', 'notulen_okt', 'pkwt'] as $i => $k) {
            $events[] = [$T->copy()->subDays(2)->setTime(21, 10 + $i * 3), 'document.access_denied', $dimas->id, $this->d[$k], 'Akses view ditolak: dokumen Rahasia (need-to-know)', ['_ip' => '10.10.2.88', '_device' => 'Mozilla/5.0 (Linux; Android 15) Chrome/131']];
        }
        $it = User::where('email', 'it@gpch.test')->first();
        $events[] = [$T->copy()->subDays(1)->setTime(13, 2), 'document.access_denied', $it->id, $this->d['mcu'], 'Akses view ditolak: Akses Anda ke dokumen ini dibatasi secara eksplisit (deny).', ['_ip' => '10.10.5.10', '_device' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/131']];
        $events[] = [$T->copy()->subDays(3)->setTime(7, 40), 'login_failed', null, null, 'Login gagal untuk admin@gpch.test', ['_ip' => '45.142.120.9', '_device' => 'python-requests/2.32']];
        $events[] = [$T->copy()->subDays(3)->setTime(7, 41), 'login_failed', null, null, 'Login gagal untuk administrator@gpch.test', ['_ip' => '45.142.120.9', '_device' => 'python-requests/2.32']];
        $events[] = [$T->copy()->subDays(3)->setTime(7, 41), 'login_failed', null, null, 'Login gagal untuk root@gpch.test', ['_ip' => '45.142.120.9', '_device' => 'python-requests/2.32']];
        $events[] = [$T->copy()->subDays(4)->setTime(16, 0), 'export', $this->user('corsec')->id, null, 'Ekspor register dokumen (58 baris, hanya metadata)', ['count' => 58, '_ip' => '10.10.1.21', '_device' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/131']];

        usort($events, fn ($a, $b) => $a[0] <=> $b[0]);
        foreach ($events as [$at, $ev, $actor, $res, $desc, $meta]) {
            AuditLogger::log($ev, $res, $desc, $meta, $actor, $at);
        }
    }
}
