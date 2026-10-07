{{--
    T-10: Halaman Kebijakan Privasi publik.
    CATATAN PEMILIK: teks ini adalah templat awal yang ditulis sesuai
    perilaku sistem yang sebenarnya (data yang memang dikumpulkan aplikasi).
    Tinjau dan sesuaikan dengan kebutuhan hukum bisnis sebelum dianggap final.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('legal.s.kebijakan_privasi_biz_id') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4',
                            400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e',
                            800: '#115e59', 900: '#134e4a', 950: '#042f2e',
                        }
                    }
                }
            }
        }
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-slate-950 dark:text-gray-100 antialiased selection:bg-brand-500 selection:text-white min-h-screen flex flex-col">
    <nav class="w-full bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <a href="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
                        <i data-lucide="bot" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Cekat<span class="text-brand-600 dark:text-brand-400">.biz.id</span></span>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-1 px-4 py-12">
        <div class="max-w-5xl mx-auto">
            <header class="mb-10">
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ __('legal.s.kebijakan_privasi') }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ __('legal.s.terakhir_diperbarui') }}</span>
                    <span aria-hidden="true">•</span>
                    <span>{{ __('legal.s.versi_dokumen') }}</span>
                    <span aria-hidden="true">•</span>
                    <span>{{ __('legal.s.dokumen_pendamping') }}: <a href="{{ route('legal.terms') }}" class="text-brand-600 dark:text-brand-400 hover:underline">{{ __('legal.s.syarat_ketentuan') }}</a></span>
                </div>
                <p class="mt-5 text-gray-600 dark:text-gray-300 leading-relaxed">{{ __('legal.s.priv_intro') }}</p>
            </header>

            <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-10">
                <aside class="mb-8 lg:mb-0">
                    <div class="lg:sticky lg:top-8 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">{{ __('legal.s.daftar_isi') }}</h2>
                        <ul class="text-sm text-gray-600 dark:text-gray-300">
                        <li><a href="#pasal-1" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_1_title') }}</a></li>
                        <li><a href="#pasal-2" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_2_title') }}</a></li>
                        <li><a href="#pasal-3" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_3_title') }}</a></li>
                        <li><a href="#pasal-4" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_4_title') }}</a></li>
                        <li><a href="#pasal-5" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_5_title') }}</a></li>
                        <li><a href="#pasal-6" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_6_title') }}</a></li>
                        <li><a href="#pasal-7" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_7_title') }}</a></li>
                        <li><a href="#pasal-8" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_8_title') }}</a></li>
                        <li><a href="#pasal-9" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_9_title') }}</a></li>
                        <li><a href="#pasal-10" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_10_title') }}</a></li>
                        <li><a href="#pasal-11" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_11_title') }}</a></li>
                        <li><a href="#pasal-12" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_12_title') }}</a></li>
                        <li><a href="#pasal-13" class="block py-1 hover:text-brand-600 dark:hover:text-brand-400">{{ __('legal.s.priv_13_title') }}</a></li>
                        </ul>
                    </div>
                </aside>

                <article class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6 md:p-9 text-gray-700 dark:text-gray-300 leading-relaxed [&_p]:mt-2 [&_.legal-list_ul]:list-disc [&_.legal-list_ul]:pl-5 [&_.legal-list_li]:mt-1.5">
                <section id="pasal-1" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_1_title') }}</h2>
                    <p>{{ __('legal.s.priv_1_body') }}</p>
                </section>
                <section id="pasal-2" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_2_title') }}</h2>
                    <div class="legal-list">{!! __('legal.s.priv_2_list') !!}</div>
                </section>
                <section id="pasal-3" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_3_title') }}</h2>
                    <div class="legal-list">{!! __('legal.s.priv_3_list') !!}</div>
                </section>
                <section id="pasal-4" class="scroll-mt-24 mt-10">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_4_title') }}</h2>
                    <div class="legal-list">{!! __('legal.s.priv_4_list') !!}</div>
                    <p class="mt-3">{{ __('legal.s.priv_4_body') }}</p>
                </section>
                <section id="pasal-5" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_5_title') }}</h2>
                    <p>{{ __('legal.s.priv_5_body') }}</p>
                </section>
                <section id="pasal-6" class="scroll-mt-24 mt-10">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_6_title') }}</h2>
                    <p>{{ __('legal.s.priv_6_body') }}</p>
                    <div class="legal-list mt-2">{!! __('legal.s.priv_6_list') !!}</div>
                </section>
                <section id="pasal-7" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_7_title') }}</h2>
                    <p>{{ __('legal.s.priv_7_body') }}</p>
                </section>
                <section id="pasal-8" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_8_title') }}</h2>
                    <p>{{ __('legal.s.priv_8_body') }}</p>
                </section>
                <section id="pasal-9" class="scroll-mt-24 mt-10">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_9_title') }}</h2>
                    <div class="legal-list">{!! __('legal.s.priv_9_list') !!}</div>
                    <p class="mt-3">{{ __('legal.s.priv_9_body') }}</p>
                </section>
                <section id="pasal-10" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_10_title') }}</h2>
                    <p>{{ __('legal.s.priv_10_body') }}</p>
                </section>
                <section id="pasal-11" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_11_title') }}</h2>
                    <p>{{ __('legal.s.priv_11_body') }}</p>
                </section>
                <section id="pasal-12" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_12_title') }}</h2>
                    <p>{{ __('legal.s.priv_12_body') }}</p>
                </section>
                <section id="pasal-13" class="scroll-mt-24 mt-10 first:mt-0">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ __('legal.s.priv_13_title') }}</h2>
                    <p>{{ __('legal.s.priv_13_body') }}</p>
                    <p class="mt-2 font-medium">{{ __('legal.s.priv_kontak') }}</p>
                </section>
                </article>
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-slate-800">
        &copy; {{ date('Y') }} Cekat.biz.id — <a href="{{ route('legal.terms') }}" class="hover:text-brand-600">{{ __('legal.s.syarat_ketentuan') }}</a>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
