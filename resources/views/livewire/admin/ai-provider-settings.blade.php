<div>
    @if (session()->has('provider_message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('provider_message') }}
        </div>
    @endif

    <div class="mb-6">
        <h3 class="text-lg font-semibold">{{ __('admin.s.ai_providers') }}</h3>
        <p class="text-sm text-muted-foreground">{{ __('admin.s.ai_providers_desc') }}</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>{{ __('admin.s.how_it_works') }}</strong> {{ __('admin.s.ai_providers_chain_note') }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-4 mb-8">
        {{-- OpenRouter --}}
        <div class="border rounded-xl p-4 bg-white dark:bg-slate-900">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model="openrouterEnabled" class="mt-1 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="font-medium block">OpenRouter</span>
                    <span class="text-sm text-muted-foreground block">{{ __('admin.s.openrouter_provider_desc') }}</span>
                </span>
            </label>
        </div>

        {{-- SumoPod --}}
        <div class="border rounded-xl p-4 bg-white dark:bg-slate-900">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model="sumopodEnabled" class="mt-1 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="font-medium block">SumoPod</span>
                    <span class="text-sm text-muted-foreground block">{{ __('admin.s.sumopod_provider_desc') }}</span>
                </span>
            </label>

            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.sumopod_api_key') }}</label>
                    <input type="password" wire:model="sumopodApiKey" autocomplete="off"
                        placeholder="{{ $hasSumopodKey ? __('admin.s.sumopod_key_stored') : 'sk-...' }}"
                        class="w-full rounded-lg border-gray-300 dark:bg-slate-800 text-sm">
                    @error('sumopodApiKey') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.sumopod_key_hint') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.sumopod_model') }}</label>
                    <input type="text" wire:model="sumopodModel" placeholder="kimi-k2-5-260127"
                        class="w-full rounded-lg border-gray-300 dark:bg-slate-800 text-sm">
                    @error('sumopodModel') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.sumopod_model_hint') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.sumopod_base_url') }}</label>
                    <input type="text" wire:model="sumopodBaseUrl"
                        class="w-full rounded-lg border-gray-300 dark:bg-slate-800 text-sm">
                    @error('sumopodBaseUrl') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-3">
                    <button type="button" wire:click="testConnection"
                        class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-slate-800">
                        <i class="fa-solid fa-plug mr-2"></i>{{ __('admin.s.test_connection') }}
                    </button>
                    @if ($testResult)
                        <span class="text-sm {{ $testOk ? 'text-green-600' : 'text-red-600' }}">{{ $testResult }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">
                <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save') }}
            </button>
        </div>
    </form>
</div>
