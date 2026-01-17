# Troubleshooting: Analytics Tab Masih Terkunci untuk User Pro

## Langkah-langkah Debugging

### 1. Cek Data Plan di Database

Buka browser dan akses:
```
http://localhost/debug-plans
```
atau
```
http://cekat.biz.id/debug-plans
```

**Yang harus dicek:**
- ✅ Plan "Pro" harus punya `can_export_leads: TRUE` (hijau)
- ✅ Plan "Pro" harus punya `ai_tier: advanced`
- ✅ Plan "Pro" harus punya `features.analytics: "advanced"`

**Jika masih FALSE/salah**, jalankan lagi seeder:
```bash
php artisan db:seed --class=UpdateExistingPlansSeeder
```

---

### 2. Cek User Pro

Di halaman debug yang sama, cek bagian "Pro Plan Users":
- ✅ Pastikan user yang login memang terdaftar sebagai Pro user
- ✅ Pastikan `plan_id` user mengarah ke plan Pro yang benar

**Jika user tidak muncul**, berarti user belum di-assign ke plan Pro. Update manual:
```sql
-- Cek ID plan Pro
SELECT id, slug, name FROM plans WHERE slug = 'pro';

-- Update user (ganti USER_ID dan PLAN_ID)
UPDATE users SET plan_id = PLAN_ID WHERE id = USER_ID;
```

---

### 3. Clear All Cache

Setelah update data, **WAJIB** clear semua cache:
```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

---

### 4. Test Analytics Tab

1. **Logout** dari aplikasi
2. **Login** kembali dengan user Pro
3. Buka chatbot → Edit → Tab **Analytics**
4. Refresh halaman (Ctrl+F5) untuk force reload

**Expected Result:**
- ✅ Konten terlihat jelas (tidak blur)
- ✅ Tidak ada overlay "Locked"
- ✅ Bisa melihat statistics cards
- ✅ Bisa melihat recent conversations

---

## Kemungkinan Masalah & Solusi

### Masalah 1: Data Plan Belum Ter-update
**Gejala:** Di `/debug-plans`, plan Pro masih `can_export_leads: FALSE`

**Solusi:**
```bash
php artisan db:seed --class=UpdateExistingPlansSeeder
```

---

### Masalah 2: User Belum Ter-assign ke Plan Pro
**Gejala:** User tidak muncul di "Pro Plan Users" atau plan_id = NULL

**Solusi via Tinker:**
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'email@user.com')->first();
$proPlan = App\Models\Plan::where('slug', 'pro')->first();
$user->plan_id = $proPlan->id;
$user->save();
exit
```

---

### Masalah 3: Cache Belum Di-clear
**Gejala:** Sudah update data tapi masih terkunci

**Solusi:**
```bash
php artisan optimize:clear
```
atau
```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

Kemudian **logout dan login** kembali.

---

### Masalah 4: Session Lama Masih Aktif
**Gejala:** Masih terkunci meskipun sudah clear cache

**Solusi:**
1. Logout dari aplikasi
2. Clear browser cache (Ctrl+Shift+Del)
3. Close semua tab browser
4. Buka browser baru
5. Login kembali

---

### Masalah 5: Field `features` Kosong/NULL
**Gejala:** Di debug, `features: null` atau `features: {}`

**Solusi via Tinker:**
```bash
php artisan tinker
```
```php
$proPlan = App\Models\Plan::where('slug', 'pro')->first();
$proPlan->features = [
    'custom_branding' => true,
    'analytics' => 'advanced',
    'priority_support' => false,
    'api_access' => false,
];
$proPlan->save();
exit
```

---

## Checklist Debugging

Gunakan checklist ini untuk memastikan semua sudah benar:

### Database:
- [ ] Plan Pro ada di database
- [ ] Plan Pro punya `can_export_leads = 1` (true)
- [ ] Plan Pro punya `ai_tier = 'advanced'`
- [ ] Plan Pro punya `features->analytics = 'advanced'`

### User:
- [ ] User Pro ter-assign ke plan Pro (`plan_id` benar)
- [ ] User bisa login
- [ ] User punya chatbot

### Cache:
- [ ] Route cache di-clear
- [ ] View cache di-clear
- [ ] Config cache di-clear
- [ ] Browser cache di-clear

### Testing:
- [ ] Logout dan login kembali
- [ ] Akses tab Analytics
- [ ] Konten tidak blur
- [ ] Tidak ada overlay lock

---

## Kode Analytics Tab (Reference)

File: `resources/views/chatbots/tabs/analytics.blade.php`

```php
@php
    // Check if user has advanced analytics (Pro/Business plans)
    $userPlan = $chatbot->user->plan;
    
    // Safely get analytics level from features JSON
    $features = $userPlan ? ($userPlan->features ?? []) : [];
    $analyticsLevel = is_array($features) ? ($features['analytics'] ?? 'basic') : 'basic';
    
    // Lock if not advanced
    $isLocked = $analyticsLevel !== 'advanced';
@endphp
```

**Logic:**
1. Ambil plan user dari chatbot
2. Ambil `features` dari plan (JSON field)
3. Ambil `analytics` dari features
4. Lock jika `analytics !== 'advanced'`

---

## SQL Query untuk Manual Check

```sql
-- Cek semua plans
SELECT id, slug, name, can_export_leads, ai_tier, features 
FROM plans;

-- Cek user Pro
SELECT u.id, u.name, u.email, u.plan_id, p.name as plan_name, p.can_export_leads
FROM users u
LEFT JOIN plans p ON u.plan_id = p.id
WHERE p.slug = 'pro';

-- Update plan Pro jika perlu
UPDATE plans 
SET can_export_leads = 1, 
    ai_tier = 'advanced',
    features = '{"custom_branding":true,"analytics":"advanced","priority_support":false,"api_access":false}'
WHERE slug = 'pro';
```

---

## Hapus Debug Route Setelah Selesai

**PENTING:** Setelah debugging selesai, hapus route debug dari `routes/web.php`:

Hapus baris ini:
```php
// DEBUG ROUTE - Remove after debugging
Route::get('/debug-plans', function () {
    // ... kode debug ...
});
```

Atau comment out:
```php
// Route::get('/debug-plans', function () { ... });
```

---

## Kontak Support

Jika masih bermasalah setelah mengikuti semua langkah di atas:
1. Screenshot halaman `/debug-plans`
2. Screenshot tab Analytics yang masih locked
3. Kirim ke developer untuk investigasi lebih lanjut
