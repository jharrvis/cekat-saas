@extends('layouts.dashboard')

@section('title', __('chat.s.chat_detail'))

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('chats.index') }}" class="text-muted-foreground hover:text-foreground transition">
                    <i class="fa-solid fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold">{{ __('chat.s.chat_detail') }}</h1>
                    <p class="text-muted-foreground">
                        {{ $session->widget->display_name }} • {{ $session->created_at->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('chats.destroy', $session->id) }}" method="POST"
                    onsubmit="return confirm('Hapus percakapan ini permanen? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary text-red-600">
                        <i class="fa-solid fa-trash mr-2"></i>{{ __('admin.s.delete') }}
                    </button>
                </form>
                @if(!$session->summary)
                    <form action="{{ route('chats.summary', $session->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-secondary">
                            <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>{{ __('chat.s.generate_summary') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Chat Messages --}}
            <div class="md:col-span-2 bg-card rounded-xl border overflow-hidden">
                <div class="p-4 border-b bg-muted/30">
                    <h3 class="font-semibold">{{ __('chat.s.conversation') }}</h3>
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
                        <i class="fa-solid fa-user mr-2"></i>{{ __('chat.s.customer_info') }}
                    </h3>
                    @if($session->visitor_name)
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">{{ __('chat.s.nama') }}</span>
                                <span class="font-medium">{{ $session->visitor_name }}</span>
                            </div>
                            @if($session->visitor_email)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('chat.s.email') }}</span>
                                    <span class="font-medium">{{ $session->visitor_email }}</span>
                                </div>
                            @endif
                            @if($session->visitor_phone)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('chat.s.phone') }}</span>
                                    <span class="font-medium">{{ $session->visitor_phone }}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">{{ __('chat.s.no_lead_data_collected') }}</p>
                    @endif
                </div>

                {{-- Summary --}}
                @if($session->summary)
                    <div class="bg-card rounded-xl border p-4">
                        <h3 class="font-semibold mb-3">
                            <i class="fa-solid fa-clipboard-list mr-2"></i>{{ __('chat.s.summary') }}
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
                        <i class="fa-solid fa-info-circle mr-2"></i>{{ __('chat.s.session_info') }}
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('chat.s.session_id') }}</span>
                            <span class="font-mono text-xs">{{ Str::limit($session->visitor_uuid ?? $session->session_id ?? '-', 16) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('admin.s.started') }}</span>
                            <span>{{ $session->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">{{ __('chat.s.last_activity') }}</span>
                            <span>{{ $session->updated_at->diffForHumans() }}</span>
                        </div>
                        @if($session->ip_address)
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.ip_address') }}</span>
                                <span class="font-mono text-xs">{{ $session->ip_address }}</span>
                            </div>
                        @endif
                        @if($browser = \App\Support\VisitorGeo::describeAgent($session->user_agent))
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.browser') }}</span>
                                <span class="text-right">{{ $browser }}</span>
                            </div>
                        @endif
                        @if($session->device_type)
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.device') }}</span>
                                <span class="capitalize">{{ $session->device_type }}</span>
                            </div>
                        @endif
                        @if($location = trim(implode(', ', array_filter([
                            $session->location_data['city'] ?? null,
                            $session->location_data['region'] ?? null,
                            $session->location_data['country'] ?? null,
                        ]))))
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.location') }}</span>
                                <span class="text-right">{{ $location }}</span>
                            </div>
                        @elseif($session->location_data['country_code'] ?? null)
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.location') }}</span>
                                <span>{{ $session->location_data['country_code'] }}</span>
                            </div>
                        @endif
                        @if($session->source_url)
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.halaman') }}</span>
                                <span class="text-right text-xs break-all" title="{{ $session->source_url }}">{{ \Illuminate\Support\Str::limit($session->source_url, 70) }}</span>
                            </div>
                        @endif
                        @if($session->referer_url)
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">{{ __('chat.s.referrer') }}</span>
                                <span class="text-right text-xs break-all" title="{{ $session->referer_url }}">{{ \Illuminate\Support\Str::limit($session->referer_url, 70) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection