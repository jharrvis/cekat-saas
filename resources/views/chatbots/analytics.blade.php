@extends('layouts.dashboard')

@section('title', 'Analytics - ' . $chatbot->display_name)
@section('page-title', $chatbot->display_name . ' - Analytics')

@section('content')
    <div>
        {{-- Breadcrumb --}}
        <div class="mb-4 text-sm">
            <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground">My Chatbots</a>
            <span class="mx-2 text-muted-foreground">/</span>
            <span class="font-medium">{{ $chatbot->display_name }}</span>
            <span class="mx-2 text-muted-foreground">/</span>
            <span>Analytics</span>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">Total Conversations</span>
                    <i class="fa-solid fa-message text-blue-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $chatbot->chatSessions()->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">All time</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">Messages Sent</span>
                    <i class="fa-solid fa-paper-plane text-green-500"></i>
                </div>
                <p class="text-3xl font-bold">0</p>
                <p class="text-xs text-muted-foreground mt-1">This month</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">Avg Response Time</span>
                    <i class="fa-solid fa-clock text-purple-500"></i>
                </div>
                <p class="text-3xl font-bold">1.2s</p>
                <p class="text-xs text-muted-foreground mt-1">Average</p>
            </div>
        </div>

        <div class="bg-card rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-bold mb-4">Recent Conversations</h3>
            <div class="text-center py-12 text-muted-foreground">
                <i class="fa-solid fa-chart-line text-4xl mb-4"></i>
                <p>No conversations yet</p>
                <p class="text-sm">Analytics will appear here once users start chatting</p>
            </div>
        </div>
    </div>
@endsection