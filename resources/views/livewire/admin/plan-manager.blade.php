<div>
    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ __('admin.s.plan_management') }}</h2>
            <p class="text-muted-foreground">{{ __('admin.s.manage_subscription_plans_and_pricing_tiers') }}</p>
        </div>
        @if(!$showForm)
            <button wire:click="createPlan"
                class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-plus mr-2"></i>{{ __('admin.s.create_new_plan') }}</button>
        @endif
    </div>

    {{-- Plan Form --}}
    @if($showForm)
        <div class="bg-card rounded-xl shadow-sm border p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">{{ $plan_id ? 'Edit Plan' : 'Create New Plan' }}</h3>

            <form wire:submit.prevent="savePlan" class="space-y-6">
                {{-- Basic Info --}}
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.plan_name') }}</label>
                        <input type="text" wire:model.live="name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.slug') }}</label>
                        <input type="text" wire:model="slug"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('slug') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ __('channels.s.description') }}</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.price_rp') }}</label>
                        <input type="number" wire:model="price" min="0"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.billing_period') }}</label>
                        <select wire:model="billing_period"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="monthly">{{ __('admin.s.monthly') }}</option>
                            <option value="yearly">{{ __('admin.s.yearly') }}</option>
                        </select>
                    </div>
                </div>

                {{-- Limits --}}
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">{{ __('admin.s.limits') }}</h4>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_widgets') }}</label>
                            <input type="number" wire:model="max_widgets" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_agents') }}</label>
                            <input type="number" wire:model="max_agents" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_messages_month') }}</label>
                            <input type="number" wire:model="max_messages_per_month" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_documents') }}</label>
                            <input type="number" wire:model="max_documents" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_file_size_mb') }}</label>
                            <input type="number" wire:model="max_file_size_mb" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_faqs') }}</label>
                            <input type="number" wire:model="max_faqs" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.chat_history_days') }}</label>
                            <input type="number" wire:model="chat_history_days" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_whatsapp_devices') }}</label>
                            <input type="number" wire:model="max_whatsapp_devices" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('admin.s.sort_order') }}</label>
                            <input type="number" wire:model="sort_order" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                </div>

                {{-- AI Quality Tier --}}
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">{{ __('admin.s.ai_quality_tier') }}</h4>
                    <p class="text-sm text-muted-foreground mb-3">{{ __('admin.s.pilih_tingkat_kualitas_ai_untuk_plan_ini_model_s') }}<a href="{{ route('admin.settings') }}" class="text-primary hover:underline">{{ __('admin.s.settings_ai_tiers') }}</a>.
                    </p>
                    <div class="grid md:grid-cols-4 gap-3">
                        @php
                            $tiers = [
                                'basic' => ['name' => 'Basic', 'desc' => 'Respons cepat, FAQ sederhana', 'color' => 'gray'],
                                'standard' => ['name' => 'Standard', 'desc' => 'Respons natural', 'color' => 'blue'],
                                'advanced' => ['name' => 'Advanced', 'desc' => 'Reasoning kompleks', 'color' => 'purple'],
                                'premium' => ['name' => 'Premium', 'desc' => 'AI terbaik', 'color' => 'amber'],
                            ];
                        @endphp
                        @foreach($tiers as $tierKey => $tier)
                            <label
                                class="relative flex flex-col p-4 border rounded-lg cursor-pointer transition
                                        {{ $ai_tier === $tierKey ? 'border-primary ring-2 ring-primary/20 bg-primary/5' : 'hover:bg-muted/30' }}">
                                <input type="radio" wire:model="ai_tier" value="{{ $tierKey }}" class="sr-only">
                                <span class="font-medium 
                                            {{ $tier['color'] === 'gray' ? 'text-gray-700' : '' }}
                                            {{ $tier['color'] === 'blue' ? 'text-blue-700' : '' }}
                                            {{ $tier['color'] === 'purple' ? 'text-purple-700' : '' }}
                                            {{ $tier['color'] === 'amber' ? 'text-amber-700' : '' }}">
                                    {{ $tier['name'] }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ $tier['desc'] }}</span>
                                @if($ai_tier === $tierKey)
                                    <div class="absolute top-2 right-2">
                                        <i class="fa-solid fa-check-circle text-primary"></i>
                                    </div>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Features --}}
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">{{ __('admin.s.features') }}</h4>
                    <div class="grid md:grid-cols-2 gap-3">
                        @foreach($availableFeatures as $featureKey => $featureName)
                            <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-muted/30 cursor-pointer">
                                <input type="checkbox" wire:model="features.{{ $featureKey }}" value="true"
                                    class="rounded border-gray-300">
                                <span class="text-sm">{{ $featureName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Status --}}
                <div class="border-t pt-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                        <span class="text-sm font-medium">{{ __('channels.s.active') }}</span>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_plan') }}</button>
                    <button type="button" wire:click="cancelEdit"
                        class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                        <i class="fa-solid fa-times mr-2"></i>{{ __('channels.s.cancel') }}</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Plans List --}}
    @if(!$showForm)
        <div class="grid md:grid-cols-3 gap-6">
            @forelse($plans as $plan)
                <div class="bg-card rounded-xl shadow-sm border p-6 {{ !$plan['is_active'] ? 'opacity-60' : '' }}">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold">{{ $plan['name'] }}</h3>
                            <p class="text-sm text-muted-foreground">{{ $plan['description'] }}</p>
                        </div>
                        <span
                            class="px-2 py-1 rounded text-xs {{ $plan['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $plan['is_active'] ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mb-4">
                        <span class="text-3xl font-bold">Rp {{ number_format($plan['price'], 0, ',', '.') }}</span>
                        <span class="text-muted-foreground">/{{ $plan['billing_period'] }}</span>
                    </div>

                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.widgets_2') }}</span>
                            <span class="font-medium">{{ $plan['max_widgets'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.agents') }}</span>
                            <span class="font-medium">{{ $plan['max_agents'] ?? 1 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.messages_2') }}</span>
                            <span class="font-medium">{{ number_format($plan['max_messages_per_month']) }}/mo</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.documents') }}</span>
                            <span class="font-medium">{{ $plan['max_documents'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.ai_tier_2') }}</span>
                            <span class="font-medium capitalize">{{ $plan['ai_tier'] ?? 'basic' }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button wire:click="editPlan({{ $plan['id'] }})"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fa-solid fa-edit mr-1"></i>{{ __('agents.s.edit') }}</button>
                        <button wire:click="toggleActive({{ $plan['id'] }})"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fa-solid fa-{{ $plan['is_active'] ? 'eye-slash' : 'eye' }}"></i>
                        </button>
                        <button wire:click="deletePlan({{ $plan['id'] }})" wire:confirm="Delete this plan?"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-muted-foreground">
                    <i class="fa-solid fa-box-open text-4xl mb-4"></i>
                    <p>{{ __('admin.s.no_plans_created_yet') }}</p>
                </div>
            @endforelse
        </div>
    @endif
</div>