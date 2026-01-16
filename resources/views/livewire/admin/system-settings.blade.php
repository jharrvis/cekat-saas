<div>
    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold">System Settings</h2>
        <p class="text-muted-foreground">Configure system-wide settings and preferences</p>
    </div>

    {{-- Tabs --}}
    <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b">
            <nav class="flex">
                <button wire:click="$set('activeTab', 'general')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'general' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-cog mr-2"></i> General
                </button>
                <button wire:click="$set('activeTab', 'api')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'api' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-key mr-2"></i> API Settings
                </button>
                <button wire:click="$set('activeTab', 'models')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'models' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-robot mr-2"></i> LLM Models
                </button>
                <button wire:click="$set('activeTab', 'limits')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'limits' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-gauge mr-2"></i> Limits
                </button>
                <button wire:click="$set('activeTab', 'ai_tiers')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'ai_tiers' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-brain mr-2"></i> AI Tiers
                </button>
            </nav>
        </div>

        <div class="p-6">
            {{-- General Settings Tab --}}
            @if($activeTab === 'general')
                <form wire:submit.prevent="saveSettings('general')" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Site Name</label>
                        <input type="text" wire:model="generalSettings.site_name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Site URL</label>
                        <input type="url" wire:model="generalSettings.site_url"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Support Email</label>
                        <input type="email" wire:model="generalSettings.support_email"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="generalSettings.allow_registration" value="1"
                            class="rounded border-gray-300">
                        <label class="text-sm font-medium">Allow New Registrations</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="generalSettings.maintenance_mode" value="1"
                            class="rounded border-gray-300">
                        <label class="text-sm font-medium">Maintenance Mode</label>
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i> Save General Settings
                    </button>
                </form>
            @endif

            {{-- API Settings Tab --}}
            @if($activeTab === 'api')
                <form wire:submit.prevent="saveSettings('api')" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">OpenRouter API Key</label>
                        <input type="password" wire:model="apiSettings.openrouter_api_key"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="sk-or-v1-...">
                        <p class="text-xs text-muted-foreground mt-1">Get your API key from <a href="https://openrouter.ai"
                                target="_blank" class="text-blue-600">openrouter.ai</a></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Default AI Model</label>
                        <select wire:model="apiSettings.default_ai_model"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @foreach(\App\Models\LlmModel::where('is_active', true)->orderBy('popularity', 'desc')->get() as $model)
                                <option value="{{ $model->model_id }}">
                                    {{ $model->name }} ({{ $model->provider }})
                                    @if($model->input_price == 0) - Free @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1">This model is used when a widget has no model selected
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">API Timeout (seconds)</label>
                        <input type="number" wire:model="apiSettings.api_timeout" min="10" max="120"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i> Save API Settings
                    </button>
                </form>
            @endif

            {{-- Limits Settings Tab --}}
            @if($activeTab === 'limits')
                <form wire:submit.prevent="saveSettings('limits')" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Max Upload Size (MB)</label>
                        <input type="number" wire:model="limitsSettings.max_upload_size_mb" min="1" max="100"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Session Timeout (minutes)</label>
                        <input type="number" wire:model="limitsSettings.session_timeout_minutes" min="30" max="1440"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Chat History Retention (days)</label>
                        <input type="number" wire:model="limitsSettings.chat_retention_days" min="7" max="365"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i> Save Limits Settings
                    </button>
                </form>
            @endif

            {{-- Models Tab --}}
            @if($activeTab === 'models')
                @livewire('admin.models-manager')
            @endif

            {{-- AI Tiers Tab --}}
            @if($activeTab === 'ai_tiers')
                @livewire('admin.ai-tier-manager')
            @endif
        </div>
    </div>
</div>