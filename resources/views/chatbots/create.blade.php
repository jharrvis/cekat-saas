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

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">
                        <i class="fa-solid fa-info-circle mr-2"></i>
                        After creating the chatbot, you can configure the Knowledge Base, Model, and Widget appearance.
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