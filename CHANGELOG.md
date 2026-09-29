# Changelog

Semua perubahan penting pada proyek ini dicatat di file ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Unreleased] — Branch `feature/business-workflow-ui-ux-robustness` (2026-09-27)

### Lead capture: data tak tersimpan + tak ada email notifikasi + JSON bocor ke chat (2026-09-29)

**Konteks (laporan user):** balasan chatbot di widget bmp.net.id menyisakan JSON mentah `{"action":"save_lead",...}` setelah teks ramah, data lead tidak muncul di Lead Collection, dan tidak ada email notifikasi ke `woodworkingytproject@gmail.com` (owner widget `widget-3-CPbqbm3C`, plan 3 / leads YES). Tiga bug berlapis ditemukan + diperbaiki:

1. **Event listener cache stale (penyebab email & persist mati total).** `bootstrap/cache/events.php` di produksi berisi discovery **lama sejak 16 Jan 2026** (111 byte) → Laravel memakai cache itu alih-alih discovery live → **SEMUA listener `app/Listeners` tak terdaftar** (`SendLeadNotification`, `SendWelcomeEmail`, `NotifyAdminNewSignup`, `NotifyAdminSettingsChanged`); listener yang di-subscribe manual (`LogSystemEvent`) tetap jalan sehingga log `chat.lead_captured` terlihat "normal" padahal tak ada yang memprosesnya. Fix: `php artisan event:cache` di produksi + **`event:cache` ditambahkan ke deploy script** (bareng `config:cache`/`view:cache`) supaya tak pernah stale lagi.
2. **Kolom terenkripsi terlalu kecil (persist dibatalkan QueryException 1406).** `ChatSession` meng-encrypt `visitor_name`/`visitor_email`/`visitor_phone` via custom setter — ciphertext base64 bahkan utk no. HP pendek ±104 char, sedangkan `visitor_phone` = `varchar(50)` (name/email `varchar(255)` juga borderline) → `Data too long for column 'visitor_phone'` membatalkan `UPDATE` di `SendLeadNotification` (bukti: `Chat API Error ... 1406` di log saat chat live + balasan "gangguan teknis"; reproduksi manual terekam pula). Test lolos karena SQLite tak menerapkan panjang kolom. Fix: migrasi `2026_09_29_130000_widen_encrypted_visitor_columns_in_chat_sessions` → ketiga kolom ke `TEXT`.
3. **Lookup sesi pakai kolom salah.** `SendLeadNotification` mencari `where('session_id', $sessionId)` padahal orchestrator mengisi **`chat_sessions.visitor_uuid`** (kolom `session_id` legacy, nullable, tak pernah diisi) → `is_lead`/`visitor_*` tak pernah terisi walau listener hidup (test lama lolos karena fixture menulis `session_id` sendiri — fixture disinkronkan ke `visitor_uuid`).
4. **JSON action bocor ke chat pengunjung.** `ChatOrchestrator` hanya me-strip blok action JSON **bila webhook terkirim** (`$webhookResult` non-null) — widget tanpa `webhook_url` → `dispatchIfAction` null → JSON mentah ikut tampil. Fix: strip berdasarkan keberadaan `$action` (dengan/tanpa webhook); reply strict-JSON tanpa webhook → pesan ramah `Data berhasil diproses.`
5. **Fallback ekstraksi lead deterministik.** Model free tidak selalu mengeluarkan blok `save_lead` yang diminta prompt (diamati langsung di live: chat tanpa JSON → event tak pernah fire). `LeadCaptureService::extractLeadFromMessage()` kini mengekstrak nama/email/HP dari **pesan pengunjung** (regex; minimal email ATAU HP, nama berhenti di keyword kontak) saat AI tak menghasilkan action → lead tidak pernah gugur diam-diam. `LeadController` (index+stats+export) juga menampilkan lead bila **salah satu** kontak ada (email/HP-only tetap tampil).

**Diverifikasi (live di bmp.net.id, widget `widget-3-CPbqbm3C`):** chat "Nama saya Dewi Anggraini, email ..., telepon ..." → balasan ramah **tanpa JSON**, `chat.lead_captured` ter-log, baris lead tersimpan `is_lead=1` + `visitor_name/email/phone` terisi (terenkripsi, kolom TEXT), **nol** `Failed to send lead notification` → `NewLead` terkirim ke email owner; log error 1406 sebelum fix terekam sebagai bukti diagnosis. Suite **195 passed / 723 assertions** (+7 test: unit ekstraksi kontak, E2E strip tanpa webhook, E2E strict-JSON, E2E fallback dari pesan). Commit: `ccb57a6` (events cache + visitor_uuid + strip + LeadController), `8f690fd` (fallback ekstraksi), `4d696e9` (migrasi TEXT).

### Verifikasi email via kode OTP + modal wajib (ganti link signed) (2026-09-29)

**Perbaikan kritis (laporan user: "email tidak terkirim sama sekali, tidak ada tombol resend"):** `x-data` modal dirender sebagai `verifyOtpModal({ idle })` — shorthand `{ idle }` di JS adalah **reference ke variabel `idle`** (bukan literal) → `ReferenceError` mematikan komponen Alpine → tombol resend tampil **kosong/tanpa teks** dan auto-send `fetch` tidak pernah jalan (bukti: `Cache::get('email_otp:14')` = null, nol error log). Perbaikan: (1) quote param → `verifyOtpModal('idle')` (+regresi test `assertSee`); (2) fetch gagal (!ok/Catch) → state kembali `idle` (tombol bisa diklik ulang, bukan fake "terkirim"); (3) **hardening server-side**: `LoginController` & `GoogleController` (existing user) kirim OTP saat login bila unverified && `!hasLiveCode` → kode selalu terkirim walau JS mati (guard cegah spam <60 dtk). E2E ulang: console nol error JS, `POST /email/verification-notification [302]`, `email_otp:{id}` terisi (16 dtk), tombol "Kode terkirim — kirim ulang dalam 60s" tampil, kode diterima → modal hilang, nol `Failed to send verification code`.

**Konteks:** permintaan user — (1) pendaftar baru wajib diverifikasi dengan **kode verifikasi yang diinput** (Email OTP), bukan klik link; (2) pendaftar Google juga wajib (email Google dianggap pre-verified → sebelumnya tidak pernah ada email terkirim, inilah penyebab "email verifikasi tidak terkirim" pada akun `jonofwb1@gmail.com` — didaftarkan via Google OAuth, otomatis `verified` tanpa email); (3) seluruh user lama non-admin di-set **belum verifikasi** + **modal wajib tak bisa ditutup** di dashboard sampai kode benar. Keputusan user: Email OTP, Google tetap wajib kode, admin dikecualikan dari un-verify (hindari lockout panel admin), modal blocking.

