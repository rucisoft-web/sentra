# Kontrak tautan dokumen SEWAKA ↔ SENTRA (draf integrasi)

SENTRA tetap menjadi pemilik berkas, versi, retensi, dan keputusan akses dokumen. SEWAKA, PERSONA, Asset Care, serta Ruci BSC hanya menyimpan referensi (`system`, `record`, dan kode dokumen), bukan salinan berkas.

## Kode sistem

| Kode | Pemilik record | Contoh ID eksternal |
| --- | --- | --- |
| `asset_care` | Asset Care | kode aset atau work order |
| `persona_gpch` | PERSONA GPCH | UUID pegawai/kontrak, bukan NIK |
| `sewaka_pms` | SEWAKA PMS | UUID reservasi/folio, bukan nama tamu |
| `sewaka_finance` | SEWAKA Finance | UUID voucher/jurnal |
| `ruci_bsc` | Ruci BSC | kode KPI/inisiatif dalam konteks GPCH |

Kode lama `crm_gpch` dan `vhp` dipertahankan untuk referensi dan migrasi.

## Alur

1. Pemilik proses menerbitkan dan mengesahkan dokumen di SENTRA.
2. Service account berizin membuat relasi melalui `POST /api/v1/links` dengan `document_code`, `related_system`, `external_record_id`, dan `relation_type`.
3. Aplikasi sumber meminta `GET /api/v1/links/{system}/{record}` untuk metadata dan `deep_link`. Kiriman ulang untuk pasangan dokumen/sistem/record tidak menggandakan relasi.
4. Saat pengguna membuka `/link/{system}/{record}`, SENTRA mewajibkan login lalu memeriksa izin pengguna atas tiap dokumen. Respons API metadata tidak boleh dipakai sebagai izin unduh.
5. Jika dokumen diganti/ditarik atau akses dicabut, aplikasi sumber membuka ulang deep link dan mengikuti status terbaru di SENTRA.

Contoh permintaan pengaitan (nilai ilustratif; token disediakan terpisah melalui pengelola rahasia):

```json
{
  "document_code": "GPCH/ENG/SOP/2026/003",
  "related_system": "sewaka_pms",
  "external_record_id": "room-workflow-uuid",
  "relation_type": "sop_berlaku"
}
```

## Batas dan prasyarat produksi

- Kode di atas hanya memperluas daftar sistem yang boleh ditautkan. Ini **belum** membuat koneksi jaringan, SSO, atau sinkronisasi data otomatis.
- Token demo tidak digunakan di produksi. Setiap aplikasi membutuhkan service account dan token yang berbeda, tersimpan sebagai secret server, dengan ruang lingkup izin yang ditinjau.
- Jangan kirim NIK, nomor identitas tamu, isi kontrak, gaji, atau data pribadi lewat ID eksternal, URL, log, dan payload metadata.
- Uji pemisahan entitas hukum, klasifikasi dokumen, pembatasan service account, penghentian token, kegagalan jaringan, dan duplikasi pesan sebelum integrasi dinyalakan.
- Asset Care belum menyediakan API PMS menurut README rilis saat ini; PERSONA belum tersedia dalam repositori yang terhubung; Ruci BSC saat ini mendokumentasikan impor Excel. Konektor masing-masing mengikuti discovery dan persetujuan pemilik data.
