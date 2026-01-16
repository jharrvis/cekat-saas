@extends('layouts.dashboard')

@section('title', 'Widget Customizer')
@section('page-title', 'Tampilan Widget')

@section('content')
    <div class="space-y-6">
        @livewire('widget-customizer')
    </div>
@endsection