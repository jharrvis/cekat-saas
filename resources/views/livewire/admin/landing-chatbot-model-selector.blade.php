<div>
    {{-- Messages --}}
    @if (session()->has('model_saved'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('model_saved') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-bold">Select AI Model</h3>
            <p class="text-sm text-muted-foreground">Pilih model LLM langsung untuk landing page widget (tanpa batasan
                tier)</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="testModel" wire:loading.attr="disabled"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition text-sm">
                <span wire:loading.remove wire:target="testModel">
                    <i class="fa-solid fa-play mr-1"></i> Test Model
                </span>
                <span wire:loading wire:target="testModel">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Testing...
                </span>
            </button>
            <button wire:click="saveModel"
                class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                <i class="fa-solid fa-save mr-1"></i> Save Model
            </button>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
        <p class="text-sm text-blue-800">
            <i class="fa-solid fa-infinity mr-2"></i>
            <strong>Landing Page Widget:</strong> Tidak ada batasan quota. Admin dapat memilih model langsung.
        </p>
    </div>

    {{-- Test Result --}}
    @if($testResult)
        <div
            class="mb-6 p-4 rounded-lg {{ $testResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
            <p class="text-sm font-semibold {{ $testResult['success'] ? 'text-green-800' : 'text-red-800' }}">
                <i class="fa-solid {{ $testResult['success'] ? 'fa-check-circle' : 'fa-times-circle' }} mr-2"></i>
                Test {{ $testResult['success'] ? 'Berhasil' : 'Gagal' }}
            </p>
            <p class="text-sm mt-1 {{ $testResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                {{ $testResult['success'] ? $testResult['response'] : $testResult['error'] }}
            </p>
        </div>
    @endif

    {{-- Current Selection --}}
    <div class="bg-muted/30 rounded-lg p-4 mb-6">
        <p class="text-sm text-muted-foreground">Model Aktif Saat Ini:</p>
        <p class="font-mono text-lg font-bold text-primary">{{ $selectedModel }}</p>
    </div>

    {{-- Model Cards Grid --}}
    <div class="grid md:grid-cols-3 gap-4">
        @foreach($models as $model)
            <div wire:click="selectModel('{{ $model->model_id }}')" class="cursor-pointer border-2 rounded-xl p-4 transition hover:shadow-lg 
                        {{ $selectedModel === $model->model_id
            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
            : 'border-border hover:border-primary/50' }}">

                {{-- Header --}}
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-2">
                        @if($selectedModel === $model->model_id)
                            <div class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center">
                                <i class="fa-solid fa-check text-xs"></i>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full border-2 border-gray-300"></div>
                        @endif
                        <span class="text-xs px-2 py-0.5 rounded bg-muted capitalize">{{ $model->provider }}</span>
                    </div>
                    @if($model->input_price == 0)
                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 font-medium">FREE</span>
                    @endif
                </div>

                {{-- Model Name --}}
                <h4 class="font-bold text-lg mb-1">{{ $model->name }}</h4>
                <p class="text-xs text-muted-foreground font-mono mb-3">{{ $model->model_id }}</p>

                {{-- Description --}}
                @if($model->description)
                    <p class="text-sm text-muted-foreground mb-3 line-clamp-2">{{ $model->description }}</p>
                @endif

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-muted/50 rounded p-2">
                        <p class="text-muted-foreground">Context</p>
                        <p class="font-semibold">{{ number_format($model->context_length) }}</p>
                    </div>
                    <div class="bg-muted/50 rounded p-2">
                        <p class="text-muted-foreground">Input Price</p>
                        <p class="font-semibold">
                            @if($model->input_price == 0)
                                Free
                            @else
                                ${{ number_format($model->input_price, 4) }}/1M
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(count($models) === 0)
        <div class="text-center py-12 text-muted-foreground">
            <i class="fa-solid fa-robot text-4xl mb-4"></i>
            <p>No active models found.</p>
            <p class="text-sm">Add models in Settings → LLM Models</p>
        </div>
    @endif
</div>