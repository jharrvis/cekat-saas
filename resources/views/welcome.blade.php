<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cekat.biz.id - Custom AI Chatbot for Your Data</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            darkMode: 'class',
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
                        },
                        primary: '#0f172a', // Zinc 900
                        accent: '#2563eb',  // Blue 600
                    }
                }
            }
        }
    </script>
    <style>
        .hero-pattern {
            background-color: #ffffff;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>

<body class="bg-white text-slate-900 font-sans antialiased selection:bg-slate-200 selection:text-slate-900">

    <!-- NAVBAR -->
    <nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-100" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <!-- Logo -->
            <a href="#" class="flex items-center gap-2 font-bold text-xl tracking-tight text-slate-900">
                <div class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                Cekat<span class="text-slate-400 font-normal">.biz.id</span>
            </a>

            <!-- Desktop Links -->
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#features" class="hover:text-slate-900 transition">Fitur</a>
                <a href="playground.html" class="hover:text-slate-900 transition">Demo</a>
                <a href="#pricing" class="hover:text-slate-900 transition">Harga</a>
            </div>

            <!-- CTA -->
            <div class="hidden md:flex items-center gap-4">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow-sm hover:shadow-md">
                        <i class="fa-solid fa-gauge mr-2"></i>Dashboard
                    </a>
                @else
                    <a href="/login" class="text-sm font-medium text-slate-600 hover:text-slate-900">Sign in</a>
                    <a href="/register"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow-sm hover:shadow-md">
                        Buat Chatbot <i class="fa-solid fa-arrow-right ml-1 text-xs text-slate-400"></i>
                    </a>
                @endauth
            </div>

            <!-- Mobile Menu Button -->
            <button @click="open = !open" class="md:hidden text-slate-600">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div x-show="open" class="md:hidden bg-white border-t border-slate-100 p-4 space-y-4 shadow-lg">
            <a href="#features" class="block text-slate-600 font-medium">Fitur</a>
            <a href="#demo" class="block text-slate-600 font-medium">Demo</a>
            <a href="#pricing" class="block text-slate-600 font-medium">Harga</a>
            <div class="pt-4 border-t border-slate-100 flex flex-col gap-3">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}"
                        class="bg-slate-900 text-white py-2 rounded-lg text-center font-medium">
                        <i class="fa-solid fa-gauge mr-2"></i>Dashboard
                    </a>
                @else
                    <a href="/login" class="text-center py-2 text-slate-600 font-medium">Sign in</a>
                    <a href="/register" class="bg-slate-900 text-white py-2 rounded-lg text-center font-medium">Buat
                        Chatbot</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="pt-32 pb-20 relative overflow-hidden hero-pattern">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-white pointer-events-none">
        </div>

        <div class="max-w-5xl mx-auto px-6 text-center relative z-10">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-xs font-medium text-slate-600 mb-8 animate-fade-in-up">
                <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                Sekarang Support GPT-4o
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-slate-900 mb-6 leading-tight">
                Custom ChatGPT untuk <br class="hidden md:block" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-500">Data Bisnis
                    Anda</span>
            </h1>

            <p class="text-lg md:text-xl text-slate-600 mb-10 max-w-2xl mx-auto leading-relaxed">
                Bangun chatbot AI yang terlatih dengan data Anda. Upload PDF atau crawl website, dan biarkan AI menjawab
                pertanyaan pelanggan 24/7.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-20">
                <a href="#"
                    class="w-full sm:w-auto bg-slate-900 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-800 transition shadow-lg hover:shadow-xl hover:-translate-y-0.5 transform duration-200">
                    Coba Gratis Sekarang
                </a>
                <a href="playground.html"
                    class="w-full sm:w-auto bg-white text-slate-700 border border-slate-200 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-slate-50 transition">
                    Lihat Demo
                </a>
            </div>

            <!-- HERO IMAGE / MOCKUP -->
            <div class="relative max-w-4xl mx-auto rounded-2xl border border-slate-200 shadow-2xl bg-white p-2">
                <div class="bg-slate-50 rounded-xl overflow-hidden aspect-[16/9] md:aspect-[21/9] relative flex">
                    <!-- Sidebar Mockup -->
                    <div class="hidden md:flex w-64 border-r border-slate-200 bg-white flex-col p-4">
                        <div class="h-8 w-8 bg-slate-900 rounded mb-6"></div>
                        <div class="space-y-3">
                            <div class="h-2 w-20 bg-slate-200 rounded"></div>
                            <div class="h-2 w-32 bg-slate-100 rounded"></div>
                            <div class="h-2 w-24 bg-slate-100 rounded"></div>
                        </div>
                    </div>
                    <!-- Chat Area Mockup (Typewriter & Smooth) -->
                    <div class="flex-1 flex flex-col bg-white relative rounded-r-xl overflow-hidden" x-data="{
                        messages: [],
                        isTyping: false,
                        
                        async typeMessage(role, fullText) {
                            this.isTyping = true;
                            await this.wait(role === 'user' ? 800 : 1500); 
                            this.isTyping = false;

                            const msgIndex = this.messages.push({ role: role, text: '', show: true }) - 1;
                            
                            const chunkSpeed = 30; 
                            for (let i = 0; i < fullText.length; i++) {
                                this.messages[msgIndex].text += fullText.charAt(i);
                                this.scrollToBottom();
                                await this.wait(chunkSpeed + Math.random() * 20);
                            }
                        },

                        async startDemo() {
                            await this.wait(1000);
                            
                            // 1. AI Greeting (Agentic: Proactive Sales)
                            await this.typeMessage('ai', 'Halo! 👋 Lagi cari solusi CS otomatis buat bisnis Kakak? Kebetulan kita lagi ada promo **Diskon 50%** khusus hari ini lho! 🤩');
                            
                            // 2. User Question
                            await this.wait(2000);
                            await this.typeMessage('user', 'Wah serius? Emang fiturnya apa aja kak?');
                            
                            // 3. AI Response
                            await this.wait(1000);
                            await this.typeMessage('ai', 'Lengkap banget! Udah support **GPT-4o**, bisa baca PDF/Website, dan aktif jualan kayak aku gini. Mau diamankan slot promonya Kak?');

                            // 4. User Follow-up
                            await this.wait(2000);
                            await this.typeMessage('user', 'Boleh deh. Caranya gimana?');

                            // 5. AI Response (Agentic: Data Collection)
                            await this.wait(1000);
                            await this.typeMessage('ai', 'Gampang! Cukup ketik **Nama** & **Email** Kakak di sini. Nanti Chika buatin akun & kirim vouchernya langsung via email. 🚀');
                        },

                        scrollToBottom() {
                            this.$nextTick(() => {
                                const container = this.$refs.chatContainer;
                                container.scrollTop = container.scrollHeight;
                            });
                        },

                        wait(ms) {
                            return new Promise(resolve => setTimeout(resolve, ms));
                        }
                    }" x-init="startDemo()">

                        <!-- Chat Header -->
                        <div
                            class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white/80 backdrop-blur-sm sticky top-0 z-10">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div
                                        class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center border-2 border-white shadow-sm">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Chika&backgroundColor=c7d2fe"
                                            alt="Chika" class="w-full h-full rounded-full">
                                    </div>
                                    <div
                                        class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full">
                                    </div>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">Chika (CS Support)</div>
                                    <div class="text-xs text-slate-500 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Online
                                    </div>
                                </div>
                            </div>
                            <button class="text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                        </div>

                        <!-- Messages Container -->
                        <div x-ref="chatContainer"
                            class="flex-1 p-6 space-y-6 overflow-y-auto max-h-[350px] scroll-smooth bg-slate-50/50">

                            <template x-for="(msg, index) in messages" :key="index">
                                <div x-show="msg.show" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4"
                                    x-transition:enter-end="opacity-100 translate-y-0" class="flex gap-3"
                                    :class="msg.role === 'user' ? 'flex-row-reverse' : ''">

                                    <!-- Avatar (Small for chat) -->
                                    <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center overflow-hidden border border-slate-100 shadow-sm"
                                        :class="msg.role === 'ai' ? 'bg-indigo-100' : 'bg-slate-200'">
                                        <template x-if="msg.role === 'ai'">
                                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Chika&backgroundColor=c7d2fe"
                                                class="w-full h-full">
                                        </template>
                                        <template x-if="msg.role === 'user'">
                                            <i class="fa-solid fa-user text-slate-500 text-xs"></i>
                                        </template>
                                    </div>

                                    <!-- Bubble -->
                                    <div class="px-4 py-3 rounded-2xl max-w-[85%] shadow-sm text-sm leading-relaxed"
                                        :class="msg.role === 'ai' ? 'bg-white border border-slate-100 rounded-tl-none text-slate-700' : 'bg-slate-900 rounded-tr-none text-white'">
                                        <p x-text="msg.text"></p>
                                    </div>
                                </div>
                            </template>

                            <!-- Typing Indicator -->
                            <div x-show="isTyping" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-90"
                                x-transition:enter-end="opacity-100 scale-100" class="flex gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-indigo-100 flex-shrink-0 overflow-hidden border border-slate-100 shadow-sm">
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Chika&backgroundColor=c7d2fe"
                                        class="w-full h-full">
                                </div>
                                <div
                                    class="bg-white border border-slate-100 px-4 py-3 rounded-2xl rounded-tl-none flex items-center gap-1.5 shadow-sm">
                                    <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"
                                        style="animation-delay: 0s"></div>
                                    <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"
                                        style="animation-delay: 0.15s"></div>
                                    <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"
                                        style="animation-delay: 0.3s"></div>
                                </div>
                            </div>

                        </div>

                        <!-- Input Mockup -->
                        <div class="p-4 border-t border-slate-100 bg-white">
                            <div
                                class="bg-slate-50 border border-slate-200 rounded-full h-12 w-full flex items-center px-5 text-slate-400 text-sm justify-between shadow-inner">
                                <span x-show="!isTyping">Ketik pesan...</span>
                                <span x-show="isTyping && messages.length % 2 !== 0"
                                    class="text-slate-300 italic text-xs">User sedang mengetik...</span>
                                <i class="fa-solid fa-paper-plane text-slate-300"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating Card 1 (Relocated) -->
                <div
                    class="absolute -left-6 bottom-10 bg-white p-4 rounded-xl shadow-xl border border-slate-100 w-64 hidden lg:block animate-pulse">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600"><i
                                class="fa-solid fa-database"></i></div>
                        <div>
                            <div class="text-sm font-bold text-slate-800">Knowledge Base</div>
                            <div class="text-xs text-slate-400">Syncing with PDF...</div>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-600 h-1.5 rounded-full animate-progress-indeterminate"></div>
                    </div>
                </div>
            </div>

            <style>
                @keyframes progress-indeterminate {
                    0% {
                        width: 0%;
                        margin-left: 0%;
                    }

                    50% {
                        width: 70%;
                        margin-left: 30%;
                    }

                    100% {
                        width: 0%;
                        margin-left: 100%;
                    }
                }

                .animate-progress-indeterminate {
                    animation: progress-indeterminate 2s infinite linear;
                }
            </style>
        </div>
    </section>

    <!-- LOGOS -->
    <section class="py-10 border-y border-slate-50">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-sm text-slate-500 font-medium mb-8">DIPERCAYA OLEH TIM INOVATIF</p>
            <div
                class="flex flex-wrap justify-center items-center gap-8 md:gap-16 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                <i class="fa-brands fa-google text-2xl"></i>
                <i class="fa-brands fa-aws text-2xl"></i>
                <i class="fa-brands fa-microsoft text-2xl"></i>
                <i class="fa-brands fa-spotify text-2xl"></i>
                <i class="fa-brands fa-airbnb text-2xl"></i>
            </div>
        </div>
    </section>

    <!-- FEATURES GRID -->
    <section id="features" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Chatbot Pintar dalam hitungan menit</h2>
                <p class="text-lg text-slate-600">Tanpa coding. Tanpa setup server yang rumit. Fokus pada bisnis Anda.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass-card p-8 rounded-2xl hover:shadow-lg transition duration-300">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-xl mb-6">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Multi Data Source</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Import data dari berbagai sumber: Upload PDF, text file, docx, atau cukup masukkan link website
                        Anda untuk di-crawl otomatis.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card p-8 rounded-2xl hover:shadow-lg transition duration-300">
                    <div
                        class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 text-xl mb-6">
                        <i class="fa-solid fa-paintbrush"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Custom Branding</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Sesuaikan tampilan widget dengan warna brand Anda. Upload logo custom, ubah pesan pembuka, dan
                        hilangkan watermark.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card p-8 rounded-2xl hover:shadow-lg transition duration-300">
                    <div
                        class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-green-600 text-xl mb-6">
                        <i class="fa-solid fa-code"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Mudah Di-embed</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Cukup copy-paste satu baris kode script ke website Anda (WordPress, Shopify, Webflow, dll) dan
                        chatbot langsung aktif.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="md:w-1/2">
                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-6">Latih AI dengan Brand Voice Anda</h2>
                    <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                        Bukan sekadar chatbot biasa. Anda bisa memberikan instruksi spesifik ("System Prompt") agar AI
                        menjawab dengan gaya bahasa yang santai, formal, atau persuasif sesuai persona brand Anda.
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <div
                                class="mt-1 w-6 h-6 bg-slate-900 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                1</div>
                            <div>
                                <h4 class="font-bold text-slate-900">Upload Dokumen</h4>
                                <p class="text-slate-600 text-sm">PDF, Docx, CSV, atau URL Website.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="mt-1 w-6 h-6 bg-slate-900 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                2</div>
                            <div>
                                <h4 class="font-bold text-slate-900">Kustomisasi Prompt</h4>
                                <p class="text-slate-600 text-sm">"Kamu adalah CS yang ramah. Jawab dalam Bahasa
                                    Indonesia..."</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="mt-1 w-6 h-6 bg-slate-900 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                3</div>
                            <div>
                                <h4 class="font-bold text-slate-900">Deploy</h4>
                                <p class="text-slate-600 text-sm">Pasang widget di website atau integrasi ke API.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:w-1/2 relative">
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-3xl opacity-10 transform rotate-3 scale-105">
                    </div>
                    <div class="bg-slate-900 p-8 rounded-3xl relative shadow-2xl border border-slate-800">
                        <div class="flex gap-2 mb-6">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <div class="space-y-4 font-mono text-sm">
                            <div class="text-slate-400">// System Prompt</div>
                            <div class="text-purple-400">const<span class="text-white"> persona</span> = <span
                                    class="text-green-400">"Customer Service Specialist"</span>;</div>
                            <div class="text-purple-400">const<span class="text-white"> tone</span> = <span
                                    class="text-green-400">"Friendly, Helpful, and Professional"</span>;</div>
                            <div class="text-purple-400">const<span class="text-white"> knowledge</span> = <span
                                    class="text-blue-400">[...uploadedDocs]</span>;</div>
                            <div class="text-slate-500 pt-2">/* AI siap menjawab pertanyaan user berdasarkan data di
                                atas tanpa halusinasi. */</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section id="pricing" class="py-24 bg-slate-50 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Harga Simpel & Transparan</h2>
                <p class="text-lg text-slate-600">Mulai gratis, upgrade saat bisnis Anda tumbuh.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Free Tier -->
                <div
                    class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition">
                    <div class="text-xl font-bold text-slate-900 mb-2">Hobby</div>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-bold text-slate-900">Rp 0</span>
                        <span class="text-slate-500">/bulan</span>
                    </div>
                    <p class="text-slate-600 text-sm mb-6">Untuk project pribadi atau testing.</p>
                    <a href="#"
                        class="block w-full py-3 text-center border border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition text-slate-700">Mulai
                        Gratis</a>
                    <ul class="mt-8 space-y-4 text-sm text-slate-600">
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> 1 Chatbot</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> 30 Pesan/bulan</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> 10 Halaman Dokumen</li>
                    </ul>
                </div>

                <!-- Pro Tier (Popular) -->
                <div
                    class="bg-slate-900 p-8 rounded-2xl border border-slate-800 shadow-xl relative transform md:-translate-y-4">
                    <div
                        class="absolute top-0 right-0 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">
                        POPULAR</div>
                    <div class="text-xl font-bold text-white mb-2">Standard</div>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-bold text-white">Rp 199rb</span>
                        <span class="text-slate-400">/bulan</span>
                    </div>
                    <p class="text-slate-400 text-sm mb-6">Untuk bisnis kecil & start-up.</p>
                    <a href="#"
                        class="block w-full py-3 text-center bg-blue-600 rounded-xl font-semibold hover:bg-blue-500 transition text-white shadow-lg">Free
                        7-Day Trial</a>
                    <ul class="mt-8 space-y-4 text-sm text-slate-300">
                        <li class="flex gap-3"><i class="fa-solid fa-check text-blue-400"></i> 2 Chatbot</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-blue-400"></i> 2,000 Pesan/bulan</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-blue-400"></i> 5 File PDF/Docx</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-blue-400"></i> Remove "Powered By"</li>
                    </ul>
                </div>

                <!-- Enterprise -->
                <div
                    class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition">
                    <div class="text-xl font-bold text-slate-900 mb-2">Unlimited</div>
                    <div class="flex items-baseline gap-1 mb-6">
                        <span class="text-4xl font-bold text-slate-900">Rp 499rb</span>
                        <span class="text-slate-500">/bulan</span>
                    </div>
                    <p class="text-slate-600 text-sm mb-6">Untuk agensi & perusahaan besar.</p>
                    <a href="#"
                        class="block w-full py-3 text-center border border-slate-200 rounded-xl font-semibold hover:bg-slate-50 transition text-slate-700">Hubungi
                        Sales</a>
                    <ul class="mt-8 space-y-4 text-sm text-slate-600">
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> 10 Chatbot</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> Unlimited Pesan</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> API Access</li>
                        <li class="flex gap-3"><i class="fa-solid fa-check text-green-500"></i> Custom Domain</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <a href="#" class="flex items-center gap-2 font-bold text-xl tracking-tight text-slate-900 mb-4">
                        <div
                            class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center text-sm">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        Cekat.biz.id
                    </a>
                    <p class="text-slate-500 text-sm leading-relaxed max-w-xs">
                        Platform no-code untuk membuat chatbot AI kustom menggunakan data bisnis Anda. Tingkatkan
                        layanan pelanggan dalam hitungan menit.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 mb-4">Produk</h4>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li><a href="#" class="hover:text-blue-600">Fitur</a></li>
                        <li><a href="#" class="hover:text-blue-600">Harga</a></li>
                        <li><a href="#" class="hover:text-blue-600">Showcase</a></li>
                        <li><a href="#" class="hover:text-blue-600">Changelog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 mb-4">Perusahaan</h4>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li><a href="#" class="hover:text-blue-600">Tentang Kami</a></li>
                        <li><a href="#" class="hover:text-blue-600">Kontak</a></li>
                        <li><a href="#" class="hover:text-blue-600">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-blue-600">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-slate-500 text-sm">
                    &copy; 2024 Cekat.biz.id. All rights reserved.
                </div>
                <div class="flex gap-6 text-slate-400">
                    <a href="#" class="hover:text-slate-900"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="hover:text-slate-900"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="hover:text-slate-900"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    @php
        $landingWidget = \App\Models\Widget::where('slug', 'landing-page-default')->first();
        $settings = $landingWidget->settings ?? [];
        $widgetName = $landingWidget->name ?? 'Customer Support';
        $color = $settings['primary_color'] ?? '#0f172a';
        $greeting = $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?';
        $position = $settings['position'] ?? 'bottom-right';
        $subtitle = $settings['subtitle'] ?? 'Online • Reply cepat';
        $placeholder = $settings['placeholder'] ?? 'Ketik pesan...';
        $avatarType = $settings['avatar_type'] ?? 'icon';
        $avatarIcon = $settings['avatar_icon'] ?? 'robot';
        $avatarUrl = $settings['avatar_url'] ?? '';
    @endphp
    <script>
        window.CSAIConfig = {
            widgetId: 'landing-page-default',
            apiUrl: '/api/chat',
            position: '{{ $position }}',
            primaryColor: '{{ $color }}',
            title: '{{ addslashes($widgetName) }}',
            subtitle: '{{ addslashes($subtitle) }}',
            greeting: `{!! addslashes($greeting) !!}`,
            placeholder: '{{ addslashes($placeholder) }}',
            avatarType: '{{ $avatarType }}',
            avatarIcon: '{{ $avatarIcon }}',
            avatarUrl: '{{ $avatarUrl }}',
            showBranding: true
        };
    </script>
    <script src="/widget/widget.js?v={{ time() }}"></script>
</body>

</html>