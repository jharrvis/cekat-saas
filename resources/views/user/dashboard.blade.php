@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
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
            <div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Kuota Pesan</p>
                        <h3 class="text-2xl font-bold mt-2">{{ number_format($user->monthly_message_used) }}</h3>
                    </div>
                    <div class="p-2 bg-orange-50 text-orange-600 dark:bg-orange-950 dark:text-orange-400 rounded-lg">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="w-full bg-secondary rounded-full h-1.5">
                        <div class="bg-primary h-1.5 rounded-full" style="width: {{ min($usagePercent, 100) }}%"></div>
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">{{ $usagePercent }}% dari
                        {{ number_format($user->monthly_message_quota) }} pesan</p>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 md:grid-cols-7 gap-6">
            {{-- Chart Area --}}
            <div class="md:col-span-4 bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <h4 class="font-semibold mb-4">Aktivitas Chat 7 Hari Terakhir</h4>
                <div class="h-[300px] w-full">
                    <canvas id="chatChart"></canvas>
                </div>
            </div>

            {{-- Topics Area --}}
            <div class="md:col-span-3 bg-card text-card-foreground p-6 rounded-xl border shadow-sm">
                <h4 class="font-semibold mb-6">Topik Terpopuler</h4>
                <div class="space-y-6">
                    @forelse($topics as $index => $topic)
                        @php
                            $colors = ['bg-primary', 'bg-indigo-500', 'bg-orange-500', 'bg-emerald-500'];
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-muted-foreground">{{ $topic['name'] }}</span>
                                <span class="font-bold">{{ $topic['percent'] }}%</span>
                            </div>
                            <div class="w-full bg-secondary rounded-full h-2">
                                <div class="{{ $colors[$index % count($colors)] }} h-2 rounded-full"
                                    style="width: {{ $topic['percent'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted-foreground text-sm">Belum ada data topik</p>
                    @endforelse
                </div>
            </div>
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