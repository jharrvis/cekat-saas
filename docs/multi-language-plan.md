# Multi-Language Implementation Plan
## Cekat SaaS - English & Indonesian Localization

---

## 📋 Overview

Implementasi dukungan multi-bahasa (English & Bahasa Indonesia) untuk seluruh web Cekat SaaS.

**Languages:**
- 🇮🇩 Bahasa Indonesia (id) - Default
- 🇬🇧 English (en)

**Estimasi Waktu:** 2-3 hari

---

## 🏗️ Architecture

### 1. Laravel Localization Structure

```
lang/
├── en/
│   ├── auth.php          # Login, register, password
│   ├── pagination.php    # Pagination
│   ├── validation.php    # Form validation
│   ├── messages.php      # Flash messages, alerts
│   ├── dashboard.php     # Dashboard texts
│   ├── widgets.php       # Widget management
│   ├── whatsapp.php      # WhatsApp integration
│   ├── plans.php         # Pricing & plans
│   ├── admin.php         # Admin panel
│   └── common.php        # Common words (Save, Cancel, etc)
├── id/
│   ├── auth.php
│   ├── pagination.php
│   ├── validation.php
│   ├── messages.php
│   ├── dashboard.php
│   ├── widgets.php
│   ├── whatsapp.php
│   ├── plans.php
│   ├── admin.php
│   └── common.php
└── (json files for frontend if needed)
```

### 2. Language Detection Flow

```
User Request
     ↓
┌────────────────────────────┐
│  LocaleMiddleware          │
│  1. Check ?lang=xx param   │
│  2. Check session locale   │
│  3. Check cookie locale    │
│  4. Check browser Accept   │
│  5. Use default (id)       │
└────────────────────────────┘
     ↓
  Set App Locale
     ↓
  Render View with
  translated strings
```

---

## 📝 Implementation Steps

### Phase 1: Setup & Configuration (Day 1 Morning)

#### Step 1.1: Configure Locale Settings

**File:** `config/app.php`
```php
'locale' => 'id',           // Default locale
'fallback_locale' => 'en',  // Fallback if translation missing
'available_locales' => [    // Custom config
    'id' => 'Bahasa Indonesia',
    'en' => 'English',
],
```

#### Step 1.2: Create Locale Middleware

**File:** `app/Http/Middleware/SetLocale.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        // Priority: URL param > Session > Cookie > Browser > Default
        $locale = $request->get('lang') 
            ?? Session::get('locale') 
            ?? $request->cookie('locale')
            ?? $this->getBrowserLocale($request)
            ?? config('app.locale');

        // Validate locale
        $availableLocales = array_keys(config('app.available_locales', ['id', 'en']));
        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale');
        }

        // Set locale
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }

    private function getBrowserLocale($request): ?string
    {
        $browserLocales = $request->getLanguages();
        $availableLocales = array_keys(config('app.available_locales', []));

        foreach ($browserLocales as $browserLocale) {
            $shortLocale = substr($browserLocale, 0, 2);
            if (in_array($shortLocale, $availableLocales)) {
                return $shortLocale;
            }
        }

        return null;
    }
}
```

#### Step 1.3: Register Middleware

**File:** `bootstrap/app.php`
```php
$middleware->web(append: [
    \App\Http\Middleware\SetLocale::class,
]);
```

#### Step 1.4: Create Language Switch Route

**File:** `routes/web.php`
```php
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, array_keys(config('app.available_locales', [])))) {
        Session::put('locale', $locale);
        Cookie::queue('locale', $locale, 60 * 24 * 365); // 1 year
    }
    return redirect()->back();
})->name('lang.switch');
```

---

### Phase 2: Create Language Files (Day 1 Afternoon)

#### Step 2.1: Common Translations

