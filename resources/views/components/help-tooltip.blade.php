@props(['text'])

<div class="group relative inline-flex items-center ml-2 align-middle">
    <i class="fa-regular fa-circle-question text-muted-foreground hover:text-primary cursor-help text-sm"></i>
    <div
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-48 bg-slate-900 text-white text-xs rounded p-2 text-center shadow-lg z-50 pointer-events-none">
        {{ $text }}
        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900"></div>
    </div>
</div>