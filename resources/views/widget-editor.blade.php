@extends('layouts.dashboard')

@section('title', __('nav.widget_customizer'))
@section('page-title', __('general.s.tampilan_widget'))

@section('content')
    <div class="space-y-6">
        @livewire('widget-customizer')
    </div>
@endsection