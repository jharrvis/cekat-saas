# Plan: Auto-close percakapan widget saat idle + summary (fix offer diulang tanpa henti)

Status: menunggu persetujuan. Keputusan user (terkunci):
- **Auto-minimize + grace 5 detik** — AI tutup sesi + summary → tampilkan pesan penutup + summary ±5 detik → minimize sendiri; **dibatalkan bila ada aktivitas visitor** (kembali & mengetik/gulir).
- **Tombol "Tutup percakapan" manual** ikut menutup sesi server + generate summary (bukan wipe lokal saja).

## Konteks & akar masalah (bug screenshot: offer "Apakah Anda masih membutuhkan bantuan?" diulang terus, tak pernah minimize)

1. `addMessage()` selalu memanggil `resetInactivityTimer()` (`public/widget/widget.js:1147`) → saat offer ditambahkan, `closeOfferSent` di-reset ke `false` → guard `showCloseOffer` (baris 262) tidak berfungsi → offer diulang setiap 90 detik selamanya.
2. `addMessage` auto-scroll (`scrollTop = scrollHeight`, baris 1140) memicu listener `scroll` (baris 1429) yang memanggil `resetInactivityTimer()` → **timer minimize 60 detik (`closeOfferTimer`) dibatalkan** → chatbot tidak pernah minimize sendiri.
3. **Duplikat deklarasi `toggleChat`**: versi lengkap (timer/badge/mobile-hide, baris 1058) **ditimpa** versi sederhana (baris 1319) karena deklarasi fungsi terakhir menang → manajemen timer saat minimize = dead code.
4. Backend belum punya endpoint "tutup sesi" (hanya `DELETE /api/widget/session` = hapus permanen/DSR). Summary dijalankan job queued (`GenerateChatSummary`) padahal **production tidak punya queue worker cekat** (queue DB, worker hanya untuk app lain) → summary tidak pernah terproses. Bug lama: `ChatInbox::closeSession:35` mengirim `int` ke constructor job bertipe `ChatSession` → TypeError (tombol close admin rusak); `ChatHistoryController::generateSummary:126` & `ChatInbox::closeSession:35` memakai `dispatch` (queued) → diam-diam tak pernah jalan.

Fakta pendukung: `chat_sessions.status` enum `active|ended|abandoned` + `ended_at` sudah ada; `ChatInbox` sudah punya pola tutup+summary (menjadi referensi); CSRF except `/api/widget/*` (bootstrap/app.php:21) sudah mencakup endpoint baru; sessionId disimpan di localStorage bersama history; `OpenRouterClient` memakai `Http` facade → `Http::fake()` bisa di test; pola test fingerprint ada di `ChatForgetTest` (`withHeaders(['User-Agent' => ...])`); build widget: `npm run build:widget` (terser).

## Perubahan backend

### 1. `routes/web.php` — `POST /api/widget/session/close` (grup widget, otomatis CSRF-exempt + `throttle:chat`)
Validasi `{widgetId, sessionId}` sama seperti DELETE. Verifikasi berurutan: widget ada → `SessionIdService::isSigned` → `ChatSession` by `widget_id + visitor_uuid` → cocokkan fingerprint IP (`HttpClientIp`) + `User-Agent` (identik dengan DELETE baris 106-108).

Respons:
- session tidak ditemukan (belum pernah ngobrol) → `200 {success:true, noop:true}` (tanpa LLM).
- sudah `ended` → `200 {success:true, summary: existing}` (idempoten, tanpa LLM ganda).
- `active` → update `status='ended'`, `ended_at=now()`; bila ada pesan → **`GenerateChatSummary::dispatchSync($session)`** (sinkron — tanpa worker; latency LLM 1-4s tertutup grace widget); `200 {success:true, summary: string|null}`. Gagal LLM → `summary:null` (widget pakai teks penutup generik).

### 2. `app/Jobs/GenerateChatSummary.php` — konstruktor terima `ChatSession|int|string`
Resolve lewat `ChatSession::find` bila skalar; lempar exception yang jelas bila tidak ditemukan. Ini memperbaiki TypeError `ChatInbox::closeSession`.

### 3. Konsistensi panggilan summary (tanpa worker queue)
- `ChatInbox::closeSession` (`:35`): `dispatch` → `dispatchSync`.
- `ChatHistoryController::generateSummary` (`:126`): `dispatch` → `dispatchSync`, flash diubah jadi "Summary berhasil di-generate." (hasil langsung tersedia, tak perlu refresh).

## Perubahan widget (`public/widget/widget.js`)

