# Panduan Instalasi SENTRA di cPanel (via Terminal)

Target instalasi:

| Item | Nilai |
|---|---|
| URL | `https://sentra.gpchotel.com` |
| Database | `gpchotel_sentra` |
| User database | `gpchotel_sentra` |
| Folder aplikasi (contoh) | `/home/gpchotel/sentra` |
| Document root subdomain | `/home/gpchotel/sentra/public` |

> Contoh di bawah memakai username cPanel `gpchotel`, sesuai prefix database. Kalau username Anda berbeda, ganti `/home/gpchotel` dengan home folder Anda. Cek dengan perintah `echo $HOME` di Terminal.

File `sentra-cpanel.zip` **sudah berisi folder `vendor/` dan CSS hasil build**, jadi Composer dan npm tidak diperlukan di server.

---

## Langkah 0 — Persiapan di cPanel (sekali saja)

1. **Versi PHP.** Buka **cPanel → MultiPHP Manager**, centang `sentra.gpchotel.com`, lalu pilih **PHP 8.3** atau **8.4**.
   Di **Select PHP Version / EasyApache**, pastikan ekstensi berikut aktif: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`.
2. **Database.** Database `gpchotel_sentra` dan user `gpchotel_sentra` sudah ada. Buka **MySQL® Databases → Add User To Database**, lalu pastikan user sudah ditambahkan ke database dengan **ALL PRIVILEGES**. Siapkan juga password database-nya.
3. **SSL.** Buka **SSL/TLS Status**, lalu jalankan **Run AutoSSL** untuk `sentra.gpchotel.com` agar HTTPS aktif.

## Langkah 1 — Unggah & ekstrak

**Opsi A (File Manager):**
1. Unggah `sentra-cpanel.zip` ke home folder (`/home/gpchotel`).
2. Klik kanan file tersebut, lalu pilih **Extract**. Hasilnya folder `sentra/`.

**Opsi B (Terminal)**, kalau zip sudah diunggah ke home folder:

```bash
cd ~
unzip -q sentra-cpanel.zip      # menghasilkan folder ~/sentra
ls sentra                        # pastikan ada: app  artisan  public  vendor  deploy ...
```

## Langkah 2 — Arahkan subdomain ke folder `public`

1. Buka **cPanel → Domains** (atau **Subdomains** di cPanel lama).
2. Kalau `sentra.gpchotel.com` belum ada, klik **Create A New Domain**. Kalau sudah ada, klik **Manage**.
3. Isi **Document Root** dengan **`sentra/public`**, lalu klik **Update/Submit**.

> Kalau document root tidak bisa diubah, folder aplikasi sudah dilengkapi `.htaccess` cadangan yang meneruskan semua request ke `public/` dan memblokir file sensitif. Meski begitu, mengarahkan document root ke `public` tetap cara yang paling aman.

## Langkah 3 — Jalankan instalasi dari Terminal

Buka **cPanel → Terminal**, lalu jalankan:

```bash
cd ~/sentra
bash deploy/install-cpanel.sh
```

Skrip ini akan melakukan langkah berikut secara otomatis:
1. Mencari PHP CLI 8.3+ (misalnya `/opt/cpanel/ea-php83/root/usr/bin/php`) dan mengecek ekstensinya.
2. Membuat `.env` dari `deploy/.env.cpanel`. URL, nama database, dan user sudah terisi.
3. **Meminta password database**, lalu menyimpannya di `.env`.
4. Membuat `APP_KEY`, mengatur izin folder `storage/` dan `bootstrap/cache/`.
5. Membuat tabel dan **mengisi data demo Grand Pasundan Convention Hotel**.
6. Menjalankan cache konfigurasi, route, dan view untuk produksi.

Kalau berhasil, akan muncul pesan `✓ SENTRA terpasang`. Buka **https://sentra.gpchotel.com**.

> Kalau skrip gagal mendeteksi PHP, tentukan path-nya secara manual:
> `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy/install-cpanel.sh`

## Langkah 4 — Cron untuk pengingat otomatis

Buka **cPanel → Cron Jobs → Add New Cron Job**, pilih **Once Per Minute (\* \* \* \* \*)**, lalu isi command:

```
/opt/cpanel/ea-php83/root/usr/bin/php /home/gpchotel/sentra/artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan path PHP dengan yang ditampilkan di akhir skrip instalasi. Cron ini menjalankan:
- pengingat masa berlaku izin/kontrak H-90/60/30 (setiap hari pukul 07:00 WIB),
- eskalasi persetujuan yang melewati SLA (setiap jam),
- evaluasi retensi arsip (setiap hari pukul 01:00 WIB).

