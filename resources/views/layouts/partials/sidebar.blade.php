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
            <span x-show="!sidebarCollapsed" class="font-bold text-lg tracking-tight transition-opacity duration-200">
                Cekat<span class="text-primary">.biz.id</span>
            </span>
        </div>
    </div>

    <!-- Nav Links -->
    <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-2">

        @if(auth()->user()->isAdmin())
            {{-- ADMIN MENU --}}
            <div x-show="!sidebarCollapsed"
                class="px-2 mb-2 text-xs font-semibold text-muted-foreground transition-opacity duration-200">
                ADMIN
            </div>

            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-gauge w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Dashboard</span>
            </a>

            <a href="{{ route('admin.landing-chatbot') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.landing-chatbot') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-globe w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Landing Chatbot</span>
            </a>

            <a href="{{ route('admin.transactions') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.transactions') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-credit-card w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Transactions</span>
            </a>

            <a href="{{ route('admin.users') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.users') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-users w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Users</span>
            </a>

            <a href="{{ route('admin.plans') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.plans') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-box w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Plans</span>
            </a>

            <a href="{{ route('admin.billing') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.billing') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Billing</span>
            </a>

            <a href="{{ route('admin.chat-inbox') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.chat-inbox') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-inbox w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="font-medium whitespace-nowrap transition-opacity duration-200">Chat
                    Inbox</span>
            </a>

            <a href="{{ route('admin.integration') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.integration') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-plug w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Integration</span>
            </a>

            <a href="{{ route('admin.whatsapp') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.whatsapp') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-brands fa-whatsapp w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">WhatsApp</span>
            </a>

            <a href="{{ route('admin.settings') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('admin.settings') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-cog w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Settings</span>
            </a>

        @else
            {{-- USER MENU --}}
            <div x-show="!sidebarCollapsed"
                class="px-2 mb-2 text-xs font-semibold text-muted-foreground transition-opacity duration-200">
                MENU UTAMA
            </div>

            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-chart-pie w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Dashboard</span>
            </a>

            <a href="{{ route('agents.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('agents.*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-brain w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="font-medium whitespace-nowrap transition-opacity duration-200">AI
                    Agents</span>
                <span x-show="!sidebarCollapsed"
                    class="ml-auto text-[10px] bg-primary/10 text-primary px-1.5 py-0.5 rounded">NEW</span>
            </a>

            <a href="{{ route('chatbots.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('chatbots.*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-robot w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Chatbot Widget</span>
            </a>

            <a href="{{ route('chats.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('chats.*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-comments w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed" class="font-medium whitespace-nowrap transition-opacity duration-200">Chat
                    History</span>
            </a>

            <a href="{{ route('leads.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('leads.*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-user-plus w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Leads</span>
            </a>

            @if(\App\Services\WhatsApp\WhatsAppManager::isEnabled())
                <a href="{{ route('whatsapp.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('whatsapp.*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                    <i class="fa-brands fa-whatsapp w-5 text-center text-base shrink-0"></i>
                    <span x-show="!sidebarCollapsed"
                        class="font-medium whitespace-nowrap transition-opacity duration-200">WhatsApp</span>
                </a>
            @endif

            <div class="my-4 border-t border-border mx-2"></div>

            <div x-show="!sidebarCollapsed"
                class="px-2 mb-2 text-xs font-semibold text-muted-foreground transition-opacity duration-200">
                PENGATURAN
            </div>

            <a href="{{ route('settings') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('settings') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-gear w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Settings</span>
            </a>

            <a href="{{ route('billing') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('billing') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-credit-card w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Billing</span>
            </a>

            <a href="{{ route('integration') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors group relative {{ request()->routeIs('integration') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                <i class="fa-solid fa-plug w-5 text-center text-base shrink-0"></i>
                <span x-show="!sidebarCollapsed"
                    class="font-medium whitespace-nowrap transition-opacity duration-200">Integration</span>
            </a>
        @endif
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t bg-muted/20">
        @php
            $user = auth()->user();
            $plan = $user->plan;
            $quota = $plan->max_messages_per_month ?? 100;
            $used = $user->monthly_message_used ?? 0;
            $percentage = $quota > 0 ? min(($used / $quota) * 100, 100) : 0;

            // Determine warning level
            $sidebarWarning = 'normal';
            $quotaColors = [
                'bg' => 'bg-primary/10',
                'border' => 'border-primary/20',
                'bar' => 'bg-primary',
                'text' => 'text-primary',
            ];

            if ($percentage >= 100) {
                $sidebarWarning = 'exceeded';
                $quotaColors = [
                    'bg' => 'bg-red-50 dark:bg-red-900/30',
                    'border' => 'border-red-400',
                    'bar' => 'bg-red-500',
                    'text' => 'text-red-600',
                ];
            } elseif ($percentage >= 90) {
                $sidebarWarning = 'critical';
                $quotaColors = [
                    'bg' => 'bg-amber-50 dark:bg-amber-900/30',
                    'border' => 'border-amber-400',
                    'bar' => 'bg-amber-500',
                    'text' => 'text-amber-600',
                ];
            } elseif ($percentage >= 75) {
                $sidebarWarning = 'warning';
                $quotaColors = [
                    'bg' => 'bg-yellow-50 dark:bg-yellow-900/30',
                    'border' => 'border-yellow-400',
                    'bar' => 'bg-yellow-500',
                    'text' => 'text-yellow-600',
                ];
            }
        @endphp
        <div x-show="!sidebarCollapsed"
            class="{{ $quotaColors['bg'] }} p-3 rounded-lg border {{ $quotaColors['border'] }}">
            <div class="flex justify-between items-center mb-1">
                <p class="text-xs font-semibold {{ $quotaColors['text'] }}">
                    @if($sidebarWarning === 'exceeded')
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>Kuota Habis!
                    @elseif($sidebarWarning === 'critical')
                        <i class="fa-solid fa-exclamation-circle mr-1"></i>Kuota Hampir Habis
                    @else
                        Plan: {{ $plan->name ?? 'Starter' }}
                    @endif
                </p>
            </div>
            <div
                class="w-full {{ $sidebarWarning === 'normal' ? 'bg-primary/20' : 'bg-gray-200 dark:bg-gray-700' }} rounded-full h-1.5 mb-2">
                <div class="{{ $quotaColors['bar'] }} h-1.5 rounded-full transition-all"
                    style="width: {{ $percentage }}%"></div>
            </div>
            <p class="text-[10px] text-muted-foreground">
                {{ number_format($used, 0, ',', '.') }} / {{ number_format($quota, 0, ',', '.') }} Messages
            </p>
            @if($sidebarWarning !== 'normal')
                <a href="{{ route('billing') }}"
                    class="block mt-2 text-center px-2 py-1 {{ $sidebarWarning === 'exceeded' ? 'bg-red-500 hover:bg-red-600' : 'bg-amber-500 hover:bg-amber-600' }} text-white text-xs rounded font-medium transition">
                    <i class="fa-solid fa-arrow-up mr-1"></i>Upgrade
                </a>
            @endif
        </div>
    </div>
</aside>