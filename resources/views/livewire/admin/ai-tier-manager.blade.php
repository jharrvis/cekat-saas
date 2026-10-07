<div>
    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <h3 class="text-lg font-semibold">{{ __('admin.s.ai_quality_tier_mapping') }}</h3>
        <p class="text-sm text-muted-foreground">{{ __('admin.s.map_each_ai_quality_tier_to_specific_llm_models') }}</p>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>{{ __('admin.s.how_it_works') }}</strong>{{ __('admin.s.each_user_s_plan_has_an_ai_tier_basic_standard_a') }}</p>
    </div>

    {{-- Tier Mapping Cards --}}
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        @foreach($tiers as $tierSlug => $tier)
            <div class="border rounded-xl p-4 
                    {{ $tier['color'] === 'gray' ? 'bg-gray-50 border-gray-200' : '' }}
                    {{ $tier['color'] === 'blue' ? 'bg-blue-50 border-blue-200' : '' }}
                    {{ $tier['color'] === 'purple' ? 'bg-purple-50 border-purple-200' : '' }}
                    {{ $tier['color'] === 'amber' ? 'bg-amber-50 border-amber-200' : '' }}">

                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                            {{ $tier['color'] === 'gray' ? 'bg-gray-200 text-gray-600' : '' }}
                            {{ $tier['color'] === 'blue' ? 'bg-blue-200 text-blue-600' : '' }}
                            {{ $tier['color'] === 'purple' ? 'bg-purple-200 text-purple-600' : '' }}
                            {{ $tier['color'] === 'amber' ? 'bg-amber-200 text-amber-600' : '' }}">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold">{{ $tier['name'] }}</h4>
                        <p class="text-xs text-muted-foreground">{{ $tier['description'] }}</p>
                    </div>
                </div>

                <label class="block text-sm font-medium mb-2">{{ __('admin.s.mapped_model') }}</label>
                <select wire:model="tierMapping.{{ $tierSlug }}"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                    <option value="">{{ __('admin.s.select_model') }}</option>
                    @foreach($models as $model)
                        <option value="{{ $model->model_id }}">
                            {{ $model->name }} ({{ $model->provider }})
                            @if($model->input_price == 0) - Free @else - ${{ number_format($model->input_price, 2) }}/1M @endif
                        </option>
                    @endforeach
                </select>

                @if($tierMapping[$tierSlug])
                    <p class="text-xs text-muted-foreground mt-2">
                        <i class="fa-solid fa-check text-green-500 mr-1"></i>
                        Current: {{ $tierMapping[$tierSlug] }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Current Mapping Summary --}}
    <div class="bg-muted/50 rounded-xl p-4 mb-6">
        <h4 class="font-semibold mb-3">{{ __('admin.s.current_mapping_summary') }}</h4>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2">{{ __('admin.s.ai_tier') }}</th>
                    <th class="text-left py-2">{{ __('admin.s.model_id') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tierMapping as $tier => $modelId)
                    <tr class="border-b last:border-0">
                        <td class="py-2 font-medium">{{ ucfirst($tier) }}</td>
                        <td class="py-2 font-mono text-xs">{{ $modelId ?: '(not set)' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Save Button --}}
    <button wire:click="save"
        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_ai_tier_mapping') }}</button>
</div>