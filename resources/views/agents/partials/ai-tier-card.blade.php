{{-- AI Tier Card Component --}}
@php
    $userPlan = Auth::user()->plan;
    $currentTier = $userPlan ? ($userPlan->ai_tier ?? 'basic') : 'basic';

    $tiers = [
        'basic' => [
            'icon' => '🌱',
            'name' => 'Basic',
            'description' => 'Respons cepat, cocok untuk FAQ sederhana',
            'color' => 'blue',
            'features' => ['Respons cepat', 'Bahasa Indonesia', 'FAQ handling'],
        ],
        'standard' => [
            'icon' => '⭐',
            'name' => 'Standard',
            'description' => 'Respons lebih natural dan kontekstual',
            'color' => 'indigo',
            'features' => ['Semua fitur Basic', 'Respons natural', 'Konteks lebih baik'],
        ],
        'advanced' => [
            'icon' => '🚀',
            'name' => 'Advanced',
            'description' => 'Pemahaman kompleks, reasoning lebih baik',
            'color' => 'purple',
            'features' => ['Reasoning mendalam', 'Konteks panjang', 'Complex problem solving'],
        ],
        'premium' => [
            'icon' => '👑',
            'name' => 'Premium',
            'description' => 'AI terbaik dengan reasoning mendalam',
            'color' => 'amber',
            'features' => ['Model AI terbaik', 'Reasoning canggih', 'Unlimited context'],
        ],
    ];

    $currentTierData = $tiers[$currentTier] ?? $tiers['basic'];
@endphp

<div class="bg-card border rounded-xl overflow-hidden">
    {{-- Current Tier Header --}}
    <div
        class="p-5 bg-gradient-to-r from-{{ $currentTierData['color'] }}-50 to-{{ $currentTierData['color'] }}-100 dark:from-{{ $currentTierData['color'] }}-900/30 dark:to-{{ $currentTierData['color'] }}-800/30 border-b">
        <div class="flex items-center gap-3">
            <div
                class="w-12 h-12 rounded-xl bg-{{ $currentTierData['color'] }}-500 flex items-center justify-center text-white text-2xl">
                {{ $currentTierData['icon'] }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-lg">{{ $currentTierData['name'] }}</h3>
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                        <i class="fa-solid fa-check mr-1"></i>ACTIVE
                    </span>
                </div>
                <p class="text-sm text-muted-foreground">{{ $currentTierData['description'] }}</p>
            </div>
        </div>
    </div>

    {{-- Features --}}
    <div class="p-5 border-b">
        <h4 class="text-sm font-medium mb-3">Fitur AI Quality {{ $currentTierData['name'] }}:</h4>
        <ul class="space-y-2">
            @foreach($currentTierData['features'] as $feature)
                <li class="flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-check text-green-500"></i>
                    <span>{{ $feature }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Tier Comparison (Collapsible) --}}
    <div x-data="{ open: false }">
        <button @click="open = !open"
            class="w-full p-4 flex items-center justify-between text-sm font-medium hover:bg-muted/50 transition">
            <span>Perbandingan AI Quality Tiers</span>
            <i class="fa-solid fa-chevron-down transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>

        <div x-show="open" x-collapse x-cloak class="border-t">
            <div class="grid grid-cols-2 gap-3 p-4">
                @foreach($tiers as $tierKey => $tier)
                    <div
                        class="p-3 rounded-lg border-2 transition
                            {{ $tierKey === $currentTier ? 'border-primary bg-primary/5' : 'border-transparent bg-muted/30' }}">
                        @if($tierKey === $currentTier)
                            <span
                                class="px-2 py-0.5 bg-primary text-white text-xs rounded-full mb-2 inline-block">Current</span>
                        @endif
                        <div class="flex flex-col items-center text-center">
                            <span class="text-2xl mb-1">{{ $tier['icon'] }}</span>
                            <span class="font-semibold text-sm">{{ $tier['name'] }}</span>
                            <p class="text-xs text-muted-foreground mt-1 line-clamp-2">{{ $tier['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Upgrade CTA --}}
    @if($currentTier !== 'premium')
        <div class="p-4 bg-gradient-to-r from-primary/10 to-indigo-500/10 border-t">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-sm">Ingin AI lebih pintar?</p>
                    <p class="text-xs text-muted-foreground">Upgrade untuk AI Quality lebih tinggi</p>
                </div>
                <a href="{{ route('billing') }}"
                    class="px-4 py-2 bg-primary text-primary-foreground text-sm rounded-lg hover:bg-primary/90 transition whitespace-nowrap">
                    <i class="fa-solid fa-arrow-up mr-1"></i> Upgrade
                </a>
            </div>
        </div>
    @endif
</div>