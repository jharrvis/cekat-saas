@extends('layouts.dashboard')

@section('title', 'Create Chatbot')
@section('page-title', 'Create New Chatbot')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-card rounded-xl shadow-sm border p-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('chatbots.index') }}" class="text-muted-foreground hover:text-foreground">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-2xl font-bold">Create New Chatbot</h2>
                    <p class="text-muted-foreground">Give your chatbot a name and description</p>
                </div>
            </div>

            <form action="{{ route('chatbots.store') }}" method="POST" class="space-y-6">
                @csrf

                @if (session()->has('error'))
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                        {{ session('error') }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium mb-2">Chatbot Name *</label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="e.g., Customer Support Bot" required>
                    @error('display_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Description (Optional)</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Brief description of what this chatbot does...">{{ old('description') }}</textarea>
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- AI Agent Selector --}}
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium mb-2">
                        <i class="fa-solid fa-brain text-primary mr-1"></i> Hubungkan ke AI Agent
                    </label>
                    
                    @if($aiAgents->count() > 0)
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition">
                                <input type="radio" name="ai_agent_id" value="" {{ !old('ai_agent_id') ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary focus:ring-primary">
                                <div>
                                    <span class="font-medium">Tanpa AI Agent</span>
                                    <p class="text-xs text-muted-foreground">Widget akan memiliki knowledge base sendiri</p>
                                </div>
                            </label>
                            
                            @foreach($aiAgents as $agent)
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition">
                                    <input type="radio" name="ai_agent_id" value="{{ $agent->id }}" 
                                        {{ old('ai_agent_id') == $agent->id ? 'checked' : '' }}
                                        class="w-4 h-4 text-primary focus:ring-primary">
                                    <div class="flex-1">
                                        <span class="font-medium">{{ $agent->name }}</span>
                                        <p class="text-xs text-muted-foreground">
                                            {{ ucfirst($agent->personality) }} • 
                                            {{ $agent->widgets()->count() }} widget terhubung
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-primary/10 text-primary rounded">
                                        {{ Str::afterLast($agent->ai_model, '/') }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Belum ada AI Agent. 
                                <a href="{{ route('agents.create') }}" class="text-primary font-medium hover:underline">
                                    Buat AI Agent dulu
                                </a> untuk menggunakan satu brain di banyak widget.
                            </p>
                        </div>
                        <input type="hidden" name="ai_agent_id" value="">
                    @endif
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <i class="fa-solid fa-lightbulb mr-2"></i>
                        <strong>Tip:</strong> Dengan AI Agent, satu "otak" bisa dipakai banyak widget. 
                        Training 1x, pakai di mana saja!
                    </p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit"
                        class="flex-1 bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                        <i class="fa-solid fa-plus mr-2"></i> Create Chatbot
                    </button>
                    <a href="{{ route('chatbots.index') }}"
                        class="px-6 py-3 border rounded-lg hover:bg-muted/30 transition font-medium">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection