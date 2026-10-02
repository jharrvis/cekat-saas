@extends('layouts.dashboard')

@section('title', 'Email Center')
@section('page-title', 'Email Center')

@section('content')
    @php
        $tab = request('tab', 'log');
        if (! in_array($tab, ['log', 'newsletter', 'pengumuman', 'template'], true)) {
            $tab = 'log';
        }
        $tabs = [
            'log' => ['label' => 'Log Email', 'icon' => 'fa-solid fa-list'],
            'newsletter' => ['label' => 'Newsletter', 'icon' => 'fa-solid fa-bullhorn'],
            'pengumuman' => ['label' => 'Pengumuman', 'icon' => 'fa-solid fa-bell'],
            'template' => ['label' => 'Template', 'icon' => 'fa-solid fa-file-lines'],
        ];
    @endphp

    {{-- Tab nav --}}
    <div class="flex items-center gap-1 border-b mb-6 overflow-x-auto">
        @foreach ($tabs as $key => $item)
            <a href="{{ route('admin.email-center', array_filter(['tab' => $key === 'log' ? null : $key])) }}"
                class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition
                    {{ $tab === $key
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground' }}">
                <i class="{{ $item['icon'] }} mr-2"></i>{{ $item['label'] }}
            </a>
        @endforeach
    </div>

    @if ($tab === 'log')
        @livewire('admin.email-log-manager')
    @elseif ($tab === 'newsletter')
        @livewire('admin.email-campaign-manager', ['type' => 'newsletter'])
    @elseif ($tab === 'pengumuman')
        @livewire('admin.email-campaign-manager', ['type' => 'announcement'])
    @else
        @livewire('admin.email-template-manager')
    @endif
@endsection
