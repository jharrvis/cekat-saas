@extends('layouts.dashboard')

@section('title', __('nav.transaction_monitor'))

@section('content')
    @livewire('admin.transaction-monitor')
@endsection