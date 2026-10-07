<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('general.s.halaman_tidak_ditemukan_cekat_biz_id') }}</title>
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
                <a href="/" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
                        <i data-lucide="bot" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Cekat<span class="text-brand-600 dark:text-brand-400">.biz.id</span></span>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-1 flex items-center justify-center px-4 py-16 relative">
        <div class="absolute inset-x-0 top-0 h-96 bg-gradient-to-b from-brand-50/50 to-transparent dark:from-brand-950/20 dark:to-transparent pointer-events-none"></div>
        <div class="relative text-center max-w-xl">
            <div class="w-20 h-20 mx-auto rounded-2xl bg-brand-600/10 dark:bg-brand-400/10 flex items-center justify-center mb-6">
                <i data-lucide="search-x" class="w-10 h-10 text-brand-600 dark:text-brand-400"></i>
            </div>
            <p class="text-7xl font-extrabold tracking-tight text-slate-900 dark:text-white mb-3">404</p>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">{{ __('general.s.halaman_tidak_ditemukan') }}</h1>
            <p class="text-gray-600 dark:text-gray-300 mb-8">
                {{ __('general.s.maaf_halaman_yang_anda_cari_tidak_ada_atau_sudah') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="/" class="px-5 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-semibold text-sm shadow-lg shadow-brand-500/25 transition">
                    <i data-lucide="home" class="w-4 h-4 inline -mt-0.5 mr-1"></i> {{ __('general.s.ke_beranda') }}
                </a>
                <a href="/#harga" class="px-5 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-brand-500 text-slate-800 dark:text-slate-100 rounded-xl font-semibold text-sm transition">
                    <i data-lucide="tags" class="w-4 h-4 inline -mt-0.5 mr-1"></i> {{ __('general.s.lihat_harga') }}
                </a>
                <a href="{{ route('api-keys.index') }}" class="px-5 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 hover:border-brand-500 text-slate-800 dark:text-slate-100 rounded-xl font-semibold text-sm transition">
                    <i data-lucide="book-open" class="w-4 h-4 inline -mt-0.5 mr-1"></i> {{ __('general.s.dokumentasi_api') }}
                </a>
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
        &copy; {{ date('Y') }} Cekat.biz.id — AI Customer Service untuk bisnis Indonesia.
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
