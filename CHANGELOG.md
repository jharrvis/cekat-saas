# Changelog

Semua perubahan penting pada proyek ini dicatat di file ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Unreleased] — Branch `feature/business-workflow-ui-ux-robustness` (2026-09-27)

### Public API v1 + manajemen API key (2026-10-01)

Fitur baru: akses data (leads, sessions, widgets, stats) via API key utk integrasi server-to-server (CRM, Zapier, warehouse).

**Skema:** tabel `api_keys` (user_id, name, key_prefix, key_hash sha256, last_used_at, expires_at, revoked_at) — secret `ck_live_` + 44 char **hanya ditampilkan sekali** saat create, tidak pernah disimpan plain. Relasi `User::apiKeys`.

**Autentikasi:** `App\Http\Middleware\ApiKeyAuth` (alias `api.key`) — `Authorization: Bearer` / `X-API-Key`, lookup by prefix + `hash_equals`, tolak revoked/expired, 403 `feature_locked` bila plan tanpa `api_access` (`User::canUseApi()`), 403 `account_suspended`, `last_used_at` update ≤1/menit. CSRF exempt `/api/v1/*`, alias terdaftar, throttle 429 JSON (`api/v1/*` di handler `ThrottleRequestsException`), `RateLimiter::for('api-key')` 120/menit per key (fallback IP).

**Endpoint** (`routes/web.php` grup `api/v1`, middleware `['api.key','throttle:api-key']`, tanpa CORS — server-to-server):
- `GET /api/v1/leads` (filter widget_id/search/since/until, cursor id-desc, limit 1-100 default 50), `GET /api/v1/leads/{id}` (+summary)
- `GET /api/v1/sessions` (filter widget_id/is_lead/since/until), `GET /api/v1/sessions/{id}/messages`
- `GET /api/v1/widgets`, `GET /api/v1/stats` (total/week/month/conversion_rate/total_sessions)
- Kontrak `{data, meta:{per_page,next_cursor}}`; error `{error,error_code,message}` 400/401/403/404/429. PII minimasi: ip_address/user_agent tak diekspos; visitor_* ter-deskripsi via accessor.

**Service:** `App\Services\Api\LeadQueryService` (leadsFor/sessionsFor/statsFor/hasContact) — dipakai juga web `LeadController` (refactor, duplikasi query terhapus).

**UI:** route `/settings/api-keys` (`plan.feature:api_access` — Free lihat `user.plan-locked`), `ApiKeyController` (index/store/destroy; store re-check `canUseApi`, destroy ownership 404), view `user/api-keys.blade.php` (secret sekali + copy, list status Aktif/Dicabut/Kedaluwarsa, quick-start curl), sidebar "API Keys" dgn lock icon. Docs: `/docs/api` (`docs/api.blade.php` — auth, endpoints, pagination, errors, privasi).

**Test:** +3 file / 23 test (`ApiKeyAuthTest`: 401/401 salah/revoked/expired/403 free/x-api-key/last_used/hash; `ApiV1DataTest`: scoping, 404 cross-user, decrypt messages, no-ip, cursor, date filter, stats; `ApiKeyManagementTest`: secret sekali, validasi nama, revoke efektif, 404 antar user, plan-lock). Suite **269 passed / 1037 assertions** (baseline 246/960).

**Deploy note:** migrasi `2026_09_30_130000_create_api_keys_table`; cek plan Pro/Business prod punya `features.api_access` aktif (toggle di Admin → Plans).

### API v1: filter `widget_id` terima slug, nilai tak valid → 400 (2026-10-01)

**Masalah:** `?widget_id=widget-3-GAHVcXuv` (slug) di-cast `integer()` jadi 0 → filter dilewati diam-diam, API mengembalikan data campur semua widget.

**Fix:** `V1Controller::resolveWidgetId()` — `widget_id` menerima ID numerik **atau** slug (di-resolve terhadap widget milik pemilik key); nilai yang bukan keduanya → **400 `invalid_param`** (bukan diabaikan). Berlaku di `GET /api/v1/leads` dan `GET /api/v1/sessions`. Docs (deskripsi filter + tabel error) ikut diperbarui.

**Test:** +1 test (slug filter, numeric filter, 400 unknown slug di leads & sessions) — suite 270 passed.

### API v1: payload leads/sessions keluar lengkap (2026-10-01)

**Permintaan:** output `/api/v1/leads` & `/api/v1/sessions` harus memuat semua data — metadata lokasi, device, chat summary, dsb.

