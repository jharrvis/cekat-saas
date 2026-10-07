@extends('layouts.dashboard')

@section('title', __('api.s.api_keys'))

@section('content')
    <div class="space-y-6 max-w-4xl">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">{{ __('api.s.api_keys') }}</h2>
                <p class="text-muted-foreground mt-1">
                    Akses data leads, riwayat chat, dan statistik secara programatik melalui
                    <code class="px-1.5 py-0.5 bg-muted rounded text-sm">/api/v1</code>.
                    Baca <a href="{{ route('docs.api') }}" target="_blank" class="text-primary underline">{{ __('api.s.dokumentasi_api') }}</a>
                    {{ __('api.s.untuk_detail_endpoint') }}
                </p>
            </div>
        </div>

        @if (session('plain_key'))
            {{-- Secret is shown exactly once, right after creation --}}
            <div class="bg-amber-50 border border-amber-300 text-amber-900 p-4 rounded-xl">
                <p class="font-semibold mb-1">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Salin secret API key "{{ session('new_key_name') }}" sekarang
                </p>
                <p class="text-sm mb-3">{{ __('api.s.secret_ini') }} <strong>{{ __('api.s.tidak_akan_ditampilkan_lagi') }}</strong> {{ __('api.s.setelah_anda_meninggalkan_halaman_ini') }}</p>
                <div class="flex items-center gap-2">
                    <code id="plain-api-key"
                        class="flex-1 bg-white border px-3 py-2 rounded-lg text-sm font-mono break-all">{{ session('plain_key') }}</code>
                    <button type="button" onclick="copyApiKey()"
                        class="px-3 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 text-sm whitespace-nowrap">
                        <i class="fa-regular fa-copy mr-1"></i>{{ __('channels.s.salin') }}
                    </button>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-xl text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Create --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold text-lg mb-1">{{ __('api.s.buat_api_key_baru') }}</h3>
            <p class="text-sm text-muted-foreground mb-4">{{ __('api.s.beri_nama_yang_jelas_per_integrasi_mis_zapier_cr') }}</p>

            <form action="{{ route('api-keys.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" maxlength="60" required
                    placeholder="{{ __('api.s.nama_integrasi_mis_zapier') }}"
                    class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <button type="submit"
                    class="px-5 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 whitespace-nowrap">
                    <i class="fa-solid fa-key mr-1"></i>{{ __('api.s.buat_key') }}
                </button>
            </form>

            @error('name')
                <p class="text-sm text-destructive mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- List --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold text-lg mb-4">{{ __('api.s.key_aktif') }}</h3>

            @forelse ($keys as $key)
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 py-3 border-b last:border-b-0 last:pb-0 first:pt-0">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium truncate">{{ $key->name }}</p>
                        <p class="text-sm text-muted-foreground font-mono">{{ $key->key_prefix }}…</p>
                    </div>
                    <div class="text-sm text-muted-foreground space-y-0.5 sm:text-right">
                        <p>{{ __('api.s.dibuat') }} <span class="text-foreground">{{ $key->created_at->format('d M Y H:i') }}</span></p>
                        <p>{{ __('api.s.dipakai') }} <span class="text-foreground">{{ $key->last_used_at?->diffForHumans() ?? 'belum pernah' }}</span></p>
                    </div>
                    <div class="flex items-center gap-3 sm:justify-end">
                        @if ($key->revoked_at)
                            <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">{{ __('api.s.dicabut') }}</span>
                        @elseif ($key->expires_at && $key->expires_at->isPast())
                            <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700">{{ __('api.s.kedaluwarsa') }}</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">{{ __('api.s.aktif') }}</span>
                            <form action="{{ route('api-keys.destroy', $key) }}" method="POST"
                                onsubmit="return confirm('Cabut API key ini? Integrasi yang memakainya akan langsung berhenti.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-destructive hover:underline">{{ __('api.s.cabut') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted-foreground">{{ __('api.s.belum_ada_api_key_buat_key_pertama_anda_di_atas') }}</p>
            @endforelse
        </div>

        {{-- Quick start --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold text-lg mb-3">{{ __('api.s.contoh_pemakaian') }}</h3>
            <pre class="bg-slate-900 text-slate-100 text-sm p-4 rounded-lg overflow-x-auto"><code>curl -H "Authorization: Bearer ck_live_..." \
  https://cekat.biz.id/api/v1/leads</code></pre>
            <p class="text-sm text-muted-foreground mt-3">{{ __('api.s.rate_limit_120_request_menit_per_key_data_yang_t') }}</p>
        </div>
    </div>

    <script>
        function copyApiKey() {
            const code = document.getElementById('plain-api-key').textContent.trim();
            navigator.clipboard.writeText(code).then(() => {
                alert('API key disalin ke clipboard.');
            });
        }
    </script>
@endsection