**Diubah:**
- `EmailOtpService` (`app/Services/Auth`): kode 6 digit disimpan **hashed** (sha256) di cache, TTL 5 mnt, jeda resend 60 dtk (`hasLiveCode`), maks 5 percobaan salah lalu kode di-invalidate (`verify` mem-padded input, `hash_equals`).
- Mailable `EmailOtp` + `emails/email-otp.blade.php` (kode besar, masa berlaku, abaikan bila tak diminta); `VerifyEmail` (link signed) + view `emails/verify-email` & `auth/verify-email` **dihapus**.
- Route: hapus `GET /email/verify/{id}/{hash}` (signed link); `POST /email/verify` (`verification.verify`, auth+`throttle:10,1`) → cocokkan kode → `markEmailAsVerified()` + `event(Verified)` → dashboard; `POST /email/verification-notification` (`verification.resend`, `throttle:6,1`) → regenerate + kirim OTP (`otp_success` flash); `GET /email/verify` (notice) → redirect dashboard.
- `User::sendEmailVerificationNotification()` kini generate + kirim `EmailOtp`; `RegisterController` redirect ke **dashboard** (bukan notice) dengan flash info kode; `GoogleController`: user baru **tanpa** `email_verified_at` + kirim OTP (hapus dispatch `Verified` otomatis — kini fired saat kode benar).
- Gerbang: hapus middleware `verified` dari grup dashboard/user & WhatsApp (biar modal yang tampil; grup admin 2× **tetap** `verified`); modal blocking `layouts/partials/verify-modal.blade.php` di-include di `layouts.dashboard` (28 view): overlay `z-[100]` tanpa tombol tutup/klik-outside/ESC, input `one-time-code` 6 digit, auto-kirim kode pertama via fetch saat `hasLiveCode` false, countdown resend 60 dtk, inline error/`otp_success`, jalan keluar via logout.
- Migrasi `2026_09_29_120000_unverify_existing_non_admin_users`: `email_verified_at = NULL` untuk semua `role != 'admin'` (down: set kembali now()); admin `admin@cekat.biz.id` tetap verified.
- `NotifyAdminNewSignup`: skip bila akun >24 jam (user lama yang re-verify bukan "pendaftar baru" — mencegah banjir notice); `SendWelcomeEmail` tetap semua (mereka belum pernah dapat welcome).
- Test: `EmailVerificationTest` di-rewrite penuh (8 — register→dashboard+OTP, kode benar→verified+welcome+admin, kode salah, resend, brute5× invalidate, modal tampil/tersembunyi, notice→dashboard); `PlanLimitEnforcementTest` ekspektasi register → `route('dashboard')`.

**Diverifikasi:**
- E2E production: register → landing `/dashboard` dengan **modal blocking** (`dialog "Verifikasi Email Anda"`, input kode, auto-send kode pertama, resend ber-countdown, tombol logout) → kode diterima → modal hilang, `email_verified_at` terisi, `EmailOtp`/`WelcomeUser`/`AdminNewSignup` terkirim (nol log error mail); migrasi: admin 1 verified, non-admin verified **0**, unverified 9; data user test dibersihkan.
- Suite **188 passed / 689 assertions** (baseline 181/659).

### Sistem email Brevo SMTP: verifikasi email, ganti email, alert keamanan & notifikasi lead/admin (2026-09-29)

**Konteks:** pengiriman email tidak pernah berfungsi — 5 mailable (`WelcomeUser`, `PaymentSuccess`, `PlanExpiringReminder`, `PlanExpired`, `AccountSuspended`) meng-`implements ShouldQueue` sementara production `QUEUE_CONNECTION=database` **tanpa queue worker** (`Mailer::sendMailable` otomatis me-queue → tak pernah terkirim); tidak ada verifikasi email pendaftar; tidak ada notifikasi saat password/ganti email/setting admin berubah; lead capture hanya lewat webhook (kini `LeadCaptured` tidak pernah sampai ke owner). Kebutuhan user: (1) verifikasi email tiap pendaftar baru, (2) notifikasi perubahan user (alert password, ganti email + link verifikasi, notifikasi setting admin), (3) notifikasi lead baru, (4) SMTP Brevo (bukan API) dengan login relay `84e87c001@smtp-brevo.com`, sender `lora@cekat.biz.id`, penerima admin via env `ADMIN_NOTIFY_EMAIL` (`info@mcimedia.net`).

**Diubah:**
- Hapus `implements ShouldQueue` (sinkron) dari 5 mailable; semua kirim email dibungkus try/catch (mail gagal tak boleh merusak alur transaksi); `config/mail.php` + `.env.example` + `docs/deployment-guide.md` (§6 & §11): blok Brevo SMTP + `ADMIN_NOTIFY_EMAIL`, catatan SMTP key (`xsmtpsib-`) ≠ API key (`xkeysib-`).
- `User` `implements MustVerifyEmail` + override `sendEmailVerificationNotification()` (mailable `App\Mail\VerifyEmail`, link signed 60 mnt); `email_verified_at` & `pending_email` masuk `$fillable`; `RegisterController` redirect ke `verification.notice`; `Verified` event → listener auto-discovery (`SendWelcomeEmail` kirim `WelcomeUser`, `NotifyAdminNewSignup` kabari admin); Google signup baru dispatch `Verified` (email pre-verified); migrasi backfill `email_verified_at` untuk user lama (up: null→now; down: no-op) agar tak terkunci.
- Route verifikasi di `routes/auth.php`: `verification.notice` (auth), `verification.verify` (auth+signed+`throttle:6,1`), `verification.resend` (auth+throttle); middleware `verified` ditambahkan ke 4 grup route (dashboard/user, 2× admin, `plan.feature:whatsapp`).
- Fitur ganti email: migrasi `pending_email` (`2026_09_29_110000`); `PUT /settings/email` (`UpdateEmailRequest`: valid, beda dari aktif, unique) → simpan pending + `EmailChangeConfirm` ke alamat baru + `EmailChangeRequestAlert` ke alamat lama; `GET /settings/email/confirm` (signed+throttle) → hash `sha1(pending)` cocok + cek unique ulang → aktifkan email, `email_verified_at=now()`, `EmailChangeDone` ke alamat lama; kartu "Ganti Email" (+badge pending) di `user/settings`.
- Alert password: mailable `PasswordChanged` (user, IP, via) dikirim dari `PUT /settings/password` dan dari `PasswordResetController::reset` ("Tautan Reset Password").
- Notifikasi lead baru: `LeadCaptured` kini membawa `sessionId` + nilai lead (PII hanya untuk email, tidak pernah di-log) dan **di-dispatch tanpa syarat webhook**, setelah `persistConversation` (baris sesi sudah ada); listener `SendLeadNotification` menandai `chat_sessions.is_lead` + `visitor_name/email/phone` (ter-encrypt via mutator) lalu kirim `NewLead` ke pemilik widget — **1× per sesi**, di-gate fitur `leads` (`can_export_leads`; data tetap tersimpan untuk plan gratis).
- Notifikasi setting admin: event `AdminSettingsChanged` + `keys` (field disimpan), dispatch juga dari `WhatsAppSettings::saveSettings/toggleModule`; listener `NotifyAdminSettingsChanged` → mailable `AdminSettingsChangedNotice` ke `ADMIN_NOTIFY_EMAIL` (fallback admin pertama), tanpa rahasia.
- Test baru (19): `EmailVerificationTest` (4), `EmailChangeTest` (5), `PasswordChangeAlertTest` (3 — settings + wrong current + reset via token), `LeadNotificationTest` (4 — persist+notify, plan gratis, dedupe per sesi, tanpa webhook), `AdminSettingsNotificationTest` (3 — konfigurasi, fallback admin, tanpa penerima).

