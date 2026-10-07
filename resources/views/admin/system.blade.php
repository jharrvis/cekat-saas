@extends('layouts.dashboard')

@section('title', __('nav.system_settings'))
@section('page-title', __('nav.system_settings'))

@section('content')
    <div class="bg-card rounded-xl shadow-sm border p-6">
        <h2 class="text-2xl font-bold mb-4">{{ __('admin.s.system_settings') }}</h2>
        <p class="text-muted-foreground">{{ __('admin.s.admin_feature_coming_soon') }}</p>
    </div>
@endsection