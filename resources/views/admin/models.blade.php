@extends('layouts.dashboard')

@section('title', 'AI Models & Tiers')
@section('page-title', 'AI Models & Tiers')

@section('content')
    <div class="space-y-6">
        @livewire('admin.ai-tier-manager')
        @livewire('admin.models-manager')
    </div>
@endsection
