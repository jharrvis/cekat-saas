@extends('layouts.dashboard')

@section('title', __('nav.agents'))
@section('page-title', __('nav.agents'))

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
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($agents as $agent)
                    @php
                        $kb = $agent->knowledgeBase;
                        $faqCount = (int) ($kb->faqs_count ?? 0);
                        $docCount = (int) ($kb->documents_count ?? 0);
                    @endphp
                    <div class="bg-card border rounded-xl p-4 hover:shadow-md transition group">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary to-indigo-600 flex items-center justify-center text-white shrink-0">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold truncate group-hover:text-primary transition">{{ $agent->name }}</h3>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $agent->widgets_count }} channel{{ $agent->widgets_count !== 1 ? 's' : '' }}
                                        · Dibuat {{ $agent->created_at->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs rounded-full shrink-0 {{ $agent->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $agent->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        @if($agent->description)
                            <p class="text-sm text-muted-foreground mb-3 line-clamp-2">{{ $agent->description }}</p>
                        @endif

                        {{-- Informative chips --}}
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">
                                <i class="fa-solid fa-user mr-1"></i>{{ ucfirst($agent->personality) }}
                            </span>
                            @if(($faqCount + $docCount) > 0)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">
                                    <i class="fa-solid fa-brain mr-1"></i>{{ $faqCount }} FAQ · {{ $docCount }} dok
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-muted text-muted-foreground text-xs rounded-full">
                                    <i class="fa-solid fa-brain mr-1"></i>Knowledge kosong
                                </span>
                            @endif
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">
                                <i class="fa-solid fa-calendar mr-1"></i>{{ $agent->created_at->diffForHumans(short: true) }}
                            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2 pt-3 border-t">
                            <a href="{{ route('agents.edit', $agent) }}"
                                class="flex-1 text-center px-3 py-1.5 bg-primary text-primary-foreground text-sm rounded-lg hover:bg-primary/90 transition">
                                <i class="fa-solid fa-edit mr-1"></i> Edit
                            </a>
                            <form action="{{ route('agents.toggle-status', $agent) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full px-3 py-1.5 bg-secondary text-secondary-foreground text-sm rounded-lg hover:bg-secondary/80 transition">
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