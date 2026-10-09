{{-- Lead Collection Tab --}}
<div>
    <div class="mb-6">
        <h3 class="text-lg font-bold mb-2">{{ __('channels.s.lead_collection_settings') }}</h3>
        <p class="text-muted-foreground text-sm">{{ __('channels.s.konfigurasi_cara_chatbot_mengumpulkan_data_lead') }}</p>
    </div>

    @php
        // Check if this is admin context (landing page widget OR current user is admin)
        $isAdminContext = $chatbot->slug === 'landing-page-default' || 
                          (auth()->check() && auth()->user()->role === 'admin');
        $formAction = $isAdminContext 
            ? route('admin.landing-chatbot.update-lead') 
            : route('channels.update', $chatbot);
    @endphp

    @php
        // Admin context = unlock all features
        // Otherwise check the widget owner's plan through PlanLimitService
        $isLocked = !$isAdminContext && !app(\App\Services\Billing\PlanLimitService::class)->feature($chatbot->user ?? auth()->user(), 'leads');
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
                            <h4 class="font-semibold">{{ __('channels.s.strategi_1_prompt_engineering') }}</h4>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ __('channels.s.recommended') }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">{{ __('channels.s.ai_akan_secara_natural_menanyakan_nama_email_dan') }}</p>
                        
                        <div class="grid md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_ask_name" value="1" 
                                    {{ ($chatbot->settings['lead_ask_name'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.tanyakan_nama') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_ask_email" value="1" 
                                    {{ ($chatbot->settings['lead_ask_email'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.tanyakan_email') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_ask_phone" value="1" 
                                    {{ ($chatbot->settings['lead_ask_phone'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.tanyakan_no_hp_wa') }}</span>
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
                            <h4 class="font-semibold">{{ __('channels.s.strategi_2_trigger_system') }}</h4>
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ __('channels.s.advanced') }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">{{ __('channels.s.ai_akan_dipaksa_bertanya_setelah_kondisi_tertent') }}</p>
                        
                        <div class="space-y-3">
                            <div class="flex items-center gap-4">
                                <label class="text-sm w-40">{{ __('channels.s.tanyakan_setelah_pesan_ke') }}</label>
                                <input type="number" name="lead_trigger_after_message" 
                                    value="{{ $chatbot->settings['lead_trigger_after_message'] ?? 3 }}"
                                    min="1" max="10"
                                    class="w-20 px-3 py-1.5 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <div>
                                <label class="text-sm block mb-2">{{ __('channels.s.trigger_keywords_pisahkan_dengan_koma') }}</label>
                                <input type="text" name="lead_trigger_keywords" 
                                    value="{{ $chatbot->settings['lead_trigger_keywords'] ?? 'beli, order, daftar, harga, promo' }}"
                                    placeholder="{{ __('channels.s.beli_order_daftar_harga') }}"
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
                            <h4 class="font-semibold">{{ __('channels.s.strategi_3_pre_chat_form') }}</h4>
                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">{{ __('channels.s.direct') }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">{{ __('channels.s.tampilkan_popup_form_sebelum_user_bisa_mulai_cha') }}</p>
                        
                        <div class="grid md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_form_require_name" value="1" 
                                    {{ ($chatbot->settings['lead_form_require_name'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.wajib_nama') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_form_require_email" value="1" 
                                    {{ ($chatbot->settings['lead_form_require_email'] ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.wajib_email') }}</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_form_require_phone" value="1" 
                                    {{ ($chatbot->settings['lead_form_require_phone'] ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.wajib_no_hp_wa') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Email notification per channel --}}
            <style>[x-cloak] { display: none !important; }</style>
            <div class="bg-muted/30 rounded-xl p-6 mb-6 border"
                 x-data="{ notifOn: {{ ($chatbot->settings['lead_email_notif_enabled'] ?? false) ? 'true' : 'false' }} }">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="lead_email_notif_enabled" value="1"
                                    x-model="notifOn"
                                    {{ ($chatbot->settings['lead_email_notif_enabled'] ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <h4 class="font-semibold">{{ __('channels.s.notifikasi_email_leads') }}</h4>
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ __('channels.s.per_channel') }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">
                            {{ __('channels.s.kirim_notifikasi_lead_ke_email_khusus_channel_in') }}
                        </p>

                        <div x-show="notifOn" class="space-y-4">
                            <div>
                                <label class="text-sm block mb-2">{{ __('channels.s.email_tujuan') }} <span class="text-red-500">*</span></label>
                                <input type="email" name="lead_email_notif"
                                    value="{{ $chatbot->settings['lead_email_notif'] ?? '' }}"
                                    placeholder="{{ __('channels.s.tim_example_com') }}"
                                    class="w-full max-w-md px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                @error('lead_email_notif')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_email_new_lead" value="1"
                                    {{ ($chatbot->settings['lead_email_new_lead'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.kirim_notifikasi_saat') }} <strong>{{ __('channels.s.lead_baru') }}</strong> {{ __('channels.s.terdeteksi') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- WhatsApp notification per channel --}}
            <div class="bg-muted/30 rounded-xl p-6 mb-6 border"
                 x-data="{ waNotifOn: {{ ($chatbot->settings['lead_wa_notif_enabled'] ?? false) ? 'true' : 'false' }} }">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="lead_wa_notif_enabled" value="1"
                                    x-model="waNotifOn"
                                    {{ ($chatbot->settings['lead_wa_notif_enabled'] ?? false) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                            <h4 class="font-semibold">{{ __('channels.s.notifikasi_whatsapp_leads') }}</h4>
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ __('channels.s.per_channel') }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">
                            {{ __('channels.s.kirim_notifikasi_lead_ke_whatsapp_khusus_channel') }}
                        </p>

                        <div x-show="waNotifOn" x-cloak class="space-y-4">
                            <div>
                                <label class="text-sm block mb-2">{{ __('channels.s.nomor_whatsapp_tujuan') }}</label>
                                <input type="tel" name="lead_wa_notif"
                                    value="{{ $chatbot->settings['lead_wa_notif'] ?? '' }}"
                                    placeholder="{{ __('channels.s.placeholder_nomor_whatsapp') }}"
                                    class="w-full max-w-md px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <p class="text-xs text-muted-foreground mt-1">{{ __('channels.s.kosongkan_untuk_memakai_nomor_akun') }}</p>
                                @error('lead_wa_notif')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="lead_wa_new_lead" value="1"
                                    {{ ($chatbot->settings['lead_wa_new_lead'] ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span>{{ __('channels.s.kirim_notifikasi_saat') }} <strong>{{ __('channels.s.lead_baru') }}</strong> {{ __('channels.s.terdeteksi') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-save mr-2"></i> {{ __('channels.s.simpan_pengaturan_lead') }}
            </button>
        </form>
    </x-feature-locked>
</div>

