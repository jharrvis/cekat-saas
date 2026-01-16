@extends('layouts.dashboard')

@section('title', 'Leads')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Leads</h1>
                <p class="text-muted-foreground">Data prospek yang dikumpulkan dari chatbot Anda</p>
            </div>
            <a href="{{ route('leads.export') }}" class="btn-secondary">
                <i class="fa-solid fa-download mr-2"></i>Export CSV
            </a>
        </div>

        {{-- Stats Cards --}}
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Total Leads</div>
                <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Bulan Ini</div>
                <div class="text-2xl font-bold text-primary">{{ $stats['this_month'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Minggu Ini</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['this_week'] }}</div>
            </div>
            <div class="bg-card rounded-xl p-4 border">
                <div class="text-muted-foreground text-sm">Conversion Rate</div>
                <div class="text-2xl font-bold">{{ number_format($stats['conversion_rate'], 1) }}%</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-card rounded-xl p-4 border">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
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
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-search mr-2"></i>Filter
                </button>
            </form>
        </div>

        {{-- Leads Table --}}
        <div class="bg-card rounded-xl border overflow-hidden">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">Nama</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Email</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Phone</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Source</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Date</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-muted/30 transition">
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $lead->visitor_name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($lead->visitor_email)
                                    <a href="mailto:{{ $lead->visitor_email }}" class="text-primary hover:underline">
                                        {{ $lead->visitor_email }}
                                    </a>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($lead->visitor_phone)
                                    <a href="tel:{{ $lead->visitor_phone }}" class="text-primary hover:underline">
                                        {{ $lead->visitor_phone }}
                                    </a>
                                @else
                                    <span class="text-muted-foreground">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full bg-primary/10 text-primary">
                                    {{ $lead->widget->display_name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-muted-foreground">
                                    {{ $lead->created_at->format('d M Y H:i') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('chats.show', $lead->id) }}"
                                    class="px-3 py-1 text-sm bg-muted hover:bg-muted/80 rounded-lg transition">
                                    <i class="fa-solid fa-comments mr-1"></i>View Chat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                <i class="fa-solid fa-users text-4xl mb-4 block"></i>
                                <p>Belum ada lead yang dikumpulkan</p>
                                <p class="text-sm mt-2">Aktifkan Lead Collection di pengaturan chatbot Anda</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $leads->links() }}
        </div>
    </div>
@endsection