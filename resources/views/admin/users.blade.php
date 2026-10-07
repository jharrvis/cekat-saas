@extends('layouts.dashboard')

@section('title', __('nav.user_management'))
@section('page-title', __('nav.user_management'))

@section('content')
    @livewire('admin.user-manager')
@endsection