### A. Fix offer diulang + minimize mati
1. **Urutan `showCloseOffer`**: guard → `addMessage(offer)` → `closeOfferSent = true` → arm `closeOfferTimer` (set flag SETELAH addMessage agar reset dari addMessage tidak menghapusnya). Offer dibungkus elemen `data-csai-offer` agar bisa dihapus.
2. **Guard scroll programmatic**: helper `scrollToBottom(el)` men-set `autoScrollUntil = Date.now() + 300` sebelum mengubah `scrollTop`; semua 4 titik `scrollTop = scrollHeight` (addMessage:1140, greeting:1335, toggleChat:1342, area typing:1253) memakai helper; listener `scroll` mengabaikan event bila `Date.now() < autoScrollUntil`. Scroll user asli tetap jadi aktivitas (reset timer, batal offer) — benar.
3. **Offer hanya bila ada percakapan**: `showCloseOffer` return lebih awal bila `chatHistory` tidak berisi pesan `role === 'user'` (widget dibuka tapi belum ngobrol → tak ada spam offer).
4. **Hapus duplikat `toggleChat`**: buang versi sederhana (1319-1349); gabung logika greeting-if-empty ke versi lengkap (1058) sehingga satu implementasi: badge, mobile-hidden, focus, greeting, scroll, timer (open: `resetInactivityTimer`; close: clear timer + `closeOfferSent=false`).

### B. Alur auto-close baru (callback `closeOfferTimer`, N detik setelah offer tanpa respon)
`closeConversation({keepLocal:true})`:
1. `POST /api/widget/session/close` (fetch + keepalive; gagal jaringan → tetap lanjut dengan teks generik).
2. Tambah pesan assistant persisten (ikut history): "Percakapan ditutup. Berikut ringkasan percakapan Anda: …summary…"/teks generik bila `summary:null`; hapus elemen offer.
3. Rotasi `sessionId = generateSessionId()` + `saveHistory()` → pesan berikutnya memulai sesi server BARU (sesi lama tetap `ended` di dashboard; history lokal tetap utuh sehingga summary terlihat saat widget dibuka lagi).
4. Arm `minimizeTimer` grace **`config.closeGraceTimeout` (default 5000ms, ikut objek config)** → minimize (`toggleChat`). `resetInactivityTimer()` juga me-clear `minimizeTimer` → **semua aktivitas (keyDown/input/scroll/emoji/click) membatalkan minimize** sesuai keputusan user; siklus idle baru berjalan normal setelahnya.

### C. Tombol "Tutup percakapan" (`window.CSAI_endChat`)
`closeSessionOnServer()` fire-and-forget (keepalive, sama seperti `forgetSessionOnServer` tapi tanpa delete) → `clearHistory()` → minimize (perilaku lokal existing dipertahankan; summary tetap tergenerate di dashboard). Tombol trash (`CSAI_forgetChat`, DSR delete) **tidak diubah**.

### D. Config & build
- Tambah default `closeGraceTimeout: 5000` (bisa dioverride via `window.CSAIConfig`).
- `npm run build:widget` (widget.min.js + source map).

## Test

1. `tests/Feature/WidgetSessionCloseTest.php` (baru; pola `ChatForgetTest`):
   - happy path: sesi `active` + pesan → `Http::fake` OpenRouter → 200, sesi `ended` + `ended_at`, `summary` terisi, jumlah pesan tidak berubah.
   - unsigned sessionId → 403; fingerprint beda UA → 403; widget tidak ada → 404.
   - idempoten: sesi sudah `ended` → 200 + summary lama, `Http::fake` tidak dipanggil lagi.
   - tanpa pesan → sesi `ended`, `summary:null`, tanpa panggilan LLM.
2. Unit: `GenerateChatSummary` menerima `int` (jalur ChatInbox) tanpa TypeError.
3. `tests/widget/inactivityClose.regression.mjs` (Node, pola `parseMarkdown.regression.mjs`): assert statis — hanya 1 deklarasi `toggleChat`; `showCloseOffer` men-set `closeOfferSent` setelah `addMessage`; listener scroll berisi guard auto-scroll; `closeGraceTimeout` ada di default config.
4. `php artisan test --compact` (baseline 151/551) + `npm run build:widget`.
5. QA browser lokal: idle 90s → offer muncul TEPAT SATU KALI; +60s → pesan penutup + summary → minimize ±5s; ketik/gulir dalam grace → minimize batal; tombol "Tutup percakapan" → sesi `ended` + summary di dashboard; reload widget → summary tetap terlihat.

## Files disentuh
- `routes/web.php` (endpoint close)
- `app/Jobs/GenerateChatSummary.php` (konstruktor)
- `app/Livewire/Admin/ChatInbox.php` (dispatchSync)
- `app/Http/Controllers/ChatHistoryController.php` (dispatchSync + flash)
- `public/widget/widget.js` + `widget.min.js` (dibuild ulang)
- Baru: `tests/Feature/WidgetSessionCloseTest.php`, `tests/widget/inactivityClose.regression.mjs`
- `CHANGELOG.md`

## Deploy
Commit → push → `deploy.sh` (down → pull → migrate (tidak ada migrasi baru) → cache → up). File widget statis langsung tersaji; pastikan `view:cache` jalan. Catatan: summary kini sinkron (tanpa worker) — opsional lanjutan di luar cakupan: pasang supervisor `queue:work` untuk cekat.

## Out of scope
- Auto-close berbasis session `ended` di sisi server (mis. penutupan pakai sesi lama >30 menit tanpa widget) — tidak diminta.
- Menambah queue worker production.
- Perubahan perilaku `DELETE /api/widget/session` (DSR) dan `chat:purge`.
