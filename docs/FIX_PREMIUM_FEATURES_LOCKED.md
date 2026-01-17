# Fix: Lead Collection & Analytics Terkunci untuk User Pro

## Masalah
User dengan plan **Pro** masih mengalami fitur **Lead Collection** dan **Analytics** yang terkunci (locked), padahal seharusnya kedua fitur ini tersedia untuk plan Pro.

## Root Cause
Database plan **tidak memiliki nilai** untuk field `can_export_leads` yang digunakan untuk mengecek akses Lead Collection. Field ini memiliki default value `false` di migration, tapi tidak di-set ke `true` saat seeding plan Pro dan Business.

### Detail Masalah:
1. **Migration** (`2026_01_15_130000_add_ai_tier_to_plans_table.php`):
   - Menambahkan kolom `can_export_leads` dengan default `false`
   - Menambahkan kolom `can_use_whatsapp` dengan default `false`
   - Menambahkan kolom `chat_history_days` dengan default `7`
   - Menambahkan kolom `ai_tier` dengan default `'basic'`

2. **Seeder** (`DefaultPlansSeeder.php`):
   - **TIDAK** mengisi field-field di atas saat create plan
   - Akibatnya semua plan punya nilai default: `can_export_leads = false`

3. **Tab Lead Collection** (`lead.blade.php`):
   - Mengecek: `$isLocked = !$isAdminContext && (!optional($chatbot->user->plan)->can_export_leads);`
   - Karena `can_export_leads = false`, maka `$isLocked = true` → **TERKUNCI**

4. **Tab Analytics** (`analytics.blade.php`):
   - Sudah diperbaiki sebelumnya untuk cek `features['analytics'] === 'advanced'`
   - Tapi karena user Pro belum di-update, masih terkunci juga

## Solusi yang Diterapkan

### 1. Update DefaultPlansSeeder.php
Menambahkan field yang hilang ke setiap plan definition:

#### **Starter Plan:**
```php
'chat_history_days' => 7,
'can_export_leads' => false,
'can_use_whatsapp' => false,
'ai_tier' => 'basic',
```

#### **Pro Plan:**
```php
'chat_history_days' => 30,
'can_export_leads' => true,      // ✅ UNLOCK Lead Collection
'can_use_whatsapp' => false,
'ai_tier' => 'advanced',
```

#### **Business Plan:**
```php
'chat_history_days' => 90,
'can_export_leads' => true,      // ✅ UNLOCK Lead Collection
'can_use_whatsapp' => true,      // ✅ UNLOCK WhatsApp Integration
'ai_tier' => 'premium',
```

### 2. Buat UpdateExistingPlansSeeder.php
Seeder baru untuk update data plan yang sudah ada di database:

```php
// Update Starter plan
Plan::where('slug', 'starter')->update([
    'chat_history_days' => 7,
    'can_export_leads' => false,
    'can_use_whatsapp' => false,
    'ai_tier' => 'basic',
]);

// Update Pro plan
Plan::where('slug', 'pro')->update([
    'chat_history_days' => 30,
    'can_export_leads' => true,
    'can_use_whatsapp' => false,
    'ai_tier' => 'advanced',
]);

// Update Business plan
Plan::where('slug', 'business')->update([
    'chat_history_days' => 90,
    'can_export_leads' => true,
    'can_use_whatsapp' => true,
    'ai_tier' => 'premium',
]);
```

### 3. Jalankan Seeder
```bash
php artisan db:seed --class=UpdateExistingPlansSeeder
```

**Output:**
```
✅ Starter plan updated
✅ Pro plan updated
✅ Business plan updated
✅ All plans updated successfully!
```

## Hasil Setelah Fix

### Tabel Perbandingan Fitur

| Fitur | Starter (Free) | Pro (Rp 299K) | Business (Rp 799K) |
|-------|----------------|---------------|-------------------|
| **Lead Collection** | ❌ Locked | ✅ **Unlocked** | ✅ **Unlocked** |
| **Analytics** | ❌ Locked (basic only) | ✅ **Unlocked** (advanced) | ✅ **Unlocked** (advanced) |
| **WhatsApp Integration** | ❌ Locked | ❌ Locked | ✅ **Unlocked** |
| **Chat History** | 7 hari | 30 hari | 90 hari |
| **AI Tier** | basic | advanced | premium |

## Testing

### Test Lead Collection:
1. Login sebagai user dengan plan **Pro**
2. Buka chatbot → Edit → Tab **Lead Collection**
3. **Expected**: ✅ Konten terlihat jelas, tidak ada overlay lock
4. **Expected**: ✅ Bisa mengatur strategi lead collection

### Test Analytics:
1. Login sebagai user dengan plan **Pro**
2. Buka chatbot → Edit → Tab **Analytics**
3. **Expected**: ✅ Konten terlihat jelas, tidak ada overlay lock
4. **Expected**: ✅ Bisa melihat statistics dan recent conversations

### Test Starter (Free):
1. Login sebagai user dengan plan **Starter**
2. Buka chatbot → Edit → Tab **Lead Collection** atau **Analytics**
3. **Expected**: ❌ Konten di-blur dengan overlay "Locked"
4. **Expected**: ✅ Tombol "Upgrade Plan" terlihat

## File yang Dimodifikasi

1. **`database/seeders/DefaultPlansSeeder.php`**
   - Menambahkan field `can_export_leads`, `can_use_whatsapp`, `chat_history_days`, `ai_tier` ke semua plan

2. **`database/seeders/UpdateExistingPlansSeeder.php`** (NEW)
   - Seeder baru untuk update existing plans di database

## Catatan Penting

### Untuk Development:
- Jika fresh install, cukup jalankan `php artisan migrate:fresh --seed`
- Seeder `DefaultPlansSeeder` sudah lengkap dengan semua field

### Untuk Production:
- **JANGAN** jalankan `migrate:fresh` (akan hapus semua data!)
- Cukup jalankan: `php artisan db:seed --class=UpdateExistingPlansSeeder`
- Atau buat migration untuk update data (lebih aman)

### Field Database yang Penting:
```php
// Boolean fields (untuk feature lock)
can_export_leads    // Lead Collection access
can_use_whatsapp    // WhatsApp Integration access

// String fields
ai_tier             // 'basic', 'advanced', 'premium'

// Integer fields
chat_history_days   // Berapa lama chat history disimpan

// JSON fields
features            // ['analytics' => 'basic'/'advanced', ...]
```

## Rekomendasi Selanjutnya

1. **Buat Migration untuk Update Data**:
   - Lebih aman daripada seeder untuk production
   - Bisa di-track di version control

2. **Tambah Validation**:
   - Pastikan semua field required terisi saat create plan
   - Tambah default value di model

3. **Dokumentasi Plan Features**:
   - Buat dokumentasi lengkap semua fitur per plan
   - Update halaman pricing/billing

4. **Testing Otomatis**:
   - Buat test untuk memastikan feature lock bekerja
   - Test untuk setiap plan tier

## Kesimpulan

✅ **Masalah Resolved**: User Pro sekarang bisa akses Lead Collection dan Analytics
✅ **Data Updated**: Semua plan sudah memiliki field yang benar
✅ **Seeder Fixed**: Plan baru akan otomatis punya field yang lengkap
✅ **Ready for Testing**: Silakan test dengan user Pro dan Starter