**File:** `lang/en/common.php`
```php
<?php
return [
    // Actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',
    'update' => 'Update',
    'search' => 'Search',
    'filter' => 'Filter',
    'reset' => 'Reset',
    'back' => 'Back',
    'next' => 'Next',
    'previous' => 'Previous',
    'submit' => 'Submit',
    'confirm' => 'Confirm',
    'close' => 'Close',
    'loading' => 'Loading...',
    
    // Status
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
    'connected' => 'Connected',
    'disconnected' => 'Disconnected',
    'success' => 'Success',
    'error' => 'Error',
    'warning' => 'Warning',
    
    // Time
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'this_week' => 'This Week',
    'this_month' => 'This Month',
    'last_30_days' => 'Last 30 Days',
    
    // Misc
    'yes' => 'Yes',
    'no' => 'No',
    'or' => 'or',
    'and' => 'and',
    'all' => 'All',
    'none' => 'None',
    'optional' => 'Optional',
    'required' => 'Required',
];
```

**File:** `lang/id/common.php`
```php
<?php
return [
    // Actions
    'save' => 'Simpan',
    'cancel' => 'Batal',
    'delete' => 'Hapus',
    'edit' => 'Edit',
    'create' => 'Buat',
    'update' => 'Perbarui',
    'search' => 'Cari',
    'filter' => 'Filter',
    'reset' => 'Reset',
    'back' => 'Kembali',
    'next' => 'Selanjutnya',
    'previous' => 'Sebelumnya',
    'submit' => 'Kirim',
    'confirm' => 'Konfirmasi',
    'close' => 'Tutup',
    'loading' => 'Memuat...',
    
    // Status
    'active' => 'Aktif',
    'inactive' => 'Tidak Aktif',
    'pending' => 'Menunggu',
    'connected' => 'Terhubung',
    'disconnected' => 'Terputus',
    'success' => 'Berhasil',
    'error' => 'Error',
    'warning' => 'Peringatan',
    
    // Time
    'today' => 'Hari Ini',
    'yesterday' => 'Kemarin',
    'this_week' => 'Minggu Ini',
    'this_month' => 'Bulan Ini',
    'last_30_days' => '30 Hari Terakhir',
    
    // Misc
    'yes' => 'Ya',
    'no' => 'Tidak',
    'or' => 'atau',
    'and' => 'dan',
    'all' => 'Semua',
    'none' => 'Tidak Ada',
    'optional' => 'Opsional',
    'required' => 'Wajib',
];
```

#### Step 2.2: Module-Specific Files

Create similar translation files for each module:
- `auth.php` - Authentication pages
- `dashboard.php` - Dashboard & stats
- `widgets.php` - Widget management
- `whatsapp.php` - WhatsApp integration
- `plans.php` - Pricing & subscription
- `admin.php` - Admin panel
- `messages.php` - Flash messages & notifications

---

### Phase 3: Create Language Switcher Component (Day 1 Evening)

#### Step 3.1: Blade Component

**File:** `resources/views/components/language-switcher.blade.php`
```blade
@props(['style' => 'dropdown']) {{-- dropdown | flags | text --}}

@php
    $currentLocale = app()->getLocale();
    $locales = config('app.available_locales', ['id' => 'Bahasa Indonesia', 'en' => 'English']);
    $flags = [
        'id' => '🇮🇩',
        'en' => '🇬🇧',
    ];
@endphp

@if($style === 'dropdown')
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-muted transition">
        <span>{{ $flags[$currentLocale] ?? '🌐' }}</span>
        <span class="hidden sm:inline">{{ $locales[$currentLocale] ?? $currentLocale }}</span>
        <i class="fa-solid fa-chevron-down text-xs"></i>
    </button>
    
    <div x-show="open" @click.away="open = false" 
         class="absolute right-0 mt-2 w-48 bg-card border rounded-lg shadow-lg z-50">
        @foreach($locales as $code => $name)
            <a href="{{ route('lang.switch', $code) }}" 
               class="flex items-center gap-2 px-4 py-2 hover:bg-muted transition {{ $code === $currentLocale ? 'bg-primary/10 text-primary' : '' }}">
                <span>{{ $flags[$code] ?? '🌐' }}</span>
                <span>{{ $name }}</span>
                @if($code === $currentLocale)
                    <i class="fa-solid fa-check ml-auto text-primary"></i>
                @endif
            </a>
        @endforeach
    </div>
</div>
@elseif($style === 'flags')
<div class="flex items-center gap-2">
    @foreach($locales as $code => $name)
        <a href="{{ route('lang.switch', $code) }}" 
           class="text-xl opacity-50 hover:opacity-100 transition {{ $code === $currentLocale ? 'opacity-100' : '' }}"
           title="{{ $name }}">
            {{ $flags[$code] ?? '🌐' }}
        </a>
    @endforeach
</div>
@endif
```

