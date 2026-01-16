<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cekat.biz.id</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-slate-900 font-sans antialiased h-screen flex overflow-hidden">

    <!-- Left Side - Form -->
    <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-8 bg-white h-full overflow-y-auto">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo -->
            <a href="/" class="flex items-center gap-2 font-bold text-2xl tracking-tight text-slate-900 mb-8">
                <div
                    class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center text-lg shadow-lg">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                Cekat<span class="text-slate-400 font-normal">.biz.id</span>
            </a>

            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">Welcome back</h1>
                <p class="text-slate-500">Masuk ke akun Anda untuk mengelola Chatbot.</p>
            </div>

            <!-- Social Login -->
            <div class="space-y-3">
                <a href="{{ route('google.login') }}"
                    class="w-full flex items-center justify-center gap-3 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-medium py-2.5 rounded-xl transition duration-200 group">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg"
                        class="w-5 h-5 group-hover:scale-110 transition" alt="Google">
                    Sign in with Google
                </a>
            </div>

            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink-0 mx-4 text-slate-400 text-xs font-medium uppercase tracking-wider">Atau dengan
                    email</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <!-- Form -->
            <form class="space-y-4" action="/login" method="POST" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-1">
                    <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 transition placeholder-slate-400"
                        placeholder="nama@perusahaan.com" required>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        <a href="{{ route('password.request') }}"
                            class="text-sm font-medium text-blue-600 hover:text-blue-500">Lupa password?</a>
                    </div>
                    <input type="password" id="password" name="password"
                        class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-900 transition placeholder-slate-400"
                        placeholder="••••••••" required>
                </div>


                <div class="pt-2">
                    <button type="submit" :disabled="loading"
                        class="w-full bg-slate-900 text-white font-semibold py-3 rounded-xl hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 transition shadow-lg shadow-slate-900/20 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!loading">Sign in</span>
                        <span x-show="loading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Signing in...
                        </span>
                    </button>
                </div>
            </form>

            <p class="text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-500">Daftar
                    sekarang</a>
            </p>
        </div>
    </div>

    <!-- Right Side - Visual -->
    <div class="hidden lg:flex lg:w-1/2 bg-slate-900 relative items-center justify-center overflow-hidden">
        <!-- Background decorative elements -->
        <div class="absolute inset-0 bg-[#0f172a]">
            <!-- Grid pattern -->
            <div class="absolute inset-0 opacity-10"
                style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 32px 32px;">
            </div>

            <!-- Glow effects -->
            <div
                class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/20 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/2">
            </div>
            <div
                class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-purple-500/10 rounded-full blur-[100px] translate-y-1/2 -translate-x-1/2">
            </div>
        </div>

        <!-- Content -->
        <div class="relative z-10 max-w-lg text-center p-12">
            <div class="mb-8 relative inline-block">
                <div class="absolute inset-0 bg-blue-500 blur-2xl opacity-20 rounded-full"></div>
                <!-- Using Font Awesome robot icon as fallback -->
                <div class="w-64 h-64 flex items-center justify-center relative z-10">
                    <i class="fa-solid fa-robot text-white text-9xl drop-shadow-2xl animate-float"></i>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-white mb-4">Customer Service 24/7 Tanpa Lelah</h2>
            <p class="text-slate-400 text-lg leading-relaxed">
                Bergabunglah dengan 500+ bisnis yang telah mengotomatiskan layanan pelanggan mereka dengan AI Cerdas.
            </p>

            <!-- Testimonial card -->
            <div
                class="mt-12 bg-white/5 backdrop-blur-md border border-white/10 p-4 rounded-xl text-left flex gap-4 items-center max-w-sm mx-auto transform hover:scale-105 transition duration-300 cursor-default">
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-tr from-yellow-400 to-orange-500 flex items-center justify-center text-white font-bold text-xs">
                    JD
                </div>
                <div>
                    <div class="flex text-yellow-400 text-xs mb-1">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-slate-300 text-xs">"Hemat waktu banget! Setup cuma 5 menit."</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</body>

</html>