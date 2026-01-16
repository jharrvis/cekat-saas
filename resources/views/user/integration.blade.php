@extends('layouts.dashboard')

@section('title', 'Integrasi')

@section('content')
    <div class="space-y-6 max-w-4xl">
        {{-- Header --}}
        <div>
            <h2 class="text-2xl font-bold tracking-tight">Integrasi Chatbot</h2>
            <p class="text-muted-foreground mt-1">Pasang chatbot di website Anda dengan mudah</p>
        </div>

        @if($widgets->count() > 0)
            {{-- Widget Selector --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <h3 class="font-semibold text-lg mb-4">Pilih Chatbot</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($widgets as $widget)
                        <button onclick="selectWidget('{{ $widget->slug }}', '{{ $widget->display_name ?? $widget->name }}')"
                            class="widget-btn p-4 border rounded-lg text-left hover:border-primary hover:bg-primary/5 transition"
                            data-slug="{{ $widget->slug }}">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                                    style="background-color: {{ $widget->settings['color'] ?? '#3b82f6' }}">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div>
                                    <p class="font-medium">{{ $widget->display_name ?? $widget->name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $widget->slug }}</p>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Embed Code --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm" id="embed-section" style="display: none;">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg">Kode Embed untuk: <span id="widget-name" class="text-primary"></span></h3>
                    <button onclick="copyCode()"
                        class="px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                        <i class="fa-solid fa-copy mr-1"></i> Salin Kode
                    </button>
                </div>

                <div class="bg-muted rounded-lg p-4 font-mono text-sm overflow-x-auto">
                    <pre id="embed-code" class="text-muted-foreground"></pre>
                </div>

                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <i class="fa-solid fa-info-circle mr-2"></i>
                        Tempel kode di atas sebelum tag <code
                            class="bg-blue-100 dark:bg-blue-900 px-1 rounded">&lt;/body&gt;</code> pada halaman website Anda.
                    </p>
                </div>
            </div>

            {{-- WordPress Plugin --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-[#21759b] rounded-lg flex items-center justify-center text-white">
                        <i class="fa-brands fa-wordpress text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg">WordPress Plugin</h3>
                        <p class="text-sm text-muted-foreground">Pasang chatbot dengan plugin WordPress resmi</p>
                    </div>
                    <a href="#" class="px-4 py-2 bg-[#21759b] text-white rounded-lg hover:bg-[#1a5f7a] transition">
                        <i class="fa-solid fa-download mr-2"></i>Download Plugin
                    </a>
                </div>
            </div>

            {{-- Other Platforms --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <h3 class="font,semibold text-lg mb-4">Platform Lainnya</h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 border rounded-lg text-center hover:border-primary transition cursor-pointer">
                        <i class="fa-brands fa-shopify text-3xl text-[#96bf48] mb-2"></i>
                        <p class="text-sm font-medium">Shopify</p>
                    </div>
                    <div class="p-4 border rounded-lg text-center hover:border-primary transition cursor-pointer">
                        <i class="fa-brands fa-wix text-3xl text-[#000]  mb-2"></i>
                        <p class="text-sm font-medium">Wix</p>
                    </div>
                    <div class="p-4 border rounded-lg text-center hover:border-primary transition cursor-pointer">
                        <i class="fa-brands fa-squarespace text-3xl text-[#000] mb-2"></i>
                        <p class="text-sm font-medium">Squarespace</p>
                    </div>
                    <div class="p-4 border rounded-lg text-center hover:border-primary transition cursor-pointer">
                        <i class="fa-solid fa-code text-3xl text-primary mb-2"></i>
                        <p class="text-sm font-medium">Custom HTML</p>
                    </div>
                </div>
            </div>

        @else
            {{-- No Widgets --}}
            <div class="bg-card text-card-foreground p-12 rounded-xl border shadow-sm text-center">
                <i class="fa-solid fa-robot text-6xl text-muted-foreground mb-4"></i>
                <h3 class="font-semibold text-lg mb-2">Belum Ada Chatbot</h3>
                <p class="text-muted-foreground mb-4">Buat chatbot terlebih dahulu untuk mendapatkan kode embed</p>
                <a href="{{ route('chatbots.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg">
                    <i class="fa-solid fa-plus"></i> Buat Chatbot
                </a>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            let currentCode = '';

            function selectWidget(slug, name) {
                // Update UI
                document.querySelectorAll('.widget-btn').forEach(btn => {
                    btn.classList.remove('border-primary', 'bg-primary/5');
                });
                document.querySelector(`[data-slug="${slug}"]`).classList.add('border-primary', 'bg-primary/5');

                // Show embed section
                document.getElementById('embed-section').style.display = 'block';
                document.getElementById('widget-name').textContent = name;

                // Generate embed code - using minified widget
                const baseUrl = '{{ config("app.url") }}';
                currentCode = `<!-- Cekat.biz.id Chatbot Widget -->
        <script>
            window.CSAIConfig = {
                widgetId: '${slug}'
            };
        <\/script>
        <script src="${baseUrl}/widget/widget.min.js" async><\/script>`;

                document.getElementById('embed-code').textContent = currentCode;
            }

            function copyCode() {
                if (!currentCode) {
                    alert('Pilih chatbot terlebih dahulu');
                    return;
                }

                // Use fallback for older browsers
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(currentCode).then(() => {
                        showCopySuccess();
                    }).catch(err => {
                        fallbackCopy();
                    });
                } else {
                    fallbackCopy();
                }
            }

            function fallbackCopy() {
                const textarea = document.createElement('textarea');
                textarea.value = currentCode;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    showCopySuccess();
                } catch (err) {
                    alert('Gagal menyalin kode. Silakan salin manual.');
                }
                document.body.removeChild(textarea);
            }

            function showCopySuccess() {
                const btn = document.querySelector('button[onclick="copyCode()"]');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Tersalin!';
                btn.classList.add('bg-emerald-500');
                btn.classList.remove('bg-primary');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('bg-emerald-500');
                    btn.classList.add('bg-primary');
                }, 2000);
            }
        </script>
    @endpush
@endsection