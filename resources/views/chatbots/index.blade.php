@extends('layouts.dashboard')

@section('title', 'Chatbot Widget')
@section('page-title', 'Chatbot Widget')

@section('content')
    <div>
        {{-- Messages --}}
        @if (session()->has('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                {{ session('error') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold">Chatbot Widget</h2>
                <p class="text-muted-foreground">Manage your AI chatbots</p>
            </div>
            <a href="{{ route('chatbots.create') }}"
                class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-plus mr-2"></i> Create New Chatbot
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">Total Chatbots</span>
                    <i class="fa-solid fa-robot text-blue-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $chatbots->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">of {{ $plan->max_widgets ?? 1 }} allowed</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">Active Chatbots</span>
                    <i class="fa-solid fa-check-circle text-green-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $chatbots->where('status', 'active')->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">Live on websites</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">Messages Used</span>
                    <i class="fa-solid fa-message text-purple-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ auth()->user()->monthly_message_used ?? 0 }}</p>
                <p class="text-xs text-muted-foreground mt-1">of {{ auth()->user()->monthly_message_quota ?? 100 }} this
                    month</p>
            </div>
        </div>

        {{-- Chatbots Table --}}
        <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Chatbot</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                FAQs</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Created</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($chatbots as $chatbot)
                            <tr class="hover:bg-muted/30 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                            <i class="fa-solid fa-robot"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ $chatbot->display_name ?? $chatbot->name }}</p>

                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <i class="fa-solid fa-hashtag text-[10px] text-muted-foreground"></i>
                                                <code
                                                    class="text-xs bg-muted px-1.5 py-0.5 rounded font-mono text-muted-foreground">{{ $chatbot->slug }}</code>
                                                <button onclick="copyWidgetId('{{ $chatbot->slug }}')"
                                                    class="text-xs text-muted-foreground hover:text-primary transition p-1"
                                                    title="Copy Widget ID">
                                                    <i class="fa-regular fa-copy"></i>
                                                </button>
                                            </div>

                                            <p class="text-sm text-muted-foreground mt-1">
                                                {{ Str::limit($chatbot->description, 40) ?? 'No description' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-medium {{ $chatbot->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($chatbot->status ?? 'draft') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $chatbot->knowledgeBase?->faqs()->count() ?? 0 }} FAQs
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ $chatbot->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex gap-2 justify-end">
                                        <a href="{{ route('chatbots.edit', $chatbot->id) }}"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg transition text-sm">
                                            <i class="fa-solid fa-edit mr-1"></i> Edit
                                        </a>
                                        <form action="{{ route('chatbots.destroy', $chatbot->id) }}" method="POST"
                                            onsubmit="return confirm('Delete this chatbot?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition text-sm">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-robot text-6xl text-muted-foreground mb-4"></i>
                                    <p class="text-lg font-medium mb-2">No chatbots yet</p>
                                    <p class="text-muted-foreground mb-4">Create your first AI chatbot to get started</p>
                                    <a href="{{ route('chatbots.create') }}"
                                        class="inline-flex items-center bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition">
                                        <i class="fa-solid fa-plus mr-2"></i> Create Your First Chatbot
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function copyWidgetId(id) {
                navigator.clipboard.writeText(id).then(() => {
                    // You can replace this with a nice toast notification
                    alert('Widget ID copied to clipboard: ' + id);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        </script>
    @endpush
@endsection