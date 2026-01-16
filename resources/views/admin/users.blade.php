@extends('layouts.dashboard')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
    @livewire('admin.user-manager')
@endsection