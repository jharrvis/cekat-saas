@extends('layouts.dashboard')

@section('title', __('nav.admin_dashboard'))
@section('page-title', __('nav.admin_dashboard'))

@section('content')
    <div>
        <div class="mb-6">
            <h2 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}!</h2>
            <p class="text-muted-foreground">{{ __('admin.s.here_s_what_s_happening_with_your_platform') }}</p>
        </div>

        <div class="grid md:grid-cols-4 gap-6 mb-6">
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('admin.s.total_users') }}</span>
                    <i class="fa-solid fa-users text-blue-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ \App\Models\User::count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.all_registered') }}</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('admin.s.total_chatbots') }}</span>
                    <i class="fa-solid fa-robot text-green-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ \App\Models\Widget::count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.created_by_users') }}</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('admin.s.active_plans') }}</span>
                    <i class="fa-solid fa-box text-purple-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ \App\Models\Plan::where('is_active', true)->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.available_plans') }}</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('admin.s.this_month') }}</span>
                    <i class="fa-solid fa-calendar text-orange-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ \App\Models\User::whereMonth('created_at', now()->month)->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.new_users') }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-bold mb-4">{{ __('admin.s.recent_users') }}</h3>
                <div class="space-y-3">
                    @foreach(\App\Models\User::latest()->take(5)->get() as $user)
                        <div class="flex items-center justify-between py-2 border-b last:border-0">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ $user->name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $user->email }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-bold mb-4">{{ __('admin.s.quick_actions') }}</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('admin.users') }}"
                        class="p-4 border rounded-lg hover:bg-muted/30 transition text-center">
                        <i class="fa-solid fa-users text-2xl text-blue-500 mb-2"></i>
                        <p class="text-sm font-medium">{{ __('admin.s.manage_users') }}</p>
                    </a>
                    <a href="{{ route('admin.plans') }}"
                        class="p-4 border rounded-lg hover:bg-muted/30 transition text-center">
                        <i class="fa-solid fa-box text-2xl text-purple-500 mb-2"></i>
                        <p class="text-sm font-medium">{{ __('admin.s.manage_plans') }}</p>
                    </a>
                    <a href="{{ route('admin.settings') }}"
                        class="p-4 border rounded-lg hover:bg-muted/30 transition text-center">
                        <i class="fa-solid fa-cog text-2xl text-gray-500 mb-2"></i>
                        <p class="text-sm font-medium">{{ __('admin.s.system_settings') }}</p>
                    </a>
                    <div class="p-4 border rounded-lg bg-muted/20 text-center opacity-50">
                        <i class="fa-solid fa-chart-line text-2xl text-green-500 mb-2"></i>
                        <p class="text-sm font-medium">{{ __('admin.s.analytics') }}</p>
                        <span class="text-xs text-muted-foreground">{{ __('admin.s.coming_soon') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection