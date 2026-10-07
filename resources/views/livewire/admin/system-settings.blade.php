<div>
    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold">{{ __('admin.s.system_settings') }}</h2>
        <p class="text-muted-foreground">{{ __('admin.s.configure_system_wide_settings_and_preferences') }}</p>
    </div>

    {{-- Tabs --}}
    <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b">
            <nav class="flex">
                <button wire:click="$set('activeTab', 'general')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'general' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-cog mr-2"></i>{{ __('admin.s.general') }}</button>
                <button wire:click="$set('activeTab', 'api')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'api' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-key mr-2"></i>{{ __('admin.s.api_settings') }}</button>
                <button wire:click="$set('activeTab', 'models')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'models' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-robot mr-2"></i>{{ __('admin.s.llm_models') }}</button>
                <button wire:click="$set('activeTab', 'limits')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'limits' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-gauge mr-2"></i>{{ __('admin.s.limits') }}</button>
                <button wire:click="$set('activeTab', 'ai_tiers')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'ai_tiers' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-brain mr-2"></i>{{ __('admin.s.ai_tiers') }}</button>
            </nav>
        </div>

        <div class="p-6">
            {{-- General Settings Tab --}}
            @if($activeTab === 'general')
                <form wire:submit.prevent="saveSettings('general')" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.site_name') }}</label>
                        <input type="text" wire:model="generalSettings.site_name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.site_url') }}</label>
                        <input type="url" wire:model="generalSettings.site_url"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.support_email') }}</label>
                        <input type="email" wire:model="generalSettings.support_email"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="generalSettings.allow_registration" value="1"
                            class="rounded border-gray-300">
                        <label class="text-sm font-medium">{{ __('admin.s.allow_new_registrations') }}</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="generalSettings.maintenance_mode" value="1"
                            class="rounded border-gray-300">
                        <label class="text-sm font-medium">{{ __('admin.s.maintenance_mode') }}</label>
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_general_settings') }}</button>
                </form>
            @endif

            {{-- API Settings Tab --}}
            @if($activeTab === 'api')
                <form wire:submit.prevent="saveSettings('api')" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.openrouter_api_key') }}</label>
                        <div class="w-full px-4 py-2 border rounded-lg bg-muted text-sm text-muted-foreground">
                            {{ config('services.openrouter.api_key') ? 'Ter-set di .env (OPENROUTER_API_KEY)' : 'Belum di-set — tambahkan OPENROUTER_API_KEY di .env' }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">{!! __('admin.s.dikelola_lewat_file_server_bukan_lewat_dashboard') !!} <a href="https://openrouter.ai" target="_blank" rel="noopener noreferrer" class="text-blue-600">{{ __('admin.s.openrouter_ai') }}</a>.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.default_ai_model') }}</label>
                        <select wire:model="apiSettings.default_ai_model"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @foreach(\App\Models\LlmModel::where('is_active', true)->orderBy('popularity', 'desc')->get() as $model)
                                <option value="{{ $model->model_id }}">
                                    {{ $model->name }} ({{ $model->provider }})
                                    @if($model->input_price == 0) - Free @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.this_model_is_used_when_a_widget_has_no_model_se') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.api_timeout_seconds') }}</label>
                        <input type="number" wire:model="apiSettings.api_timeout" min="10" max="120"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_api_settings') }}</button>
                </form>
            @endif

            {{-- Limits Settings Tab --}}
            @if($activeTab === 'limits')
                <form wire:submit.prevent="saveSettings('limits')" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_upload_size_mb') }}</label>
                        <input type="number" wire:model="limitsSettings.max_upload_size_mb" min="1" max="100"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.session_timeout_minutes') }}</label>
                        <input type="number" wire:model="limitsSettings.session_timeout_minutes" min="30" max="1440"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.chat_history_retention_days') }}</label>
                        <input type="number" wire:model="limitsSettings.chat_retention_days" min="7" max="365"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_limits_settings') }}</button>
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