#### Step 3.2: Add to Header/Navbar

**File:** `resources/views/layouts/partials/header.blade.php`
```blade
{{-- Add language switcher to navbar --}}
<x-language-switcher style="dropdown" />
```

---

### Phase 4: Update Blade Views (Day 2)

#### Step 4.1: Replace Hardcoded Strings

**Before:**
```blade
<h1>Dashboard</h1>
<p>Welcome back!</p>
<button>Save Changes</button>
```

**After:**
```blade
<h1>{{ __('dashboard.title') }}</h1>
<p>{{ __('dashboard.welcome') }}</p>
<button>{{ __('common.save') }}</button>
```

#### Step 4.2: Files to Update

| Priority | File/Folder | Description |
|----------|-------------|-------------|
| 🔴 High | `layouts/` | Main layouts |
| 🔴 High | `layouts/partials/sidebar.blade.php` | Navigation menu |
| 🔴 High | `layouts/partials/header.blade.php` | Top header |
| 🔴 High | `auth/` | Login, Register, etc |
| 🟡 Medium | `dashboard.blade.php` | Main dashboard |
| 🟡 Medium | `widgets/` | Widget management |
| 🟡 Medium | `whatsapp/` | WhatsApp pages |
| 🟡 Medium | `livewire/` | Livewire components |
| 🟢 Low | `admin/` | Admin panel |
| 🟢 Low | `plans/` | Pricing pages |
| 🟢 Low | `welcome.blade.php` | Landing page |

#### Step 4.3: Livewire Components

For Livewire components, use:
```php
// In component
public function render()
{
    return view('livewire.component', [
        'translations' => [
            'title' => __('module.title'),
            'button' => __('common.save'),
        ],
    ]);
}
```

Or directly in blade:
```blade
{{-- In Livewire blade --}}
<button>{{ __('common.save') }}</button>
```

---

### Phase 5: Dynamic Content (Day 2 Afternoon)

#### Step 5.1: Flash Messages

**File:** `app/helpers.php` or use directly
```php
// Instead of:
return back()->with('success', 'Widget berhasil dibuat!');

// Use:
return back()->with('success', __('widgets.created'));
```

#### Step 5.2: Validation Messages

Laravel automatically uses `lang/{locale}/validation.php` for validation messages.

**File:** `lang/id/validation.php`
```php
<?php
return [
    'required' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'min' => [
        'string' => ':attribute minimal harus :min karakter.',
    ],
    // ... etc
    
    'attributes' => [
        'email' => 'Email',
        'password' => 'Password',
        'name' => 'Nama',
        'phone_number' => 'Nomor Telepon',
    ],
];
```

#### Step 5.3: JavaScript Translations

For frontend JS that needs translations, create a global JS object:

**File:** `resources/views/layouts/app.blade.php`
```blade
<script>
    window.translations = @json([
        'confirm_delete' => __('messages.confirm_delete'),
        'loading' => __('common.loading'),
        'success' => __('common.success'),
        'error' => __('common.error'),
    ]);
    window.locale = '{{ app()->getLocale() }}';
</script>
```

**Usage in JS:**
```javascript
if (confirm(window.translations.confirm_delete)) {
    // delete action
}
```

---

### Phase 6: Testing & QA (Day 3)

#### Step 6.1: Manual Testing Checklist

- [ ] Landing page - both languages
- [ ] Login page
- [ ] Register page
- [ ] Dashboard
- [ ] Widget management (list, create, edit)
- [ ] WhatsApp integration
- [ ] Admin panel
- [ ] Pricing page
- [ ] User profile
- [ ] All flash messages
- [ ] Form validation errors
- [ ] Email templates (if any)

