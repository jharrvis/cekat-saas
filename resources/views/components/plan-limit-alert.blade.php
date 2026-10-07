{{--
    T-05: banner tunggal untuk penolakan batas paket.
    Ditampilkan dari flash 'plan_limit_error' (diisi PlanLimitService::limitMessage()).
    Dipasang di layouts.dashboard (redirect penuh) dan di view komponen Livewire
    yang menangani batas di tempat (KB editor, CreateChatbot).
--}}
@if (session()->has('plan_limit_error'))
    <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-xl mb-6 flex flex-wrap items-center gap-3" role="alert">
        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
        <span class="flex-1 text-sm font-medium">{{ session('plan_limit_error') }}</span>
        <a href="{{ route('billing') }}"
            class="px-3 py-1.5 bg-amber-500 text-white text-xs font-semibold rounded-lg hover:bg-amber-600 transition">
            {{ __('plans.view_plans', [], 'id') }}
        </a>
    </div>
@endif
