@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        {{-- QUOTA ALERTS --}}
        @if($quotaWarningLevel === 'exceeded')
            <div class="bg-red-50 dark:bg-red-900/30 border-2 border-red-500 rounded-xl p-4 animate-pulse">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-red-800 dark:text-red-200 text-lg">⚠️ Kuota Pesan Habis!</h3>
                        <p class="text-red-700 dark:text-red-300 mt-1">
                            Chatbot Anda tidak akan merespon sampai kuota di-reset bulan depan atau Anda upgrade plan.
                        </p>
                        <div class="mt-3 flex gap-3">
                            <a href="{{ route('billing') }}"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                                <i class="fa-solid fa-arrow-up mr-2"></i> Upgrade Sekarang
                            </a>
                            <span class="text-sm text-red-600 dark:text-red-400 flex items-center">
                                Reset otomatis: {{ now()->endOfMonth()->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($quotaWarningLevel === 'critical')
            <div class="bg-amber-50 dark:bg-amber-900/30 border-2 border-amber-500 rounded-xl p-4">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-full">
                        <i class="fa-solid fa-exclamation-circle text-amber-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-amber-800 dark:text-amber-200">🔥 Kuota Hampir Habis! ({{ $usagePercent }}%)
                        </h3>
                        <p class="text-amber-700 dark:text-amber-300 mt-1">
                            Tersisa <strong>{{ number_format($quotaRemaining) }} pesan</strong>. Jika habis, chatbot tidak akan
                            merespon.
                        </p>
                        <a href="{{ route('billing') }}"
                            class="inline-flex items-center mt-2 text-amber-700 dark:text-amber-300 font-medium hover:underline">
                            <i class="fa-solid fa-arrow-up mr-1"></i> Upgrade untuk kuota lebih besar
                        </a>
                    </div>
                </div>
            </div>
        @elseif($quotaWarningLevel === 'warning')
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-400 rounded-xl p-3">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-info-circle text-yellow-600"></i>
                    <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                        <strong>Kuota {{ $usagePercent }}% terpakai</strong> - Tersisa {{ number_format($quotaRemaining) }}
                        pesan.
                        <a href="{{ route('billing') }}" class="underline font-medium">Upgrade?</a>
                    </p>
                </div>
            </div>
        @endif

        {{-- Welcome Header --}}
        <div>
            <h2 class="text-3xl font-bold tracking-tight">Halo, {{ $user->name }}! 👋</h2>
            <p class="text-muted-foreground mt-1">Lihat performa chatbot Anda hari ini</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Card 1: Total Conversations --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Percakapan</p>
                        <h3 class="text-2xl font-bold mt-2">{{ number_format($totalConversations) }}</h3>
                    </div>
                    <div class="p-2 bg-primary/10 text-primary rounded-lg">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center text-xs font-medium {{ $conversationChange >= 0 ? 'text-emerald-500' : 'text-destructive' }}">
                    <i class="fa-solid fa-arrow-{{ $conversationChange >= 0 ? 'up' : 'down' }} mr-1"></i>
                    {{ abs($conversationChange) }}% dari bulan lalu
                </div>
            </div>

            {{-- Card 2: AI Messages --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Dijawab AI</p>
                        <h3 class="text-2xl font-bold mt-2">{{ number_format($totalAiMessages) }}</h3>
                    </div>
                    <div class="p-2 bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 rounded-lg">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                </div>
                <div class="mt-4 text-xs text-muted-foreground">
                    {{ $automationRate }}% Otomatisasi
                </div>
            </div>

            {{-- Card 3: Leads --}}
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Lead</p>
                        <h3 class="text-2xl font-bold mt-2">{{ number_format($totalLeads) }}</h3>
                    </div>
                    <div class="p-2 bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 rounded-lg">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                </div>
                <div class="mt-4 text-xs text-muted-foreground">
                    {{ $leadConversionRate }}% Konversi
                </div>
            </div>

            {{-- Card 4: Usage --}}
            @php
                $quotaColor = match ($quotaWarningLevel) {
                    'exceeded' => 'bg-red-500',
                    'critical' => 'bg-amber-500',
                    'warning' => 'bg-yellow-500',
                    default => 'bg-primary'
                };
                $quotaBgColor = match ($quotaWarningLevel) {
                    'exceeded' => 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800',
                    'critical' => 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800',
                    default => null
                };
            @endphp
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm {{ $quotaBgColor }}">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Kuota Pesan</p>
                        <h3 class="text-2xl font-bold mt-2">{{ number_format($usedMessages) }}</h3>
                    </div>
                    <div
                        class="p-2 {{ $quotaWarningLevel === 'exceeded' ? 'bg-red-100 text-red-600 dark:bg-red-900' : ($quotaWarningLevel === 'critical' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900' : 'bg-orange-50 text-orange-600 dark:bg-orange-950 dark:text-orange-400') }} rounded-lg">
                        <i
                            class="fa-solid {{ $quotaWarningLevel === 'exceeded' ? 'fa-triangle-exclamation' : 'fa-gauge-high' }}"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-secondary rounded-full h-2">
                        <div class="{{ $quotaColor }} h-2 rounded-full transition-all"
                            style="width: {{ min($usagePercent, 100) }}%"></div>
                    </div>
                    <p
                        class="text-xs {{ $quotaWarningLevel === 'exceeded' ? 'text-red-600 font-semibold' : 'text-muted-foreground' }} mt-1">
                        {{ $usagePercent }}% terpakai ({{ number_format($usedMessages) }} / {{ number_format($quotaLimit) }}
                        pesan)
                    </p>
                </div>
            </div>
        </div>

        {{-- Row 2: Chart + Topics + Response Time --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Chart Area --}}
            <div class="lg:col-span-5 bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <h4 class="font-semibold mb-4">Aktivitas Chat 7 Hari Terakhir</h4>
                <div class="h-[250px] w-full">
                    <canvas id="chatChart"></canvas>
                </div>
            </div>

            {{-- Topics Area - Livewire Component --}}
            <div class="lg:col-span-4">
                @livewire('topic-analyzer')
            </div>

            {{-- Peak Hours + Hot Sessions --}}
            <div class="lg:col-span-3 space-y-6">
                {{-- Peak Hours Card --}}
                <div class="bg-card text-card-foreground p-5 rounded-xl border shadow-sm">
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-clock text-blue-500"></i> Jam Tersibuk
                    </h4>
                    @if(!empty($peakHours['hours']))
                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold text-primary">{{ $peakHours['peak_formatted'] }}</div>
                            <p class="text-xs text-muted-foreground">{{ $peakHours['peak_count'] }} chat</p>
                        </div>
                        <div class="space-y-2">
                            @foreach($peakHours['hours'] as $idx => $hour)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2">
                                        @if($idx === 0)
                                            <i class="fa-solid fa-trophy text-yellow-500 text-xs"></i>
                                        @else
                                            <span class="w-4 text-center text-muted-foreground">{{ $idx + 1 }}</span>
                                        @endif
                                        {{ $hour['formatted'] }}
                                    </span>
                                    <span class="text-muted-foreground">{{ $hour['count'] }} chat</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted-foreground">
                            <i class="fa-solid fa-clock text-2xl mb-2 opacity-50"></i>
                            <p class="text-sm">Belum ada data</p>
                        </div>
                    @endif
                </div>

                {{-- Hot Sessions Card --}}
                <div class="bg-card text-card-foreground p-5 rounded-xl border shadow-sm">
                    <h4 class="font-semibold mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-fire text-orange-500"></i> Chat Terpanjang
                    </h4>
                    <div class="space-y-2">
                        @forelse($hotSessions as $session)
                            <div class="flex items-center justify-between text-sm py-1 border-b border-border/50 last:border-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    @if($session['is_lead'])
                                        <i class="fa-solid fa-star text-yellow-500 text-xs"></i>
                                    @endif
                                    <span class="truncate">{{ $session['visitor_name'] }}</span>
                                </div>
                                <span class="px-2 py-0.5 bg-primary/10 text-primary rounded-full text-xs font-medium shrink-0">
                                    {{ $session['message_count'] }} msg
                                </span>
                            </div>
                        @empty
                            <p class="text-center text-muted-foreground text-sm py-4">Belum ada data</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 3: Recent Conversations --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-blue-500"></i> Percakapan Terbaru
                </h4>
                <a href="{{ route('chats.index') }}" class="text-sm text-primary hover:underline">
                    Lihat Semua <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>

            @if(count($recentConversations) > 0)
                <div class="space-y-4">
                    @foreach($recentConversations as $conv)
                        <div class="p-4 border rounded-lg hover:bg-muted/30 transition">
                            {{-- Header Row --}}
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                        @if($conv['is_lead'])
                                            <i class="fa-solid fa-star text-yellow-500"></i>
                                        @else
                                            <i class="fa-solid fa-user text-primary"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ $conv['visitor_name'] }}</span>
                                            @if($conv['visitor_email'])
                                                <span class="text-xs text-muted-foreground">{{ $conv['visitor_email'] }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span><i class="fa-solid fa-robot mr-1"></i>{{ $conv['widget_name'] }}</span>
                                            <span>•</span>
                                            <span>{{ $conv['time_ago'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    {{-- Status Badge --}}
                                    @php
                                        $statusStyles = [
                                            'active' => 'bg-green-100 text-green-700',
                                            'converted' => 'bg-blue-100 text-blue-700',
                                            'ended' => 'bg-gray-100 text-gray-600',
                                            'inactive' => 'bg-yellow-100 text-yellow-700',
                                        ];
                                        $statusLabels = [
                                            'active' => 'Aktif',
                                            'converted' => 'Lead',
                                            'ended' => 'Selesai',
                                            'inactive' => 'Tidak Aktif',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusStyles[$conv['status']] }}">
                                        {{ $statusLabels[$conv['status']] }}
                                    </span>
                                </div>
                            </div>

                            {{-- First Message Preview --}}
                            @if($conv['first_message'])
                                <div class="mb-2 pl-13">
                                    <p class="text-sm text-foreground bg-muted/50 px-3 py-2 rounded-lg inline-block max-w-full">
                                        "{{ $conv['first_message'] }}"
                                    </p>
                                </div>
                            @endif

                            {{-- Topics & Stats Row --}}
                            <div class="flex items-center justify-between mt-2 pl-13">
                                <div class="flex items-center gap-2 flex-wrap">
                                    {{-- Topic Tags --}}
                                    @if(!empty($conv['topics']))
                                        @foreach($conv['topics'] as $topic)
                                            <span class="px-2 py-0.5 bg-primary/10 text-primary rounded text-xs">
                                                {{ $topic }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                    <span><i class="fa-solid fa-message mr-1"></i>{{ $conv['message_count'] }} pesan</span>
                                    @if($conv['duration'])
                                        <span><i class="fa-solid fa-clock mr-1"></i>{{ $conv['duration'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-muted-foreground">
                    <i class="fa-solid fa-comments text-4xl mb-3 opacity-50"></i>
                    <p>Belum ada percakapan</p>
                    <p class="text-sm">Percakapan akan muncul setelah widget digunakan</p>
                </div>
            @endif
        </div>

        {{-- Widgets Section --}}
        <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-semibold">Chatbot Anda</h4>
                <a href="{{ route('chatbots.create') }}" class="text-sm text-primary hover:underline">
                    <i class="fa-solid fa-plus mr-1"></i> Buat Baru
                </a>
            </div>

            @if($widgets->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($widgets as $widget)
                        <a href="{{ route('chatbots.edit', $widget) }}"
                            class="p-4 border rounded-lg hover:border-primary hover:bg-primary/5 transition group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                                    style="background-color: {{ $widget->settings['color'] ?? '#3b82f6' }}">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate group-hover:text-primary">
                                        {{ $widget->display_name ?? $widget->name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $widget->chat_sessions_count }} percakapan
                                    </p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted-foreground group-hover:text-primary"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fa-solid fa-robot text-4xl text-muted-foreground mb-4"></i>
                    <p class="text-muted-foreground">Belum ada chatbot</p>
                    <a href="{{ route('chatbots.create') }}"
                        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                        <i class="fa-solid fa-plus"></i> Buat Chatbot Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>

    @push('head-scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? '#334155' : '#f1f5f9';
                const textColor = isDark ? '#94a3b8' : '#64748b';

                const ctx = document.getElementById('chatChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json(array_column($chatActivity, 'label')),
                        datasets: [{
                            label: 'Total Chat',
                            data: @json(array_column($chatActivity, 'count')),
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
    @endpush
@endsection