**Diperbaiki (temuan saat test):**
- `SettingsController::confirmEmail` membaca `$request->route('id')`/`route('hash')` padahal signed link membawa param sebagai **query string** → selalu "link tidak valid"; kini `$request->query('id')`/`query('hash')`.
- `PasswordResetController::reset` closure memanggil `$this->ip()` (tidak ada di controller) → alert reset tak terkirim (ter-log error); kini `use ($request)` → `$request->ip()`.

**Diverifikasi:**
- SMTP lokal & production port 587 terbuka; uji `Mail::raw` via Brevo relay → `SENT_OK` (lokal & production).
- E2E production: register → halaman `verification.notice` + `VerifyEmail` terkirim → link signed dikunjungi → `email_verified_at` terisi + `WelcomeUser` & `AdminNewSignup` terkirim (nol log error mail) → landing dashboard; `config:show mail` prod: `smtp`/Brevo/`ADMIN_NOTIFY_EMAIL` aktif (blok Gmail placeholder diganti, backup `.env.bak-20260929-email`); data user test dibersihkan.
- Suite **181 passed / 659 assertions** (baseline 162/596).

### Resume percakapan: natural tanpa sebutan "AI" + validasi output job summary (2026-09-29)

**Konteks:** resume (summary) di dashboard menyebut "AI" karena label transkrip `AI:` ikut terkirim ke prompt; user meminta resume lebih informatif & natural tanpa menyebut AI (ganti "layanan customer service"). Ditemukan juga penyebab "resume tidak muncul di production": job summary lama di-dispatch ke queue sedangkan production `QUEUE_CONNECTION=database` **tanpa queue worker** (worker yang ada milik aplikasi lain) → ChatInbox "Generate" & closing lama tak pernah menghasilkan ringkasan; model free kadang mengembalikan sampah (mis. `User Safety: safe`) yang tersimpan apa adanya. Lingkup fitur (penawaran penutupan + resume): berlaku untuk **semua widget semua pelanggan** — timing global di `widget.js` (90 dtk idle → tawaran, +60 dtk → tutup+resume, +5 dtk → minimize), bisa dioverride per-site via `window.CSAIConfig` sebelum memuat widget.

**Diubah:**
- `GenerateChatSummary`: label transkrip `AI` → `Layanan Customer Service`; system prompt baru — catatan profesional untuk tim layanan customer service (topik pembicaraan, kebutuhan customer, hasil + tindak lanjut; maksimal 3 kalimat, prosa mengalir tanpa label), larangan eksplisit menyebut "AI"/"chatbot"/"model" (puji pihak pelayan dengan "layanan customer service"/"tim kami"); `max_tokens` 200→250.
- Validasi output `isUsableSummary()`: buang code fence markdown; tolak <40 karakter, echo instruksi (EN/ID: "We need to produce…", "Buatkan resume…", "User Safety"-style junk), **meta-leak reasoning** ("Okay, let me tackle…", "The user wants me…", "Berikut…"), dan hasil tanpa penanda Bahasa Indonesia; retry 1× dengan guard anti-echo eksplisit + `temperature` 0.7 (attempt 1: 0.3), `max_tokens` 400 (reasoning models memotong tengah kalimat pada 250); gagal total → `summary` null (closing generik di widget / resume kosong di dashboard — bukan sampah tersimpan).

**Diverifikasi:**
- Regenerasi resume pada sesi percakapan nyata → natural, informatif, tanpa "AI": *"Customer menanyakan harga paket Business, dan layanan customer service menjelaskan rincian paket beserta fitur-fiturnya serta menawarkan bantuan lebih lanjut untuk melakukan pemesanan…"*.
- Backfill awal di production memunculkan sampah prompt-echo & meta-leak (model kecil menyalin instruksi) → diperkuat `isUsableSummary()` + `temperature 0.3`; 3 test regression baru (prompt-echo → retry, hanya-sampah → summary null, unit validasi echo/meta); sesi terdampak di-regenerate setelah deploy.
- Suite **162 passed / 596 assertions** (baseline 159/583).

### Widget: offer idle mengulang tanpa henti + auto-close sesi dengan ringkasan & auto-minimize (2026-09-29)

**Konteks:** tawaran "Apakah Anda masih membutuhkan bantuan?" setelah idle terus-muncul-muncul dan widget tak pernah menutup diri; beberapa kasus menampilkan "Percakapan ditutup." berulang-ulang. Akar: (1) duplikat `function toggleChat` — versi sederhana menimpa versi lengkap ber-timer; (2) `addMessage()` selalu me-reset siklus idle → `closeOfferSent` ter-flip balik; (3) auto-scroll memicu listener `scroll` → membatalkan timer offer/minimize; (4) tidak ada endpoint menutup sesi server; (5) offer bisa fired saat balasan AI masih loading → close memakai sessionId unsigned (belum diadopsi dari respons `/api/chat`) → 403 → closing generik; (6) pesan closing sendiri me-reset idle cycle → offer → close → offer (loop; closing berikutnya 403 karena sessionId sudah dirotasi). Keputusan user: **auto-minimize + grace 5 detik** (dibatalkan bila visitor kembali aktif) dan tombol manual **"Tutup percakapan" juga menutup sesi server + summary**.

