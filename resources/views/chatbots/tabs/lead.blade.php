{{-- Lead Collection Tab --}}
<div>
    <div class="mb-6">
        <h3 class="text-lg font-bold mb-2">Lead Collection Settings</h3>
        <p class="text-muted-foreground text-sm">Konfigurasi cara chatbot mengumpulkan data lead (Nama, Email, No HP) dari pengunjung.</p>
    </div>

    @php
        // Check if this is admin context (landing page widget OR current user is admin)
        $isAdminContext = $chatbot->slug === 'landing-page-default' || 
                          (auth()->check() && auth()->user()->role === 'admin');
        $formAction = $isAdminContext 
            ? route('admin.landing-chatbot.update-lead') 
            : route('chatbots.update', $chatbot);
    @endphp

    @php
        // Admin context = unlock all features
        // Otherwise check user's plan for can_export_leads
        $isLocked = !$isAdminContext && (!optional(optional($chatbot->user)->plan)->can_export_leads);
    @endphp

    <x-feature-locked :locked="$isLocked" feature-name="Lead Collection" description="Upgrade to Creator or Business plan to collect leads automatically from your chatbot.">
        <form action="{{ $formAction }}" method="POST">
            @csrf
            @if(!$isAdminContext)
                @method('PUT')
            @endif
            <input type="hidden" name="tab" value="lead">

            {{-- Strategy 1: Prompt Engineering --}}
            <div class="bg-muted/30 rounded-xl p-6 mb-6 border">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="lead_prompt_enabled" value="1" 
                                    {{ ($chatbot->settings['lead_prompt_enabled'] ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <h4 class="font-semibold">Strategi 1: Prompt Engineering</h4>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Recommended</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">AI akan secara natural menanyakan nama, email, dan nomor HP di sela-sela percakapan.</p>
                        
                        <div class="grid md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_ask_name" value="1" 
                                    {{ ($chatbot->settings['lead_ask_name'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>Tanyakan Nama</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_ask_email" value="1" 
                                    {{ ($chatbot->settings['lead_ask_email'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>Tanyakan Email</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_ask_phone" value="1" 
                                    {{ ($chatbot->settings['lead_ask_phone'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>Tanyakan No HP/WA</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Strategy 2: Trigger System --}}
            <div class="bg-muted/30 rounded-xl p-6 mb-6 border">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="lead_trigger_enabled" value="1" 
                                    {{ ($chatbot->settings['lead_trigger_enabled'] ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <h4 class="font-semibold">Strategi 2: Trigger System</h4>
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Advanced</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">AI akan dipaksa bertanya setelah kondisi tertentu terpenuhi.</p>
                        
                        <div class="space-y-3">
                            <div class="flex items-center gap-4">
                                <label class="text-sm w-40">Tanyakan setelah pesan ke-</label>
                                <input type="number" name="lead_trigger_after_message" 
                                    value="{{ $chatbot->settings['lead_trigger_after_message'] ?? 3 }}"
                                    min="1" max="10"
                                    class="w-20 px-3 py-1.5 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <div>
                                <label class="text-sm block mb-2">Trigger Keywords (pisahkan dengan koma)</label>
                                <input type="text" name="lead_trigger_keywords" 
                                    value="{{ $chatbot->settings['lead_trigger_keywords'] ?? 'beli, order, daftar, harga, promo' }}"
                                    placeholder="beli, order, daftar, harga"
                                    class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Strategy 3: Pre-chat Form --}}
            <div class="bg-muted/30 rounded-xl p-6 mb-6 border">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="lead_form_enabled" value="1" 
                                    {{ ($chatbot->settings['lead_form_enabled'] ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <h4 class="font-semibold">Strategi 3: Pre-Chat Form</h4>
                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">Direct</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">Tampilkan popup form sebelum user bisa mulai chat. Konversi tinggi tapi bisa mengurangi engagement.</p>
                        
                        <div class="grid md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_form_require_name" value="1" 
                                    {{ ($chatbot->settings['lead_form_require_name'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>Wajib Nama</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_form_require_email" value="1" 
                                    {{ ($chatbot->settings['lead_form_require_email'] ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>Wajib Email</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_form_require_phone" value="1" 
                                    {{ ($chatbot->settings['lead_form_require_phone'] ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>Wajib No HP/WA</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-save mr-2"></i> Simpan Pengaturan Lead
            </button>
        </form>
    </x-feature-locked>
</div>
