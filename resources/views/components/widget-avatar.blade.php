@props(['type' => 'icon', 'icon' => 'bot', 'url' => '', 'size' => '24px', 'className' => ''])

@php
    // Lucide (MIT) style stroke icons for the widget avatar picker/preview.
    // Keep the icon set in sync with public/widget/widget.js getAvatarHtml().
    $aliases = [
        'robot' => 'bot',
        'support' => 'headphones',
        'user' => 'user-round',
        'headset' => 'headphones',
        'comment-dots' => 'message-circle',
        'comments' => 'message-circle',
        'message' => 'message-circle',
        'bell' => 'sparkles',
        'circle-question' => 'life-buoy',
        'heart' => 'heart',
    ];

    $paths = [
        'bot' => '<path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>',
        'headphones' => '<path d="M3 14h3v7H3z"/><path d="M21 14h-3v7h3z"/><path d="M3 14a9 9 0 0 1 18 0"/>',
        'user-round' => '<circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
        'message-circle' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'store' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/>',
        'briefcase' => '<rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'life-buoy' => '<circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/><circle cx="12" cy="12" r="4"/>',
        'sparkles' => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>',
    ];

    $key = $aliases[$icon] ?? $icon;
    $key = array_key_exists($key, $paths) ? $key : 'bot';
    $useImage = in_array($type, ['image', 'url'], true) && $url !== '';
@endphp

@if ($useImage)
    <img src="{{ $url }}" alt="Avatar"
        class="{{ $className }}"
        style="width:{{ $size }};height:{{ $size }};object-fit:cover;border-radius:50%;">
@else
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
        class="{{ $className }}"
        style="width:{{ $size }};height:{{ $size }};">
        {!! $paths[$key] !!}
    </svg>
@endif
