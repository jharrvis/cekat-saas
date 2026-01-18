@extends('layouts.dashboard')

@section('title', 'AI Agents')
@section('page-title', 'AI Agents')

@section('content')
    <div class="space-y-6">
        {{-- Success/Error Messages --}}
        @if (session('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                {{ session('message') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold">AI Agents</h2>
                <p class="text-muted-foreground mt-1">Kelola AI Agent dan knowledge base untuk chatbot Anda</p>
            </div>
            <a href="{{ route('agents.create') }}"
                class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-plus mr-2"></i> Buat Agent Baru
            </a>
        </div>

        {{-- Info Card --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex gap-3">
                <i class="fa-solid fa-lightbulb text-blue-500 mt-1"></i>
                <div>
                    <h4 class="font-medium text-blue-800 dark:text-blue-200">💡 Apa itu AI Agent?</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        AI Agent adalah "otak" chatbot Anda yang berisi pengetahuan, personality, dan konfigurasi AI.
                        Satu Agent bisa digunakan oleh banyak Widget (tampilan chatbot) di berbagai channel.
                    </p>
                </div>
            </div>
        </div>

        {{-- Agents Grid --}}
        @if($agents->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($agents as $agent)
                    <div class="bg-card border rounded-xl p-5 hover:shadow-md transition group">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-indigo-600 flex items-center justify-center text-white">
                                    <i class="fa-solid fa-robot text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-lg group-hover:text-primary transition">{{ $agent->name }}</h3>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $agent->widgets_count }} widget{{ $agent->widgets_count !== 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs rounded-full {{ $agent->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $agent->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        @if($agent->description)
                            <p class="text-sm text-muted-foreground mb-4 line-clamp-2">{{ $agent->description }}</p>
                        @endif

                        {{-- Stats --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-muted/50 rounded-lg p-3 text-center">
                                <p class="text-lg font-bold text-primary">{{ number_format($agent->messages_used) }}</p>
                                <p class="text-xs text-muted-foreground">Pesan</p>
                            </div>
                            <div class="bg-muted/50 rounded-lg p-3 text-center">
                                <p class="text-lg font-bold text-indigo-600">{{ number_format($agent->conversations_count) }}</p>
                                <p class="text-xs text-muted-foreground">Percakapan</p>
                            </div>
                        </div>

                        {{-- AI Model & Personality --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="px-2 py-1 bg-primary/10 text-primary text-xs rounded-full">
                                <i class="fa-solid fa-microchip mr-1"></i>
                                {{ Str::afterLast($agent->ai_model, '/') }}
                            </span>
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                <i class="fa-solid fa-user mr-1"></i>
                                {{ ucfirst($agent->personality) }}
                            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 pt-3 border-t">
                            <a href="{{ route('agents.edit', $agent) }}"
                                class="flex-1 text-center px-3 py-2 bg-primary text-primary-foreground text-sm rounded-lg hover:bg-primary/90 transition">
                                <i class="fa-solid fa-edit mr-1"></i> Edit
                            </a>
                            <form action="{{ route('agents.toggle-status', $agent) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full px-3 py-2 bg-secondary text-secondary-foreground text-sm rounded-lg hover:bg-secondary/80 transition">
                                    <i class="fa-solid {{ $agent->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                                    {{ $agent->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-card border rounded-xl p-12 text-center">
                <div class="w-20 h-20 mx-auto bg-primary/10 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-robot text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Belum Ada AI Agent</h3>
                <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                    AI Agent adalah otak dari chatbot Anda. Buat agent pertama untuk mulai melatih AI dengan pengetahuan bisnis
                    Anda.
                </p>
                <a href="{{ route('agents.create') }}"
                    class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium">
                    <i class="fa-solid fa-plus mr-2"></i> Buat Agent Pertama
                </a>
            </div>
        @endif
    </div>
@endsection