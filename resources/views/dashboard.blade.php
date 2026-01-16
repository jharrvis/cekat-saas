<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cekat.biz.id</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "hsl(var(--background))",
                        foreground: "hsl(var(--foreground))",
                        primary: {
                            DEFAULT: "hsl(var(--primary))",
                            foreground: "hsl(var(--primary-foreground))",
                        },
                        secondary: {
                            DEFAULT: "hsl(var(--secondary))",
                            foreground: "hsl(var(--secondary-foreground))",
                        },
                        destructive: {
                            DEFAULT: "hsl(var(--destructive))",
                            foreground: "hsl(var(--destructive-foreground))",
                        },
                        muted: {
                            DEFAULT: "hsl(var(--muted))",
                            foreground: "hsl(var(--muted-foreground))",
                        },
                        accent: {
                            DEFAULT: "hsl(var(--accent))",
                            foreground: "hsl(var(--accent-foreground))",
                        },
                        popover: {
                            DEFAULT: "hsl(var(--popover))",
                            foreground: "hsl(var(--popover-foreground))",
                        },
                        card: {
                            DEFAULT: "hsl(var(--card))",
                            foreground: "hsl(var(--card-foreground))",
                        },
                    },
                }
            }
        }
    </script>

    <style type="text/tailwindcss">
        @layer base {
            :root {
                --background: 0 0% 100%;
                --foreground: 222.2 84% 4.9%;
                --card: 0 0% 100%;
                --card-foreground: 222.2 84% 4.9%;
                --popover: 0 0% 100%;
                --popover-foreground: 222.2 84% 4.9%;
                --primary: 221.2 83.2% 53.3%;
                --primary-foreground: 210 40% 98%;
                --secondary: 210 40% 96.1%;
                --secondary-foreground: 222.2 47.4% 11.2%;
                --muted: 210 40% 96.1%;
                --muted-foreground: 215.4 16.3% 46.9%;
                --accent: 210 40% 96.1%;
                --accent-foreground: 222.2 47.4% 11.2%;
                --destructive: 0 84.2% 60.2%;
                --destructive-foreground: 210 40% 98%;
                --border: 214.3 31.8% 91.4%;
                --input: 214.3 31.8% 91.4%;
                --ring: 221.2 83.2% 53.3%;
                --radius: 0.5rem;
            }
        
            .dark {
                --background: 222.2 84% 4.9%;
                --foreground: 210 40% 98%;
                --card: 222.2 84% 4.9%;
                --card-foreground: 210 40% 98%;
                --popover: 222.2 84% 4.9%;
                --popover-foreground: 210 40% 98%;
                --primary: 217.2 91.2% 59.8%;
                --primary-foreground: 222.2 47.4% 11.2%;
                --secondary: 217.2 32.6% 17.5%;
                --secondary-foreground: 210 40% 98%;
                --muted: 217.2 32.6% 17.5%;
                --muted-foreground: 215 20.2% 65.1%;
                --accent: 217.2 32.6% 17.5%;
                --accent-foreground: 210 40% 98%;
                --destructive: 0 62.8% 30.6%;
                --destructive-foreground: 210 40% 98%;
                --border: 217.2 32.6% 17.5%;
                --input: 217.2 32.6% 17.5%;
                --ring: 224.3 76.3% 48%;
            }
        }
        
        @layer base {
            * {
                @apply border-border;
            }
            body {
                @apply bg-background text-foreground;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            @apply bg-transparent;
        }

        ::-webkit-scrollbar-thumb {
            @apply bg-muted-foreground/20 rounded-full;
        }

        ::-webkit-scrollbar-thumb:hover {
            @apply bg-muted-foreground/40;
        }

        /* Transitions */
        .sidebar-transition {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body x-data="{ 
        sidebarOpen: false, 
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        darkMode: localStorage.getItem('darkMode') === 'true',
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        darkMode: localStorage.getItem('darkMode') === 'true',
        loading: true,
        init() {
            // Simulate initial loading
            setTimeout(() => {
                this.loading = false;
            }, 2000);
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        },
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        isActive(page) {
            const path = window.location.pathname;
            return path.includes(page) || (page === 'dashboard' && path.endsWith('dashboard.html'));
        }
    }"
    x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');"
    class="antialiased min-h-screen flex text-sm">

    <!-- MOBILE SIDEBAR BACKDROP -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
        class="fixed inset-0 bg-background/80 backdrop-blur-sm z-40 md:hidden"></div>

    <!-- SIDEBAR -->
    <aside :class="{ 
            'w-64': !sidebarCollapsed, 
            'w-16': sidebarCollapsed, 
            '-translate-x-full': !sidebarOpen, 
            'translate-x-0': sidebarOpen 
        }"
        class="fixed md:translate-x-0 md:static inset-y-0 left-0 z-50 bg-card border-r flex flex-col transition-all duration-300 ease-in-out">
        <!-- Logo -->
        <div class="h-16 flex items-center border-b px-4 justify-between transition-all duration-300"
            :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
            <div class="flex items-center gap-2 overflow-hidden whitespace-nowrap">
                <div
                    class="w-8 h-8 rounded bg-primary flex items-center justify-center text-primary-foreground font-bold shrink-0">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <span x-show="!sidebarCollapsed"
                    class="font-bold text-lg tracking-tight transition-opacity duration-200">Cekat<span
                        class="text-primary">.biz.id</span></span>
            </div>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-2">

            <div x-show="!sidebarCollapsed"
                class="px-2 mb-2 text-xs font-semibold text-muted-foreground items-center transition-opacity duration-200">
                MENU UTAMA
            </div>
            <div x-show="sidebarCollapsed" class="px-2 mb-2 h-4 flex justify-center items-center">
                <div class="w-4 h-[1px] bg-border"></div>
            </div>

            <a href="dashboard.html"
                :class="isActive('dashboard') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative"
                title="Dashboard">
                <i class="fa-solid fa-chart-pie w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Dashboard</span>
            </a>

            <a href="knowledge-base.html"
                :class="isActive('knowledge-base') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative"
                title="Knowledge Base">
                <i class="fa-solid fa-brain w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Training AI</span>
            </a>

            <a href="models.html"
                :class="isActive('models') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative"
                title="Model Intelligence">
                <i class="fa-solid fa-robot w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Model Intelligence</span>
            </a>

            <a href="widget-editor.html"
                :class="isActive('widget-editor') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative"
                title="Widget Editor">
                <i class="fa-solid fa-paintbrush w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Tampilan Widget</span>
            </a>

            <a href="analytics.html"
                :class="isActive('analytics') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative"
                title="Analytics">
                <i class="fa-solid fa-chart-line w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Analytics</span>
                <span x-show="!sidebarCollapsed"
                    class="ml-auto text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded border border-amber-200">Biz</span>
            </a>

            <div class="my-4 border-t border-border mx-2"></div>

            <div x-show="!sidebarCollapsed"
                class="px-2 mb-2 text-xs font-semibold text-muted-foreground transition-opacity duration-200">
                PENGATURAN
            </div>

            <a href="integration.html"
                :class="isActive('integration') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative">
                <i class="fa-solid fa-plug w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Integrasi</span>
            </a>

            <a href="settings.html"
                :class="isActive('settings') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative">
                <i class="fa-solid fa-gear w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Pengaturan</span>
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t bg-muted/20">
            <div x-show="!sidebarCollapsed" class="bg-primary/10 p-3 rounded-lg border border-primary/20">
                <div class="flex justify-between items-center mb-1">
                    <p class="text-xs font-semibold text-primary">Paket: UMKM Pro</p>
                </div>
                <div class="w-full bg-primary/20 rounded-full h-1.5 mb-2">
                    <div class="bg-primary h-1.5 rounded-full" style="width: 65%"></div>
                </div>
                <p class="text-[10px] text-muted-foreground">650 / 1000 Pesan</p>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-muted/40 transition-colors duration-300">

        <!-- Header -->
        <header
            class="h-16 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b flex items-center justify-between px-4 md:px-6 sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <!-- Mobile Toggle -->
                <button @click="sidebarOpen = true"
                    class="md:hidden p-2 -ml-2 text-muted-foreground hover:text-foreground rounded-md">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <!-- Desktop Toggle (Moved Here) -->
                <button @click="toggleSidebar()"
                    class="hidden md:flex p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-md transition-colors mr-2">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>

                <div class="font-semibold text-lg">Overview Bisnis</div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Dark Mode Toggle -->
                <button @click="toggleDarkMode()"
                    class="p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-full transition-colors w-9 h-9 flex items-center justify-center">
                    <i x-show="!darkMode" class="fa-regular fa-moon"></i>
                    <i x-show="darkMode" class="fa-regular fa-sun"></i>
                </button>

                <!-- Notifications -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false"
                        class="relative p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-full transition-colors w-9 h-9 flex items-center justify-center">
                        <i class="fa-regular fa-bell"></i>
                        <span
                            class="absolute top-2 right-2.5 w-2 h-2 bg-destructive rounded-full border-2 border-background"></span>
                    </button>
                    <!-- Dropdown -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-80 bg-popover text-popover-foreground border rounded-lg shadow-lg py-1 z-50">
                        <div class="px-4 py-2 border-b">
                            <h4 class="text-sm font-semibold">Notifikasi</h4>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <a href="#" class="block px-4 py-3 hover:bg-accent transition-colors">
                                <p class="text-sm font-medium">Pelanggan Baru</p>
                                <p class="text-xs text-muted-foreground line-clamp-1">User #9928 memulai percakapan
                                    baru.</p>
                                <p class="text-[10px] text-muted-foreground mt-1">2 menit yang lalu</p>
                            </a>
                            <a href="#" class="block px-4 py-3 hover:bg-accent transition-colors">
                                <p class="text-sm font-medium">Training Selesai</p>
                                <p class="text-xs text-muted-foreground line-clamp-1">Dokumen 'Kebijakan Retur.docx'
                                    selesai diproses.</p>
                                <p class="text-[10px] text-muted-foreground mt-1">1 jam yang lalu</p>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="relative ml-2" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-2 outline-none">
                        <div
                            class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-primary-foreground font-semibold text-sm">
                            BS
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium leading-none">Budi Santoso</p>
                            <p class="text-xs text-muted-foreground">Admin</p>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs text-muted-foreground ml-1"></i>
                    </button>
                    <!-- Dropdown -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-popover text-popover-foreground border rounded-lg shadow-lg py-1 z-50">
                        <div class="px-2 py-1.5">
                            <p class="text-sm font-medium">Budi Santoso</p>
                            <p class="text-xs text-muted-foreground">budi@example.com</p>
                        </div>
                        <div class="border-t my-1"></div>
                        <a href="#" class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                            <i class="fa-regular fa-user w-4 mr-2"></i> Profile
                        </a>
                        <a href="#" class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                            <i class="fa-solid fa-credit-card w-4 mr-2"></i> Billing
                        </a>
                        <a href="settings.html"
                            class="flex w-full items-center px-2 py-1.5 text-sm hover:bg-accent rounded-sm">
                            <i class="fa-solid fa-gear w-4 mr-2"></i> Settings
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="login.html"
                            class="flex w-full items-center px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10 rounded-sm">
                            <i class="fa-solid fa-arrow-right-from-bracket w-4 mr-2"></i> Log out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 space-y-6">

            <!-- DASHBOARD SECTION -->
            <div x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

                <h2 class="text-3xl font-bold tracking-tight mb-6">Halo, Budi! 👋</h2>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

                    <!-- Skeletons -->
                    <template x-for="i in 4" :key="i">
                        <div x-show="loading" class="bg-card p-6 rounded-xl border shadow-sm animate-pulse">
                            <div class="flex justify-between items-start">
                                <div class="space-y-2 w-full">
                                    <div class="h-4 bg-muted rounded w-1/2"></div>
                                    <div class="h-8 bg-muted rounded w-16"></div>
                                </div>
                                <div class="w-10 h-10 bg-muted rounded-lg"></div>
                            </div>
                            <div class="mt-4 h-3 bg-muted rounded w-2/3"></div>
                        </div>
                    </template>

                    <!-- Real Content -->
                    <template x-if="!loading">
                        <div class="contents">
                            <!-- Card 1 -->
                            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Total Percakapan</p>
                                        <h3 class="text-2xl font-bold mt-2">1,240</h3>
                                    </div>
                                    <div class="p-2 bg-primary/10 text-primary rounded-lg">
                                        <i class="fa-solid fa-comments"></i>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-xs text-emerald-500 font-medium">
                                    <i class="fa-solid fa-arrow-up mr-1"></i> 12% dari bulan lalu
                                </div>
                            </div>

                            <!-- Card 2 -->
                            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Dijawab AI</p>
                                        <h3 class="text-2xl font-bold mt-2">985</h3>
                                    </div>
                                    <div
                                        class="p-2 bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 rounded-lg">
                                        <i class="fa-solid fa-robot"></i>
                                    </div>
                                </div>
                                <div class="mt-4 text-xs text-muted-foreground">
                                    79% Otomatisasi
                                </div>
                            </div>

                            <!-- Card 3 -->
                            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Butuh Manusia</p>
                                        <h3 class="text-2xl font-bold mt-2">255</h3>
                                    </div>
                                    <div
                                        class="p-2 bg-orange-50 text-orange-600 dark:bg-orange-950 dark:text-orange-400 rounded-lg">
                                        <i class="fa-solid fa-headset"></i>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center text-xs text-destructive font-medium">
                                    <i class="fa-solid fa-arrow-up mr-1"></i> Perlu perhatian
                                </div>
                            </div>

                            <!-- Card 4 -->
                            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-muted-foreground">Sentimen User</p>
                                        <h3 class="text-2xl font-bold mt-2">4.8/5</h3>
                                    </div>
                                    <div
                                        class="p-2 bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 rounded-lg">
                                        <i class="fa-solid fa-face-smile"></i>
                                    </div>
                                </div>
                                <div class="mt-4 text-xs text-muted-foreground">
                                    Berdasarkan feedback
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 md:grid-cols-7 gap-6">

                    <!-- Chart area -->
                    <div class="md:col-span-4 bg-card text-card-foreground p-6 rounded-xl border shadow-sm relative">
                        <!-- Skeleton -->
                        <div x-show="loading" class="animate-pulse space-y-4">
                            <div class="h-6 w-1/3 bg-muted rounded"></div>
                            <div class="h-[250px] bg-muted rounded-lg"></div>
                        </div>

                        <!-- Content -->
                        <div x-show="!loading">
                            <h4 class="font-semibold mb-4">Aktivitas Chat 7 Hari Terakhir</h4>
                            <div class="h-[300px] w-full">
                                <canvas id="chatChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Topics Area -->
                    <div class="md:col-span-3 bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                        <!-- Skeleton -->
                        <div x-show="loading" class="animate-pulse space-y-6">
                            <div class="h-6 w-1/2 bg-muted rounded mb-6"></div>
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <div class="h-4 w-1/3 bg-muted rounded"></div>
                                        <div class="h-4 w-8 bg-muted rounded"></div>
                                    </div>
                                    <div class="h-2 w-full bg-muted rounded"></div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <div class="h-4 w-1/3 bg-muted rounded"></div>
                                        <div class="h-4 w-8 bg-muted rounded"></div>
                                    </div>
                                    <div class="h-2 w-full bg-muted rounded"></div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <div class="h-4 w-1/3 bg-muted rounded"></div>
                                        <div class="h-4 w-8 bg-muted rounded"></div>
                                    </div>
                                    <div class="h-2 w-full bg-muted rounded"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div x-show="!loading" class="space-y-6">
                            <h4 class="font-semibold mb-6">Topik Terpopuler</h4>
                            <div class="space-y-6">
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-muted-foreground">Harga Produk</span>
                                        <span class="font-bold">45%</span>
                                    </div>
                                    <div class="w-full bg-secondary rounded-full h-2">
                                        <div class="bg-primary h-2 rounded-full" style="width: 45%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-muted-foreground">Jam Operasional</span>
                                        <span class="font-bold">30%</span>
                                    </div>
                                    <div class="w-full bg-secondary rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" style="width: 30%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-muted-foreground">Cara Retur</span>
                                        <span class="font-bold">15%</span>
                                    </div>
                                    <div class="w-full bg-secondary rounded-full h-2">
                                        <div class="bg-orange-500 h-2 rounded-full" style="width: 15%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Chart Config -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Check for dark mode initially to set chart colors
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? '#334155' : '#f1f5f9';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            const ctx = document.getElementById('chatChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Total Chat',
                        data: [65, 59, 80, 81, 56, 95, 110],
                        borderColor: '#3b82f6',
                        backgroundColor: (context) => {
                            const bg = context.chart.ctx.createLinearGradient(0, 0, 0, 300);
                            bg.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
                            bg.addColorStop(1, 'rgba(59, 130, 246, 0)');
                            return bg;
                        },
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? '#1e293b' : '#ffffff',
                            titleColor: isDark ? '#f8fafc' : '#0f172a',
                            bodyColor: isDark ? '#e2e8f0' : '#334155',
                            borderColor: isDark ? '#334155' : '#e2e8f0',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                }
            });
        });
    </script>
</body>

</html>