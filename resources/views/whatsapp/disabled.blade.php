@extends('layouts.dashboard')

@section('content')
    <div class="max-w-2xl mx-auto py-12 text-center">
        <div class="bg-card rounded-xl border shadow-sm p-12">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-brands fa-whatsapp text-4xl text-gray-400"></i>
            </div>

            <h1 class="text-2xl font-bold mb-2">WhatsApp Integration Not Available</h1>

            <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                The WhatsApp integration module is currently disabled by the administrator.
                Please contact support if you need this feature.
            </p>

            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>
@endsection