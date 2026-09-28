# Plan: Single Source of Truth Pembatasan Plan Tiers

## Summary

Buat satu class pusat `App\Services\Billing\PlanLimitService` sebagai satu-satunya pintu untuk membaca dan mengecek limit/fitur plan. Data tetap bersumber dari tabel `plans`; tidak ada hard-coded tier rule di controller, middleware, Livewire, Blade, atau service fitur. Perubahan limit hanya dilakukan admin melalui Plan Manager yang sudah ada.

Keputusan terkunci:

- Cakupan: backend enforcement + UI display.
- Fallback user tanpa plan: pakai plan gratis/starter aktif dari database.
- Struktur DB: tetap pakai kolom `plans` sekarang, tanpa tabel limits baru.

## Key Changes

- Tambahkan `PlanLimitService` dengan konstanta limit/feature resmi:
  - Limits: `active_channels`, `monthly_messages`, `knowledge_documents`, `file_size_mb`, `faqs`, `chat_history_days`.
  - Features: `leads`, `whatsapp`, `custom_branding`, `analytics`, `priority_support`, `api_access`, `white_label`.
  - AI: `ai_tier`, `allowed_models`.
- API service yang wajib dipakai seluruh sistem:
  - `planFor(User $user): Plan`
  - `limit(User|Plan $subject, string $key): int`
  - `feature(User|Plan $subject, string $key): bool`
  - `featureValue(User|Plan $subject, string $key): mixed`
  - `aiTier(User|Plan $subject): string`
  - `allowsModel(User|Plan $subject, string $model): bool`
  - `check(User $user, string $ability, array $context = []): array`
  - `usage(User $user, string $key, array $context = []): array`
- `check()` mengembalikan shape konsisten:
  - `allowed`, `code`, `message`, `used`, `limit`, `remaining`, `plan`.
  - Tidak throw exception agar bisa dipakai controller, middleware, Livewire, dan API response.

## Implementation Changes

- Refactor enforcement lama agar semua lewat `PlanLimitService`:
  - Quota chat bulanan di `QuotaService`.
  - Feature gate `leads` dan `whatsapp` di middleware.
  - Channel create/activate limit.
  - Knowledge base document count, file size, dan FAQ count untuk widget maupun agent.
  - AI tier/model resolver.
  - Topic analyzer paid-feature check.
- Refactor UI display supaya tidak membaca kolom plan langsung:
  - dashboard/sidebar billing quota,
  - channel index/create,
  - billing page,
  - lead/whatsapp lock state,
  - admin user usage display.
- `Plan` model tetap menjadi representasi database, tapi method seperti `hasFeature()` dan `allowsModel()` diarahkan ke format data yang dipakai service atau dibiarkan hanya sebagai helper internal; business decision tetap di `PlanLimitService`.
- `User::canUseLeads()` dan `User::canUseWhatsApp()` tetap boleh ada untuk kompatibilitas, tapi implementasinya harus delegate ke `PlanLimitService`.
- Admin-only mutation tetap lewat `PlanManager`; validasi admin plan harus memastikan nilai limit valid dan fitur tersimpan ke kolom `plans`/`features`, bukan setting global.

## Test Plan

- Unit test `PlanLimitService`:
  - membaca semua limit dari `plans`,
  - fallback ke starter aktif saat user tanpa plan,
  - feature boolean dan feature value dari kolom/JSON,
  - `allowed_models` dan `ai_tier`,
  - admin tidak otomatis mengubah database limit.
- Feature test enforcement:
  - user tidak bisa create/activate channel melebihi `max_widgets`,
  - chat quota 429 saat `monthly_message_used >= max_messages_per_month`,
  - upload document ditolak saat count/size melebihi plan,
  - FAQ ditolak saat melewati `max_faqs`,
  - lead/whatsapp terkunci sesuai plan.
- UI smoke test:
  - dashboard/sidebar/billing menampilkan angka dari service,
  - plan lock page tetap muncul untuk fitur terkunci,
  - admin Plan Manager bisa mengubah limit lalu perubahan langsung dipakai service.
- Regression test:
  - tidak ada pemakaian langsung `max_widgets`, `max_messages_per_month`, `max_documents`, `max_file_size_mb`, `max_faqs`, `can_use_whatsapp`, `can_export_leads`, atau `ai_tier` di controller/middleware/Livewire selain Plan Manager, seeders, migrations, tests, dan `PlanLimitService`.

## Assumptions

- Starter/free plan aktif wajib ada di database; fallback memilih plan aktif dengan `price = 0`, lalu `sort_order`, lalu `id`.
- Tidak ada migration schema baru kecuali ditemukan kolom `plans` yang hilang di environment tertentu.
- Seeders boleh tetap mendefinisikan default plan awal, tetapi runtime source of truth tetap database `plans`.
- Settings global seperti `max_upload_size_mb` tidak boleh lagi menjadi limit plan user; kalau masih dibutuhkan, hanya sebagai batas teknis maksimum sistem, bukan pembatas tier.
