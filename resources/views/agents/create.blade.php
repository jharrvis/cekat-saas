@extends('layouts.dashboard')

@section('title', 'Buat AI Agent')
@section('page-title', 'Buat AI Agent Baru')

@section('content')
    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('agents.index') }}" class="text-muted-foreground hover:text-foreground transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke AI Agents
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Form (2 columns) --}}
        <div class="lg:col-span-2">
            <div class="bg-card border rounded-xl p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-indigo-600 flex items-center justify-center text-white">
                        <i class="fa-solid fa-robot text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Buat AI Agent Baru</h2>
                        <p class="text-sm text-muted-foreground">Konfigurasi otak chatbot Anda</p>
                    </div>
                </div>

                <form action="{{ route('agents.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Basic Info --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">📝 Informasi Dasar</h3>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Nama Agent *</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Customer Service Bot" required>
                            @error('name')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Deskripsi (opsional)</label>
                            <textarea name="description" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Agent untuk menjawab pertanyaan customer tentang produk dan layanan">{{ old('description') }}</textarea>
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
                                            {{ old('personality', 'friendly') === $value ? 'checked' : '' }}
                                            class="peer sr-only">
                                        <div class="p-3 border rounded-lg text-center transition peer-checked:border-primary peer-checked:bg-primary/5 hover:bg-muted/50">
                                            <span class="text-sm font-medium">{{ $label }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Kreativitas AI</label>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-muted-foreground">Fokus</span>
                                <input type="range" name="ai_temperature" min="0" max="1.5" step="0.1" 
                                    value="{{ old('ai_temperature', 0.7) }}"
                                    class="flex-1 h-2 bg-muted rounded-lg appearance-none cursor-pointer">
                                <span class="text-xs text-muted-foreground">Kreatif</span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">Nilai rendah = jawaban lebih konsisten, nilai tinggi = jawaban lebih variatif</p>
                        </div>
                    </div>

                    {{-- Greeting & Fallback --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">💬 Pesan</h3>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Greeting Message</label>
                            <textarea name="greeting_message" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Halo! 👋 Ada yang bisa saya bantu?">{{ old('greeting_message', 'Halo! 👋 Ada yang bisa saya bantu?') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Fallback Message (saat error)</label>
                            <textarea name="fallback_message" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi nanti.">{{ old('fallback_message') }}</textarea>
                        </div>
                    </div>

                    {{-- System Prompt --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">📋 System Prompt (Opsional)</h3>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Custom Instructions</label>
                            <textarea name="system_prompt" rows="4"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"
                                placeholder="Kamu adalah asisten customer service yang ramah dari Toko ABC. Jawab pertanyaan dengan singkat dan jelas. Jika tidak tahu jawabannya, minta customer untuk menghubungi CS.">{{ old('system_prompt') }}</textarea>
                            <p class="text-xs text-muted-foreground mt-1">Instruksi khusus untuk AI tentang cara menjawab. Kosongkan untuk menggunakan default berdasarkan personality.</p>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex gap-3 pt-4 border-t">
                        <a href="{{ route('agents.index') }}"
                            class="px-6 py-2 border rounded-lg hover:bg-muted transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="flex-1 px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium">
                            <i class="fa-solid fa-check mr-2"></i> Buat AI Agent
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar (1 column) --}}
        <div class="space-y-6">
            {{-- AI Tier Info --}}
            @include('agents.partials.ai-tier-card')

            {{-- Tips --}}
            <div class="bg-card border rounded-xl p-5">
                <h3 class="font-semibold mb-3">💡 Tips</h3>
                <ul class="text-sm text-muted-foreground space-y-2">
                    <li>• Agent adalah "otak" chatbot yang berisi pengetahuan dan konfigurasi AI</li>
                    <li>• Satu Agent bisa digunakan oleh banyak Widget</li>
                    <li>• Setelah membuat Agent, tambahkan FAQ di Knowledge Base</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