**Isi resource (`V1Controller::sessionResource`) kini:** `summary` + `summary_generated_at` (di index juga, bukan hanya show), `is_converted`, `status`, `referer_url`, `ip_address`, `device:{type,label,user_agent}` (`VisitorGeo::describeAgent` → mis. "Chrome 153 · Windows"), `location:{country_code,country,region,city,isp}` (dari `location_data`), `ended_at`. Test lama "ip/user_agent tidak diekspos" dibalik jadi assert positif; docs (deskripsi endpoint, contoh payload, catatan privasi) diperbarui.

**Test:** 1 test diperluas — suite 270 passed.

### Notifikasi email lead per channel (tab Lead) (2026-10-01)

**Permintaan:** tiap channel bisa punya email notifikasi sendiri utk lead baru — saat fitur diaktifkan user memasukkan email + config notifikasi lead baru; saat nonaktif, email tetap dikirim ke email utama akun (perilaku lama).

**Implementasi** (semua key di JSON `settings` widget — tanpa migrasi):
- **UI** kartu "Notifikasi Email Leads" di tab Lead (`channels/tabs/lead.blade.php`, juga tampil di admin landing-chatbot): toggle `lead_email_notif_enabled`, input `lead_email_notif` (muncul saat aktif, wajib & divalidasi email), checkbox `lead_email_new_lead` "Kirim notifikasi saat lead baru" (default on).
- **`SendLeadNotification`**: penerima = email channel (aktif + alamat valid) selain itu email owner; `lead_email_new_lead` off → tanpa email (lead tetap tersimpan); alamat kosong/tidak valid → fallback defensif ke email owner.
- **Penyimpanan:** branch lead `ChannelController::update()` + route admin `admin.landing-chatbot.update-lead` menulis 3 key; saat toggle off, `lead_email_new_lead` dipaksa `true` (legacy).
- **Validasi:** `UpdateWidgetRequest` — `lead_email_notif` `required_if:lead_email_notif_enabled,1|email`; `prepareForValidation` membuang input kosong saat toggle off supaya rule `email` tak menolak field tersembunyi.

**Test:** +4 (email khusus menerima lead, fallback owner bila nonaktif, checkbox off = tanpa email, validasi wajib-email saat aktif) — LeadNotificationTest 13 test, suite **274 passed / 1068 assertions**.

### Tabel chatbot dirender profesional (2026-09-30)

**Masalah:** balasan AI berformat markdown table (`| Ukuran | Estimasi Harga |` + `|---|`) tampil mentah — pipe & garis pemisah berantakan di widget maupun inbox admin.

**Fix dua lapis:**
1. **`TextSanitizer::markdownToPlain`** — blok tabel ditarik sejak awal (placeholder `\x1B`, aman dari collapse spasi) lalu dirender jadi **tabel plain-text rapi berkolom** (`renderPlainTable`): lebar kolom pakai `mb_strwidth`, align kanan `:-:` didukung, sel bersihin inline markdown (link/emphasis/tag) via `inlineToPlain` (refactor step 2/4/5 jadi `linksToPlain`/`emphasisToPlain`/`tagsToPlain` — perilaku utuh). Format hasil tetap idempotent & terdeteksi ulang sebagai tabel saat sanitize berikutnya. Terpakai di widget, chat history, inbox admin (nl2br) & email lead.
2. **Widget `parseMarkdown`** — deteksi tabel (GFM berpipe **dan** tabel plain server) setelah pass inline → rebuild jadi `<table class="csai-table">` + wrapper `overflow-x` (th nowrap, header bg, zebra row, hover, align per kolom); restore sebelum placeholder link/code agar isi sel ikut diproses; `<br>` di sekitar tabel dibersihkan. `<script>` dalam sel tetap escaped.
3. CSS `.csai-table*` di widget; cache buster → `?v=20260930-p6`.

**Test:** +4 TextSanitizer (`aligned plain`, `inline cleaned`, `round two unchanged`, `pipe tanpa separator bukan tabel`) → suite **246 passed / 960 assertions**. Smoke Node: GFM→`<table>`, plain server→`<table>`, XSS escaped, list/link biasa utuh.

### Fix lead: data pelengkap tak lagi diabaikan setelah lead terkunci (2026-09-30)

**Laporan 2 session:**
1. `sess_OXiFp7cQPZj…` (465): form pre-chat → lead LENGKAP (name/email/phone), plan business, 0 error mail di log — **kode benar**; email kemungkinan nyangkut **SPAM** (From `lora@cekat.biz.id` via Brevo relay; verifikasi sender/SPF-DKIM di dashboard Brevo + cek folder spam).
2. `sess_UwkS0LtJXO4…` (458): nama+email hilang, hanya phone — **bug listener**: `SendLeadNotification` hanya persist saat `!$alreadyLead`. Urutan chat: "082190906070" → dispatch `{phone}` → lead terkunci; "rintoelfrido@yahoo.com" & "Nama RINTO" → dispatch berikutnya **di-return** → name/email tak pernah masuk.

