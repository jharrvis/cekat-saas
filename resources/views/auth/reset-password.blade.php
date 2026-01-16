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

                <h1 class="text-2xl font-bold text-slate-900 mb-2 text-center">Reset Password</h1>
                <p class="text-slate-600 mb-8 text-center">Enter your new password</p>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">New Password</label>
                        <input id="password" type="password" name="password" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Confirm
                            Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <button type="submit"
                        class="w-full bg-slate-900 text-white py-3 rounded-lg font-medium hover:bg-slate-800 transition">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection