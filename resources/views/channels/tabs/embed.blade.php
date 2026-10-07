@php
    // Generate embed code for the chatbot widget
    $url = config('app.url');
    $widgetSlug = $chatbot->slug;

    $embedCode = "<!-- Cekat AI Chatbot Widget -->\n" .
        "<script>\n" .
        "  window.CSAIConfig = {\n" .
        "    widgetId: '{$widgetSlug}'\n" .
        "  };\n" .
        "</script>\n" .
        "<script src=\"{$url}/widget/widget.min.js?v=20260930-p6\" async></script>";
@endphp

<div>
    <h3 class="text-lg font-bold mb-2">{{ __('channels.s.embed_code') }}</h3>
    <p class="text-muted-foreground mb-6">{{ __('channels.s.copy_and_paste_this_code_to_install_the_chatbot') }}</p>

    @if(($chatbot->status ?? 'draft') !== 'active' || !$chatbot->is_active)
        <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl mb-4 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            {{ __('channels.s.channel_masih_berstatus') }} <strong>{{ ucfirst($chatbot->status ?? 'draft') }}</strong> {{ __('channels.s.widget') }} <strong>{{ __('channels.s.belum_akan_tampil') }}</strong> {{ __('channels.s.di_website_sampai_status_diubah_ke') }}
            <strong>{{ __('channels.s.active') }}</strong> {{ __('channels.s.pada_tab') }}
            <a href="{{ route('channels.edit.tab', [$chatbot->id, 'general']) }}" class="underline font-semibold">{{ __('channels.s.umum') }}</a>.
        </div>
    @endif

    <div class="bg-card rounded-xl shadow-sm border p-6 max-w-3xl">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-code text-primary"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-semibold mb-1">{{ __('channels.s.installation_instructions') }}</h4>
                <p class="text-sm text-muted-foreground">
                    Copy the code below and paste it just before the closing <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">&lt;/body&gt;</code> tag in your website's HTML.
                </p>
            </div>
        </div>

        <div class="relative">
            <pre id="embed-code-block" class="bg-slate-900 text-slate-100 p-4 rounded-lg overflow-x-auto text-xs leading-relaxed"><code>{{ $embedCode }}</code></pre>
            <button onclick="copyEmbedCode()" id="copy-btn"
                class="absolute top-2 right-2 bg-slate-700 hover:bg-slate-600 text-white px-3 py-1.5 rounded text-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-copy" id="copy-icon"></i>
                <span id="copy-text">{{ __('channels.s.salin') }}</span>
            </button>
        </div>

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded-lg text-sm border border-blue-100 dark:border-blue-800 flex gap-3">
            <i class="fa-solid fa-shield-halved mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="font-medium">{{ __('channels.s.keamanan_domain') }}</p>
                <p class="mt-1 opacity-90">
                    {{ __('channels.s.demi_keamanan_pastikan_domain_situs_anda_terdaft') }}
                    <a href="{{ route('channels.edit.tab', [$chatbot->id, 'general']) }}"
                        class="underline hover:text-blue-900 dark:hover:text-blue-100 font-medium">{{ __('channels.s.tab_umum') }}</a>{{ __('channels.s.jika_tidak_widget_tidak_akan_dimuat_di_situs_and') }}
                </p>
            </div>
        </div>

        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded-lg text-sm border border-green-100 dark:border-green-800 flex gap-3">
            <i class="fa-solid fa-lightbulb mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="font-medium mb-2">{{ __('channels.s.tips_cepat') }}</p>
                <ul class="space-y-1 opacity-90 list-disc list-inside">
                    <li>{{ __('channels.s.widget_otomatis_dimuat_di_semua_halaman_tempat_k') }}</li>
                    <li>{{ __('channels.s.anda_dapat_menyesuaikan_tampilan_di') }} <a href="{{ route('channels.edit.tab', [$chatbot->id, 'widget']) }}" class="underline hover:text-green-900 dark:hover:text-green-100 font-medium">{{ __('channels.s.tab_tampilan') }}</a></li>
                    <li>{{ __('channels.s.uji_widget_anda_sebelum_dipasang_di_situs_produk') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyEmbedCode() {
    const codeText = `{{ addslashes($embedCode) }}`;
    const copyBtn = document.getElementById('copy-btn');
    const copyIcon = document.getElementById('copy-icon');
    const copyText = document.getElementById('copy-text');
    
    navigator.clipboard.writeText(codeText).then(() => {
        // Change button appearance
        copyBtn.classList.remove('bg-slate-700', 'hover:bg-slate-600');
        copyBtn.classList.add('bg-green-600', 'hover:bg-green-700');
        copyIcon.className = 'fa-solid fa-check';
        copyText.textContent = 'Copied!';
        
        // Reset after 2 seconds
        setTimeout(() => {
            copyBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            copyBtn.classList.add('bg-slate-700', 'hover:bg-slate-600');
            copyIcon.className = 'fa-solid fa-copy';
            copyText.textContent = 'Copy';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy code. Please copy manually.');
    });
}
</script>
@endpush
