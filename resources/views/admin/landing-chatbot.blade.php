@extends('layouts.dashboard')

@section('title', 'Landing Page Chatbot')

@section('content')
    @livewire('admin.landing-chatbot-manager', ['widget' => $widget])
@endsection