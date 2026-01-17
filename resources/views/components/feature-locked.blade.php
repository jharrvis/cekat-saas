@props(['locked' => false, 'featureName' => 'Feature', 'description' => 'Upgrade your plan to access this premium feature.'])

<div class="relative">
    @if($locked)
        <div
            class="absolute inset-x-0 top-0 bottom-0 z-10 flex flex-col items-center justify-center bg-white/60 dark:bg-slate-900/60 backdrop-blur-[2px] rounded-xl border border-dashed border-primary/20 p-6 text-center">
            <div class="bg-card shadow-lg ring-1 ring-border p-6 rounded-2xl max-w-sm mx-auto">
                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-lock text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-bold mb-2">{{ $featureName }} Locked</h3>
                <p class="text-sm text-muted-foreground mb-4">{{ $description }}</p>
                <a href="{{ route('billing') }}"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                    <i class="fa-solid fa-rocket mr-2"></i> Upgrade Plan
                </a>
            </div>
        </div>
    @endif

    <div class="{{ $locked ? 'opacity-40 pointer-events-none select-none filter blur-[1px]' : '' }}">
        {{ $slot }}
    </div>
</div>