**Fix:** `SendLeadNotification::handle` kini **merge setiap `LeadCaptured`**: `is_lead=true` + isi/timpa `visitor_name/email/phone` dari data dispatch (nilai baru menang utk field yang disertakan; field tak disentuh dipertahankan). Email notifikasi tetap **sekali per session** (data pelengkap hanya update Lead Collection). +2 test (`later captures complete the lead even after first notification` — phone dulu lalu name/email tetap masuk & mail=1; `new capture overwrites stale lead values`). Suite **242 passed / 939 assertions** (baseline 240/929). Backfill session 458 (Rinto: name+email dari transcript) via script prod.

### Pre-chat form: AI tahu nama pengunjung + greeting menyapa nama (2026-09-30)

**Laporan:** form diisi & tersimpan sbg lead, tapi bot tak tahu nama saat ditanya ("siapa nama saya?").

**Fix 1 — identitas pengunjung disuntik ke system prompt** (`ChatOrchestrator::visitorContext()`):
- Turn 1: data `leadForm` dari request (session row belum ada sebelum LLM).
- Turn berikutnya: dibaca dari `chat_sessions.visitor_name/email/phone` (lead yg sudah persist).
- Bentuk: `[Data pengunjung: Nama: X; Email: Y; No HP: Z. Sapa pengunjung dengan namanya dan gunakan data ini bila relevan.]`
- Tanpa data → tanpa konteks (netral).

**Fix 2 — greeting menyapa nama** (`widget.js renderGreetingText` + `showGreeting`):
- `{name}` di greeting disubstitusikan verbatim utk posisi/gaya bebas.
- Tanpa token → disisipkan ke sapaan awal: `Halo! 👋 ...` → `Halo Dewi! 👋 ...`; greeting non-"Halo" → prepend `Halo {nama}, `.
- Greeting **ditunda** selama pre-chat form terbuka → muncul sesudah submit/skip dgn nama; skip (tanpa data) → greeting biasa. `CSAI_forgetChat` reset flag agar greeting baru tampil.
- Nama dari form aman (melewati sanitasi `parseMarkdown` widget).

**Test:** +3 (`visitor dari form masuk prompt turn 1`, `identitas dari session masuk turn 2`, `tanpa lead data = tanpa konteks`) + perbaikan `lastSystemPrompt` test (skip request prompt summary). Suite **240 passed / 929 assertions** (baseline 237/916). Cache buster → `?v=20260930-p5`.

### Config endpoint: expose `model` (2026-09-30)
- `/api/widget/{slug}/config` kini mengembalikan `model` = `settings['model']` widget (fallback `config('services.openrouter.default_model')`, konsisten `WidgetCustomizer::boot`); `widget.js` defaultConfig ikut punya `model: null`. +1 test (default & override), suite **237 passed / 916 assertions**. Cache buster → `?v=20260930-p4`.
- **E2E Strategi 3 terverifikasi di prod:** form tampil dari `https://cekat.biz.id/` (Nama*, Email*, tanpa tombol Lewati), submit → chat → **session 460** `is_lead=true` + `visitor_name/email/phone` terisi.
- **Temuan:** `allowed_domains` landing widget = `cekat.biz.id, www.cekat.biz.id` → origin `bmp.net.id` kena **403 Domain not allowed** (config & chat) → widget ter-embed di bmp tidak berfungsi sampai bmp di-whitelist (keputusan pemilik landing). Config endpoint tak men-set ACAO utk origin di-luar allowlist (by design `WidgetApiCors`) → browser bmp melihat CORS error.

### Lead Collection: fix Strategi 2 (trigger) + implement Strategi 3 (Pre-Chat Form) (2026-09-30)

**Audit** menemukan S1 jalan, S2 cacat, S3 dead code. Perbaikan (dipilih user: fix S2 + implement S3):

**Strategi 2 — Trigger System (fix):**
- Hitungan "setelah pesan ke-N" kini dari **transcript DB** (`ChatOrchestrator::currentTurn()`: jumlah `chat_messages` role=user utk session + 1), bukan `count($history)+1` dari payload klien (spoofable & ikut menghitung giliran AI → trigger sering kebablasan/dini). Baris pertama / demo tanpa widget = turn 1.
- Kata kunci → **word-boundary** (`\b` + preg_quote, case-insensitive): `"harga"` match "Berapa **harga** paket?" tapi tidak di "**menghargai**"/"borders". Keyword tetap shortcut OR dgn ambang; ambang di-clamp min 1.
- `LeadCaptureService::triggerInstruction($settings, $message, $currentTurn)` — signature berubah (history dihapus).

