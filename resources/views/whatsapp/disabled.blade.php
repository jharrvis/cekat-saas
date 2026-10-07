@extends('layouts.dashboard')

@section('title', 'WhatsApp Tidak Tersedia')

@section('content')
    <div class="max-w-2xl mx-auto py-12 text-center">
        <div class="bg-card rounded-xl border shadow-sm p-12">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-brands fa-whatsapp text-4xl text-gray-400"></i>
            </div>

            <h1 class="text-2xl font-bold mb-2">{{ __('whatsapp.s.whatsapp_integration_not_available') }}</h1>

            <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                {{ __('whatsapp.s.the_whatsapp_integration_module_is_currently_dis') }}
            </p>

            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition">
                <i class="fa-solid fa-arrow-left"></i>
                {{ __('whatsapp.s.back_to_dashboard') }}
            </a>
        </div>
    </div>
@endsection