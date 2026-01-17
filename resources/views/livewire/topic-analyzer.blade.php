<div class="bg-card text-card-foreground p-6 rounded-xl border shadow-sm" wire:poll.keep-alive>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h4 class="font-semibold flex items-center gap-2">
            <span class="text-lg">🔥</span> Topik Terpopuler
        </h4>
        <div class="flex items-center gap-2">
            @if($isPaidUser)
                <button wire:click="summarize" wire:loading.attr="disabled"
                    class="px-3 py-1.5 text-xs font-medium bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition disabled:opacity-50 flex items-center gap-1">
                    <span wire:loading.remove wire:target="summarize">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> AI Summarize
                    </span>
                    <span wire:loading wire:target="summarize">
                        <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                    </span>
                </button>
            @else
                <button onclick="window.location.href='{{ route('billing') }}'"
                    class="px-3 py-1.5 text-xs font-medium bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:from-amber-600 hover:to-orange-600 transition flex items-center gap-1"
                    title="Upgrade untuk AI Summarize">
                    <i class="fa-solid fa-crown"></i> Upgrade
                </button>
            @endif
        </div>
    </div>

    {{-- Last Updated Info --}}
    @if($lastUpdated)
        <p class="text-xs text-muted-foreground mb-3 flex items-center gap-1">
            <i class="fa-solid fa-clock"></i>
            Terakhir update: {{ $lastUpdated->diffForHumans() }}
            @if(!$isPaidUser)
                <span class="text-amber-600 ml-2">• Word frequency</span>
            @else
                <span class="text-green-600 ml-2">• AI-powered</span>
            @endif
        </p>
    @endif

    {{-- Loading Skeleton --}}
    <div wire:loading wire:target="summarize,loadTopics" class="space-y-4 animate-pulse">
        @for($i = 0; $i < 4; $i++)
            <div>
                <div class="flex justify-between mb-1">
                    <div class="h-4 bg-muted rounded w-32"></div>
                    <div class="h-4 bg-muted rounded w-12"></div>
                </div>
                <div class="w-full bg-secondary rounded-full h-2">
                    <div class="bg-muted h-2 rounded-full" style="width: {{ rand(30, 80) }}%"></div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Topics Content --}}
    <div wire:loading.remove wire:target="summarize,loadTopics" class="space-y-4">
        @php
            $colors = ['bg-primary', 'bg-indigo-500', 'bg-orange-500', 'bg-emerald-500'];
        @endphp

        @forelse($topics as $index => $topic)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium">{{ $topic['name'] }}</span>
                    <div class="flex items-center gap-2">
                        @if(isset($topic['count']) && $topic['count'] > 0)
                            <span class="text-xs text-muted-foreground">{{ $topic['count'] }}x</span>
                        @endif
                        <span class="font-bold text-primary">{{ $topic['percent'] }}%</span>
                    </div>
                </div>
                <div class="w-full bg-secondary rounded-full h-2">
                    <div class="{{ $colors[$index % count($colors)] }} h-2 rounded-full transition-all duration-500"
                        style="width: {{ $topic['percent'] }}%"></div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-muted-foreground">
                <i class="fa-solid fa-chart-bar text-3xl mb-2 opacity-50"></i>
                <p class="text-sm">Belum ada data topik</p>
                <p class="text-xs">Mulai percakapan untuk melihat topik</p>
            </div>
        @endforelse

        {{-- Upgrade Teaser for Free Users --}}
        @if(!$isPaidUser && count($topics) > 0)
            <div
                class="mt-4 p-3 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-amber-500 mt-0.5"></i>
                    <div>
                        <p class="text-xs font-medium text-amber-800 dark:text-amber-200">
                            Ingin topik lebih detail?
                        </p>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                            Upgrade untuk AI Summarize: "Berapa" → "Harga paket layanan"
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>