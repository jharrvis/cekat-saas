# Changelog

Semua perubahan penting pada proyek ini dicatat di file ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Unreleased] — Branch `feature/business-workflow-ui-ux-robustness` (2026-09-27)

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