**Strategi 3 — Pre-Chat Form (implementasi, sebelumnya dead code):**
- **Config endpoint** (`/api/widget/{slug}/config`) kini mengekspos `leadForm {enabled, requireName, requireEmail, requirePhone}` dari key `lead_form_*`.
- **Widget** (`widget.js`): overlay form sekali per browser (`csai_leadform_done_{widgetId}` localStorage) tampil saat window dibuka sebelum pesan pertama; field Nama/Email/No HP (tanda `*` utk required); tombol "Lewati" hanya muncul kalau tak ada field wajib; validasi client (required + format email); header tetap di atas overlay (z-index) supaya tombol close selalu bisa diklik. Data dikirim **sekali** bersama pesan pertama sebagai `leadForm`.
- **Server**: `ChatRequest` validasi `leadForm` (name ≤120, email valid ≤190, phone ≤30 → 422 bila tidak); `ChatOrchestrator` — data form **diutamakan** di atas ekstraksi AI/regex lalu `LeadCaptured::dispatch` → session `is_lead` + `visitor_name/email/phone` + email owner (sekali per session, gate plan `leads`).
- Cache buster widget → `?v=20260930-p3`.

**Test baru (10):** `LeadPreChatFormTest` (8: expose config, default off, form→chat→session+mail, validasi 422 email/nama, trigger turn ke-3 dari DB, forged history tak memicu trigger, keyword whole-word) + `LeadCaptureServiceTest` diperbarui (3 utuh: server turn, word-boundary, clamp). Suite **236 passed / 913 assertions** (baseline 227/870).

### Bersihkan markdown/mojibake dari balasan chatbot (2026-09-30)

**Permintaan:** hapus karakter `##`, `**`, dll (markdown mentah) & mojibake dari chatbot dan chat history.

**Akar masalah:** widget hanya mem-parse subset markdown (tanpa aturan heading → `##` tampil mentah), sedangkan chat history, admin inbox, email lead & ringkasan menampilkan teks escape polos → `**bold**`/`##` terlihat apa adanya.

**Fix (satu titik sanitasi, menutup semua permukaan):**
- `app/Support/TextSanitizer.php` (baru): `markdownToPlain()` — headings/`**`/`__`/`*`/`_` (word-boundary: `snake_case`, `2*3` aman) /`~~`/inline code/blockquote/HR/tag HTML; `[label](url)` → `label (url)` (URL tetap); gambar → alt; **fenced code diproteksi placeholder** (isi kode utuh, fence dibuang — sekaligus menutup leak ```json sisa `stripActionJson`); spasi ganda & blank line dirapikan; repair mojibake best-effort (validasi UTF-8 → iconv; peta CP1252 umum: `â€™`→`, `Ã©`→é, dll). Invarian: URL polos byte-identikal, emoji utuh, idempoten.
- `ChatOrchestrator`: panggil **setelah** ekstraksi action JSON (`{"action":...}` tetap utuh) & **sebelum** `persistConversation` → widget + history + inbox + email bersih; panggilan kedua sebelum `return` menutup path demo tanpa widget. `GenerateChatSummary`: sanitasi ringkasan (mengganti strip fence manual).
- `chat:sanitize-history` (command baru, `--dry-run`): bersihkan **baris lama** di `chat_messages.content` + `chat_sessions.summary` (konten ter-encrypt, diakses via model).
- Tidak mengubah `widget.js` (parseMarkdown tetap sebagai escaper XSS + linkifier; test regresi utuh).

**Audit Lead Collection Settings (Strategi 1/2/3) — temuan:** S1 Prompt Engineering = **berjalan** (`PromptBuilder.php:197-215`, diuji `PromptBuilderTest`); S2 Trigger System = **berjalan tapi cacat** — `count($history)+1` memakai history **klien** (bisa dipalsukan, menghitung giliran AI), `lead_trigger_keywords` (substring) menimpa ambang pesan ke-N, tanpa resep; S3 Pre-Chat Form = **dead code** — `lead_form_*` disimpan UI & controller tapi **tak pernah dibaca** (tak ada form di widget/config); plus 2 jalur tak terlihat selalu aktif (JSON `save_lead` dari AI & regex fallback) yang **mengabaikan ketiga toggle**, dan plan lock bersifat presentasi-only. Keputusan perbaikan → ditawarkan ke user.

