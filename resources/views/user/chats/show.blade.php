@extends('layouts.dashboard')

@section('title', 'Chat Detail')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('chats.index') }}" class="text-muted-foreground hover:text-foreground transition">
                    <i class="fa-solid fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold">Chat Detail</h1>
                    <p class="text-muted-foreground">
                        {{ $session->widget->display_name }} • {{ $session->created_at->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
            @if(!$session->summary)
                <form action="{{ route('chats.summary', $session->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Generate Summary
                    </button>
                </form>
            @endif
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Chat Messages --}}
            <div class="md:col-span-2 bg-card rounded-xl border overflow-hidden">
                <div class="p-4 border-b bg-muted/30">
                    <h3 class="font-semibold">Conversation</h3>
                    <p class="text-sm text-muted-foreground">{{ $session->messages->count() }} messages</p>
                </div>
                <div class="p-4 space-y-4 max-h-[600px] overflow-y-auto">
                    @foreach($session->messages as $message)
                        <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div
                                class="max-w-[80%] px-4 py-2 rounded-xl {{ $message->role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted' }}">
                                <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                                <p class="text-xs opacity-70 mt-1">
                                    {{ $message->created_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4">
                {{-- Lead Info --}}
                <div class="bg-card rounded-xl border p-4">
                    <h3 class="font-semibold mb-3">
                        <i class="fa-solid fa-user mr-2"></i>Customer Info
                    </h3>
                    @if($session->visitor_name)
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Nama:</span>
                                <span class="font-medium">{{ $session->visitor_name }}</span>
                            </div>
                            @if($session->visitor_email)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Email:</span>
                                    <span class="font-medium">{{ $session->visitor_email }}</span>
                                </div>
                            @endif
                            @if($session->visitor_phone)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Phone:</span>
                                    <span class="font-medium">{{ $session->visitor_phone }}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">No lead data collected</p>
                    @endif
                </div>

                {{-- Summary --}}
                @if($session->summary)
                    <div class="bg-card rounded-xl border p-4">
                        <h3 class="font-semibold mb-3">
                            <i class="fa-solid fa-clipboard-list mr-2"></i>Summary
                        </h3>
                        <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ $session->summary }}</p>
                        <p class="text-xs text-muted-foreground mt-2">
                            Generated: {{ $session->summary_generated_at?->format('d M Y H:i') }}
                        </p>
                    </div>
                @endif

                {{-- Session Info --}}
                <div class="bg-card rounded-xl border p-4">
                    <h3 class="font-semibold mb-3">
                        <i class="fa-solid fa-info-circle mr-2"></i>Session Info
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Session ID:</span>
                            <span class="font-mono text-xs">{{ Str::limit($session->session_id, 12) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Started:</span>
                            <span>{{ $session->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Last Activity:</span>
                            <span>{{ $session->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection