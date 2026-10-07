@extends('layouts.dashboard')

@section('title', __('nav.plan_management'))
@section('page-title', __('nav.plan_management'))

@section('content')
    @livewire('admin.plan-manager')
@endsection