**Test baru (8):** `TextSanitizerTest` (7 unit: heading/bold, URL+emoji+snake_case identik, link, fence utuh, blockquote/HR/HTML, mojibake, idempoten) + `ChatApiTest::test_markdown_in_ai_reply_is_sanitized_before_return_and_persist`. Suite **227 passed / 870 assertions** (baseline 219/849).

### Halaman & referrer chat (history + email) + fix sinkronisasi jam chat (2026-09-30)

**Permintaan:** (1) ambil URL referral & halaman tempat user chat → tampilkan di chat history & notifikasi email; (2) jam chat tidak sinkron — jam server atau jam aplikasi?

**1. Halaman + Referrer chat:**
- **Widget** kirim `pageUrl` (`location.href`) & `referrerUrl` (`document.referrer`) di payload `/api/chat` (cap 500; header Referer server hanya fallback karena cross-origin browser hanya kirim origin saja).
- `ChatRequest` validasi nullable, `ChatOrchestrator` simpan ke kolom **yang sudah ada tapi tak pernah ditulis**: `chat_sessions.source_url` (halaman) & `referer_url` — diperbarui tiap pesan (halaman saat ini, `updated_at` ikut ter-refresh → "Last Activity" jadi akurat) + fallback header.
- **Chat detail** (`chats.show`): baris `Halaman:` & `Referrer:` (truncate 70 + full title). **Email lead**: field `Halaman` & `Referrer` (mono, 120) setelah Perangkat.
- Cache buster widget → `?v=20260930-p2`.

**2. Jam chat tidak sinkron — diagnosis: JAM APLIKASI (bukan jam server error).** Tiga penyebab:
- (a) **Penyebab utama:** app simpan `created_at` dalam **UTC** (`.env` `APP_TIMEZONE=UTC`) tapi semua view cetak `format('d M Y H:i')` **tanpa konversi**, sementara 6 email menulis label **"WIB"** dengan nilai UTC → telat 7 jam.
- (b) Pesan user di-stamp **setelah** panggilan LLM selesai (bisa +60 detik) → timestamp bubble user = waktu bot selesai balas, bukan waktu pesan dikirim.
- (c) Drift env: `.env.example` = `Asia/Jakarta` tapi `.env` live = `UTC`.
- **Fix:** `APP_TIMEZONE=Asia/Jakarta` (config default + local & prod .env) + migrasi sekali-jalan `2026_09_30_080000_shift_datetimes_from_utc_to_wib` (geser **semua** kolom datetime/timestamp di 18 tabel +7 jam — nilai tersimpan tetap merepresentasikan instan yang sama; jalankan **bersamaan** dengan perubahan .env dalam satu deploy) → semua view, email, CSV, relatif- waktu otomatis WIB. `ChatOrchestrator::handle()` kini menangkap `$receivedAt = now()` **di awal request**; pesan user di-stamp `receivedAt`, balasan bot tetap waktu balas nyata (uji: sleep 1s di fake LLM → user msg < bot msg).

**Test baru (6):** `ChatApiTest` (+2: persist pageUrl/referrerUrl & update halaman berikutnya; stamp receive-time vs reply-time), `ChatHistoryShowTest` (+1: baris Halaman/Referrer & absensi saat tak ada), `LeadNotificationTest` (+1, diperluas: email berisi Halaman/Referrer), `TimezoneTest` (2: app berjalan `+07:00`, chat detail render wall-clock tersimpan tanpa konversi ganda). Suite **219 passed / 849 assertions** (baseline 214/824).

**Catatan operasional:** deploy timezone WAJIB urutan: (1) `sed` prod `.env` `APP_TIMEZONE=Asia/Jakarta` + `config:cache`, (2) jalankan deploy (migrasi geser data). Developer lain: samakan `.env` lokal sebelum `php artisan migrate`.

**Cloudflare stale widget (found during E2E):** edge cache `widget.min.js` 4 jam (CF default utk JS; origin tak kirim `Cache-Control`) → embed tanpa query-string menerima JS lama lama setelah deploy. Fix: `public/widget/.htaccess` set `Cache-Control: no-cache, must-revalidate` utk `widget.min.js` + `a2enmod headers` di server (mod_headers sebelumnya mati, tanpa ini `.htaccess` diam-diam di-skip). E2E terverifikasi di prod: session 455 `source_url=https://bmp.net.id/promo-e2e-browser`, `referer_url=https://google.com/search?q=hosting+bmp`, pesan user di-stamp `06:55:39` vs balasan bot `06:55:42`. Edge entry lama expire sendiri ≤4 jam (atau purge manual di dashboard CF); embed baru (`?v=20260930-p2`) langsung miss → file terbaru.

