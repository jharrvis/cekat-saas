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
            <h2 class="text-2xl font-bold">Plan Management</h2>
            <p class="text-muted-foreground">Manage subscription plans and pricing tiers</p>
        </div>
        @if(!$showForm)
            <button wire:click="createPlan"
                class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-plus mr-2"></i> Create New Plan
            </button>
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
                        <label class="block text-sm font-medium mb-2">Plan Name *</label>
                        <input type="text" wire:model.live="name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Slug *</label>
                        <input type="text" wire:model="slug"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('slug') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Price (Rp) *</label>
                        <input type="number" wire:model="price" min="0"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Billing Period</label>
                        <select wire:model="billing_period"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                {{-- Limits --}}
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">Limits</h4>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Max Widgets *</label>
                            <input type="number" wire:model="max_widgets" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Max Messages/Month *</label>
                            <input type="number" wire:model="max_messages_per_month" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Max Documents *</label>
                            <input type="number" wire:model="max_documents" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Max File Size (MB) *</label>
                            <input type="number" wire:model="max_file_size_mb" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Max FAQs *</label>
                            <input type="number" wire:model="max_faqs" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Sort Order</label>
                            <input type="number" wire:model="sort_order" min="0"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                </div>

                {{-- AI Quality Tier --}}
                <div class="border-t pt-4">
                    <h4 class="font-semibold mb-3">AI Quality Tier</h4>
                    <p class="text-sm text-muted-foreground mb-3">
                        Pilih tingkat kualitas AI untuk plan ini. Model spesifik diatur di
                        <a href="{{ route('admin.settings') }}" class="text-primary hover:underline">Settings → AI
                            Tiers</a>.
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
                    <h4 class="font-semibold mb-3">Features</h4>
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
                        <span class="text-sm font-medium">Active</span>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i> Save Plan
                    </button>
                    <button type="button" wire:click="cancelEdit"
                        class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                        <i class="fa-solid fa-times mr-2"></i> Cancel
                    </button>
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
                            <span class="text-muted-foreground">Widgets:</span>
                            <span class="font-medium">{{ $plan['max_widgets'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Messages:</span>
                            <span class="font-medium">{{ number_format($plan['max_messages_per_month']) }}/mo</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Documents:</span>
                            <span class="font-medium">{{ $plan['max_documents'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">AI Tier:</span>
                            <span class="font-medium capitalize">{{ $plan['ai_tier'] ?? 'basic' }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button wire:click="editPlan({{ $plan['id'] }})"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fa-solid fa-edit mr-1"></i> Edit
                        </button>
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
                    <p>No plans created yet.</p>
                </div>
            @endforelse
        </div>
    @endif
</div>