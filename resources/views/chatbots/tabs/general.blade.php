{{-- General Tab --}}
<div>
    <h3 class="text-lg font-bold mb-4">General Information</h3>
    <p class="text-muted-foreground mb-6">Basic information about your chatbot</p>

    <form action="{{ route('chatbots.update', $chatbot->id) }}" method="POST" class="space-y-6 max-w-2xl">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-2">Chatbot Name *</label>
            <input type="text" name="display_name" value="{{ $chatbot->display_name }}"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="e.g., Customer Support Bot" required>
            @error('display_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="Brief description of what this chatbot does...">{{ $chatbot->description }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Status</label>
            <select name="status"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="draft" {{ $chatbot->status === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ $chatbot->status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $chatbot->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="border-t pt-6">
            <h4 class="font-medium mb-4">Company Information</h4>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Company Name</label>
                    <input type="text" name="company_name" value="{{ $chatbot->knowledgeBase?->company_name }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Your company name">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">AI Assistant Name</label>
                    <input type="text" name="persona_name"
                        value="{{ $chatbot->knowledgeBase?->persona_name ?? 'AI Assistant' }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="e.g., Maya, Alex, Support Bot">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Tone of Voice</label>
                    <select name="persona_tone"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="friendly" {{ ($chatbot->knowledgeBase?->persona_tone ?? 'friendly') === 'friendly' ? 'selected' : '' }}>Friendly & Casual</option>
                        <option value="professional" {{ ($chatbot->knowledgeBase?->persona_tone) === 'professional' ? 'selected' : '' }}>Professional</option>
                        <option value="formal" {{ ($chatbot->knowledgeBase?->persona_tone) === 'formal' ? 'selected' : '' }}>Formal</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="submit"
                class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-save mr-2"></i> Save Changes
            </button>
        </div>
    </form>
</div>