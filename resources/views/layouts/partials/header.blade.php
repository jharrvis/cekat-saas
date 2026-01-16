<header
    class="h-16 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b flex items-center justify-between px-4 md:px-6 sticky top-0 z-30">
    <div class="flex items-center gap-4">
        <!-- Mobile Toggle -->
        <button @click="sidebarOpen = true"
            class="md:hidden p-2 -ml-2 text-muted-foreground hover:text-foreground rounded-md">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>

        <!-- Desktop Toggle -->
        <button @click="toggleSidebar()"
            class="hidden md:flex p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-md transition-colors mr-2">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>

        <div class="font-semibold text-lg">@yield('page-title', 'Dashboard')</div>
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
        </div>

        <!-- User Menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-2 p-1.5 hover:bg-accent rounded-lg transition-colors">
                <div
                    class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-semibold text-sm">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
            </button>

            <!-- Dropdown -->
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 bg-card border rounded-lg shadow-lg py-1">
                <div class="px-4 py-3 border-b">
                    <p class="text-sm font-medium">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-xs text-muted-foreground">{{ auth()->user()->email ?? 'user@example.com' }}</p>
                </div>
                <a href="{{ route('settings') }}" class="block px-4 py-2 text-sm hover:bg-accent">
                    <i class="fa-solid fa-gear w-4 mr-2"></i> Pengaturan
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-accent text-destructive">
                        <i class="fa-solid fa-right-from-bracket w-4 mr-2"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>