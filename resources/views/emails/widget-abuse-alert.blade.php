<x-emails.layout title="{{ __('emails.s.abuse_alert_heading') }}" category="Keamanan">
    <x-emails.heading>{{ __('emails.s.abuse_alert_heading') }}</x-emails.heading>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        Halo {{ $user->name }},
    </p>
    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __($type === 'domain' ? 'emails.s.abuse_intro_domain' : 'emails.s.abuse_intro_quota', ['widget' => $widgetName]) }}
    </p>

    <x-emails.panel>
        <x-emails.field label="Widget" :value="$widgetName" />
        <x-emails.field label="{{ __('emails.s.abuse_count_label') }}"
            :value="__('emails.s.abuse_within_minutes', ['count' => $count, 'minutes' => $windowMinutes])" />
    </x-emails.panel>

    <p style="font-size:15px;line-height:1.65;color:#3f3f46;margin:0 0 4px;">
        {{ __($type === 'domain' ? 'emails.s.abuse_advice_domain' : 'emails.s.abuse_advice_quota') }}
    </p>
</x-emails.layout>
