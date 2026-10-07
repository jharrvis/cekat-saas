<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.s.masuk_cekat_biz_id_ai_chatbot_kustom') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#1e1b4b',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans min-h-screen m-0 p-0 overflow-x-hidden transition-colors duration-300">

    <!-- Floating Controls -->
    <div class="absolute top-6 right-6 z-50 flex items-center gap-3">
        <button onclick="toggleTheme()" class="p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shadow-lg" title="{{ __('auth.s.ganti_tema') }}">
            <i data-lucide="sun" class="w-4 h-4 hidden dark:block"></i>
            <i data-lucide="moon" class="w-4 h-4 block dark:hidden"></i>
        </button>
        <a href="/" class="px-3.5 py-2.5 rounded-xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center gap-2 shadow-lg">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> {{ __('auth.s.beranda') }}
        </a>
    </div>

    <main class="w-screen min-h-screen grid lg:grid-cols-12 m-0 p-0">

        <!-- Kolom Kiri: Branding -->
        <div class="lg:col-span-5 bg-slate-900 dark:bg-slate-950 p-8 sm:p-12 lg:p-16 flex flex-col justify-between text-white relative overflow-hidden border-r border-slate-800">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-30"></div>
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_30%,rgba(99,102,241,0.15),transparent_50%)]"></div>

            <!-- Logo & Brand -->
            <div class="relative z-10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white shadow-lg shadow-brand-500/30 ring-1 ring-brand-400/30">
                    <i data-lucide="bot" class="w-5 h-5"></i>
                </div>
                <span class="font-bold text-xl tracking-tight text-white">Cekat<span class="text-brand-400">.biz.id</span></span>
            </div>

            <!-- Pesan Utama -->
            <div class="relative z-10 my-auto py-12">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-300 text-xs font-medium mb-6 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>{{ __('auth.s.neural_network_engine_v4_2') }}</div>

                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-4 leading-tight">
                    {{ __('auth.s.otomatisasi_layanan_pelanggan_dengan') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-indigo-300 to-sky-400">{{ __('auth.s.data_anda_sendiri') }}</span>.
                </h1>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">{{ __('auth.s.unggah_dokumen_perusahaan_anda_bangun_asisten_ai') }}</p>

                <div class="mt-8 p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xl flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <i data-lucide="cpu" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-200">{{ __('auth.s.kapasitas_embedding_rag') }}</div>
                        <div class="text-[11px] text-slate-400">{{ __('auth.s.sinkronisasi_dokumen_instan_aman') }}</div>
                    </div>
                </div>
            </div>

            <!-- Bagian Bawah Kolom Kiri -->
            <div class="relative z-10 text-xs text-slate-500 font-mono">{{ __('auth.s.secure_256_bit_ssl_encryption') }}</div>
        </div>

        <!-- Kolom Kanan: Form -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-950 p-8 sm:p-12 lg:p-16 flex flex-col justify-center items-center relative">

            <div class="w-full max-w-md mx-auto">
                <!-- Tab Switcher (Masuk / Daftar) -->
                <div class="flex p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl mb-8 border border-slate-200 dark:border-slate-800">
                    <span class="flex-1 py-3 text-xs font-bold rounded-xl transition-all bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm text-center">{{ __('auth.s.masuk') }}</span>
                    <a href="{{ route('register') }}" class="flex-1 py-3 text-xs font-bold rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-center">{{ __('auth.s.daftar_baru') }}</a>
                </div>

                <div class="text-left mb-6">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('auth.s.selamat_datang_kembali') }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('auth.s.masukkan_detail_akun_anda_untuk_mengakses_dasbor') }}</p>
                </div>

                {{-- Google SSO --}}
                <a href="{{ route('google.login') }}"
                    class="w-full flex items-center justify-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium py-3.5 rounded-xl text-sm transition-colors group mb-4">
                    <svg class="w-5 h-5 group-hover:scale-110 transition" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    {{ __('auth.s.masuk_dengan_google') }}
                </a>

                <div class="relative flex py-2 items-center mb-4">
                    <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
                    <span class="flex-shrink-0 mx-4 text-slate-400 text-xs font-medium uppercase tracking-wider">{{ __('auth.s.atau_dengan_email') }}</span>
                    <div class="flex-grow border-t border-slate-200 dark:border-slate-800"></div>
                </div>

                <!-- FORM LOGIN -->
                <form method="POST" action="/login" data-loading novalidate>
                    @csrf

                    <x-auth-error-summary />

                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">{{ __('auth.s.email_perusahaan') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i data-lucide="mail" class="w-4 h-4"></i></span>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="{{ __('auth.s.nama_perusahaan_com') }}" @error('email') aria-invalid="true" @enderror
                                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-slate-900 dark:text-white transition-colors @error('email') border-red-500 focus:border-red-500 @enderror">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label for="password" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">{{ __('auth.s.kata_sandi') }}</label>
                                <a href="{{ route('password.request') }}" class="text-xs text-brand-600 dark:text-brand-400 hover:underline font-medium">{{ __('auth.s.lupa_sandi') }}</a>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i data-lucide="key" class="w-4 h-4"></i></span>
                                <input type="password" id="password" name="password" required placeholder="••••••••" @error('password') aria-invalid="true" @enderror
                                    class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-slate-900 dark:text-white transition-colors @error('password') border-red-500 focus:border-red-500 @enderror">
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full py-4 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-brand-500/25 transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:scale-100">{{ __('auth.s.masuk_ke_dasbor') }}</button>
                    </div>
                </form>
            </div>

        </div>

    </main>

    <script>
        lucide.createIcons();

        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                html.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }

        document.querySelectorAll('form[data-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (!btn) return;
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            });
        });
    </script>
</body>
</html>