**Ditambahkan:**
- Endpoint `POST /api/widget/session/close` (grup widget, CSRF-except `/api/widget/*`, `throttle:chat`): verifikasi widgetId + sessionId signed (HMAC) + fingerprint IP/UA → tutup sesi (`status='ended'`, `ended_at`) → `dispatchSync(GenerateChatSummary)` bila ada pesan → kembalikan `{success, summary}`; idempoten (sesi sudah ended → ringkasan lama), sesi tak ditemukan → `{noop}`; tanpa queue worker (prod tanpa worker) summary tetap sinkron.
- Latch `conversationClosed` di widget: satu closing per percakapan — `showCloseOffer`/`resetInactivityTimer` menolak siklus baru setelah closing; diangkat hanya oleh pesan visitor baru (`sendMessage`) atau `CSAI_endChat`. Guard `isLoading` mencegah offer/close saat balasan AI masih in-flight (sessionId belum signed). `closeConversation`: latch di-set sebelum await → closing message & aktivitas apa pun tak bisa memulai siklus kedua; pesan penutup (summary LLM atau generik) → rotasi `sessionId` → auto-minimize setelah `closeGraceTimeout` (5 detik; dibatalkan aktivitas visitor). `CSAI_endChat` kini juga `closeSessionOnServer()` (fire-and-forget) + latch; `CSAI_continueChat` menghapus bubble offer.
- Test: `WidgetSessionCloseTest` (8 — happy path + summary, idempoten, noop, sesi tanpa pesan, unsigned 403, fingerprint mismatch 403, widget salah 404, ctor `GenerateChatSummary` menerima int); `tests/widget/inactivityClose.regression.mjs` diperluas (16 check — latch, guard isLoading, urutan latch-vs-await, rotasi sessionId, endpoint, dsb).

**Diubah:**
- `GenerateChatSummary::__construct` menerima `ChatSession|int|string` (sebelumnya hanya model → `ChatInbox::closeSession` yang mengirim int kena TypeError); `ChatInbox` & `ChatHistoryController` meng-`dispatchSync` job summary (tanpa worker → summary selalu terhasil, flash kini "Summary berhasil di-generate.").
- Widget state machine: `resetInactivityTimer` me-clear timer minimize + menghapus bubble offer basi; auto-scroll ditandai `autoScrollUntil` (listener `scroll` mengabaikannya); duplikat `toggleChat` dihapus (satu deklarasi menyatukan greeting/badge/timer); satu helper `scrollToBottom()` sebagai satu-satunya tulis `scrollTop`.

**Diverifikasi:**
- QA browser (timer QA 4s/3s/2s): offer tampil tepat 1× → close **200 + summary** → minimize tepat 1× → tidak ada loop; guard `isLoading` terbukti melewatkan offer saat reply in-flight; tombol manual "Tutup percakapan" menutup sesi server (200, idempoten). Ringkasan LLM pernah `empty_content` (provider) → degradasi graceful ke closing generik.
- Suite **159 passed / 583 assertions** (baseline 151/551); regression widget 16/16 + parseMarkdown 9/9 hijau; `npm run build:widget` (min.js bebas instrumen QA).

### Lanjutan remediasi plan tiers: 5 temuan follow-up (2026-09-29)

**Konteks:** audit internal menemukan 5 hal: (1) hapus chat history mengandalkan `redirect()->back()` → 404 bila halaman asal sudah tertutup; (2) batas device WhatsApp masih hard-code pencocokan slug (`getMaxDevicesForUser` 1/3/10) di luar service; (3) token `active_channels` dipakai untuk create channel — user dengan widget draft tertolak membuat chatbot padahal slot masih ada; (4) registrasi/Google masih menulis legacy `plan_tier`/`monthly_message_quota` (kolom sudah drop) dan mengandalkan fallback implisit; (5) regression test belum memindai `app/Services`/`app/Console`, token legacy, maupun branching slug.

**Ditambahkan:**
- Kolom `plans.max_whatsapp_devices` (migrasi `2026_09_29_000001_add_max_whatsapp_devices_to_plans_table`, guard `hasColumn`, backfill slug lama `pro|professional`→3 dan `business|enterprise`→10, default 1) + key service `whatsapp_devices` (used = jumlah device milik user) + input "Max WhatsApp Devices *" di Plan Manager (validasi `integer|min:0`, ikut save/reset/edit) + `DefaultPlansSeeder`/`UpdateExistingPlansSeeder` menyetel nilai per tier (1/3/10).
- `PlanLimitService::defaultPlan()` (publik): plan berbayar-nol/aktif termurah dari DB. `RegisterController` & `GoogleController` kini mengikat `plan_id` eksplisit via `defaultPlan()` (bila tidak ada → null, service memakai default schema) dan tidak lagi menulis `plan_tier`/`monthly_message_quota`.
- Test baru: `ChatHistoryDeleteTest` (3 — hapus dari show/index/alias `leads.destroy` selalu redirect `chats.index` + baris terhapus); unit `total vs active counting`, `whatsapp_devices` limit+used, `defaultPlan()`; feature: widget draft tetap memblokir create melebihi `max_widgets`, gate device WhatsApp (module on + token + plan `max_whatsapp_devices=1` + 1 device → tertolak, jumlah tak berubah), registrasi mengikat `plan_id` plan gratis & null bila tak ada plan.

**Diubah:**
- `ChatHistoryController::destroy` → selalu `redirect()->route('chats.index')` dengan flash sukses — menghapus 404 pasca-hapus (hapus dari leads pun mendarat di chat history).
- Semantik channel dipisah: `total_channels` (create/store; used = seluruh widget — perilaku tidak berubah) vs `active_channels` (aktivasi; used = status aktif): key baru `total_channels→max_widgets` di `PlanLimitService`, ditukar di `ChannelController` create/store, `CreateChatbot`, display `channels/index`, `welcome`, `create-chatbot`, `billing` (aktivasi/`guardActiveLimit` + `channels/index` copy kolom tetap `active_channels`).
- `WhatsAppController::create` memakai `check($user, 'whatsapp_devices', ['used' => ...])`; `getMaxDevicesForUser` (pencocokan slug hard-code) dihapus — pesan "Anda sudah mencapai batas maksimum {limit} device." dipertahankan.
- `PlanLimitRegressionTest` diperluas: dirs += `app/Services`, `app/Console`; token += `plan_tier`, `monthly_message_quota`; allowlist += `PlanLimitService`, `ModelResolver` (label log `ai_tier`); + tes anti slug-branching `/\$plan->slug…'(starter|free|pro|professional|business|enterprise)'/` (hanya WhatsAppController/Register/Google yang dulu match — semuanya sudah ikut diperbaiki).

**Diverifikasi:**
- Suite **151 passed / 551 assertions** (baseline 140/503).
- Deviasi tersisa di luar cakupan: `PurgeExpiredChats` tetap membaca `plans.chat_history_days` DB mentah (NULL → fallback 7 hari; memaksa ke service akan mengubah semantik menjadi hapus-semua) — `chat_history_days` sengaja tidak dimasukkan ke daftar token terlarang.

### Single source of truth pembatasan plan tiers — `PlanLimitService` (2026-09-28)