### Fix avatar channel (5 bug), picker ikon Lucide, & perbaikan domain restriction (2026-09-30)

**Konteks (permintaan user):** (1) tab Tampilan channel — icon/avatar tak bisa diganti & upload tak tampil di widget; (2) tampilkan ikon avatar relevan pakai Lucide; (3) audit ikon aplikasi; (4) persona name (analisis, jawaban di thread); (5) pembatasan domain — sudah benar? boleh kosong? sebaiknya mandatori?

**Bug avatar yang ditemukan & diperbaiki:**
1. **Upload tak pernah tampil di widget** — customizer menyimpan `avatar_type='image'` tapi `widget.js getAvatarHtml()` hanya me-render `<img>` untuk `'url'` → jatuh ke ikon default. Fix: widget.js menerima `'image'` ATAU `'url'` (admin pakai 'url', customizer 'image').
2. **Preview widget box hard-code** — header preview selalu `fa-robot`, launcher `fa-comment`, tak pernah membaca `$avatarIcon`/`$avatarUrl` → "klik icon tidak berubah". Fix: preview membaca state via component baru `x-widget-avatar` (SVG Lucide inline, 10 ikon + alias lama).
3. **`avatarUpload` tak pernah di-reset** — save berikutnya (mis. setelah kembali ke ikon) re-store file & memaksa `avatar_type='image'` lagi → pilihan icon hilang. Fix: reset ke null setelah store.
4. **`saveSettings()` MENIMPA seluruh settings JSON** (bug paling kritis) — hanya 7 key ditulis ulang → **`allowed_domains` (domain restriction!) hilang tiap save**, plus `subtitle`, `placeholder`, `lead_*`, `webhook_*`. Fix: **merge** key (pola `LandingChatbotManager`). Tanpa ini, pembatasan domain bisa mati diam-diam.
5. **6 dari 8 ikon picker tak didukung widget** (widget cuma `robot|support|user`) → semua fallback jadi robot.
Plus: embed code tanpa cache-buster, test-widget injection pakai key snake_case (tak dipakai widget.js) → camelCase + `?v=20260930-p1` di semua embed.

**Ikon avatar baru (Lucide-style, stroke SVG):** `bot, headphones, user-round, smile, message-circle, heart, store, briefcase, life-buoy, sparkles` — relevan utk customer service (Bot AI, Headset, Persona, Ramah, Chat, Peduli, Toko, Bisnis, Bantuan, AI Premium); grid 5×2 dengan label; alias lama (`robot/support/user` + nama FA lama) tetap diterima. **Catatan `lucide-animated.com`:** library React + Motion — komponen React, tidak bisa dipakai langsung di widget vanilla JS/Blade; yang dipakai = SVG statis gaya Lucide (MIT) dengan animasi CSS `hover:scale-105` di picker. (Audit ikon: dashboard 531 ikon FontAwesome di 55 file, landing/auth sudah Lucide via `data-lucide`, widget = inline SVG — migrasi total FA→Lucide terpisah, besar, tidak diganggu di perubahan ini.)

**Domain restriction — analisis:** diterapkan di 5 titik (config 403, `ChatOrchestrator` 403 `domain_blocked`, `WidgetApiCors` tanpa ACAO, `ChatRequest` origin_required, client gate) + localhost/own-host bypass. **Kosong = izinkan semua (fail-open) by design** → embed lintas situs memang bisa; mitigasi tetap ada (quota, throttle, origin wajib). Perbaikan: (a) bug #4 di atas (wipe); (b) **server exact-match vs UI & client yang menjanjikan subdomain** → `mysite.com` di server menolak `www.mysite.com` padahal teks tab Domains bilang diizinkan → `DomainAccessService` kini match exact + subdomain + www-tolerant (mirror `widget.js`); (c) tab Domains kini menampilkan **peringatan merah bila kosong** ("widget bisa dipasang di situs mana pun"). Keputusan *domain wajib diisi* (mandatory) = kebijakan breaking → ditawarkan ke user.

**Test baru (5):** `WidgetCustomizerTest` (4 — save MERGE settings mempertahankan allowed_domains/subtitle/placeholder/webhook/model, pilihan icon tersimpan, upload→icon tak re-upload, preview render ikon) + `DomainAccessServiceTest::test_subdomain_and_www_variants_match_as_ui_promises`. Suite **214 passed / 824 assertions** (baseline 195/723).

### Duplikasi Greeting dihapus + halaman AI Agents dirapikan (2026-09-29)

