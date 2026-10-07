# Prosedur Perubahan Paket & Harga (Plans)

Dokumen ini adalah prosedur resmi mengubah paket (harga, fitur, batas) di
Cekat. Ia ada karena temuan QA F-01 (2026-10-07): tabel `plans` di
produksi menyimpang diam-diam dari seeder — pelanggan Pro membayar tetapi
Leads terkunci, tier AI tetap Basic, riwayat chat tetap 7 hari.

## Sumber kebenaran

- **Nilai kanonis paket** hidup di `UpdateExistingPlansSeeder`
  (harga, `can_export_leads`, `ai_tier`, `chat_history_days`, `features`)
  dan dicerminkan oleh penjaga `PlansAudit` (`app/Console/Commands/PlansAudit.php`)
  serta test `PlanIntegrityTest`.
- Halaman harga di landing membaca langsung tabel `plans` — jadi yang
  terlihat pelanggan adalah isi database, bukan seeder. Keduanya wajib sinkron.

## Prosedur mengubah paket

1. Ubah `UpdateExistingPlansSeeder` (dan `DefaultPlansSeeder` untuk
   instalasi baru bila relevan) di branch terpisah.
2. Perbarui cermin invarian di `PlansAudit::EXPECTED` agar audit tetap akurat.
3. Jalankan test: `php artisan test` — **ini gerbang wajib sebelum deploy**
   (repo ini tidak memiliki CI; `PlanIntegrityTest` adalah penjaga utamanya).
4. Di server, SEBELUM menjalankan seeder, backup tabel:
   ekspor baris `plans` (mis. lewat tinker ke berkas JSON bertanggal).
5. Jalankan HANYA seeder rekonsiliasi (jangan pernah `db:seed` penuh di produksi):
   `php artisan db:seed --class=UpdateExistingPlansSeeder --force`
6. Verifikasi: `php artisan plans:audit` harus melaporkan **OK** (exit 0).
7. Uji satu akun per paket (login → buka Leads, periksa tier & riwayat).

## Pemeriksaan berkala

Jalankan `php artisan plans:audit` di server secara berkala (mis. setelah
setiap deploy atau perubahan admin pada paket). Exit code non-nol berarti
ada drift: ikuti langkah 5–7 di atas. Mengubah paket lewat panel admin
SAH, tetapi setelahnya perbarui juga seeder + `PlansAudit` agar nilai
kanonis tidak tertinggal — panel admin mengubah database, bukan sumber
kebenaran ini.
