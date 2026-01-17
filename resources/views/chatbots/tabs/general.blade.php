{{-- General Tab --}}
<div x-data="{ showAiInfo: true }">
    <h3 class="text-lg font-bold mb-4">General Information</h3>
    <p class="text-muted-foreground mb-6">Basic settings for your chatbot widget</p>

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- Left Column: Form --}}
        <div>
            <form action="{{ route('chatbots.update', $chatbot->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Chatbot Name --}}
                <div>
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        Chatbot Name *
                        <x-help-tooltip text="The internal name for this chatbot, visible only to you." />
                    </label>
                    <input type="text" name="display_name" value="{{ $chatbot->display_name }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="e.g., Customer Support Bot" required>
                    @error('display_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        Description
                        <x-help-tooltip text="A brief description to help you organize your chatbots." />
                    </label>
                    <textarea name="description" rows="2"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Brief description...">{{ $chatbot->description }}</textarea>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        Status
                        <x-help-tooltip text="Controls whether the chatbot is publicly accessible." />
                    </label>
                    <select name="status"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="draft" {{ $chatbot->status === 'draft' ? 'selected' : '' }}>🔒 Draft</option>
                        <option value="active" {{ $chatbot->status === 'active' ? 'selected' : '' }}>✅ Active</option>
                        <option value="inactive" {{ $chatbot->status === 'inactive' ? 'selected' : '' }}>⏸️ Inactive
                        </option>
                    </select>
                </div>

                {{-- Allowed Domain (1 per widget) --}}
                <div
                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        <i class="fa-solid fa-shield-halved text-amber-600 mr-2"></i>
                        Allowed Domain *
                        <x-help-tooltip text="The domain where this widget can be embedded." />
                    </label>
                    <input type="text" name="allowed_domains" value="{{ $chatbot->settings['allowed_domains'] ?? '' }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800"
                        placeholder="e.g., mysite.com" required>
                    <div class="mt-2 text-xs text-amber-700 dark:text-amber-300 space-y-1">
                        <p><i class="fa-solid fa-info-circle mr-1"></i> <strong>1 widget = 1 domain</strong> (termasuk
                            subdomain)</p>
                        <p><i class="fa-solid fa-check mr-1"></i> <code
                                class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">mysite.com</code> → izin <code
                                class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">www.mysite.com</code>, <code
                                class="bg-amber-100 dark:bg-amber-800/50 px-1 rounded">blog.mysite.com</code></p>
                    </div>
                    @error('allowed_domains') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Save Button --}}
                <div class="pt-2">
                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                        <i class="fa-solid fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Column: AI Quality Info --}}
        <div>
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

            {{-- AI Quality Header --}}
            <div class="bg-card rounded-xl border p-5 mb-4">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center
                            {{ $currentTier['color'] === 'gray' ? 'bg-gray-100 text-gray-600' : '' }}
                            {{ $currentTier['color'] === 'blue' ? 'bg-blue-100 text-blue-600' : '' }}
                            {{ $currentTier['color'] === 'purple' ? 'bg-purple-100 text-purple-600' : '' }}
                            {{ $currentTier['color'] === 'amber' ? 'bg-amber-100 text-amber-600' : '' }}">
                        <i class="fa-solid {{ $currentTier['icon'] }} text-2xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-bold">{{ $currentTier['name'] }}</h3>
                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                <i class="fa-solid fa-check mr-1"></i>ACTIVE
                            </span>
                        </div>
                        <p class="text-muted-foreground text-sm">{{ $currentTier['description'] }}</p>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h5 class="font-medium mb-2 text-sm">Fitur AI Quality {{ $currentTier['name'] }}:</h5>
                    <ul class="space-y-1">
                        @foreach($currentTier['features'] as $feature)
                            <li class="flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-check text-green-500"></i>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Collapsible Tier Comparison --}}
            <div class="border rounded-xl overflow-hidden">
                <button @click="showAiInfo = !showAiInfo"
                    class="w-full flex items-center justify-between p-4 bg-muted/30 hover:bg-muted/50 transition text-left">
                    <span class="font-medium text-sm">Perbandingan AI Quality Tiers</span>
                    <i class="fa-solid" :class="showAiInfo ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>

                <div x-show="showAiInfo" x-collapse class="p-4 bg-white dark:bg-slate-900">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($tierInfo as $tierSlug => $tier)
                            <div
                                class="border rounded-lg p-3 relative text-center {{ $aiTier === $tierSlug ? 'border-primary ring-2 ring-primary/20 bg-primary/5' : '' }}">
                                @if($aiTier === $tierSlug)
                                    <div
                                        class="absolute -top-2 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-primary text-primary-foreground text-xs rounded-full">
                                        Current
                                    </div>
                                @endif
                                <div class="w-8 h-8 mx-auto rounded-lg flex items-center justify-center mb-2
                                                {{ $tier['color'] === 'gray' ? 'bg-gray-100 text-gray-600' : '' }}
                                                {{ $tier['color'] === 'blue' ? 'bg-blue-100 text-blue-600' : '' }}
                                                {{ $tier['color'] === 'purple' ? 'bg-purple-100 text-purple-600' : '' }}
                                                {{ $tier['color'] === 'amber' ? 'bg-amber-100 text-amber-600' : '' }}">
                                    <i class="fa-solid {{ $tier['icon'] }} text-sm"></i>
                                </div>
                                <h4 class="font-semibold text-sm">{{ $tier['name'] }}</h4>
                                <p class="text-xs text-muted-foreground mt-1 line-clamp-2">{{ $tier['description'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Upgrade CTA --}}
                    @if($aiTier !== 'premium')
                        <div
                            class="mt-4 bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-sm">Ingin AI lebih pintar?</h4>
                                    <p class="text-xs text-muted-foreground">Upgrade untuk AI Quality lebih tinggi</p>
                                </div>
                                <a href="{{ route('billing') }}"
                                    class="px-3 py-1.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-xs font-medium">
                                    <i class="fa-solid fa-arrow-up mr-1"></i>Upgrade
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>