{{-- Widget Customizer Tab --}}
<div>
    <h3 class="text-lg font-bold mb-4">{{ __('channels.s.widget_customizer') }}</h3>
    <p class="text-muted-foreground mb-6">{{ __('channels.s.customize_the_appearance_and_behavior_of_your_ch') }}</p>

    @livewire('widget-customizer', ['widgetId' => $chatbot->id])
</div>