<div class="border rounded-xl p-4 bg-white dark:bg-slate-900">
    <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
        <div>
            <h3 class="text-lg font-semibold">{{ __('admin.s.ai_usage_per_model') }}</h3>
            <p class="text-sm text-muted-foreground">{{ __('admin.s.ai_usage_desc') }}</p>
        </div>
        <div class="flex gap-2">
            @foreach([7, 30, 90] as $period)
                <button wire:click="setDays({{ $period }})"
                    class="px-3 py-1 rounded-lg text-sm border {{ $days === $period ? 'bg-brand-600 text-white border-brand-600' : 'hover:bg-gray-50 dark:hover:bg-slate-800' }}">
                    {{ $period }} {{ __('admin.s.days') }}
                </button>
            @endforeach
        </div>
    </div>

    @if (empty($rows))
        <p class="text-sm text-muted-foreground py-6 text-center">{{ __('admin.s.no_usage_data') }}</p>
    @else
        <div class="grid lg:grid-cols-2 gap-6">
            <div wire:ignore>
                <canvas id="aiUsageChart" height="260"></canvas>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2 pr-2">{{ __('admin.s.model') }}</th>
                            <th class="py-2 pr-2 text-right">{{ __('admin.s.replies') }}</th>
                            <th class="py-2 pr-2 text-right">{{ __('admin.s.tokens') }}</th>
                            <th class="py-2 text-right">{{ __('admin.s.share') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr class="border-b last:border-0">
                                <td class="py-2 pr-2 font-mono text-xs">{{ $row['model'] }}</td>
                                <td class="py-2 pr-2 text-right">{{ number_format($row['messages']) }}</td>
                                <td class="py-2 pr-2 text-right">{{ number_format($row['tokens']) }}</td>
                                <td class="py-2 text-right">{{ $row['share'] }}%</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="py-2 pr-2">{{ __('admin.s.total') }}</td>
                            <td class="py-2 pr-2 text-right">{{ number_format($totalMessages) }}</td>
                            <td class="py-2 pr-2 text-right">{{ number_format($totalTokens) }}</td>
                            <td class="py-2 text-right">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (function () {
                const ctx = document.getElementById('aiUsageChart');
                if (!ctx || typeof Chart === 'undefined') return;
                const chart = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json(array_column($rows, 'model')),
                        datasets: [{
                            label: @json(__('admin.s.replies')),
                            data: @json(array_column($rows, 'messages')),
                            backgroundColor: 'rgba(79, 70, 229, 0.65)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
                window.addEventListener('ai-usage-updated', (e) => {
                    chart.data.labels = e.detail.labels;
                    chart.data.datasets[0].data = e.detail.values;
                    chart.update();
                });
            })();
        </script>
    @endif
</div>