**Konteks:** aturan limit & fitur plan tersebar hard-code di ~15 titik (controller/middleware/Livewire/view/service), masing-masing membaca kolom `plans` langsung dengan fallback berbeda; KB (jumlah dokumen/ukuran file/FAQ) tidak pernah dienforce; beberapa limit tidak punya UI admin. Kebijakan (terkunci): satu service `App\Services\Billing\PlanLimitService` sebagai satu-satunya pintu baca/cek — data dari tabel `plans` tanpa migrasi baru; fallback user tanpa plan = plan gratis/starter aktif dari DB (bila tak ada → default schema; tidak pernah jatuh ke plan berbayar); ubah limit hanya via Plan Manager admin.

**Ditambahkan:**
- `PlanLimitService`: `planFor()`, `limit()`, `feature()`, `featureValue()`, `aiTier()`, `allowsModel()`, `check()` (shape `allowed/code/message/used/limit/remaining/plan`; code `allowed|limit_exceeded|feature_locked|paid_required`; throw `InvalidArgumentException` hanya untuk key tak dikenal), `usage()`. Pemetaan key: `active_channels→max_widgets`, `monthly_messages→max_messages_per_month`, `knowledge_documents→max_documents`, `file_size_mb→max_file_size_mb`, `faqs→max_faqs`, `chat_history_days→chat_history_days`; fitur `leads`/`whatsapp` dari kolom boolean (bypass admin, read-only DB dijaga test), `analytics` terbuka hanya `true`/`'advanced'` (starter `'basic'` tetap terkunci), sisanya JSON `features`; `ai_summarize` = plan berbayar.
- Enforcement baru KB di `KnowledgeBaseEditor` & `AgentKnowledgeEditor`: gate jumlah FAQ/dokumen (bypass admin) + aturan upload `max:min(system max_upload_size_mb, plan file_size_mb)` (admin: system saja) — sebelumnya unggah hard-code 10 MB tanpa cek jumlah.
- Plan Manager admin: input "Chat History (days)" + checklist fitur `leads`/`whatsapp` (dipetakan ke kolom `can_export_leads`/`can_use_whatsapp`, dikeluarkan dari JSON `features`), validasi `chat_history_days` (integer ≥0) & `features` (array).
- Test baru: `PlanLimitServiceTest` (16 unit), `PlanLimitEnforcementTest` (9 — create/store/activate channel melebihi limit, FAQ & unggah dokumen (jumlah/ukuran) tertolak, angka limit tampil di dashboard/sidebar/channels/billing, edit Plan Manager langsung dipakai service), `PlanLimitRegressionTest` (2 — static scan 8 token kolom terlarang di `app/Http/Controllers`, `app/Http/Middleware`, `app/Livewire` (kecuali `PlanManager`) + `resources/views`).

**Diubah:**
- Enforcement backend kini lewat service: `ChannelController` (create/store/guardActiveLimit), `CreateChatbot`, `QuotaService` (payload 429 legacy `quota.used/limit/reset_date` dipertahankan), `ChatOrchestrator`, `PlanFeatureGate`, `User::canUseLeads/canUseWhatsApp`, `DashboardController`, `ModelResolver`, `WhatsAppManager::getModelForWidget`, `TopicAnalyzer(Service)`, `Plan::allowsModel/hasFeature`.
- Display UI lewat service: `sidebar`, `channels/index`, `livewire/create-chatbot`, `user/billing`, `user/settings` (angka tidak lagi baca `users.monthly_message_quota` — kolom sudah drop), tabs `general`/`lead`/`analytics`, `agents/partials/ai-tier-card`, `admin/user-manager`, `welcome` (teks output identik; contract `LandingPricingTest` lulus).
- Perubahan perilaku disengaja: user tanpa plan kini memakai limit plan gratis/starter (bukan unlimited) dan model AI tier `basic` — `ModelResolverTest` disesuaikan (2 test baru untuk plan-less).

**Diverifikasi:**
- Suite **140 passed / 503 assertions** (baseline 112/383).
- Deviasi di luar cakupan saat itu (kini diperbaiki pada bagian "Lanjutan remediasi plan tiers" di atas): `WhatsAppController::getMaxDevicesForUser` mencocokkan slug plan hard-code; `PurgeExpiredChats` membaca `plans.chat_history_days` via DB langsung (console command harian — masih deviasi aktif).

### Remediasi 3 risiko audit: PII di localStorage widget, riwayat ke provider LLM, tanpa kontrol retensi (2026-09-28)

**Konteks:** audit keamanan menyebut (1) `chatHistory` disimpan plaintext di `localStorage` browser visitor, (2) riwayat penuh mengembang ke provider LLM, (3) `plans.chat_history_days` (7/30/90) diiklankan billing tapi tidak dieksekusi & tidak ada kontrol hapus data. Keputusan user: redaksi PII sebelum tulis localStorage (bukan server-backed), enkripsi at-rest **hanya data baru** (tanpa migrasi re-enkripsi massal; pembaca tahan-banting fallback plaintext), enforce retensi via `chat:purge` harian, DSR ganda (tenant + visitor).

**Ditambahkan:**
- `App\Support\CipherText` + accessor/mutator di `ChatMessage.content` & `ChatSession.visitor_name/email/phone/summary` — tulis baru selalu terenkripsi (`Crypt::encryptString`, `APP_KEY`), baca try/catch → baris plaintext lama tetap terbaca (tua menua via purge, bukan re-encode massal). *Catatan: `APP_KEY` kini protektif — backup & rotasi wajib.*
- `chat:purge` (`PurgeExpiredChats`, `--dry-run`): hapus sesi pesan > retensi plan (join widget→user→plan `chat_history_days`; fallback 7 hari bila owner/plan/tanpa jendela retensi); index migrasi `chat_sessions.created_at`; `Schedule::command('chat:purge')->dailyAt('03:30')` (cron `schedule:run` sudah ada di prod).
- DSR tenant: `DELETE /chats/{id}` + alias `DELETE /leads/{id}` (`ChatHistoryController@destroy`, scoped `whereIn widget user` + `ChatSessionPolicy::delete`) + tombol Delete di `chats/index`, `chats/show`, `leads/index`.
- DSR visitor: `DELETE /api/widget/session` (grup `WidgetApiCors`, `throttle:chat`, sudah kena CSRF except `/api/widget/*`) — verifikasi sessionId signed (HMAC) + cocokkan fingerprint IP (`HttpClientIp`)/user agent seperti lanjutan sesi, idempoten; preflight CORS kini meng-echo `DELETE`.
- Widget: `redactPII()` di `saveHistory()` (email, telepon 08/62, NIK 16 digit, keyword invoice/faktur/pesanan/order — hanya salinan at-rest; memori & wire tetap mentah), tombol header "Hapus percakapan" (`CSAI_forgetChat`: konfirmasi → DELETE server → `clearHistory` → greeting baru).

