@extends('layouts.dashboard')

@section('title', __('nav.chat_history'))

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">{{ __('chat.s.chat_history') }}</h1>
                <p class="text-muted-foreground">{{ __('chat.s.lihat_semua_percakapan_dari_chatbot_anda') }}</p>
            </div>
            <a href="{{ route('chats.export') }}" class="btn-secondary">
                <i class="fa-solid fa-download mr-2"></i>{{ __('admin.s.export_csv') }}
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-card rounded-xl p-4 border">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('chat.s.cari_nama_customer') }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <select name="widget" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20">
                        <option value="">{{ __('chat.s.semua_widget') }}</option>
                        @foreach($widgets as $w)
                            <option value="{{ $w->id }}" {{ request('widget') == $w->id ? 'selected' : '' }}>
                                {{ $w->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20">
                        <option value="">{{ __('admin.s.semua_status') }}</option>
                        <option value="has_lead" {{ request('status') == 'has_lead' ? 'selected' : '' }}>{{ __('chat.s.has_lead') }}</option>
                        <option value="no_lead" {{ request('status') == 'no_lead' ? 'selected' : '' }}>{{ __('chat.s.no_lead') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-search mr-2"></i>{{ __('chat.s.filter') }}
                </button>
            </form>
        </div>

        {{-- Stats Cards --}}
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">{{ __('channels.s.total_conversations') }}</div>
                <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">{{ __('admin.s.bulan_ini') }}</div>
                <div class="text-2xl font-bold text-primary">{{ $stats['this_month'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">{{ __('chat.s.leads_collected') }}</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['leads'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">{{ __('chat.s.avg_messages_chat') }}</div>
                <div class="text-2xl font-bold">{{ \App\Support\Format::decimal($stats['avg_messages'], 1) }}</div>
            </div>
        </div>

        {{-- Chat List --}}
        <div class="bg-card rounded-xl border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('agents.s.widget') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('chat.s.customer') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.messages') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('channels.s.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('channels.s.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-muted/30 transition">
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium">{{ $session->widget->display_name ?? 'Unknown' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($session->visitor_name)
                                    <div class="font-medium">{{ $session->visitor_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $session->visitor_email ?? '' }}</div>
                                @else
                                    <span class="text-muted-foreground">{{ __('chat.s.anonymous') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm">{{ $session->messages_count }} pesan</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($session->visitor_name)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                        <i class="fa-solid fa-star mr-1"></i>{{ __('channels.s.lead') }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">{{ __('chat.s.visitor') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-muted-foreground">
                                    {{ $session->created_at->format('d M Y H:i') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="{{ route('chats.show', $session->id) }}"
                                        class="px-3 py-1 text-sm bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition">
                                        <i class="fa-solid fa-eye mr-1"></i>{{ __('chat.s.view') }}
                                    </a>
                                    <form action="{{ route('chats.destroy', $session->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus percakapan ini permanen? Tindakan ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-1 text-sm bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                                            <i class="fa-solid fa-trash mr-1"></i>{{ __('admin.s.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                <i class="fa-solid fa-comments text-4xl mb-4 block"></i>
                                <p>{{ __('chat.s.belum_ada_percakapan') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $sessions->links() }}
        </div>
    </div>
@endsection