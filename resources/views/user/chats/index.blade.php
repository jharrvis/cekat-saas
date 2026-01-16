@extends('layouts.dashboard')

@section('title', 'Chat History')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Chat History</h1>
                <p class="text-muted-foreground">Lihat semua percakapan dari chatbot Anda</p>
            </div>
            <a href="{{ route('chats.export') }}" class="btn-secondary">
                <i class="fa-solid fa-download mr-2"></i>Export CSV
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-card rounded-xl p-4 border">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama customer..."
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <select name="widget" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20">
                        <option value="">Semua Widget</option>
                        @foreach($widgets as $w)
                            <option value="{{ $w->id }}" {{ request('widget') == $w->id ? 'selected' : '' }}>
                                {{ $w->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20">
                        <option value="">Semua Status</option>
                        <option value="has_lead" {{ request('status') == 'has_lead' ? 'selected' : '' }}>Has Lead</option>
                        <option value="no_lead" {{ request('status') == 'no_lead' ? 'selected' : '' }}>No Lead</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-search mr-2"></i>Filter
                </button>
            </form>
        </div>

        {{-- Stats Cards --}}
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Total Conversations</div>
                <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Bulan Ini</div>
                <div class="text-2xl font-bold text-primary">{{ $stats['this_month'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Leads Collected</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['leads'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Avg. Messages/Chat</div>
                <div class="text-2xl font-bold">{{ number_format($stats['avg_messages'], 1) }}</div>
            </div>
        </div>

        {{-- Chat List --}}
        <div class="bg-card rounded-xl border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">Widget</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Customer</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Messages</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Date</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Actions</th>
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
                                    <span class="text-muted-foreground">Anonymous</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm">{{ $session->messages_count }} pesan</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($session->visitor_name)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                        <i class="fa-solid fa-star mr-1"></i>Lead
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                        Visitor
                                    </span>
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
                                        <i class="fa-solid fa-eye mr-1"></i>View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                <i class="fa-solid fa-comments text-4xl mb-4 block"></i>
                                <p>Belum ada percakapan</p>
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