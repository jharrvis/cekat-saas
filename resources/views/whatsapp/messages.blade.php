@extends('layouts.dashboard')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('whatsapp.index') }}" class="text-muted-foreground hover:text-foreground">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold flex items-center gap-2">
                        <i class="fa-brands fa-whatsapp text-green-500"></i>
                        {{ $device->device_name }}
                    </h1>
                    <p class="text-muted-foreground text-sm">
                        {{ $device->phone_number ? '+' . $device->phone_number : 'Not connected' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @php
                    $statusColors = [
                        'connected' => 'bg-green-100 text-green-700',
                        'disconnected' => 'bg-red-100 text-red-700',
                    ];
                    $color = $statusColors[$device->status] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $color }}">
                    <span
                        class="w-2 h-2 rounded-full inline-block mr-1 {{ $device->status === 'connected' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    {{ ucfirst($device->status) }}
                </span>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-card rounded-xl border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-inbox text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Received</p>
                        <p class="text-xl font-bold">{{ number_format($device->messages_received) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-xl border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-paper-plane text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Sent</p>
                        <p class="text-xl font-bold">{{ number_format($device->messages_sent) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-xl border p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-robot text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">AI Responses</p>
                        <p class="text-xl font-bold">{{ number_format($messages->where('is_ai_response', true)->count()) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messages Table --}}
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <div class="p-4 border-b">
                <h2 class="font-semibold">Message History</h2>
            </div>

            @if($messages->count() > 0)
                <div class="divide-y max-h-[600px] overflow-y-auto">
                    @foreach($messages as $message)
                        <div class="p-4 hover:bg-muted/30 {{ $message->direction === 'outbound' ? 'bg-blue-50/50' : '' }}">
                            <div class="flex items-start gap-3">
                                {{-- Avatar --}}
                                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                                            {{ $message->direction === 'inbound' ? 'bg-gray-200' : 'bg-green-100' }}">
                                    @if($message->direction === 'inbound')
                                        <i class="fa-solid fa-user text-gray-500"></i>
                                    @else
                                        @if($message->is_ai_response)
                                            <i class="fa-solid fa-robot text-green-600"></i>
                                        @else
                                            <i class="fa-solid fa-paper-plane text-green-600"></i>
                                        @endif
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-sm">
                                            @if($message->direction === 'inbound')
                                                {{ $message->sender_name ?? $message->sender_phone }}
                                            @else
                                                {{ $message->is_ai_response ? 'AI Response' : 'You' }}
                                            @endif
                                        </span>
                                        @if($message->direction === 'inbound')
                                            <span class="text-xs text-muted-foreground">{{ $message->sender_phone }}</span>
                                        @endif
                                        @if($message->is_ai_response)
                                            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">AI</span>
                                        @endif
                                    </div>

                                    <p class="text-sm text-foreground whitespace-pre-wrap">{{ $message->message }}</p>

                                    <div class="flex items-center gap-3 mt-2 text-xs text-muted-foreground">
                                        <span>{{ $message->created_at->format('d M Y H:i') }}</span>
                                        @if($message->direction === 'outbound')
                                            <span class="flex items-center gap-1">
                                                @if($message->status === 'read')
                                                    <i class="fa-solid fa-check-double text-blue-500"></i>
                                                @elseif($message->status === 'delivered')
                                                    <i class="fa-solid fa-check-double"></i>
                                                @elseif($message->status === 'sent')
                                                    <i class="fa-solid fa-check"></i>
                                                @elseif($message->status === 'failed')
                                                    <i class="fa-solid fa-times text-red-500"></i>
                                                @endif
                                                {{ ucfirst($message->status) }}
                                            </span>
                                        @endif
                                        @if($message->tokens_used > 0)
                                            <span>{{ number_format($message->tokens_used) }} tokens</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Direction Indicator --}}
                                <div class="flex-shrink-0">
                                    @if($message->direction === 'inbound')
                                        <span class="text-green-500"><i class="fa-solid fa-arrow-down"></i></span>
                                    @else
                                        <span class="text-blue-500"><i class="fa-solid fa-arrow-up"></i></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t">
                    {{ $messages->links() }}
                </div>
            @else
                <div class="p-12 text-center text-muted-foreground">
                    <i class="fa-solid fa-message text-4xl mb-4 opacity-50"></i>
                    <p>No messages yet.</p>
                    <p class="text-sm">Messages will appear here when customers start chatting.</p>
                </div>
            @endif
        </div>
    </div>
@endsection