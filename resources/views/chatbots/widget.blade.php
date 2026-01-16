@extends('layouts.dashboard')

@section('title', 'Widget Customizer - ' . $chatbot->display_name)
@section('page-title', $chatbot->display_name . ' - Widget Customizer')

@section('content')
    <div>
        {{-- Breadcrumb --}}
        <div class="mb-4 text-sm">
            <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground">My Chatbots</a>
            <span class="mx-2 text-muted-foreground">/</span>
            <span class="font-medium">{{ $chatbot->display_name }}</span>
            <span class="mx-2 text-muted-foreground">/</span>
            <span>Widget Customizer</span>
        </div>

        @livewire('widget-customizer', ['widgetId' => $chatbot->id])
    </div>
@endsection