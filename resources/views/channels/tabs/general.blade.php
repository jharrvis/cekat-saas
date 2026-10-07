{{-- General Tab --}}
<div x-data="{ showAiInfo: true }">
    <h3 class="text-lg font-bold mb-4">General Information</h3>
    <p class="text-muted-foreground mb-6">Basic settings for your chatbot widget</p>

    {{-- AI Agent Linked Banner --}}
    @if($chatbot->ai_agent_id)
        @php $agent = $chatbot->aiAgent; @endphp
        <div class="bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center">
                        <i class="fa-solid fa-robot text-primary"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm">Terhubung ke AI Agent</p>
                        <p class="text-muted-foreground text-xs">{{ $agent->name }} - Knowledge Base dan persona dikelola
                            oleh AI Agent</p>
                    </div>
                </div>
                <a href="{{ route('agents.edit', $agent) }}" class="text-primary hover:underline text-sm">
                    <i class="fa-solid fa-external-link-alt mr-1"></i>Kelola Agent
                </a>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8">

        {{-- Left Column: Form --}}
        <div>
            <form action="{{ route('channels.update', $chatbot->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Chatbot Name --}}
                <div>
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        Chatbot Name *
                        <x-help-tooltip text="The internal name for this chatbot, visible only to you." />
                    </label>
                    <input type="text" name="display_name" value="{{ $chatbot->display_name }}"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="e.g., Customer Support Bot" required>
                    @error('display_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        Description
                        <x-help-tooltip text="A brief description to help you organize your channels." />
                    </label>
                    <textarea name="description" rows="2"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        placeholder="Brief description...">{{ $chatbot->description }}</textarea>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        Status
                        <x-help-tooltip text="Controls whether the chatbot is publicly accessible." />
                    </label>
                    <select name="status"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="draft" {{ $chatbot->status === 'draft' ? 'selected' : '' }}>🔒 Draft</option>
                        <option value="active" {{ $chatbot->status === 'active' ? 'selected' : '' }}>✅ Active</option>
                        <option value="inactive" {{ $chatbot->status === 'inactive' ? 'selected' : '' }}>⏸️ Inactive
                        </option>
                    </select>
                </div>

                {{-- AI Agent Selection --}}
                @php
                    $userAgents = auth()->user()->aiAgents()->where('is_active', true)->get();
                @endphp
                <div class="bg-gradient-to-r from-primary/5 to-primary/10 border border-primary/20 rounded-xl p-4">
                    <label class="block text-sm font-medium mb-2 flex items-center">
                        <i class="fa-solid fa-robot text-primary mr-2"></i>
                        AI Agent
                        <x-help-tooltip
                            text="Pilih AI Agent untuk menggunakan knowledge base dan persona yang sudah ditraining. Satu AI Agent bisa dipakai di banyak widget." />
                    </label>
                    <select name="ai_agent_id"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white dark:bg-slate-800">
                        <option value="">🚫 Tanpa AI Agent (Knowledge Base Sendiri)</option>
                        @foreach($userAgents as $agent)
                            <option value="{{ $agent->id }}" {{ $chatbot->ai_agent_id == $agent->id ? 'selected' : '' }}>
                                🤖 {{ $agent->name }}
                                @if($agent->knowledgeBase)
                                    ({{ $agent->knowledgeBase->faqs()->count() }} FAQ,
                                    {{ $agent->knowledgeBase->documents()->count() }} Doc)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="mt-2 text-xs text-muted-foreground space-y-1">
                        <p><i class="fa-solid fa-info-circle mr-1"></i> AI Agent menentukan personality, knowledge base,
                            dan cara AI menjawab.</p>
                        @if($userAgents->isEmpty())
                            <p class="text-amber-600"><i class="fa-solid fa-plus mr-1"></i>
                                <a href="{{ route('agents.create') }}" class="underline">Buat AI Agent baru</a> untuk
                                berbagi knowledge base antar widget.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Domain moved to the dedicated Domain tab --}}
                <div class="bg-muted/40 border rounded-xl p-4 text-sm text-muted-foreground">
                    <i class="fa-solid fa-shield-halved mr-2"></i>
                    Pengaturan domain pindah ke tab
                    <a href="{{ route('channels.edit.tab', [$chatbot->id, 'domains']) }}" class="text-primary hover:underline font-medium">Domain</a>.
                    Saat ini: <code class="bg-muted px-1 rounded">{{ $chatbot->settings['allowed_domains'] ?? 'semua domain diizinkan' }}</code>
                </div>

                {{-- Save Button --}}
                <div class="pt-2">
                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                        <i class="fa-solid fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
