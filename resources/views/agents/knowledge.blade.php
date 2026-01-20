@extends('layouts.dashboard')

@section('title', 'Knowledge Base - ' . $agent->name)
@section('page-title', 'Knowledge Base')

@section('content')
    {{-- Compact Header with Back Button --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('agents.edit', $agent) }}"
                class="w-10 h-10 rounded-lg bg-muted flex items-center justify-center hover:bg-muted/80 transition">
                <i class="fa-solid fa-arrow-left text-muted-foreground"></i>
            </a>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold">Knowledge Base</h1>
                    <p class="text-sm text-muted-foreground">{{ $agent->name }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Knowledge Editor Card with Tabs - NO EXTRA WRAPPER --}}
    <div class="bg-card border rounded-xl p-6">
        @livewire('agent-knowledge-editor', ['agentId' => $agent->id])
    </div>
@endsection