<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email | Cekat.biz.id - AI Chatbot Kustom</title>
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
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                            800: '#1e1b4b', 900: '#312e81',
                        }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
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

    <main class="w-screen min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="flex items-center justify-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white shadow-lg shadow-brand-500/30">
                    <i data-lucide="bot" class="w-5 h-5"></i>
                </div>
                <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Cekat<span class="text-brand-500">.biz.id</span></span>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-lg">
                @if (session('success'))
                    <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl text-sm mb-5">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm mb-5">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="text-center mb-6">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 flex items-center justify-center text-brand-600 dark:text-brand-400">
                        <i data-lucide="mail-check" class="w-7 h-7"></i>
                    </div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Cek Email Anda</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                        Kami telah mengirim link verifikasi ke<br>
                        <strong class="text-slate-700 dark:text-slate-200">{{ auth()->user()->email }}</strong>
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                        Link berlaku 60 menit. Periksa folder spam jika tidak muncul.
                    </p>
                </div>

                <form method="POST" action="{{ route('verification.resend') }}" data-loading class="mb-4">
                    @csrf
                    <button type="submit" class="w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-brand-500/25 transition-all disabled:opacity-70">
                        Kirim Ulang Link Verifikasi
                    </button>
                </form>

                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span class="text-slate-400 dark:text-slate-500">Belum menerima email?</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:text-brand-600 dark:hover:text-brand-400 font-medium">Keluar</button>
                    </form>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 dark:text-slate-500 mt-6">
                Tidak menerima email? Pastikan alamat benar lalu kirim ulang.
            </p>
        </div>
    </main>

    <script>
        lucide.createIcons();
        document.querySelectorAll('form[data-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (!btn) return;
                btn.disabled = true;
                btn.textContent = 'Mengirim...';
            });
        });
    </script>
</body>
</html>
