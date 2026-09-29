@props(['tone' => null])
@php
    $styles = match ($tone) {
        'alert' => 'background-color:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;',
        'danger' => 'background-color:#fef2f2;border:1px solid #fecaca;border-left:3px solid #dc2626;',
        default => 'background-color:#fafafa;border:1px solid #e4e4e7;',
    };
@endphp
<div style="{{ $styles }}border-radius:8px;padding:20px 22px;margin:20px 0;">
    {{ $slot }}
</div>
