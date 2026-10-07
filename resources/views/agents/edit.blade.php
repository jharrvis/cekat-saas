@extends('layouts.dashboard')

@section('title', 'Edit ' . $agent->name)
@section('page-title', __('agents.s.edit_agent_title'))

@section('content')
    <div class="space-y-6" x-data="{ tab: 'profil' }">

        {{-- Back Button --}}
        <div class="mb-2">
            <a href="{{ route('agents.index') }}" class="text-muted-foreground hover:text-foreground transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>{{ __('agents.s.kembali_ke_ai_agents') }}</a>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                {{ session('message') }}
            </div>
        @endif

        {{-- Agent Header --}}
        <div class="bg-card border rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-indigo-600 flex items-center justify-center text-white">
                    <i class="fa-solid fa-robot text-xl"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold">{{ $agent->name }}</h2>
                    <p class="text-sm text-muted-foreground">Slug: {{ $agent->slug }} · {{ $agent->is_active ? 'Aktif' : 'Nonaktif' }} · Dibuat {{ $agent->created_at->format('d M Y') }}</p>
                </div>
                <form action="{{ route('agents.toggle-status', $agent) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 border rounded-lg text-sm font-medium hover:bg-muted/50 transition">
                        {{ $agent->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Tab Nav --}}
        <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
            <div class="border-b flex overflow-x-auto" role="tablist" aria-label="{{ __('agents.s.editor_ai_agent') }}">
                <button type="button" role="tab" @click="tab = 'profil'" :aria-selected="tab === 'profil'"
                    :class="tab === 'profil' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                    <i class="fa-solid fa-user mr-2"></i>{{ __('agents.s.profil') }}</button>
                <button type="button" role="tab" @click="tab = 'perilaku'" :aria-selected="tab === 'perilaku'"
                    :class="tab === 'perilaku' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                    <i class="fa-solid fa-sliders mr-2"></i>{{ __('agents.s.perilaku') }}</button>
                <button type="button" role="tab" @click="tab = 'knowledge'" :aria-selected="tab === 'knowledge'"
                    :class="tab === 'knowledge' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                    <i class="fa-solid fa-brain mr-2"></i>{{ __('agents.s.knowledge') }}</button>
                <button type="button" role="tab" @click="tab = 'channels'" :aria-selected="tab === 'channels'"
                    :class="tab === 'channels' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                    <i class="fa-solid fa-share-nodes mr-2"></i>{{ __('agents.s.channels') }}<span class="ml-1 text-xs bg-muted px-1.5 py-0.5 rounded-full">{{ $agent->widgets->count() }}</span>
                </button>
                <button type="button" role="tab" @click="tab = 'testing'" :aria-selected="tab === 'testing'"
                    :class="tab === 'testing' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                    <i class="fa-solid fa-flask mr-2"></i>{{ __('agents.s.uji_coba') }}</button>
                <button type="button" role="tab" @click="tab = 'lanjutan'" :aria-selected="tab === 'lanjutan'"
                    :class="tab === 'lanjutan' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                    class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                    <i class="fa-solid fa-gear mr-2"></i>{{ __('agents.s.lanjutan') }}</button>
            </div>

            <div class="p-6">
                <form action="{{ route('agents.update', $agent) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- TAB: Profil --}}
                    <div x-show="tab === 'profil'" role="tabpanel" class="space-y-4 max-w-2xl">
                        <h3 class="font-semibold text-lg">{{ __('agents.s.profil_agent') }}</h3>
                        <div>
                            <label for="agent-name" class="block text-sm font-medium mb-2">{{ __('agents.s.nama_agent') }}</label>
                            <input id="agent-name" type="text" name="name" value="{{ old('name', $agent->name) }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('agents.s.customer_service_bot') }}" required>
                            @error('name')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="agent-desc" class="block text-sm font-medium mb-2">{{ __('agents.s.deskripsi') }}</label>
                            <textarea id="agent-desc" name="description" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('agents.s.agent_untuk_menjawab_pertanyaan_customer') }}">{{ old('description', $agent->description) }}</textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', $agent->is_active) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-muted peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <span class="text-sm font-medium">{{ __('agents.s.agent_aktif') }}</span>
                        </div>
                    </div>

                    {{-- TAB: Perilaku --}}
                    <div x-show="tab === 'perilaku'" role="tabpanel" class="space-y-6 max-w-2xl" x-cloak>
                        <h3 class="font-semibold text-lg">{{ __('agents.s.perilaku_gaya_bicara') }}</h3>
                        <div>
                            <span class="block text-sm font-medium mb-2" id="personality-label">{{ __('agents.s.personality') }}</span>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" role="radiogroup" aria-labelledby="personality-label">
                                @foreach(['friendly' => '😊 Friendly', 'professional' => '💼 Professional', 'casual' => '😎 Casual', 'formal' => '🎩 Formal'] as $value => $label)
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="personality" value="{{ $value }}"
                                            {{ old('personality', $agent->personality) === $value ? 'checked' : '' }}
                                            class="peer sr-only">
                                        <div class="p-3 border rounded-lg text-center transition peer-checked:border-primary peer-checked:bg-primary/5 hover:bg-muted/50">
                                            <span class="text-sm font-medium">{{ $label }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="agent-temp" class="block text-sm font-medium mb-2">{{ __('agents.s.kreativitas_ai_2') }} <span id="temp-value">{{ $agent->ai_temperature }}</span></label>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-muted-foreground">{{ __('agents.s.fokus') }}</span>
                                <input id="agent-temp" type="range" name="ai_temperature" min="0" max="1.5" step="0.1"
                                    value="{{ old('ai_temperature', $agent->ai_temperature) }}"
                                    oninput="document.getElementById('temp-value').textContent = this.value"
                                    class="flex-1 h-2 bg-muted rounded-lg appearance-none cursor-pointer">
                                <span class="text-xs text-muted-foreground">{{ __('agents.s.kreatif') }}</span>
                            </div>
                        </div>

                        <div>
                            <label for="agent-fallback" class="block text-sm font-medium mb-2">{{ __('agents.s.fallback_message') }}</label>
                            <textarea id="agent-fallback" name="fallback_message" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('agents.s.maaf_saya_sedang_mengalami_gangguan_teknis') }}">{{ old('fallback_message', $agent->fallback_message) }}</textarea>
                        </div>

                        <div>
                            <label for="agent-prompt" class="block text-sm font-medium mb-2">{{ __('agents.s.custom_instructions_system_prompt') }}</label>
                            <textarea id="agent-prompt" name="system_prompt" rows="5"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"
                                placeholder="{{ __('agents.s.kamu_adalah_asisten_customer_service_yang_ramah_2') }}">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
                        </div>
                    </div>

                    {{-- Sticky save bar for the two form tabs --}}
                    <div x-show="tab === 'profil' || tab === 'perilaku'" class="flex gap-3 pt-4 border-t mt-6 max-w-2xl">
                        <button type="submit"
                            class="flex-1 px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium">
                            <i class="fa-solid fa-save mr-2"></i>{{ __('agents.s.simpan_perubahan') }}</button>
                    </div>
                </form>

                {{-- TAB: Knowledge --}}
                <div x-show="tab === 'knowledge'" role="tabpanel" class="max-w-2xl" x-cloak>
                    @php
                        $kb = $agent->knowledgeBase;
                        $faqCount = $kb ? $kb->faqs->count() : 0;
                        $docCount = $kb && method_exists($kb, 'documents') ? $kb->documents()->count() : 0;
                    @endphp
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full -ml-12 -mb-12"></div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                    <i class="fa-solid fa-brain text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg">{{ __('agents.s.knowledge_base') }}</h3>
                                    <p class="text-white/80 text-sm">{{ __('agents.s.latih_ai_dengan_pengetahuan_bisnis_anda') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-white/20 rounded-lg p-3 text-center">
                                    <div class="text-2xl font-bold">{{ $faqCount }}</div>
                                    <div class="text-xs text-white/80">{{ __('agents.s.faqs') }}</div>
                                </div>
                                <div class="bg-white/20 rounded-lg p-3 text-center">
                                    <div class="text-2xl font-bold">{{ $docCount }}</div>
                                    <div class="text-xs text-white/80">{{ __('agents.s.dokumen') }}</div>
                                </div>
                            </div>
                            @if($faqCount === 0 && $docCount === 0)
                                <div class="bg-white/20 rounded-lg p-3 mb-4">
                                    <p class="text-sm flex items-start gap-2">
                                        <i class="fa-solid fa-lightbulb mt-0.5"></i>
                                        <span>{{ __('agents.s.tambahkan_faq_untuk_melatih_ai_menjawab_pertanya') }}</span>
                                    </p>
                                </div>
                            @endif
                            <a href="{{ route('agents.knowledge', $agent) }}"
                                class="block w-full text-center px-4 py-3 bg-white text-amber-600 rounded-lg hover:bg-amber-50 transition font-bold text-sm shadow-lg">
                                <i class="fa-solid fa-edit mr-2"></i>{{ __('agents.s.kelola_knowledge_base') }}</a>
                        </div>
                    </div>
                </div>

                {{-- TAB: Channels --}}
                <div x-show="tab === 'channels'" role="tabpanel" class="max-w-2xl" x-cloak>
                    <h3 class="font-semibold text-lg mb-1">{{ __('agents.s.channel_terhubung') }}</h3>
                    <p class="text-sm text-muted-foreground mb-4">{{ __('agents.s.satu_agent_bisa_dipasang_di_banyak_channel_web_w') }}</p>
                    @if($agent->widgets->count() > 0)
                        <div class="space-y-2">
                            @foreach($agent->widgets as $widget)
                                <a href="{{ route('channels.edit', $widget) }}"
                                    class="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted/50 transition">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs"
                                        style="background-color: {{ $widget->settings['color'] ?? '#6366f1' }}">
                                        <i class="fa-solid fa-comment"></i>
                                    </div>
                                    <span class="text-sm font-medium flex-1">{{ $widget->display_name ?? $widget->name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $widget->slug }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="border border-dashed rounded-xl p-8 text-center">
                            <p class="text-sm text-muted-foreground mb-3">{{ __('agents.s.belum_ada_channel_yang_memakai_agent_ini') }}</p>
                            <a href="{{ route('channels.create') }}"
                                class="inline-flex items-center text-sm text-primary hover:underline font-medium">
                                <i class="fa-solid fa-plus mr-1"></i>{{ __('agents.s.buat_channel_baru') }}</a>
                        </div>
                    @endif
                </div>

                {{-- TAB: Uji Coba --}}
                <div x-show="tab === 'testing'" role="tabpanel" class="max-w-2xl" x-cloak
                    x-data="{
                        loading: false,
                        messages: [],
                        draft: '',
                        error: '',
                        session: 'test-' + Date.now(),
                        lastDuration: '',
                        async send() {
                            const text = this.draft.trim();
                            if (!text || this.loading) return;
                            const channel = document.getElementById('test-channel').value;
                            this.messages.push({ role: 'user', content: text });
                            this.draft = '';
                            this.error = '';
                            this.loading = true;
                            const started = performance.now();
                            try {
                                const r = await fetch('/api/chat', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                    body: JSON.stringify({
                                        message: text,
                                        widgetId: channel,
                                        history: this.messages.slice(0, -1).slice(-12),
                                        sessionId: this.session,
                                        preview: true
                                    })
                                });
                                const d = await r.json();
                                const reply = d.response || d.message || d.error || 'Tidak ada respons.';
                                this.lastDuration = ((performance.now() - started) / 1000).toFixed(1).replace('.', ',') + ' dtk';
                                this.messages.push({ role: 'assistant', content: reply });
                            } catch (e) {
                                this.error = 'Gagal menghubungi API: ' + e.message;
                            } finally {
                                this.loading = false;
                                this.$nextTick(() => { const el = document.getElementById('test-thread'); if (el) el.scrollTop = el.scrollHeight; });
                            }
                        }
                    }">
                    <h3 class="font-semibold text-lg mb-1">{{ __('agents.s.uji_coba_agent') }}</h3>
                    <p class="text-sm text-muted-foreground mb-4">{{ __('agents.s.kirim_pesan_percobaan_lewat_channel_agent_ini_se') }}</p>
                    @if($agent->widgets->count() > 0)
                        <div class="space-y-4">
                            <div>
                                <label for="test-channel" class="block text-sm font-medium mb-2">{{ __('agents.s.channel') }}</label>
                                <select id="test-channel"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    @foreach($agent->widgets as $widget)
                                        <option value="{{ $widget->slug }}">{{ $widget->display_name ?? $widget->name }} ({{ $widget->slug }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Utas percakapan --}}
                            <div id="test-thread" class="border rounded-xl p-4 bg-muted/20 h-80 overflow-y-auto space-y-3">
                                <template x-if="messages.length === 0 && !loading">
                                    <p class="text-sm text-muted-foreground text-center py-10">{{ __('agents.s.belum_ada_pesan_tulis_pertanyaan_pertama_anda_di') }}</p>
                                </template>
                                <template x-for="(m, i) in messages" :key="i">
                                    <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                        <div :class="m.role === 'user'
                                                ? 'bg-primary text-primary-foreground rounded-2xl rounded-br-md'
                                                : 'bg-white dark:bg-slate-800 border rounded-2xl rounded-bl-md'"
                                            class="px-4 py-2.5 max-w-[85%] shadow-sm">
                                            <p class="text-sm whitespace-pre-wrap" x-text="m.content"></p>
                                            <p x-show="m.role === 'assistant' && i === messages.length - 1 && lastDuration"
                                                class="text-[11px] opacity-60 mt-1" x-text="'Dijawab dalam ' + lastDuration"></p>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="loading" class="flex justify-start" x-cloak>
                                    <div class="bg-white dark:bg-slate-800 border rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                                        <p class="text-sm text-muted-foreground"><i class="fa-solid fa-circle-notch fa-spin mr-2"></i>{{ __('agents.s.ai_sedang_mengetik') }}</p>
                                    </div>
                                </div>
                            </div>

                            <template x-if="error"><p class="text-sm text-red-600" x-text="error"></p></template>

                            <div class="flex gap-2">
                                <textarea id="test-message" rows="2" x-model="draft"
                                    @keydown.enter.prevent="if (!$event.shiftKey) send()"
                                    class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="{{ __('agents.s.tulis_pertanyaan_percobaan_enter_untuk_mengirim') }}"></textarea>
                                <button type="button" @click="send()" :disabled="loading"
                                    class="px-5 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium disabled:opacity-50 self-end">
                                    <span x-show="!loading"><i class="fa-solid fa-paper-plane mr-2"></i>{{ __('agents.s.kirim_tes') }}</span>
                                    <span x-show="loading">{{ __('agents.s.mengirim') }}</span>
                                </button>
                            </div>
                        </div>
                    @elseif($unlinkedWidget)
                        <div class="border border-dashed rounded-xl p-8 text-center">
                            <p class="text-sm text-muted-foreground mb-2">{{ __('agents.s.agent_ini_belum_terhubung_ke_channel_mana_pun_ja') }}</p>
                            <p class="text-sm text-muted-foreground mb-4">{{ __('agents.s.widget') }} <strong>{{ $unlinkedWidget->display_name ?? $unlinkedWidget->name }}</strong> {{ __('agents.s.milik_anda_belum_terhubung_ke_agent_hubungkan_se') }}</p>
                            <form method="POST" action="{{ route('agents.attach-default-widget', $agent) }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 bg-primary text-primary-foreground text-sm rounded-lg hover:bg-primary/90 transition font-medium">
                                    <i class="fa-solid fa-link mr-2"></i>{{ __('agents.s.hubungkan_widget_saya') }}</button>
                            </form>
                        </div>
                    @else
                        <div class="border border-dashed rounded-xl p-8 text-center">
                            <p class="text-sm text-muted-foreground mb-3">{{ __('agents.s.buat_channel_dulu_untuk_menguji_agent_ini') }}</p>
                            <a href="{{ route('channels.create') }}"
                                class="inline-flex items-center text-sm text-primary hover:underline font-medium">
                                <i class="fa-solid fa-plus mr-1"></i>{{ __('agents.s.buat_channel_baru') }}</a>
                        </div>
                    @endif
                </div>

                {{-- TAB: Lanjutan --}}
                <div x-show="tab === 'lanjutan'" role="tabpanel" class="max-w-2xl space-y-6" x-cloak>

                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-5">
                        <h3 class="font-semibold text-red-700 dark:text-red-400 mb-4">{{ __('agents.s.danger_zone') }}</h3>
                        @if($agent->widgets->count() > 0)
                            <p class="text-sm text-red-600 dark:text-red-400">
                                Tidak bisa menghapus agent yang masih digunakan oleh {{ $agent->widgets->count() }} channel.
                            </p>
                        @else
                            <form action="{{ route('agents.destroy', $agent) }}" method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus AI Agent ini? Semua data akan hilang!')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                                    <i class="fa-solid fa-trash mr-2"></i>{{ __('agents.s.hapus_agent') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
