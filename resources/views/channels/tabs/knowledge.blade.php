{{-- Knowledge Base Tab --}}
<div>
    @if($chatbot->ai_agent_id)
        {{-- Widget linked to AI Agent --}}
        @php $agent = $chatbot->aiAgent; @endphp
        <div
            class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div
                    class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white flex-shrink-0">
                    <i class="fa-solid fa-brain text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold mb-2">{{ __('channels.s.knowledge_base_dikelola_oleh_ai_agent') }}</h3>
                    <p class="text-muted-foreground mb-4">
                        {{ __('channels.s.widget_ini_terhubung_ke_ai_agent') }} <strong>{{ $agent->name }}</strong>{{ __('channels.s.semua_faq_dokumen_dan_training_ai_dikelola_melal') }}
                    </p>

                    {{-- Quick Stats --}}
                    @php
                        $kb = $agent->knowledgeBase;
                        $faqCount = $kb ? $kb->faqs()->count() : 0;
                        $docCount = $kb ? $kb->documents()->count() : 0;
                    @endphp
                    <div class="flex gap-6 mb-4">
                        <div>
                            <span class="text-2xl font-bold text-primary">{{ $faqCount }}</span>
                            <span class="text-sm text-muted-foreground ml-1">{{ __('agents.s.faqs') }}</span>
                        </div>
                        <div>
                            <span class="text-2xl font-bold text-green-600">{{ $docCount }}</span>
                            <span class="text-sm text-muted-foreground ml-1">{{ __('agents.s.dokumen') }}</span>
                        </div>
                    </div>

                    <a href="{{ route('agents.knowledge', $agent) }}"
                        class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-700 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-900/30 transition font-medium text-sm">
                        <i class="fa-solid fa-external-link-alt mr-2"></i>
                        {{ __('channels.s.kelola_knowledge_base_di_ai_agent') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Agent Info Card --}}
        <div class="mt-6 bg-card border rounded-xl p-4">
            <h4 class="font-medium mb-3 flex items-center gap-2">
                <i class="fa-solid fa-link text-primary"></i>
                {{ __('channels.s.ai_agent_terhubung') }}
            </h4>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                    <i class="fa-solid fa-robot text-primary"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">{{ $agent->name }}</p>
                    <p class="text-sm text-muted-foreground">{{ $agent->description ?: 'Tidak ada deskripsi' }}</p>
                </div>
                <a href="{{ route('agents.edit', $agent) }}" class="text-primary hover:underline text-sm">
                    <i class="fa-solid fa-edit mr-1"></i>{{ __('channels.s.edit_agent') }}
                </a>
            </div>
        </div>

        {{-- Unlink Option --}}
        <div class="mt-6 text-center">
            <p class="text-sm text-muted-foreground mb-2">
                {{ __('channels.s.ingin_mengelola_knowledge_base_terpisah_dari_ai') }}
            </p>
            <form action="{{ route('channels.unlink-agent', $chatbot->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                    onclick="return confirm('Yakin ingin memutuskan koneksi dengan AI Agent? Widget akan memiliki Knowledge Base sendiri.')"
                    class="text-red-500 hover:text-red-700 text-sm font-medium">
                    <i class="fa-solid fa-unlink mr-1"></i>{{ __('channels.s.putuskan_koneksi_ai_agent') }}
                </button>
            </form>
        </div>
    @else
        {{-- Widget has its own Knowledge Base --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold">{{ __('channels.s.knowledge_base_ai_training') }}</h3>
                <p class="text-muted-foreground">{{ __('channels.s.train_your_ai_with_faqs_documents_and_website_co') }}</p>
            </div>

            {{-- Link to AI Agent suggestion --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-2">
                <p class="text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2">
                    <i class="fa-solid fa-lightbulb"></i>
                    <span>{{ __('channels.s.gunakan') }} <a href="{{ route('agents.index') }}" class="font-medium underline">{{ __('channels.s.ai_agent') }}</a> {{ __('channels.s.untuk_berbagi_knowledge_base_antar_widget') }}</span>
                </p>
            </div>
        </div>

        @livewire('knowledge-base-editor', ['widgetId' => $chatbot->id])
    @endif
</div>
