{{-- AI Quality Tier Tab (LLM Abstraction - Model hidden from users) --}}
<div>
    @php
        $isLandingPage = $chatbot->slug === 'landing-page-default';
    @endphp

    @if($isLandingPage)
        {{-- ADMIN: Landing Page Widget - Direct Model Selection --}}
        @livewire('admin.landing-chatbot-model-selector', ['widget' => $chatbot])
    @else
        {{-- USER: Tier-based AI Quality Display --}}
        <h3 class="text-lg font-bold mb-4">AI Quality</h3>
        <p class="text-muted-foreground mb-6">Kualitas AI ditentukan oleh plan Anda</p>

        @php
            $userPlan = auth()->user()->plan;
            $aiTier = $userPlan->ai_tier ?? 'basic';

            $tierInfo = [
                'basic' => [
                    'name' => 'Basic',
                    'icon' => 'fa-brain',
                    'color' => 'gray',
                    'description' => 'Respons cepat, cocok untuk FAQ sederhana',
                    'features' => ['Respons cepat', 'Bahasa Indonesia', 'FAQ handling'],
                ],
                'standard' => [
                    'name' => 'Standard',
                    'icon' => 'fa-brain',
                    'color' => 'blue',
                    'description' => 'Respons lebih natural dan kontekstual',
                    'features' => ['Respons natural', 'Konteks lebih baik', 'Multi-turn conversation'],
                ],
                'advanced' => [
                    'name' => 'Advanced',
                    'icon' => 'fa-brain',
                    'color' => 'purple',
                    'description' => 'Pemahaman kompleks, reasoning lebih baik',
                    'features' => ['Reasoning mendalam', 'Konteks panjang', 'Complex problem solving'],
                ],
                'premium' => [
                    'name' => 'Premium',
                    'icon' => 'fa-crown',
                    'color' => 'amber',
                    'description' => 'AI terbaik dengan reasoning mendalam',
                    'features' => ['AI terbaik', 'Reasoning expert-level', 'Unlimited context'],
                ],
            ];

            $currentTier = $tierInfo[$aiTier] ?? $tierInfo['basic'];
        @endphp

        {{-- Current AI Quality --}}
        <div class="bg-card rounded-xl border p-6 mb-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-xl flex items-center justify-center
                        {{ $currentTier['color'] === 'gray' ? 'bg-gray-100 text-gray-600' : '' }}
                        {{ $currentTier['color'] === 'blue' ? 'bg-blue-100 text-blue-600' : '' }}
                        {{ $currentTier['color'] === 'purple' ? 'bg-purple-100 text-purple-600' : '' }}
                        {{ $currentTier['color'] === 'amber' ? 'bg-amber-100 text-amber-600' : '' }}">
                    <i class="fa-solid {{ $currentTier['icon'] }} text-3xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-2xl font-bold">{{ $currentTier['name'] }}</h3>
                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                            <i class="fa-solid fa-check mr-1"></i>ACTIVE
                        </span>
                    </div>
                    <p class="text-muted-foreground">{{ $currentTier['description'] }}</p>
                </div>
            </div>

            <div class="border-t pt-4">
                <h4 class="font-semibold mb-2">Fitur AI Quality {{ $currentTier['name'] }}:</h4>
                <ul class="grid md:grid-cols-3 gap-2">
                    @foreach($currentTier['features'] as $feature)
                        <li class="flex items-center gap-2 text-sm">
                            <i class="fa-solid fa-check text-green-500"></i>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- All Tiers Comparison --}}
        <h4 class="font-semibold mb-4">Perbandingan AI Quality Tiers</h4>
        <div class="grid md:grid-cols-4 gap-4">
            @foreach($tierInfo as $tierSlug => $tier)
                <div
                    class="border rounded-xl p-4 relative {{ $aiTier === $tierSlug ? 'border-primary ring-2 ring-primary/20' : '' }}">
                    @if($aiTier === $tierSlug)
                        <div
                            class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-primary text-primary-foreground text-xs rounded-full">
                            Current
                        </div>
                    @endif
                    <div class="text-center mb-3">
                        <div class="w-12 h-12 mx-auto rounded-xl flex items-center justify-center mb-2
                                        {{ $tier['color'] === 'gray' ? 'bg-gray-100 text-gray-600' : '' }}
                                        {{ $tier['color'] === 'blue' ? 'bg-blue-100 text-blue-600' : '' }}
                                        {{ $tier['color'] === 'purple' ? 'bg-purple-100 text-purple-600' : '' }}
                                        {{ $tier['color'] === 'amber' ? 'bg-amber-100 text-amber-600' : '' }}">
                            <i class="fa-solid {{ $tier['icon'] }} text-xl"></i>
                        </div>
                        <h4 class="font-semibold">{{ $tier['name'] }}</h4>
                    </div>
                    <p class="text-xs text-center text-muted-foreground">{{ $tier['description'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Upgrade CTA --}}
        @if($aiTier !== 'premium')
            <div class="mt-6 bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold">Ingin AI yang lebih pintar?</h4>
                        <p class="text-sm text-muted-foreground">Upgrade plan Anda untuk akses AI Quality yang lebih tinggi</p>
                    </div>
                    <a href="{{ route('billing') }}" class="btn-primary">
                        <i class="fa-solid fa-arrow-up mr-2"></i>Upgrade Plan
                    </a>
                </div>
            </div>
        @endif

        {{-- Info Box --}}
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <i class="fa-solid fa-info-circle mr-2"></i>
                <strong>Bagaimana AI Quality bekerja?</strong><br>
                AI Quality menentukan seberapa "pintar" chatbot Anda dalam memahami dan merespons pertanyaan customer.
                Semakin tinggi tier, semakin baik kemampuan AI dalam reasoning, konteks, dan respons natural.
            </p>
        </div>
    @endif
</div>