**Diubah:**
- `ChatRequest`: `history` dibatasi `array|max:50`, item `role in:user,assistant`, `content|max:10000` — 1 request tak lagi bisa mengirim transkrip tak terbatas.
- `ChatOrchestrator`: konstanta `HISTORY_WINDOW = 10` (slice riwayat yang dikirim ke LLM); log respons OpenRouter → metadata saja (model+usage) di produksi, body penuh hanya saat `APP_DEBUG` (PII tak lagi masuk `laravel.log`).
- `GenerateChatSummary`: potong input ke 20 pesan terakhir ±6.000 karakter (sebelumnya seluruh transkrip dikirim ke provider).
- `widget.js`: wire history `slice(-12)` + cap per-item; (widget.min.js + map dibuild ulang).

**Diverifikasi:**
- Suite **112 passed / 383 assertions** — 4 test baru: `ChatHistoryCapTest`, `ChatCipherTest` (enkripsi at-rest + fallback plaintext + pluck aksesor), `ChatForgetTest` (visitor/tenant/guest), `ChatRetentionTest` (retensi 30h, dry-run, fallback 7 hari).
- QA browser lokal: redaksi localStorage (email/telepon/NIK/invoice → `[... dihapus]`), wire history ≤12 item (raw sesuai keputusan), `DELETE /api/widget/session` 200 + sesi terhapus dari DB (`visitor_uuid` lookup 0), tombol + konfirmasi berfungsi, chat 200.

### Harga landing dari tabel `plans` sebagai sumber kebenaran (2026-09-28)

**Konteks:** billing (`PaymentController`) menagih `plans.price` langsung (Starter Rp0, Pro **Rp99.000**, Business **Rp299.000**), sementara landing & FAQ bot mengiklankan Rp299k/Rp799k — divergensi 3x. Kebijakan (dipilih user): pakai nilai DB.

**Diubah:**
- `routes/web.php`: `GET /` meneruskan `Plan::where('is_active', true)->orderBy('sort_order')` ke view.
- `welcome.blade.php` section `#harga`: 3 kartu hardcoded → loop `@forelse ($plans)` — nama & harga dari DB (`Rp` + `number_format`), bullet dibangun dari field plan (max_widgets/pesan/dokumen/FAQ + flags `features`), tagline per-slug (fallback `description`), badge "Paling Populer" saat `slug === 'pro'`, CTA: harga 0 → "Mulai Gratis", plan terakhir → "Hubungi Penjualan", sisanya "Berlangganan Sekarang". Simulasi RAG: "Paket Pemula" → "Paket Starter".
- `LandingPageChatbotSeeder`: FAQ pricing kini dibangun `buildPricingAnswer()` dari tabel plans (reseed ikut harga terbaru); widget settings di-merge (`firstOrNew` + `array_merge`) agar reseed tak menimpa kustomisasi admin (greeting/warna/model/avatar).

**Ditambahkan:**
- Contract test `LandingPricingTest` (2 kasus: kartu & harga aktif dirender dari DB + harga ikut berubah saat `plans.price` diubah; plan non-aktif tidak tampil) — suite **92 passed / 324 assertions**.
- Skrip one-off `update-faq-pricing.php` (Reflection ke `buildPricingAnswer()`) untuk memperbarui FAQ pricing di lingkungan yang ada tanpa reseed penuh (lokal sudah dijalankan; prod dijalankan saat deploy).

### Widget tidak tampil di homepage + XSS stored via settings widget (2026-09-28)

**Diperbaiki (widget landing hilang — `#csai-toggle` tidak ada, konsol: "not authorized for this domain"):**
- Akar masalah: migrasi lockdown menulis `allowed_domains` sebagai CSV (`cekat.biz.id, www.cekat.biz.id`) sedangkan `isDomainAllowed()` di `public/widget/widget.js` membandingkan seluruh string sebagai SATU domain → widget diblokir di semua origin, termasuk domain yang benar. `isDomainAllowed()` kini memecah CSV, mencocokkan per-entri (exact/subdomain/www), selalu mengizinkan host asal `<script>` (cermin server `DomainAccessService::ownHost()`), dan mempertahankan bypass localhost.

**Diperbaiki (XSS stored — settings widget kendali customer mengalir ke `innerHTML`):**
- `createWidget()` menyisipkan `title` (= `widgets.name`, bisa diedit customer), `subtitle`, `placeholder` (atribut), dan `avatarUrl` (atribut `<img>`) tanpa escape → payload HTML dieksekusi di setiap halaman yang memuat widget, termasuk preview dashboard same-origin (pencurian sesi). Ditambahkan `escapeHtml()` untuk keempatnya + `isRenderableUrl()` (hanya `http(s)://` atau path root-relatif, tanpa spasi/tanda kutip/brackets) untuk avatar; avatar tidak valid jatuh ke ikon default.
- `parseMarkdown()`: guard pemulihan `__CODE_BLOCK_n__` — placeholder tanpa code block asli kini dipertahankan sebagai teks (sebelumnya `undefined.substring` melempar TypeError dan merusak rendering pesan).

**Diverifikasi:**
- Browser lokal: widget render (12 elemen `csai-*`), 13 payload XSS parser history tidak ada yang tereksekusi (0 flag `window.__xss*`, 0 elemen injeksi), 4 payload settings (title/subtitle/placeholder/avatar) ter-escape/ditolak, code block & inline code tetap dirender, settings lokal dipulihkan dari snapshot. `npm run build:widget` (terser) dijalankan ulang. Suite **90 passed / 308 assertions**.

### Audit keamanan API chat publik + sinkronisasi harga (`POST /api/chat`) (2026-09-28)

**Diperbaiki (temuan audit — 1 request curl tanpa Origin/Relier bisa membongkar system prompt 3.287 karakter):**
- **CORS wildcard**: `config/cors.php` tidak lagi memasukkan `api/*` (framework `HandleCors` memberi `Access-Control-Allow-Origin: *` ke semua situs). CORS kini ditangani `WidgetApiCors` — ACAO hanya di-echo untuk origin yang diizinkan `allowed_domains` widget; preflight `OPTIONS` dijawab 204 (echo origin, tetap digate di respons asli).
- **Origin wajib**: `POST /api/chat` menolak request tanpa `Origin`/`Referer` → `403 origin_required` (jalur eksploit curl/hardening script mati sebelum ada pekerjaan).
- **Rate limit**: `RateLimiter::for('chat')` — 30/menit per IP+widget, 120/menit per IP (`throttle:chat`), respons 429 JSON ramah (`error_code: rate_limited`) via renderer exception. IP asli diambil dari `CF-Connecting-IP` (`App\Support\HttpClientIp`) agar di belakang Cloudflare tidak jadi satu bucket bersama.
- **sessionId tidak lagi bisa dipalsukan**: `SessionIdService` — id ditandatangani HMAC-SHA256 dengan `app.key` (`sess_<24>.<sig64>`, muat kolom `visitor_uuid(100)`); id lama/tanpa tanda tangan diganti id baru; `bindFingerprint` membatalkan lanjutan sesi bila IP+user agent berbeda dari yang tercatat (hijack/leak id → sesi baru).
- **Kebocoran system prompt**: rule "Batasan Keamanan (WAJIB)" di-append terakhir di semua cabang `PromptBuilder` (larangan membocorkan instruksi sistem termasuk framing "hardcoded"/"laporan"/"matriks analisis risiko" + anti prompt-injection); field `debug` (exception message) dihapus dari respons publik fallback.
- **Widget demo landing dikunci**: migrasi `2026_09_28_100000` set `allowed_domains = cekat.biz.id, www.cekat.biz.id` pada `landing-page-default` (seeder disamakan); `DomainAccessService` selalu mengizinkan host `app.url` (landing lokal + dashboard tetap jalan).
- **Security headers** (global `SecurityHeaders`): `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera/mic/geo off`, CSP defensif (`frame-ancestors/base-uri/form-action 'self'; object-src 'none'`), HSTS saat HTTPS. `script-src` sengaja ditunda (halaman bergantung inline script).

