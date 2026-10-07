@extends('layouts.dashboard')

@section('title', $featureName . ' Locked')

@section('content')
    <div class="max-w-2xl mx-auto py-12 text-center">
        <div class="bg-card rounded-xl border border-dashed border-primary/20 shadow-sm p-12">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-lock text-primary text-2xl"></i>
            </div>

            <h1 class="text-2xl font-bold mb-2">{{ $featureName }} Locked</h1>

            <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                {{ $description }}
            </p>

            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('billing') }}"
                    class="inline-flex items-center justify-center px-5 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                    <i class="fa-solid fa-rocket mr-2"></i> {{ __('channels.s.upgrade_plan') }}
                </a>
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center px-5 py-3 border border-border rounded-lg hover:bg-muted transition text-sm font-medium">
                    <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('general.s.dashboard') }}
                </a>
            </div>
        </div>
    </div>
@endsection
