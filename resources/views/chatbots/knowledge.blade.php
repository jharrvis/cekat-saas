@extends('layouts.dashboard')

@section('title', 'Knowledge Base - ' . $chatbot->display_name)
@section('page-title', $chatbot->display_name . ' - Knowledge Base')

@section('content')
    <div>
        {{-- Breadcrumb --}}
        <div class="mb-4 text-sm">
            <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground">My Chatbots</a>
            <span class="mx-2 text-muted-foreground">/</span>
            <span class="font-medium">{{ $chatbot->display_name }}</span>
            <span class="mx-2 text-muted-foreground">/</span>
            <span>Knowledge Base</span>
        </div>

        @livewire('knowledge-base-editor', ['widgetId' => $chatbot->id])
    </div>
@endsection