## Langkah 5 — Uji & amankan

1. Login dengan `gm@gpch.test` / `password`. Daftar akun demo lain tampil di halaman login.
2. **Karena situs dapat diakses publik**, segera ganti kata sandi semua akun demo:
   ```bash
   cd ~/sentra
   php artisan archive:set-password --all-demo          # ganti semua akun sekaligus
   php artisan archive:set-password gm@gpch.test        # atau per akun
   ```
   Kalau `php` di Terminal bukan versi 8.3, pakai path lengkap, misalnya `/opt/cpanel/ea-php83/root/usr/bin/php artisan ...`.
3. Setelah masa demo selesai, buka `.env` dan ubah `ARCHIVE_DEMO_MODE=false`. Setelah itu daftar akun demo dan kode MFA tidak lagi tampil di layar. Lalu jalankan:
   ```bash
   php artisan config:cache
   ```
   Dengan mode demo nonaktif, pengguna memasukkan kode MFA dari aplikasi autentikator (Google atau Microsoft Authenticator). Secret per pengguna ditampilkan di halaman verifikasi MFA.

---

## Perintah yang sering dipakai

| Keperluan | Perintah (dari `~/sentra`) |
|---|---|
| Reset data demo (hapus semua & isi ulang) | `bash deploy/install-cpanel.sh --reset` |
| Bersihkan cache setelah mengubah `.env` | `php artisan optimize:clear && php artisan config:cache` |
| Kirim pengingat masa berlaku sekarang | `php artisan archive:reminders` |
| Lihat log error | `tail -n 50 storage/logs/laravel-$(date +%F).log` |
| Mode maintenance | `php artisan down` / `php artisan up` |

## Pembaruan versi

1. **Backup dulu** database (lewat phpMyAdmin → Export, atau Backup cPanel) dan folder `storage/app/private/archive`.
2. Ekstrak zip versi baru ke folder sementara, lalu salin isinya ke `~/sentra`. **Jangan menimpa** `.env` dan folder `storage/`.
3. Jalankan `bash deploy/install-cpanel.sh`. Kalau database sudah terpasang, skrip hanya menjalankan migrasi baru dan **tidak menghapus data**.

## Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| **500 Server Error** | Lihat `storage/logs/`. Biasanya karena izin folder, jadi jalankan `chmod -R 775 storage bootstrap/cache`. |
| `could not find driver` | Ekstensi `pdo_mysql` belum aktif untuk PHP yang dipakai. |
| `Access denied for user 'gpchotel_sentra'` | Password di `.env` salah, atau user belum diberi ALL PRIVILEGES pada database. Perbaiki lalu jalankan `php artisan config:cache`. |
| `Your PHP version ... does not satisfy` / syntax error | PHP CLI di Terminal versinya lama. Pakai `/opt/cpanel/ea-php83/root/usr/bin/php`. |
| Halaman tampil daftar file / 403 Forbidden | Document root belum diarahkan ke `sentra/public` (Langkah 2). |
| Tampilan tanpa gaya (CSS tidak muncul) | Pastikan file `public/css/sentra.css` ada dan `APP_URL` sudah `https://sentra.gpchotel.com`. |
| File dokumen tidak bisa dibuka setelah pindah server | `APP_KEY` di `.env` berubah. File arsip terenkripsi dengan key ini, jadi selalu simpan dan backup `APP_KEY`. |
| Upload file besar gagal | Naikkan `upload_max_filesize` dan `post_max_size` (misalnya 25M) di **MultiPHP INI Editor**. |