**Diperbaiki (harga tidak konsisten di 3 tempat — tabel `plans` jadi sumber kebenaran):**
- Landing `welcome.blade.php`: Pemula Rp0 (100 pesan, 3 dokumen & 10 FAQ), Profesional **Rp399k → Rp299k** (3 chatbot, 2.000 pesan), Perusahaan **Rp1.4jt → Rp799k** (10 chatbot, 10.000 pesan, 100 dokumen & 999 FAQ) — klaim "karakter" diganti field plan nyata; simulasi FAQ RAG disamakan (Enterprise → Business, 20 jt karakter → 100 dokumen/999 FAQ).
- FAQ bot `LandingPageChatbotSeeder`: Pro **Rp99rb → Rp299.000**, Business **Rp299rb → Rp799.000**, kuota disesuaikan, klaim promo "Early Access diskon 50%" dihapus. (`DemoWidgetSeeder` sudah cocok.)

**Ditambahkan:**
- Test `ChatSecurityTest` (14 kasus: origin-wajib, CORS per-origin, preflight, rate limit, sesi signed/tamper/fingerprint, prompt guard, hilangnya `debug`, security headers) + penyesuaian `ChatApiTest`/`WidgetStatusGateTest` (kirim `Origin`), `DomainAccessServiceTest` kini boot Laravel (host app selalu diizinkan) — suite **89 passed / 305 assertions**.
- QA lokal 11/11 (tanpa Origin, origin jahat, sesi signed, preflight, headers, rate limit).

### Redesign landing page & halaman auth (2026-09-28)

**Diubah:**
- `welcome.blade.php` diganti total mengikuti template desain baru (`design/cekat_biz_id_ai_chatbot_template_rag_interactive.html`): hero dengan simulasi chat, grid fitur, demo dashboard interaktif (kursor palsu, drag & drop), diagram alur RAG (React Flow), tabel harga, CTA kontak, footer. Navigasi sadar-autentikasi (tamu: Masuk + Buat Bot Anda → `/login`/`/register`; login: tombol Dashboard admin-aware); CTA hero/harga/kontak tertaut `/register` atau `/dashboard`; "Lihat Demo" `#demo` yang tidak ada → `#cara-kerja`; skrip widget landing (`window.CSAIConfig` + `/widget/widget.js`) tetap dimuat di akhir halaman.
- `auth/login.blade.php` & `auth/register.blade.php` memakai shell desain baru (panel branding gelap 2 kolom, tab Masuk/Daftar, toggle tema dark/light). Tab kini navigasi server-side antar `/login` dan `/register`; form asli dengan `@csrf`, kotak error, `old()`, dan tautan lupa sandi; **Google SSO (`route('google.login')`) dipertahankan di login dan ditambahkan di register**; register berdiri sendiri (tidak lagi `@extends('layouts.app')`) dan menambah `password_confirmation` + error per-field.

**Diperbaiki:**
- Bug palet desain: `tailwind.config` auth tidak punya brand `300/400` → judul gradien "Data Anda Sendiri" transparan/tidak terlihat; landing tidak punya brand `200/300/700/800` → `from-*`/`hover:bg-brand-700` mati. Palet dilengkapi di ketiga view.
- Ikon sosial footer memakai SVG inline — lucide telah menghapus ikon merek `twitter`/`github`/`linkedin` (sebelumnya ikon kosong + warning konsol).
- A11y: `for`/`id` label mock dashboard, `aria-label` + `id` input hero, tautan sosial `aria-label`.

**Ditambahkan:**
- `CHANGELOG.md` (file ini) — riwayat perubahan Fase 0–8, perbaikan admin models, dan perubahan operasional.

### Fase 8 — Gate plan, widget non-aktif, kedaluwarsa langganan (`e5d9cae`, `b746acc`)

**Ditambahkan:**
- Middleware `PlanFeatureGate` (alias `plan.feature`) + halaman `plan-locked.blade.php`; ikon gunci di sidebar untuk fitur terkunci plan.
- Gate rute Leads (`can_export_leads`) dan WhatsApp (`can_use_whatsapp`); admin membypass keduanya.
- Batas kanal aktif per plan: `ChannelController::activate()` + `guardActiveLimit()`; banner aktivasi di `channels/index`.
- `PlanExpiryService`: deteksi kedaluwarsa, downgrade malas di middleware `CheckUserStatus` (redirect ke `channels.index` dengan flash info), perpanjangan dihitung dari masa berlaku (bukan tanggal bayar) di `PaymentController` dan `TransactionMonitor`, `CheckPlanExpiry` memakai service yang sama.
- Tampilan masa berlaku langganan di sidebar dan halaman Billing.
- Endpoint konfigurasi widget melayani `404 widget_inactive` bila kanal bukan `active`/`is_active`; `widget.js` memeriksa `fetchConfig` → `'disabled'` dan membatalkan render sebelum `createWidget()` (bubble tidak muncul sama sekali).
- Test: `WidgetStatusGateTest`, `PlanFeatureGateTest`, `PlanExpiryTest` (suite 71 passed).

**Diperbaiki:**
- Rute widget config terdaftar ganda prefix (`api/api/widget/...`) sehingga endpoint mati untuk semua pengguna — dikoreksi ke `/widget/{slug}/config` (`routes/web.php`).
- `DashboardController::getPeakHours()` memakai pengelompokan jam berbasis PHP (tanpa `HOUR()` MySQL) — perbaikan 500 pada dashboard.
- `DefaultPlansSeeder`: Pro `can_use_whatsapp` salah set `false` → `true`; migrasi `2026_09_27_140000` diperluas ke plan `pro`/`business` (`b746acc`); data prod disesuaikan (Starter 0 / Pro 1 / Business 1).
- `PolicyTest::makeUser` fixture plan diberi `can_use_whatsapp`/`can_export_leads` (maksud kebijakan tetap sama).
- Embed cache-bust `20260927-p2`; `widget.min.js` di-rebuild.

