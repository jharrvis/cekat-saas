{{-- Widget Customizer Tab --}}
<div>
    <h3 class="text-lg font-bold mb-4">Widget Customizer</h3>
    <p class="text-muted-foreground mb-6">Customize the appearance and behavior of your chat widget</p>

    @livewire('widget-customizer', ['widgetId' => $chatbot->id])
</div>