**Konteks (permintaan user):** (1) Greeting Message muncul di dua tempat — AI Agent & Channel — mana yang dipakai & hapus salah satu; (2) tab Lanjutan detail agent ada section "Statistik" isinya kosong — hapus bila tak relevan; (3) list agents dibuat lebih compact & informatif — hapus kolom Percakapan/Pesan (selalu 0) dan nama model.

**Analisis & keputusan:**
- **Greeting aktif = di Channel.** Endpoint `/api/widget/{slug}/config` (routes/web.php) menyajikan `settings['greeting']` dari **Widget Customizer** → itulah yang dirender widget sebagai bubble sapaan. `ai_agents.greeting_message` **dead code**: tak pernah dibaca renderer/prompt mana pun (`PromptBuilder::buildSystemPrompt` hanya memakai system_prompt/FAQ/dokumen; accessor `getGreetingMessageWithDefault()` juga tak pernah dipanggil). **Diputuskan: greeting tetap di Channel, field di AI Agent dihapus total** — form create/edit, validasi `Store/UpdateAiAgentRequest`, key `greeting_message` dari knowledge array, `fillable`+accessor model, dan **drop column** via migrasi `2026_09_29_170315_drop_greeting_message_from_ai_agents_table`. Form create menampilkan catatan "Greeting widget diatur di pengaturan Channel".
- **Section "Statistik" (tab Lanjutan) dihapus** — `messages_used`/`conversations_count` selalu 0 karena method `incrementMessagesUsed`/`incrementConversations` tak pernah dipanggil dari mana pun. "Dibuat" dipindahkan ke header agent (`Slug · Aktif · Dibuat 14 Mar 2026`).
- **List agents compact & informatif:** hapus grid statistik 2 kotak (Pesan/Percakapan) + chip nama model; kartu dikecilkan (padding p-4, avatar 10, judul text-base, gap-3); chip baru: **Personality**, **"N FAQ · M dok"** (atau "Knowledge kosong" — data dari `knowledgeBase` `withCount(['faqs','documents'])` di `AiAgentController@index`), dan relatif "Dibuat"; sub-header kini "N channel · Dibuat …".

**Test baru (2):** `AgentsIndexTest` — index tanpa "Percakapan"/field greeting + chip knowledge, form create tanpa field Greeting. Suite **209 passed / 796 assertions** (baseline 195/723).

### Email lead lengkap (IP/Perangkat/Lokasi) + baris "Percakapan Terbaru" klik ke Chat Detail (2026-09-29)

**Konteks (permintaan user):** (1) user bertanya kapan summary dibuat — dijawab & didokumentasikan: **summary dibuat saat lead terdeteksi** (deferred via `app()->terminating()` sesaat setelah respons chat keluar, sebelum email dikirim), **bukan** menunggu session ditutup; endpoint close hanya fallback idempoten bila belum ada. (2) Email lead diminta memuat data leads lengkap: IP address, device, dan lokasi kota. (3) Setiap baris "Percakapan Terbaru" di dashboard harus bisa diklik menuju halaman chat (Chat Detail sesi tsb — konfirmasi user).

**Diubah:**
1. **Email `new-lead` + field `IP Address`** (mono), **`Perangkat`** (label `VisitorGeo::describeAgent` "Chrome 153 · Windows", fallback `device_type`), dan **`Lokasi`** kini lengkap `city, region, country` (sebelumnya hanya city+country). `SendLeadNotification`: `$session->refresh()` dipindah ke **awal** callback terminating — callback geo (`ChatOrchestrator::persistConversation`) terdaftar lebih dulu (FIFO) sehingga `location_data` hasil lookup sudah tersedia saat email dirender. IP/device sudah diisi saat sesi dibuat sehingga selalu ada.
2. **Dashboard `user/dashboard.blade.php`:** kartu baris "Percakapan Terbaru" kini `<a href="route('chats.show', $conv['id'])">` (block, hover `border-primary/40`, tooltip "Buka detail percakapan") — satu klik ke Chat Detail percakapan tersebut; data `id` sudah tersedia dari `DashboardController::getRecentConversations`.

**Test baru (2):** `LeadNotificationTest::test_lead_email_contains_ip_device_and_location` (assert render email memuat IP `203.0.113.42`, "Chrome 153 · Windows", "Semarang, Java, Indonesia") + `DashboardRecentConversationsTest` (row link ke `chats.show`). Suite **207 passed / 786 assertions** (baseline 195/723).

### Redesign total 13 template email + summary di email lead + rekam detail pengunjung di chat history (2026-09-29)