### Fix — Admin AI Models & Tiers (`d76094d`)

**Diperbaiki:**
- "Fetch from OpenRouter" gagal `SQLSTATE[22003] Out of range value for column 'input_price'` saat API mengembalikan harga sponsored negatif (mis. `typesafe/jev-router` = -1.000.000 → kolom `decimal(10,6)`). Harga kini di-clamp `max(0, ...)` (fetch massal dan `fetchModelInfo`), `context_length` di-clamp minimal 1024.
- Impor kini per-baris dengan try/catch — satu baris rusak tidak lagi menggagalkan seluruh impor (disampaikan sebagai "(N rows skipped)").
- Tombol play/test di baris model tidak menampilkan notifikasi apa pun (hasil flash hanya dirender di dalam modal yang sudah tertutup). Notifikasi kini dirender di atas tabel: hijau "— active & responding" + isi respons, merah "— test failed, model not responding" + pesan error dari OpenRouter.

**Ditambahkan:**
- Test `ModelsManagerTest` (5 kasus: clamp harga sponsored, lanjut lewati baris rusak, clamp `fetchModelInfo`, notifikasi sukses, notifikasi gagal) — suite **76 passed / 226 assertions**.

### Perubahan operasional (di luar Git)

- **nginx (origin)**: include kustom `nginx.conf_widgetcache`/`nginx.ssl.conf_widgetcache` — `location = /widget/widget.min.js` dan `= /widget/widget.js` → `expires 1h` (sebelumnya `expires max`); bertahan setelah rebuild template Hestia.
- **Cloudflare**: purge cache sukses (URL widget lama kini MISS + header baru). Menunggu pengaturan dashboard: Browser Cache TTL 4h → "Respect Origin Header"/"Bypass" dan peninjauan rule Cache (edge bypass belum aktif).
- **Data prod**: `UPDATE plans SET can_use_whatsapp=1 WHERE slug='business'`.
- QA produksi penuh (Fase 8 + Admin Models) memakai user sementara; semua data QA sudah dibersihkan.

**Catatan keamanan:**
- API key OpenRouter lama masih ada di riwayat Git (komit lama) — **wajib di-rotate**.

### Fase 7 — Port hotfix produksi (`ad5fd51`)
- `OpenRouterClient`: rantai fallback model utama → `openrouter/free` pada 404/429/402/5xx/konten kosong (dipakai `TopicAnalyzerService`, `WhatsAppManager`, `GenerateChatSummary`).
- `OPENROUTER_FALLBACK_MODEL` (env-only); model hardcoded → `config('services.openrouter.default_model')`.
- Mapping default semua tier memakai model gratis; `ChatOrchestrator` retry diperluas ke 402/429/5xx; `DefaultPlansSeeder` `allowed_models` hanya model gratis terverifikasi.

### Fase 6 — Fix model free tier + API key ke `.env` (`5b6d7be`)
- ID model lama dihapus OpenRouter (404) diganti; semua default → `openrouter/free` (Free Models Router, konteks 200k).
- `ChatOrchestrator` self-healing: retry 400/404 ke `openrouter/free`, retry 1× saat konten kosong.
- Secret API key dihapus dari `config/services.php` (kunci lama masih di riwayat Git — rotate); System Settings menampilkan indikator status `.env`; migrasi pembersihan row `settings` + update mapping.
- Bug schema `knowledge_documents` fresh-install → migrasi repair lintas driver.
- Test: +ModelResolver/QuotaService/PromptBuilder/fallback → 53 passed / 159 assertions.

### Fase 5 — Dokumentasi (`56eb530`)
- README (ganti boilerplate Laravel): overview, setup, env, queue/scheduler, build widget, perintah test, bentuk chat API, catatan deployment.
- `docs/ARCHITECTURE.md`: model domain, relasi Agent-KB-Channel-Conversation, lifecycle chat/billing/kuota, batas admin.

### Fase 4 — Channels sebagai konsep utama (`6c91b46`)
- Rename `chatbots.*` → `channels.*` (redirect 301 untuk URL lama), `ChatbotController` → `ChannelController`, label UI Chatbot → Channel.
- Editor channel 8 tab (Umum/Tampilan/Lead/Domain/Embed/Webhook/Analitik); editor agent 6 tab + playground uji coba.
- Admin: hapus rute duplikat; halaman AI Models & Tiers.
- Test: `UiSmokeTest` (semua tab, redirect legacy, admin gate).

### Fase 3 — Form Requests, Policies, events (`a6ce3dd`)
- Form Request untuk chat, agent, widget, WA device, update profile/password (ownership via `Rule::exists`).
- Policies + `Gate::authorize` ganti cek manual (tutup hole `widget_id` WhatsApp).
- Chat API aditif: `meta{model,tokens_used}` + `error_code` di semua cabang (widget lama aman).
- Events: chat.processed, quota, domain, webhook, lead, status dokumen, admin settings + subscriber `LogSystemEvent`.
- Test: `PolicyTest` (cross-user, admin 403, suspend) → 26 passed.

### Follow-up — WP plugin v1.0.1 (`d174e9d`)
- Cache-busting widget `?v=20260927-p1` di semua embed; bump plugin 1.0.0 → 1.0.1 + rebuild zip.

### Fase 2 — Ekstraksi ChatController (`8117e65`)
- `ChatController::chat()` 613 → 37 baris; layanan baru: `ChatOrchestrator`, `PromptBuilder`, `ModelResolver`, `QuotaService`, `DomainAccessService`, `LeadCaptureService`, `WebhookActionService`.
- Bentuk respons JSON dipertahankan identik; test API chat (sukses/kuota/domain/suspend/404).

### Fase 1 — Harden widget parseMarkdown + cache-busting (`a700525`)
- Tautan markdown di-stash ke placeholder; validasi URL + escape atribut (anti attribute-breakout); strip tanda baca; `rel=noopener noreferrer`.
- Rebuild `widget.min.js`; cache-busting `?v=20260927-p1`; 9 regression test Node.

### Fase 0 — Baseline branch (`ef7e7a2`)
- Test memakai SQLite `:memory:` (`phpunit.xml`); migrasi `widget_id` nullable lintas driver; `.env.example` dilengkapi var OPENROUTER/GOOGLE/MIDTRANS/FONNTE; SOP `agent.md` + dokumen plan disertakan.

### Chore (`fa75aea`)
- Dokumen perencanaan `docs/*.md` → `plan/*.md` (rename murni); `docs/` khusus panduan operasional.

---

## Riwayat sebelumnya

Lihat `git log` — rilis sebelumnya antara lain: Webhook System (2026-02-02), sinkronisasi `widget.min.js`, perbaikan rendering link chatbot (2026-02-06).
