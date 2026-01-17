# Perbaikan Tab Analytics - Fitur Berbayar

## Ringkasan
Tab Analytics telah diperbaiki untuk menjadi fitur berbayar yang hanya dapat diakses oleh user dengan plan **Pro** dan **Business**. User dengan plan **Starter (Free)** akan diminta untuk upgrade ketika mencoba mengakses tab ini.

---

## Masalah yang Ditemukan

1. **Logic pengecekan fitur salah**: 
   - Kode lama menggunakan `hasFeature('analytics')` yang tidak sesuai dengan struktur data plan
   - Seharusnya mengecek level analytics: `'basic'` vs `'advanced'`

2. **Deskripsi upgrade kurang spesifik**:
   - Hanya menyebutkan "Business plan"
   - Seharusnya menyebutkan "Pro or Business plan"

---

## Struktur Plan & Analytics

Berdasarkan `DefaultPlansSeeder.php`, struktur analytics di setiap plan:

| Plan | Harga | Analytics Level | Akses Tab Analytics |
|------|-------|----------------|---------------------|
| **Starter** | Gratis | `'basic'` | ❌ Tidak (Locked) |
| **Pro** | Rp 299.000/bulan | `'advanced'` | ✅ Ya (Full Access) |
| **Business** | Rp 799.000/bulan | `'advanced'` | ✅ Ya (Full Access) |

---

## Perbaikan yang Dilakukan

### 1. File: `resources/views/chatbots/tabs/analytics.blade.php`

#### A. Perbaikan Logic Pengecekan (Lines 3-23)

**Sebelum:**
```php
@php
    $isLocked = !optional($chatbot->user->plan)->hasFeature('analytics') ?? true;
    // ...
@endphp
```

**Sesudah:**
```php
@php
    // Check if user has advanced analytics (Pro/Business plans)
    $userPlan = $chatbot->user->plan;
    $analyticsLevel = $userPlan->features['analytics'] ?? 'basic';
    $isLocked = $analyticsLevel !== 'advanced';
    // ...
@endphp
```

**Penjelasan:**
- Mengambil plan user dari chatbot
- Mengecek level analytics dari array `features`
- Lock jika analytics bukan `'advanced'` (berarti `'basic'` atau tidak ada)
- Default ke `'basic'` jika tidak ada setting

#### B. Update Deskripsi Upgrade (Line 25-26)

**Sebelum:**
```blade
<x-feature-locked :locked="$isLocked" feature-name="Advanced Analytics"
    description="Upgrade to Business plan to view detailed conversation insights and usage stats.">
```

**Sesudah:**
```blade
<x-feature-locked :locked="$isLocked" feature-name="Advanced Analytics"
    description="Upgrade to Pro or Business plan to view detailed conversation insights, usage statistics, and engagement metrics.">
```

**Penjelasan:**
- Menyebutkan kedua plan yang memiliki akses (Pro dan Business)
- Deskripsi lebih detail tentang fitur yang didapat

---

## Fitur Analytics yang Ditampilkan

Ketika user memiliki akses (Pro/Business), mereka dapat melihat:

### 1. **Statistics Cards** (3 metrics)
- **Total Conversations**: Jumlah total percakapan sepanjang waktu
- **Messages This Month**: Jumlah pesan bulan ini
- **Avg Messages/Session**: Rata-rata pesan per sesi (engagement rate)

### 2. **Recent Conversations** (5 terakhir)
- Nama visitor
- Waktu percakapan (relative time)
- Jumlah pesan
- Status: Lead atau Visitor

---

## Cara Kerja Feature Lock

Komponen `<x-feature-locked>` akan:

1. **Jika `$isLocked = true` (User Starter)**:
   - Menampilkan overlay blur dengan lock icon
   - Menampilkan card upgrade dengan:
     - Icon lock
     - Judul: "Advanced Analytics Locked"
     - Deskripsi upgrade
     - Tombol "Upgrade Plan" → link ke `/billing`
   - Konten di belakang di-blur dan tidak bisa diklik

2. **Jika `$isLocked = false` (User Pro/Business)**:
   - Tidak ada overlay
   - Konten analytics ditampilkan penuh
   - Semua data real-time dari database

---

## Testing

### Test Case 1: User Starter (Free)
1. Login sebagai user dengan plan Starter
2. Buka chatbot → Edit → Tab Analytics
3. **Expected**: 
   - ✅ Konten di-blur
   - ✅ Muncul overlay "Advanced Analytics Locked"
   - ✅ Tombol "Upgrade Plan" terlihat
   - ✅ Klik tombol redirect ke halaman billing

### Test Case 2: User Pro
1. Login sebagai user dengan plan Pro
2. Buka chatbot → Edit → Tab Analytics
3. **Expected**:
   - ✅ Konten terlihat jelas (tidak blur)
   - ✅ Statistics cards menampilkan data real
   - ✅ Recent conversations terlihat (jika ada data)

### Test Case 3: User Business
1. Login sebagai user dengan plan Business
2. Buka chatbot → Edit → Tab Analytics
3. **Expected**:
   - ✅ Sama seperti User Pro
   - ✅ Semua fitur analytics dapat diakses

---

## File yang Dimodifikasi

1. `resources/views/chatbots/tabs/analytics.blade.php`
   - Perbaikan logic pengecekan analytics level
   - Update deskripsi upgrade message

---

## Konsistensi dengan Tab Lead Collection

Perbaikan ini mengikuti pola yang sama dengan tab **Lead Collection**:

| Aspek | Lead Collection | Analytics |
|-------|----------------|-----------|
| **Komponen** | `<x-feature-locked>` | `<x-feature-locked>` |
| **Pengecekan** | `can_export_leads` (boolean) | `analytics` = `'advanced'` (string) |
| **Plan yang Unlock** | Creator, Business | Pro, Business |
| **Upgrade Message** | "Upgrade to Creator or Business..." | "Upgrade to Pro or Business..." |

---

## Catatan Penting

1. **Analytics 'basic' vs 'advanced'**:
   - `'basic'`: Mungkin untuk fitur analytics sederhana di masa depan
   - `'advanced'`: Full analytics seperti yang ada sekarang

2. **Null Safety**:
   - Kode menggunakan null coalescing operator (`??`) untuk handle user tanpa plan
   - Default ke `'basic'` jika plan tidak ada

3. **Performance**:
   - Query database hanya dijalankan jika `!$isLocked`
   - Menghemat resource untuk user yang tidak punya akses

---

## Rekomendasi Pengembangan Selanjutnya

1. **Basic Analytics untuk Starter**:
   - Tampilkan statistik terbatas (misalnya hanya total conversations)
   - Blur/lock statistik advanced lainnya

2. **Analytics Dashboard Terpisah**:
   - Buat halaman analytics yang lebih komprehensif
   - Tambah grafik, chart, export data

3. **Real-time Updates**:
   - Implementasi Livewire untuk update real-time
   - Auto-refresh setiap X detik

4. **Export Analytics**:
   - Fitur export ke PDF/Excel untuk Business plan
   - Scheduled reports via email