#### Step 6.2: Language Switching Test

- [ ] Dropdown/flags work correctly
- [ ] Preference persists in session
- [ ] Preference persists after logout/login
- [ ] Browser language detection works
- [ ] URL param ?lang=xx works

#### Step 6.3: Consistency Check

- [ ] All UI text translated
- [ ] No mixed languages on same page
- [ ] Date/time formats correct per locale
- [ ] Number formats correct (1,000.00 vs 1.000,00)

---

## 🔧 Additional Configurations

### Date/Time Formatting

**File:** `config/app.php`
```php
'timezone' => 'Asia/Jakarta',
```

**In views:**
```blade
{{-- Date formatting based on locale --}}
{{ $date->translatedFormat('d F Y') }}  {{-- 18 Januari 2026 --}}
{{ $date->isoFormat('LL') }}             {{-- January 18, 2026 --}}
```

### Number Formatting

```blade
{{-- Price formatting --}}
@if(app()->getLocale() === 'id')
    Rp {{ number_format($price, 0, ',', '.') }}
@else
    ${{ number_format($price / 15000, 2) }}  {{-- if showing USD --}}
@endif
```

---

## 📁 File Structure Summary

```
├── app/
│   └── Http/
│       └── Middleware/
│           └── SetLocale.php          # NEW
├── config/
│   └── app.php                         # MODIFY (add available_locales)
├── lang/
│   ├── en/
│   │   ├── auth.php
│   │   ├── common.php                  # NEW
│   │   ├── dashboard.php               # NEW
│   │   ├── widgets.php                 # NEW
│   │   ├── whatsapp.php                # NEW
│   │   ├── plans.php                   # NEW
│   │   ├── admin.php                   # NEW
│   │   ├── messages.php                # NEW
│   │   ├── pagination.php
│   │   └── validation.php
│   └── id/
│       ├── auth.php
│       ├── common.php                  # NEW
│       ├── dashboard.php               # NEW
│       ├── widgets.php                 # NEW
│       ├── whatsapp.php                # NEW
│       ├── plans.php                   # NEW
│       ├── admin.php                   # NEW
│       ├── messages.php                # NEW
│       ├── pagination.php
│       └── validation.php
├── resources/
│   └── views/
│       └── components/
│           └── language-switcher.blade.php  # NEW
├── routes/
│   └── web.php                         # MODIFY (add lang route)
└── bootstrap/
    └── app.php                         # MODIFY (add middleware)
```

---

## ✅ Checklist

### Setup
- [ ] Configure `config/app.php` with available_locales
- [ ] Create `SetLocale` middleware
- [ ] Register middleware in `bootstrap/app.php`
- [ ] Create language switch route

### Translation Files
- [ ] Create `lang/en/` directory with all files
- [ ] Create `lang/id/` directory with all files
- [ ] Translate all strings

### UI Components
- [ ] Create language switcher component
- [ ] Add language switcher to header/navbar

### Update Views
- [ ] Update layouts
- [ ] Update auth pages
- [ ] Update dashboard
- [ ] Update widget pages
- [ ] Update WhatsApp pages
- [ ] Update admin pages
- [ ] Update Livewire components

### Testing
- [ ] Test all pages in both languages
- [ ] Test language persistence
- [ ] Test validation messages
- [ ] Test flash messages

---

## 🔑 Quick Reference

### Translation Syntax

```blade
{{-- Basic --}}
{{ __('file.key') }}

{{-- With parameters --}}
{{ __('messages.welcome', ['name' => $user->name]) }}
// Translation: "Welcome, :name!"

{{-- Pluralization --}}
{{ trans_choice('messages.items', $count) }}
// Translation: "{0} No items|{1} One item|[2,*] :count items"

{{-- Check if translation exists --}}
@if(Lang::has('file.key'))
    {{ __('file.key') }}
@endif

{{-- Get current locale --}}
{{ app()->getLocale() }}  // 'id' or 'en'
```

---

**Created:** January 18, 2026  
**Author:** AI Assistant  
**Version:** 1.0
