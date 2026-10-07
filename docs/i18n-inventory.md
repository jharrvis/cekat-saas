# Inventaris String Lokalisasi (T-12)

Daftar migrasi string menghadap pengguna ke sistem lokalisasi Laravel
(`lang/id/*` + cermin `lang/en/*` dengan himpunan kunci identik, dijaga
`LocaleTest`). Status per 7 Oktober 2026 (branch `fix/t-12-i18n`).

## Sudah bermigrasi ke kunci lokalisasi

| Permukaan | Kunci | Catatan |
|---|---|---|
| Flash auth (register, verifikasi, reset, Google) | `auth.*` | termasuk halaman lupa/atur ulang sandi yang sebelumnya full Inggris |
| Flash channel (buat/ubah/hapus/aktifkan/model/lead/domain/webhook/unlink) | `channels.*` | dua pesan batas di ChannelController dimiliki T-05 (`plans.limit_reached` + flash `plan_limit_error`) |
| Flash WhatsApp (device) | `whatsapp.*` | |
| Flash billing & pengaturan | `billing.*`, `settings.*` | termasuk pemilih bahasa di Pengaturan (`users.locale`) |
| Flash agen | `agents.*` | |
| Error API chat publik | `api.*` | widget_not_found/inactive/domain_blocked/owner_missing/account_suspended + rate_limited; menghormati `Accept-Language` khusus jalur `api/*` |
| Instruksi embed channel | teks ID di view | nama tab dikoreksi: **Umum** & **Tampilan** (sebelumnya "General/Appearance") |
| Email utama (OTP, pembayaran sukses) | `emails.*` | subjek + locale penerima dari `users.locale` (`$mailable->locale`) |
| Widget publik | `widget.*` | string bawaan (salam, placeholder, subtitle, branding, formulir pra-chat) dilayani endpoint config widget menurut locale pemilik widget; `public/widget/widget.js` mengonsumsi `config.i18n` |
| Validasi | `validation.php`, `auth.php`, `passwords.php`, `pagination.php` | dikerjakan T-08, dilengkapi cermin en di T-12 |
| Format angka/durasi | `App\Support\Format` | desimal koma untuk id ("3,2 dtk"), terpasang di durasi dasbor + rata-rata pesan Riwayat Chat; panel Uji Coba memformat di sisi klien (T-07) |

## Infrastruktur

- `config/app.php` + `.env.example`: locale & fallback default **`id`**.
- Middleware `SetLocale` (grup web + api): prioritas `users.locale` →
  session `locale` → `Accept-Language` (khusus `api/*`) → default.
- Migrasi `users.locale` (default `id`); pemilih bahasa di Pengaturan
  mengisi daftar dari folder `lang/` yang tersedia — menambah bahasa =
  menambah folder, tanpa ubah kode.
- `lang/en`: cakupan penuh untuk domain di atas (publik, auth, email
  utama). Kunci di luar cakupan itu pada berkas lain berisi teks
  Indonesia bertanda `// TODO: translate` bila belum diterjemahkan —
  fallback tetap `id`, tidak ada kunci mentah yang tampil.

## Pengecualian yang disetujui (belum bermigrasi)

- Label statis Blade pada sebagian halaman member/admin yang sudah
  berbahasa Indonesia (tidak hardcode Inggris): aman secara UX pada
  locale default; migrasi kuncinya adalah pekerjaan lanjutan bertahap.
- Nama merek & istilah glosarium (Cekat, Midtrans, WhatsApp, Knowledge
  Base, API key) tetap apa adanya di semua bahasa.
- Halaman admin: dianjurkan, tidak wajib (sesuai rencana).
- Template email kampanye & email admin (subjek sudah Bahasa Indonesia).
- `plans.php` dimiliki branch T-05 (`fix/t-05-limit-messages`) dan
  dikecualikan dari uji paritas di branch ini; paritasnya dijaga di sana.

## Cara menambah bahasa baru

1. Salin folder `lang/id` menjadi `lang/<kode>` dan terjemahkan nilainya
   (pertahankan semua kunci — `LocaleTest` akan gagal bila ada selisih).
2. Bahasa otomatis muncul di pemilih Pengaturan dan dapat dipilih
   pengguna; API menghormatinya lewat `Accept-Language`.
