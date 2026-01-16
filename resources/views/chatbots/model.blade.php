@extends('layouts.dashboard')

@section('title', 'Model Selection - ' . $chatbot->display_name)
@section('page-title', $chatbot->display_name . ' - Model Selection')

@section('content')
    <div>
        {{-- Breadcrumb --}}
        <div class="mb-4 text-sm">
            <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground">My Chatbots</a>
            <span class="mx-2 text-muted-foreground">/</span>
            <span class="font-medium">{{ $chatbot->display_name }}</span>
            <span class="mx-2 text-muted-foreground">/</span>
            <span>Model Selection</span>
        </div>

        <div class="bg-card rounded-xl shadow-sm border p-6">
            <h2 class="text-xl font-bold mb-4">Select AI Model</h2>
            <p class="text-muted-foreground mb-6">Choose the AI model for this chatbot based on your plan</p>

            <div class="grid md:grid-cols-2 gap-4">
                @php
                    $models = [
                        'nvidia/nemotron-3-nano-30b-a3b:free' => [
                            'name' => 'Nemotron Nano',
                            'description' => 'Fast and free model, good for basic queries',
                            'badge' => 'Free',
                            'color' => 'green'
                        ],
                        'openai/gpt-4o-mini' => [
                            'name' => 'GPT-4o Mini',
                            'description' => 'Balanced performance and cost',
                            'badge' => 'Pro',
                            'color' => 'blue'
                        ],
                        'openai/gpt-4o' => [
                            'name' => 'GPT-4o',
                            'description' => 'Most capable model, best quality responses',
                            'badge' => 'Business',
                            'color' => 'purple'
                        ],
                        'anthropic/claude-3.5-sonnet' => [
                            'name' => 'Claude 3.5 Sonnet',
                            'description' => 'Excellent for complex conversations',
                            'badge' => 'Business',
                            'color' => 'purple'
                        ],
                    ];
                @endphp

                @foreach($models as $modelId => $model)
                    <div class="border rounded-lg p-4 hover:border-primary transition cursor-pointer">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-semibold">{{ $model['name'] }}</h3>
                            <span class="px-2 py-1 rounded text-xs bg-{{ $model['color'] }}-100 text-{{ $model['color'] }}-700">
                                {{ $model['badge'] }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-3">{{ $model['description'] }}</p>
                        <button
                            class="w-full bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                            Select Model
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800">
                    <i class="fa-solid fa-info-circle mr-2"></i>
                    Your current plan allows: <strong>{{ implode(', ', array_column($models, 'name')) }}</strong>
                </p>
            </div>
        </div>
    </div>
@endsection