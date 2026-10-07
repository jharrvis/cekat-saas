@extends('layouts.dashboard')

@section('title', __('agents.s.buat_ai_agent'))
@section('page-title', __('agents.s.buat_ai_agent_baru'))

@section('content')
    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('agents.index') }}" class="text-muted-foreground hover:text-foreground transition">
            <i class="fa-solid fa-arrow-left mr-2"></i>{{ __('agents.s.kembali_ke_ai_agents') }}</a>
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
                        <h2 class="text-xl font-bold">{{ __('agents.s.buat_ai_agent_baru') }}</h2>
                        <p class="text-sm text-muted-foreground">{{ __('agents.s.konfigurasi_otak_chatbot_anda') }}</p>
                    </div>
                </div>

                <form action="{{ route('agents.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Basic Info --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">{{ __('agents.s.informasi_dasar') }}</h3>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('agents.s.nama_agent') }}</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('agents.s.customer_service_bot') }}" required>
                            @error('name')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('agents.s.deskripsi_opsional') }}</label>
                            <textarea name="description" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('agents.s.agent_untuk_menjawab_pertanyaan_customer_tentang') }}">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    {{-- AI Configuration --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">{{ __('agents.s.konfigurasi_ai') }}</h3>

                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('agents.s.personality') }}</label>
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
                            <label class="block text-sm font-medium mb-2">{{ __('agents.s.kreativitas_ai') }}</label>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-muted-foreground">{{ __('agents.s.fokus') }}</span>
                                <input type="range" name="ai_temperature" min="0" max="1.5" step="0.1" 
                                    value="{{ old('ai_temperature', 0.7) }}"
                                    class="flex-1 h-2 bg-muted rounded-lg appearance-none cursor-pointer">
                                <span class="text-xs text-muted-foreground">{{ __('agents.s.kreatif') }}</span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">{{ __('agents.s.nilai_rendah_jawaban_lebih_konsisten_nilai_tingg') }}</p>
                        </div>
                    </div>

                    {{-- Fallback --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">{{ __('agents.s.fallback') }}</h3>

                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('agents.s.fallback_message_saat_error') }}</label>
                            <textarea name="fallback_message" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('agents.s.maaf_saya_sedang_mengalami_gangguan_teknis_silak') }}">{{ old('fallback_message') }}</textarea>
                            <p class="text-xs text-muted-foreground mt-1">{{ __('agents.s.greeting_widget_diatur_di_pengaturan_channel_wid') }}</p>
                        </div>
                    </div>

                    {{-- System Prompt --}}
                    <div class="space-y-4">
                        <h3 class="font-semibold text-lg border-b pb-2">{{ __('agents.s.system_prompt_opsional') }}</h3>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">{{ __('agents.s.custom_instructions') }}</label>
                            <textarea name="system_prompt" rows="4"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"
                                placeholder="{{ __('agents.s.kamu_adalah_asisten_customer_service_yang_ramah') }}">{{ old('system_prompt') }}</textarea>
                            <p class="text-xs text-muted-foreground mt-1">{{ __('agents.s.instruksi_khusus_untuk_ai_tentang_cara_menjawab') }}</p>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex gap-3 pt-4 border-t">
                        <a href="{{ route('agents.index') }}"
                            class="px-6 py-2 border rounded-lg hover:bg-muted transition">{{ __('agents.s.batal') }}</a>
                        <button type="submit"
                            class="flex-1 px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition font-medium">
                            <i class="fa-solid fa-check mr-2"></i>{{ __('agents.s.buat_ai_agent') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar (1 column) --}}
        <div class="space-y-6">

            {{-- Tips --}}
            <div class="bg-card border rounded-xl p-5">
                <h3 class="font-semibold mb-3">{{ __('agents.s.tips') }}</h3>
                <ul class="text-sm text-muted-foreground space-y-2">
                    <li>{{ __('agents.s.agent_adalah_otak_chatbot_yang_berisi_pengetahua') }}</li>
                    <li>{{ __('agents.s.satu_agent_bisa_digunakan_oleh_banyak_widget') }}</li>
                    <li>{{ __('agents.s.setelah_membuat_agent_tambahkan_faq_di_knowledge') }}</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
