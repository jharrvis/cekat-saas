{{-- Allowed Domains Tab --}}
<div>
    <h3 class="text-lg font-bold mb-4">Domain yang Diizinkan</h3>
    <p class="text-muted-foreground mb-6">Batasi website mana saja yang boleh memuat channel ini.</p>

    <form action="{{ route('channels.update', $chatbot->id) }}" method="POST" class="max-w-2xl space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" value="domains">

        @php $isEmpty = trim($chatbot->settings['allowed_domains'] ?? '') === ''; @endphp
        @if ($isEmpty)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <p class="text-sm text-red-700 dark:text-red-300">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    <strong>Tanpa pembatasan domain.</strong>
                    Widget ini saat ini bisa dipasang dan dipakai dari situs mana pun
                    (orang lain bisa menyalin kode embed dan memakai chatbot Anda).
                    Isi daftar domain agar aman.
                </p>
            </div>
        @endif

        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <label for="allowed_domains" class="block text-sm font-medium mb-2 flex items-center">
                <i class="fa-solid fa-shield-halved text-amber-600 mr-2"></i>
                Allowed Domains
                <x-help-tooltip text="Pisahkan beberapa domain dengan koma. Contoh: mysite.com, toko.id" />
            </label>
            <input type="text" id="allowed_domains" name="allowed_domains"
                value="{{ old('allowed_domains', $chatbot->settings['allowed_domains'] ?? '') }}"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
                placeholder="mis. mysite.com, toko.id">
            @error('allowed_domains') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <div class="mt-2 text-xs text-amber-700 dark:text-amber-300 space-y-1">
                <p><i class="fa-solid fa-info-circle mr-1"></i> <strong>1 widget = 1 domain</strong> (termasuk subdomain)</p>
                <p><i class="fa-solid fa-check mr-1"></i> <code class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">mysite.com</code> → izin <code class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">www.mysite.com</code>, <code class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">blog.mysite.com</code></p>
                <p><i class="fa-solid fa-flask mr-1"></i> <code class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">localhost</code> dan <code class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">127.0.0.1</code> selalu diizinkan untuk testing.</p>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit"
                class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-save mr-2"></i> Simpan Domain
            </button>
        </div>
    </form>
</div>
