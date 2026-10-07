@extends('layouts.dashboard')

@section('title', __('nav.landing_chatbot'))

@section('content')
    @livewire('admin.landing-chatbot-manager', ['widget' => $widget])
@endsection