**Konteks (4 permintaan user):** (1) semua template email terlihat "AI-generated" — gradasi warna pelangi + emoji ikon (🆕🔐🎉⚙️⏰✅🚫⚠️🚀) di header; diminta redesign profesional/elegant tanpa gradasi & ikon ambigu; (2) email notifikasi lead harus menyertakan ringkasan percakapan; (3) chat history harus merekam detail pengunjung (IP, browser, negara/kota/lokasi, info penting); (4) saat chat ditutup AI langsung meng-generate summary tanpa klik manual.

**Diubah:**
1. **Design system email baru.** Komponen Blade `components/emails/`: `layout` (shell: header solid `#18181b` + wordmark "CEKAT" tracking + label kategori uppercase, kartu putih border `#e4e4e7` radius 8, footer 12px), `heading`, `panel` (tone `neutral`/`alert` amber /`danger` merah, flat tanpa gradasi), `button` (CTA solid hitam radius 6), `field` (label uppercase 11px di atas value). Semua **13 view email** di-rewrite memakai komponen ini — hilangkan 100% gradasi & emoji, copy ditulis ulang singkat & natural, OTP code box monospace solid gelap. Verifikasi: `view:cache` sukses, smoke-render 14 kasus via tinker (`ALL RENDERED`, nol `linear-gradient`), preview visual (lead + OTP) dicek di browser.
2. **Summary di email lead (`NewLead`).** `SendLeadNotification`: bila sesi belum punya summary & punya pesan → **generate `GenerateChatSummary` via `app()->terminating()`** (setelah respons chat keluar — pengunjung tak pernah menunggu LLM summary), lalu kirim email; bila gagal → view `new-lead` fallback menampilkan **excerpt 3 pesan terakhir** ("Potongan Percakapan Terakhir"). Panel lead kini juga menampilkan **Lokasi** (city/country dari geo) bila ada.
3. **Rekam detail pengunjung.** `app/Support/VisitorGeo.php` baru: `resolve()` lookup geo best-effort `ipwho.is` (timeout 2 dtk, tanpa API key, https) + fallback header `CF-IPCountry` (Cloudflare), **hanya untuk IP publik** (IP privat/loopback = test & dev → nol request jaringan); `deviceType()` (mobile/tablet/desktop dari UA); `describeAgent()` (label pendek "Chrome 153 · Windows" — UA penuh tak dirender). `ChatOrchestrator::persistConversation` isi `device_type` saat sesi dibuat + `location_data` (city/country/region/isp/country_code) via `app()->terminating()` setelah respons. UI `user/chats.show`: blok Session Info kini menampilkan **IP Address, Browser, Device, Location**; display Session ID diperbaiki dari kolom legacy `session_id` (nullable, tak pernah terisi) ke `visitor_uuid`.
4. **Auto-summary saat tutup chat (#4):** endpoint `POST /api/widget/session/close` (routes/web.php) `dispatchSync(GenerateChatSummary)` idempoten + `widget.min.js` `closeSessionOnServer()` dipanggil saat `closeConversation` & unload. **Live E2E menemukan bug CORS preflight:** route close & DELETE `widget/session` tak punya companion `Route::options(...)` (padahal `/chat` & `/config` punya) → preflight OPTIONS kena **405 router tanpa header CORS** → browser membatalkan POST dengan `net::ERR_FAILED` (teramati langsung di DevTools bmp.net.id — inilah sebabnya user merasa summary "harus manual"). Fix: tambah companion OPTIONS untuk kedua route (dijawab `WidgetApiCors::preflight` → ACAO echo) + regresi test preflight close & delete. **Retest live pasca-deploy:** chat lead baru → `CSAI_endChat()` → `POST /api/widget/session/close [200]` → sesi 449 `status=ended`, `summary_generated_at` terisi, `location_data` = Semarang/Indonesia + `device_type=desktop` (ipwho.is), nol error log "Failed to send lead notification".

**Test baru (10):** `tests/Unit/VisitorGeoTest.php` (6 — device type, label browser termasuk deteksi Edge/Opera di atas Chrome, IP publik/privat/invalid, resolve+mapping field provider, privasi "no HTTP utk IP privat", fallback CF-IPCountry) + `LeadNotificationTest` (2 — email lead berisi "Ringkasan Percakapan" dari summary yang di-generate sebelum kirim, dan fallback excerpt saat summary gagal) + `WidgetSessionCloseTest` preflight CORS (1) + `ChatHistoryShowTest` (1 — halaman detail chat render meta block IP/Browser/Device/Location & Session ID visitor_uuid). Suite **205 passed / 782 assertions** (baseline 195/723).

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
