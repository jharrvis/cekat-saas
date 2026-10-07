@props(['variant' => 'dashboard'])

{{--
    Quick language switcher (dashboard header + landing nav).
    Vanilla JS on purpose: the landing page does not load Alpine.
    Language names are shown in their own language by convention.
--}}
@php
    $current = app()->getLocale();
    $names = ['id' => 'Bahasa Indonesia', 'en' => 'English'];
    $uid = 'locsw-' . $variant . '-' . uniqid();
    $dark = $variant === 'landing';
@endphp

<div class="relative inline-block text-left">
    <button type="button" onclick="document.getElementById('{{ $uid }}').classList.toggle('hidden')"
        class="{{ $dark
            ? 'flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 px-2 py-2 rounded-lg text-sm font-medium transition-colors'
            : 'flex items-center gap-1.5 p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-full transition-colors h-9 px-2.5' }}"
        aria-label="{{ __('settings.language') }}" title="{{ __('settings.language') }}">
        <i class="fa-solid fa-globe {{ $dark ? 'w-4 h-4' : '' }}"></i>
        <span class="text-xs font-bold tracking-wide">{{ strtoupper($current) }}</span>
        <i class="fa-solid fa-chevron-down text-[9px]"></i>
    </button>

    <div id="{{ $uid }}"
        class="hidden absolute right-0 mt-2 w-48 rounded-lg shadow-lg py-1 z-50 {{ $dark
            ? 'bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800'
            : 'bg-card border' }}">
        @foreach (\App\Http\Middleware\SetLocale::availableLocales() as $code)
            <form method="POST" action="{{ route('locale.switch') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $code }}">
                <button type="submit"
                    class="w-full text-left px-4 py-2 text-sm flex items-center justify-between {{ $dark
                        ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800'
                        : 'hover:bg-accent' }} {{ $code === $current ? 'font-semibold ' . ($dark ? 'text-brand-600 dark:text-brand-400' : 'text-primary') : '' }}">
                    <span>{{ $names[$code] ?? strtoupper($code) }}</span>
                    @if ($code === $current)
                        <i class="fa-solid fa-check text-xs"></i>
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('click', function (e) {
        var menu = document.getElementById('{{ $uid }}');
        if (menu && !menu.parentElement.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
