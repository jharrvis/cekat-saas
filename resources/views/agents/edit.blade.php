@extends('layouts.dashboard')

@section('title', 'Edit ' . $agent->name)
@section('page-title', 'Edit AI Agent')

@section('content')
    <div class="space-y-6">

        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('agents.index') }}" class="text-muted-foreground hover:text-foreground transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke AI Agents
            </a>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('message'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Agent Info Card --}}
                <div class="bg-card border rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-indigo-600 flex items-center justify-center text-white">
                            <i class="fa-solid fa-robot text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">{{ $agent->name }}</h2>
                            <p class="text-sm text-muted-foreground">Slug: {{ $agent->slug }}</p>
                        </div>
                    </div>

                    <form action="{{ route('agents.update', $agent) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Basic Info --}}
                        <div class="space-y-4">
                            <h3 class="font-semibold text-lg border-b pb-2">📝 Informasi Dasar</h3>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Nama Agent *</label>
                                <input type="text" name="name" value="{{ old('name', $agent->name) }}"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Customer Service Bot" required>
                                @error('name')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Deskripsi</label>
                                <textarea name="description" rows="2"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Agent untuk menjawab pertanyaan customer">{{ old('description', $agent->description) }}</textarea>
                            </div>

                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" 
                                        {{ old('is_active', $agent->is_active) ? 'checked' : '' }}
                                        class="sr-only peer">
                                    <div class="w-11 h-6 bg-muted peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                                <span class="text-sm font-medium">Agent Aktif</span>
                            </div>
                        </div>

                        {{-- AI Configuration --}}
                        <div class="space-y-4">
                            <h3 class="font-semibold text-lg border-b pb-2">🤖 Konfigurasi AI</h3>

                            <div>
                                <label class="block text-sm font-medium mb-2">Personality *</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
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
                                <label class="block text-sm font-medium mb-2">Kreativitas AI: <span id="temp-value">{{ $agent->ai_temperature }}</span></label>
                                <div class="flex items-center gap-4">
                                    <span class="text-xs text-muted-foreground">Fokus</span>
                                    <input type="range" name="ai_temperature" min="0" max="1.5" step="0.1" 
                                        value="{{ old('ai_temperature', $agent->ai_temperature) }}"
                                        oninput="document.getElementById('temp-value').textContent = this.value"
                                        class="flex-1 h-2 bg-muted rounded-lg appearance-none cursor-pointer">
                                    <span class="text-xs text-muted-foreground">Kreatif</span>
                                </div>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div class="space-y-4">
                            <h3 class="font-semibold text-lg border-b pb-2">💬 Pesan</h3>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Greeting Message</label>
                                <textarea name="greeting_message" rows="2"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Halo! 👋 Ada yang bisa saya bantu?">{{ old('greeting_message', $agent->greeting_message) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Fallback Message</label>
                                <textarea name="fallback_message" rows="2"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Maaf, saya sedang mengalami gangguan teknis...">{{ old('fallback_message', $agent->fallback_message) }}</textarea>
                            </div>
                        </div>

                        {{-- System Prompt --}}
                        <div class="space-y-4">
                            <h3 class="font-semibold text-lg border-b pb-2">📋 System Prompt</h3>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Custom Instructions</label>
                                <textarea name="system_prompt" rows="5"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"
                                    placeholder="Kamu adalah asisten customer service yang ramah...">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex gap-3 pt-4 border-t">
                            <button type="submit"
                                class="flex-1 px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium">
                                <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Knowledge Base - HERO CARD (Most Important) --}}
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white relative overflow-hidden">
                    {{-- Background Pattern --}}
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full -ml-12 -mb-12"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-brain text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Knowledge Base</h3>
                                <p class="text-white/80 text-sm">Latih AI dengan pengetahuan bisnis Anda</p>
                            </div>
                        </div>

                        @php
                            $kb = $agent->knowledgeBase;
                            $faqCount = $kb ? $kb->faqs->count() : 0;
                            $docCount = $kb && method_exists($kb, 'documents') ? $kb->documents()->count() : 0;
                        @endphp

                        {{-- Quick Stats Grid --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-white/20 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold">{{ $faqCount }}</div>
                                <div class="text-xs text-white/80">FAQs</div>
                            </div>
                            <div class="bg-white/20 rounded-lg p-3 text-center">
                                <div class="text-2xl font-bold">{{ $docCount }}</div>
                                <div class="text-xs text-white/80">Dokumen</div>
                            </div>
                        </div>

                        {{-- Tips --}}
                        @if($faqCount === 0 && $docCount === 0)
                            <div class="bg-white/20 rounded-lg p-3 mb-4">
                                <p class="text-sm flex items-start gap-2">
                                    <i class="fa-solid fa-lightbulb mt-0.5"></i>
                                    <span>Tambahkan FAQ untuk melatih AI menjawab pertanyaan pelanggan!</span>
                                </p>
                            </div>
                        @endif

                        <a href="{{ route('agents.knowledge', $agent) }}"
                            class="block w-full text-center px-4 py-3 bg-white text-amber-600 rounded-lg hover:bg-amber-50 transition font-bold text-sm shadow-lg">
                            <i class="fa-solid fa-edit mr-2"></i> Kelola Knowledge Base
                        </a>
                    </div>
                </div>

                {{-- AI Tier Card --}}
                @include('agents.partials.ai-tier-card')

                {{-- Linked Widgets --}}
                <div class="bg-card border rounded-xl p-5">
                    <h3 class="font-semibold mb-4">🔗 Widget Terhubung</h3>
                    @if($agent->widgets->count() > 0)
                        <div class="space-y-2">
                            @foreach($agent->widgets as $widget)
                                <a href="{{ route('chatbots.edit', $widget) }}"
                                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs"
                                        style="background-color: {{ $widget->settings['color'] ?? '#6366f1' }}">
                                        <i class="fa-solid fa-comment"></i>
                                    </div>
                                    <span class="text-sm font-medium">{{ $widget->display_name ?? $widget->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">Belum ada widget yang menggunakan agent ini.</p>
                        <a href="{{ route('chatbots.create') }}"
                            class="inline-flex items-center mt-3 text-sm text-primary hover:underline">
                            <i class="fa-solid fa-plus mr-1"></i> Buat Widget Baru
                        </a>
                    @endif
                </div>

                {{-- Stats Card --}}
                <div class="bg-card border rounded-xl p-5">
                    <h3 class="font-semibold mb-4">📊 Statistik</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Total Pesan</span>
                            <span class="font-bold">{{ number_format($agent->messages_used) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Total Percakapan</span>
                            <span class="font-bold">{{ number_format($agent->conversations_count) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Dibuat</span>
                            <span class="font-bold">{{ $agent->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Danger Zone --}}

                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-5">
                    <h3 class="font-semibold text-red-700 dark:text-red-400 mb-4">⚠️ Danger Zone</h3>
                    
                    @if($agent->widgets->count() > 0)
                        <p class="text-sm text-red-600 dark:text-red-400 mb-3">
                            Tidak bisa menghapus agent yang masih digunakan oleh {{ $agent->widgets->count() }} widget.
                        </p>
                    @else
                        <form action="{{ route('agents.destroy', $agent) }}" method="POST"
                            onsubmit="return confirm('Yakin ingin menghapus AI Agent ini? Semua data akan hilang!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                                <i class="fa-solid fa-trash mr-2"></i> Hapus Agent
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
