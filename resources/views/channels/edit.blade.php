@extends('layouts.dashboard')

@section('title', 'Edit ' . $chatbot->display_name)
@section('page-title', 'Edit Channel')

@section('content')
    <div>
        {{-- Messages --}}
        @if (session()->has('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                {{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('channels.index') }}" class="text-muted-foreground hover:text-foreground">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-2xl font-bold">{{ $chatbot->display_name }}</h2>
                    <p class="text-muted-foreground">Kelola channel Web Widget</p>
                </div>
            </div>
            <span
                class="px-3 py-1 rounded-full text-sm font-medium {{ $chatbot->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                {{ ucfirst($chatbot->status ?? 'draft') }}
            </span>
        </div>

        {{-- Tabs --}}
        <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
            <div class="border-b flex overflow-x-auto">
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'general']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'general' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-info-circle mr-2"></i> Umum
                </a>
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'knowledge']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'knowledge' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-brain mr-2"></i> Knowledge
                </a>

                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'widget']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'widget' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-paintbrush mr-2"></i> Tampilan
                </a>
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'lead']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'lead' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-user-plus mr-2"></i> Lead
                </a>
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'domains']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'domains' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-shield-halved mr-2"></i> Domain
                </a>
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'embed']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'embed' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-code mr-2"></i> Embed
                </a>
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'webhook']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'webhook' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-plug mr-2"></i> Webhook
                </a>
                <a href="{{ route('channels.edit.tab', [$chatbot->id, 'analytics']) }}"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap {{ $tab === 'analytics' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30' }}">
                    <i class="fa-solid fa-chart-line mr-2"></i> Analitik
                </a>
            </div>

            <div class="p-6">
                @if($tab === 'general')
                    @include('channels.tabs.general', ['chatbot' => $chatbot])
                @elseif($tab === 'knowledge')
                    @include('channels.tabs.knowledge', ['chatbot' => $chatbot])

                @elseif($tab === 'widget')
                    @include('channels.tabs.widget', ['chatbot' => $chatbot])
                @elseif($tab === 'lead')
                    @include('channels.tabs.lead', ['chatbot' => $chatbot])
                @elseif($tab === 'domains')
                    @include('channels.tabs.domains', ['chatbot' => $chatbot])
                @elseif($tab === 'webhook')
                    @include('channels.tabs.webhook', ['chatbot' => $chatbot])
                @elseif($tab === 'analytics')
                    @include('channels.tabs.analytics', ['chatbot' => $chatbot])
                @elseif($tab === 'embed')
                    @include('channels.tabs.embed', ['chatbot' => $chatbot])
                @endif
            </div>
        </div>
    </div>
@endsection
