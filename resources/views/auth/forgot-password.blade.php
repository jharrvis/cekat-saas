@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center p-8 bg-slate-50">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                {{-- Logo --}}
                <div class="mb-8 text-center">
                    <a href="/" class="inline-flex items-center gap-2">
                        <div class="w-10 h-10 rounded bg-slate-900 flex items-center justify-center text-white font-bold">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <span class="text-2xl font-bold text-slate-900">Cekat<span
                                class="text-blue-600">.biz.id</span></span>
                    </a>
                </div>

                <h1 class="text-2xl font-bold text-slate-900 mb-2 text-center">{{ __('auth.forgot_title') }}</h1>
                <p class="text-slate-600 mb-8 text-center">{{ __('auth.forgot_intro') }}</p>

                @if (session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    @csrf

                    <x-auth-error-summary />

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">{{ __('auth.email_label') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus @error('email') aria-invalid="true" @enderror
                            class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full bg-slate-900 text-white py-3 rounded-lg font-medium hover:bg-slate-800 transition mb-4">
                        {{ __('auth.forgot_submit') }}
                    </button>

                    <p class="text-center text-sm text-slate-600">
                        {{ __('auth.remembered') }}
                        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">{{ __('auth.sign_in') }}</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection