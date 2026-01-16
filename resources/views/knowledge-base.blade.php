@extends('layouts.dashboard')

@section('title', 'Knowledge Base')
@section('page-title', 'Training AI (Knowledge Base)')

@section('content')
    <div class="space-y-6">
        @livewire('knowledge-base-editor')
    </div>
@endsection