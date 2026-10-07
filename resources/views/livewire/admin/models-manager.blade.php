<div>
    {{-- Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            {{ session('error') }}
        </div>
    @endif

    {{-- Model test result (visible from table AND form) --}}
    @if (session()->has('test_result'))
        @php $tr = session('test_result'); @endphp
        <div
            class="px-4 py-3 rounded-xl mb-6 border {{ $tr['success'] ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' }}">
            <p class="text-sm font-semibold">
                <i class="fa-solid {{ $tr['success'] ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                @if ($tr['success'])
                    {{ $tr['model'] }} — active &amp; responding
                @else
                    {{ $tr['model'] }} — test failed, model not responding
                @endif
            </p>
            <p class="text-sm mt-1">
                {{ $tr['success'] ? ($tr['response'] ?? '') : ($tr['error'] ?? '') }}
            </p>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-bold">{{ __('admin.s.llm_models_management') }}</h3>
            <p class="text-muted-foreground text-sm">{{ __('admin.s.manage_ai_models_available_for_each_tier') }}</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="fetchFromOpenRouter"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition text-sm">
                <i class="fa-solid fa-cloud-download-alt mr-2"></i>{{ __('admin.s.fetch_from_openrouter') }}</button>
            <button wire:click="create"
                class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                <i class="fa-solid fa-plus mr-2"></i>{{ __('admin.s.add_model') }}</button>
        </div>
    </div>

    {{-- Form Modal --}}
    @if($showForm)
        <div class="bg-muted/30 rounded-xl p-6 mb-6 border">
            <h4 class="text-lg font-bold mb-4">{{ $editingModel ? 'Edit Model' : 'Add New Model' }}</h4>

            {{-- Quick Add Info --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                    <i class="fa-solid fa-lightbulb mr-2"></i>
                    <strong>{{ __('admin.s.quick_add') }}</strong> {!! __('admin.s.paste_model_id_from_openrouter_e_g_then_click') !!}
                    <strong>{{ __('admin.s.fetch_info') }}</strong>{{ __('admin.s.to_auto_fill_specs') }}</p>
            </div>

            <form wire:submit.prevent="save" class="grid md:grid-cols-2 gap-4">
                {{-- Model ID with Fetch Button --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.model_id_2') }}<span class="text-muted-foreground">{{ __('admin.s.from_openrouter') }}</span></label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="model_id" class="flex-1 px-3 py-2 border rounded-lg text-sm"
                            placeholder="{{ __('admin.s.e_g_openai_gpt_4o_mini_or_google_gemini_1_5_pro') }}">
                        <button type="button" wire:click="fetchModelInfo" wire:loading.attr="disabled"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition text-sm whitespace-nowrap">
                            <span wire:loading.remove wire:target="fetchModelInfo">
                                <i class="fa-solid fa-cloud-download-alt mr-1"></i>{{ __('admin.s.fetch_info') }}</span>
                            <span wire:loading wire:target="fetchModelInfo">
                                <i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('admin.s.fetching') }}</span>
                        </button>
                    </div>
                    @error('model_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.display_name') }}</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="{{ __('admin.s.e_g_gpt_4o') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.provider_2') }}</label>
                    <input type="text" wire:model="provider" class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="{{ __('admin.s.e_g_openai') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.context_length') }}</label>
                    <input type="number" wire:model="context_length" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.input_price_per_1m_tokens') }}</label>
                    <input type="number" step="0.0001" wire:model="input_price"
                        class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.output_price_per_1m_tokens') }}</label>
                    <input type="number" step="0.0001" wire:model="output_price"
                        class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('admin.s.popularity_0_100') }}</label>
                    <input type="number" wire:model="popularity" min="0" max="100"
                        class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">{{ __('channels.s.description') }}</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="md:col-span-2 flex gap-2 items-center">
                    <button type="submit" class="bg-primary text-primary-foreground px-6 py-2 rounded-lg">
                        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save') }}</button>
                    <button type="button" wire:click="testModel" wire:loading.attr="disabled"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                        <span wire:loading.remove wire:target="testModel">
                            <i class="fa-solid fa-play mr-2"></i>{{ __('admin.s.test_model') }}</span>
                        <span wire:loading wire:target="testModel">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>{{ __('admin.s.testing') }}</span>
                    </button>
                    <button type="button" wire:click="resetForm" class="px-6 py-2 border rounded-lg">{{ __('channels.s.cancel') }}</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Models Table --}}
    <div class="bg-card rounded-xl border overflow-hidden">
        <table class="w-full">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">{{ __('admin.s.model') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">{{ __('admin.s.provider') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">{{ __('admin.s.price') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">{{ __('admin.s.tiers') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">{{ __('channels.s.status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">{{ __('channels.s.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($models as $model)
                    <tr class="hover:bg-muted/30 transition">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium">{{ $model->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ $model->model_id }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $model->provider }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($model->input_price == 0)
                                <span class="text-green-600 font-medium">{{ __('admin.s.free_2') }}</span>
                            @else
                                ${{ number_format($model->input_price, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1 flex-wrap">
                                @foreach($model->allowed_tiers ?? [] as $tier)
                                    <span class="px-2 py-0.5 rounded text-xs 
                                                    {{ $tier === 'starter' ? 'bg-green-100 text-green-700' : '' }}
                                                    {{ $tier === 'pro' ? 'bg-blue-100 text-blue-700' : '' }}
                                                    {{ $tier === 'business' ? 'bg-purple-100 text-purple-700' : '' }}">
                                        {{ ucfirst($tier) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleActive({{ $model->id }})"
                                class="px-2 py-1 rounded text-xs font-medium {{ $model->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $model->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="testModel('{{ $model->model_id }}')" 
                                wire:loading.attr="disabled"
                                class="text-green-600 hover:text-green-700 p-1" title="{{ __('admin.s.test_model') }}">
                                <i class="fa-solid fa-play"></i>
                            </button>
                            <button wire:click="edit({{ $model->id }})" class="text-blue-600 hover:text-blue-700 p-1" title="{{ __('agents.s.edit') }}">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button wire:click="delete({{ $model->id }})" onclick="return confirm('Delete this model?')"
                                class="text-red-600 hover:text-red-700 p-1" title="{{ __('admin.s.delete') }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">{{ __('admin.s.no_models_configured_click_add_model_or_fetch_fr') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-800">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>{{ __('admin.s.tier_assignment') }}</strong>{{ __('admin.s.starter_free_users_can_only_use_models_marked_st') }}</p>
    </div>
</div>