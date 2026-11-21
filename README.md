# SENTRA — Enterprise Document & Governance System

Pusat arsip korporasi resmi untuk **Grand Pasundan Convention Hotel (GPCH)**. SENTRA menyimpan, mengatur, menelusuri, menyetujui, melindungi dan mempertahankan dokumen perusahaan sepanjang siklus hidupnya, sesuai *SRS GPCH Corporate Archive v1.0*.

> Prinsip 3T: **Terstruktur • Tersistem • Tepat Sasaran**

Dibangun dengan **Laravel 13**, **PHP 8.3+**, dan **MySQL 8** (MariaDB 10.6+ juga bisa). Tampilannya responsif dan nyaman dipakai dari smartphone.

---

## 1. Instalasi cepat

> **Hosting cPanel?** Ikuti [PANDUAN-CPANEL.md](PANDUAN-CPANEL.md) — tersedia paket zip siap pakai (sudah berisi vendor) dan skrip `deploy/install-cpanel.sh`.

Kebutuhan: PHP ≥ 8.3 (ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`), Composer 2, dan MySQL 8.

```bash
git clone https://github.com/rucisoft-web/sentra.git
cd sentra
composer install
cp .env.example .env
php artisan key:generate
```

Buat database, lalu sesuaikan `.env`:

```sql
CREATE DATABASE gpch_archive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gpch_archive
DB_USERNAME=root
DB_PASSWORD=
APP_TIMEZONE=Asia/Jakarta
ARCHIVE_DEMO_MODE=true   # menampilkan kode MFA demo di layar; set false di produksi
```

Jalankan migrasi beserta data demo, lalu jalankan servernya:

```bash
php artisan migrate --seed      # atau: php artisan migrate:fresh --seed untuk reset demo
php artisan serve
```

Buka `http://localhost:8000` untuk landing page, lalu klik **Masuk Demo**.

> **APP_KEY penting.** File arsip dienkripsi dengan `APP_KEY`. Kalau key diganti, file lama tidak bisa dibuka lagi, jadi jalankan ulang `migrate:fresh --seed` untuk demo.

CSS sudah di-build ke `public/css/sentra.css`, jadi **npm tidak wajib**. Kalau tampilan diubah, build ulang dengan `npm install && npm run build:css`.

### Penjadwal (opsional, untuk pengingat otomatis)

```bash
php artisan schedule:work        # pengembangan
# produksi (cron): * * * * * cd /path/sentra && php artisan schedule:run >> /dev/null 2>&1
```

| Perintah | Fungsi |
|---|---|
| `php artisan archive:reminders` | Pengingat masa berlaku H-90 / H-60 / H-30 / kedaluwarsa (setiap hari 07:00) |
| `php artisan archive:escalate` | Eskalasi langkah review/persetujuan yang melewati SLA (setiap jam) |
| `php artisan archive:retention` | Hitung retensi & tandai arsip yang layak musnah (setiap hari 01:00) |

---

## 2. Akun demo

Semua akun memakai kata sandi **`password`**. Di halaman masuk, klik nama akun untuk mengisi form otomatis.

| Email | Nama | Peran | Cocok untuk mencoba |
|---|---|---|---|
| `owner@gpch.test` | Ir. H. Dadang Suryana | Owner / Komisaris | AC-02: menemukan notulen direksi tetapi tidak bisa membukanya |
| `gm@gpch.test` | Rina Kartikasari | BOD / General Manager | Dashboard eksekutif, pengesahan dengan MFA, persetujuan pemusnahan tahap 2 |
| `direktur@gpch.test` | Budi Santoso | BOD / Direktur Operasional | Persetujuan RKAP 2027 yang melewati SLA |
| `corsec@gpch.test` | Siti Nurhaliza | Document Controller | Publikasi, ACL, usulan dan eksekusi pemusnahan, ekspor register |
| `legal@gpch.test` | Andi Pratama, S.H. | Legal & Compliance | Register izin & kontrak, legal hold, persetujuan pemusnahan tahap 1 |
| `finance@gpch.test` | Dewi Lestari | Finance & Tax | Arsip keuangan & pajak, permintaan akses masuk |
| `hr@gpch.test` | Yusuf Maulana | HR | Dokumen SDM & data pribadi, review kebijakan |
| `engineering@gpch.test` | Agus Setiawan | Kepala Departemen | Action item terlambat, review SOP chiller |
| `fo@gpch.test` | Maya Anggraini | Kepala Departemen | Pengaju SOP VIP |
| `rizky@gpch.test` | Rizky Ramadhan | Staf / Uploader | Mengunggah revisi SOP yang dikembalikan |
| `dimas@gpch.test` | Dimas Prakoso | Staf | Akses terbatas & permintaan akses |
| `it@gpch.test` | Fajar Nugroho | IT / Security Admin | AC-07: admin tidak bisa membaca dokumen rahasia |
| `auditor@kap-mitra.test` | Drs. Hartono Wibisono | Auditor eksternal | AC-06: akses read-only sesuai mandat, unduhan tercatat |
| `sri.audit@gpch.test` | Sri Wahyuni | Auditor (akses berakhir) | Login ditolak karena masa akses sudah habis |
| `teguh@gbk.test` | Teguh Hidayat | Finance entitas GBK | Isolasi data antar-entitas hukum |

**MFA (step-up).** Pengesahan, dokumen Sangat Rahasia, ekspor, pemusnahan, dan menu admin meminta kode TOTP. Saat `ARCHIVE_DEMO_MODE=true`, kode yang berlaku ditampilkan di layar verifikasi. Di produksi, gunakan aplikasi autentikator (Google atau Microsoft Authenticator).

**Token API demo:** `demo-assetcare-token-0123456789` (service account Asset Care).

---

## 3. Skenario demo yang bisa dicoba

1. **Pengesahan dengan MFA (AC-04).** Masuk sebagai GM, buka *Tugas Saya*, lalu klik **Sahkan** pada *SOP Check-in Tamu VIP*. Setelah verifikasi MFA dan klik lagi, versinya terkunci. Lanjutkan sebagai Document Controller untuk **Publikasikan**.
2. **Revisi tanpa kehilangan riwayat (AC-03).** Masuk sebagai Rizky, buka *SOP Pemeliharaan Chiller*, baca catatan revisi, unggah versi baru, lalu ajukan ulang.
3. **Pengingat izin (AC-05).** Masuk sebagai Legal dan buka *Legal & Perizinan*. Di sana terlihat SLF (25 hari lagi), riksa uji lift (sudah lewat 12 hari), limbah B3, dan lainnya, beserta notifikasinya.
4. **Owner & dokumen rahasia (AC-02).** Masuk sebagai Owner, cari "notulen rapat direksi", lalu buka *Notulen Rapat Direksi Oktober 2026*. Muncul layar *Akses terbatas* dan form permintaan akses.
5. **Admin bukan pembaca (AC-07).** Masuk sebagai IT Admin dan buka *Rekapitulasi Medical Check-up*. Hasilnya 403 dan upaya itu tercatat di audit.
6. **Auditor (AC-06).** Masuk sebagai auditor KAP, unduh *Laporan Keuangan Audited 2025*, lalu lihat jejaknya di *Audit Trail*.
7. **Legal hold memblokir pemusnahan (AC-08).** Masuk sebagai Document Controller, buka *Data Peserta Program Loyalitas 2019*, lalu coba usulkan pemusnahan. Usulan akan ditolak.
8. **Pemusnahan persetujuan ganda.** *Retensi & Legal Hold* berisi tiga contoh: menunggu Legal, menunggu GM, dan sudah dieksekusi beserta **berita acara** yang bisa dicetak.
9. **Dashboard GM (AC-09).** Dashboard GM menampilkan action item terlambat beserta PIC, persetujuan tertunda, dokumen kedaluwarsa, dan review overdue.
10. **Tautan dari sistem lain (AC-10).** Buka `/link/asset_care/AST-ENG-CHL-01` saat sudah login, atau panggil API-nya:
    ```bash
    curl -H "Authorization: Bearer demo-assetcare-token-0123456789" http://localhost:8000/api/v1/links/asset_care/AST-ENG-CHL-01
    ```
11. **Integritas audit trail.** Buka *Audit Trail* lalu klik **Verifikasi integritas rantai**. Rantai hash SHA-256 diperiksa ulang dari awal sampai akhir.

---

## 4. Cakupan modul (SRS §4)

| Modul | Implementasi |
|---|---|
| M01 Repository & klasifikasi | Folder virtual (modul → kategori), jenis dokumen, nomor otomatis `ENTITAS/DEPT/JENIS/TAHUN/NNN` yang unik per entitas, metadata wajib, relasi antardokumen, tag, preview aman ber-watermark |
| M02 Legal & compliance | Register izin & kontrak, pihak terkait, kewajiban, nilai kontrak, tanggal efektif/akhir, pengingat 90/60/30 hari, relasi "perpanjangan dari" |
| M03 Policy, SOP & organisasi | Pusat SOP versi berlaku, acknowledgement karyawan dan rekapnya, siklus review periodik |
| M04 Board & management records | Rapat (RUPS, BOD, manajemen), keputusan, action item, PIC, tenggat, bukti tindak lanjut wajib, akses khusus |
| M05 Finance & tax | Arsip laporan final, pajak, anggaran, evaluasi, tautan VHP & Ruci BSC |
| M06 Marketing & customer | Kontrak korporat, proposal, kampanye, tautan CRM GPCH |
| M07 Workflow & e-approval | Alur serial/paralel, SLA, eskalasi, delegasi sementara, revisi/tolak beralasan, pemisahan tugas |
| M08 Versioning & publication | Check-out/check-in, versi major/minor, compare metadata, supersede, tanggal efektif, versi final terkunci |
| M09 Search & discovery | Full-text (nomor, judul, deskripsi, tag, pihak, isi teks PDF), filter, saved search, hasil disaring sesuai izin |
| M10 Retention & records | Jadwal retensi per kategori, arsip inaktif, legal hold, pemusnahan dengan persetujuan ganda + MFA + berita acara & manifest checksum |
| M11 Audit & security | Log lihat/preview/unduh/ubah/approve/ekspor/akses ditolak, append-only + hash chain, monitoring anomali, watermark |
| M12 Dashboard & reports | Kedaluwarsa, review overdue, persetujuan tertunda, arsip tidak lengkap, aktivitas pengguna, SLA pencarian (p95), KPI SRS §14 |
| M13 Administration & integration | Entitas, departemen, pengguna & peran, masa berlaku akses, izin ekspor terpisah, mandat auditor, API & token service account |

### Model akses (SRS §5)

Akses ditentukan oleh **RBAC + ABAC + ACL** per dokumen (`app/Services/AccessControl.php`): default deny, *deny* mengalahkan *allow*, dan ada 4 tingkat klasifikasi. Dokumen Sangat Rahasia hanya untuk whitelist individu dan wajib MFA ulang. Administrator teknis tidak otomatis bisa membaca. Pembuat tidak boleh mengesahkan dokumen material miliknya sendiri. Akses auditor dan tamu selalu berbatas waktu. Otorisasi dijalankan di server untuk setiap view, preview, unduh, cari, API, dan ekspor.

### Penyimpanan file

File disimpan di disk privat `storage/app/private/archive` (bukan folder publik), dienkripsi AES-256 dengan `APP_KEY`, dan diverifikasi SHA-256 setiap kali dibuka. Untuk produksi, disk `archive` di `config/filesystems.php` bisa diganti ke S3-compatible privat.

---

## 5. Pengujian

```bash
php artisan test
```

Terdapat 15 uji fitur (`tests/Feature/SentraAcceptanceTest.php`) yang memverifikasi AC-01 sampai AC-10 dan *smoke test* semua halaman untuk setiap akun demo. Uji ini memakai SQLite in-memory, jadi tidak menyentuh database MySQL.

---

## 6. Catatan & batasan versi demo

- OCR penuh dan mesin pencari (Meilisearch/OpenSearch) belum dipasang. Saat ini pencarian memakai indeks teks di MySQL dan ekstraksi teks PDF sederhana.
- SSO/OIDC, antivirus upload, e-sign/e-meterai, dan integrasi API sungguhan ke VHP, Asset Care, CRM, dan BSC masih berupa titik integrasi (deep link + API metadata). Fitur ini perlu discovery sesuai SRS §15.
- Dokumen PDF demo dibuat otomatis oleh seeder dan isinya ilustratif. Nama perusahaan mitra di data demo adalah fiktif.
- Matikan `ARCHIVE_DEMO_MODE` dan `APP_DEBUG` sebelum dipakai di lingkungan nyata.
