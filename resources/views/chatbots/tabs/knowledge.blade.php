{{-- Knowledge Base Tab --}}
<div>
    <h3 class="text-lg font-bold mb-4">Knowledge Base (AI Training)</h3>
    <p class="text-muted-foreground mb-6">Train your AI with FAQs, documents, and website content</p>

    @livewire('knowledge-base-editor', ['widgetId' => $chatbot->id])
</div>