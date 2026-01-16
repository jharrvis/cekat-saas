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

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-bold">LLM Models Management</h3>
            <p class="text-muted-foreground text-sm">Manage AI models available for each tier</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="fetchFromOpenRouter"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition text-sm">
                <i class="fa-solid fa-cloud-download-alt mr-2"></i> Fetch from OpenRouter
            </button>
            <button wire:click="create"
                class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                <i class="fa-solid fa-plus mr-2"></i> Add Model
            </button>
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
                    <strong>Quick Add:</strong> Paste Model ID from OpenRouter (e.g., <code>openai/gpt-4o</code>) then click
                    <strong>Fetch Info</strong> to auto-fill specs.
                </p>
            </div>

            <form wire:submit.prevent="save" class="grid md:grid-cols-2 gap-4">
                {{-- Model ID with Fetch Button --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Model ID * <span class="text-muted-foreground">(from
                            OpenRouter)</span></label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="model_id" class="flex-1 px-3 py-2 border rounded-lg text-sm"
                            placeholder="e.g., openai/gpt-4o-mini or google/gemini-1.5-pro">
                        <button type="button" wire:click="fetchModelInfo" wire:loading.attr="disabled"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition text-sm whitespace-nowrap">
                            <span wire:loading.remove wire:target="fetchModelInfo">
                                <i class="fa-solid fa-cloud-download-alt mr-1"></i> Fetch Info
                            </span>
                            <span wire:loading wire:target="fetchModelInfo">
                                <i class="fa-solid fa-spinner fa-spin mr-1"></i> Fetching...
                            </span>
                        </button>
                    </div>
                    @error('model_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Display Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="e.g., GPT-4o">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Provider *</label>
                    <input type="text" wire:model="provider" class="w-full px-3 py-2 border rounded-lg text-sm"
                        placeholder="e.g., OpenAI">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Context Length</label>
                    <input type="number" wire:model="context_length" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Input Price (per 1M tokens)</label>
                    <input type="number" step="0.0001" wire:model="input_price"
                        class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Output Price (per 1M tokens)</label>
                    <input type="number" step="0.0001" wire:model="output_price"
                        class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Popularity (0-100)</label>
                    <input type="number" wire:model="popularity" min="0" max="100"
                        class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <textarea wire:model="description" rows="2"
                        class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                </div>

                {{-- Test Result --}}
                @if(session()->has('test_result'))
                    @php $testResult = session('test_result'); @endphp
                    <div
                        class="md:col-span-2 p-4 rounded-lg {{ $testResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                        <p class="text-sm font-semibold {{ $testResult['success'] ? 'text-green-800' : 'text-red-800' }}">
                            <i class="fa-solid {{ $testResult['success'] ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                            Test {{ $testResult['success'] ? 'Successful' : 'Failed' }} - {{ $testResult['model'] }}
                        </p>
                        <p class="text-sm mt-2 {{ $testResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                            {{ $testResult['success'] ? $testResult['response'] : $testResult['error'] }}
                        </p>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="md:col-span-2 flex gap-2 items-center">
                    <button type="submit" class="bg-primary text-primary-foreground px-6 py-2 rounded-lg">
                        <i class="fa-solid fa-save mr-2"></i> Save
                    </button>
                    <button type="button" wire:click="testModel" wire:loading.attr="disabled"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                        <span wire:loading.remove wire:target="testModel">
                            <i class="fa-solid fa-play mr-2"></i> Test Model
                        </span>
                        <span wire:loading wire:target="testModel">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Testing...
                        </span>
                    </button>
                    <button type="button" wire:click="resetForm" class="px-6 py-2 border rounded-lg">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Models Table --}}
    <div class="bg-card rounded-xl border overflow-hidden">
        <table class="w-full">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Tiers</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Actions</th>
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
                                <span class="text-green-600 font-medium">Free</span>
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
                                class="text-green-600 hover:text-green-700 p-1" title="Test Model">
                                <i class="fa-solid fa-play"></i>
                            </button>
                            <button wire:click="edit({{ $model->id }})" class="text-blue-600 hover:text-blue-700 p-1" title="Edit">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button wire:click="delete({{ $model->id }})" onclick="return confirm('Delete this model?')"
                                class="text-red-600 hover:text-red-700 p-1" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                            No models configured. Click "Add Model" or "Fetch from OpenRouter" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-800">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>Tier Assignment:</strong>
            Starter (Free) users can only use models marked "Starter".
            Pro users can use Starter + Pro models.
            Business users can use all models.
        </p>
    </div>
</div>