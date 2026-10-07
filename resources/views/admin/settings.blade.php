@extends('layouts.dashboard')

@section('title', __('nav.system_settings'))
@section('page-title', __('nav.system_settings'))

@section('content')
    @livewire('admin.system-settings')
@endsection