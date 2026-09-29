@props(['label' => '', 'value' => null, 'mono' => false])
@if($value !== null && $value !== '')
    <div style="margin:0 0 14px;">
        <div
            style="font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;color:#71717a;margin-bottom:4px;">
            {{ $label }}</div>
        <div
            style="font-size:15px;line-height:1.5;color:#18181b;{{ $mono ? 'font-family:Consolas,Menlo,monospace;letter-spacing:0.5px;' : '' }}">{{ $value }}</div>
    </div>
@endif
