<div x-data="{ activeTab: 'knowledge' }">
    {{-- Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ __('admin.s.landing_page_chatbot') }}</h2>
            <p class="text-muted-foreground">{{ __('admin.s.manage_the_default_chatbot_for_cekat_biz_id_webs') }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
            <i class="fa-solid fa-infinity mr-1"></i>{{ __('admin.s.unlimited_quota') }}</span>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <p class="text-blue-800 text-sm">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>{{ __('admin.s.widget_ini_spesial') }}</strong>{{ __('admin.s.admin_dapat_memilih_llm_model_langsung_tanpa_bat') }}</p>
    </div>

    {{-- Tabs --}}
    <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b flex overflow-x-auto">
            <button @click="activeTab = 'knowledge'"
                :class="activeTab === 'knowledge' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-brain mr-2"></i>{{ __('agents.s.knowledge_base') }}</button>
            <button @click="activeTab = 'model'"
                :class="activeTab === 'model' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-robot mr-2"></i>{{ __('admin.s.ai_model') }}</button>
            <button @click="activeTab = 'widget'"
                :class="activeTab === 'widget' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-paintbrush mr-2"></i>{{ __('docs.s.widget_settings') }}</button>
            <button @click="activeTab = 'lead'"
                :class="activeTab === 'lead' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-user-plus mr-2"></i>{{ __('emails.s.lead_collection') }}</button>
            <button @click="activeTab = 'analytics'"
                :class="activeTab === 'analytics' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-chart-line mr-2"></i>{{ __('admin.s.analytics') }}</button>

        </div>

        <div class="p-6">
            {{-- Knowledge Base Tab --}}
            <div x-show="activeTab === 'knowledge'" x-cloak>
                @livewire('knowledge-base-editor', ['widgetId' => $widget->id])
            </div>

            {{-- AI Model Tab --}}
            <div x-show="activeTab === 'model'" x-cloak>
                @livewire('admin.landing-chatbot-model-selector', ['widget' => $widget])
            </div>

            {{-- Widget Settings Tab --}}
            <div x-show="activeTab === 'widget'" x-cloak>
                <h3 class="text-lg font-bold mb-4">{{ __('docs.s.widget_settings') }}</h3>

                <form wire:submit.prevent="saveSettings" class="space-y-4 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.widget_name') }}</label>
                        <input type="text" wire:model="widgetName"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.this_appears_in_the_chat_header') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.subtitle') }}</label>
                        <input type="text" wire:model="subtitle"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('admin.s.online_reply_cepat') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.greeting_message') }}</label>
                        <textarea wire:model="greeting" rows="2"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.placeholder_text') }}</label>
                        <input type="text" wire:model="placeholder"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('admin.s.ketik_pesan') }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.primary_color') }}</label>
                        <div class="flex gap-2">
                            <input type="color" wire:model="primaryColor"
                                class="w-12 h-10 border rounded cursor-pointer">
                            <input type="text" wire:model="primaryColor"
                                class="flex-1 px-4 py-2 border rounded-lg font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.position') }}</label>
                        <select wire:model="position"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="bottom-right">{{ __('admin.s.bottom_right') }}</option>
                            <option value="bottom-left">{{ __('admin.s.bottom_left') }}</option>
                        </select>
                    </div>

                    {{-- Avatar Settings --}}
                    <div class="border-t pt-4 mt-4">
                        <label class="block text-sm font-medium mb-3">{{ __('general.s.avatar') }}</label>

                        {{-- Current Avatar Preview --}}
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center text-white"
                                style="background: linear-gradient(135deg, {{ $primaryColor }}, {{ $primaryColor }}dd)">
                                @if($avatarType === 'url' && $avatarUrl)
                                    <img src="{{ $avatarUrl }}" class="w-16 h-16 rounded-full object-cover">
                                @else
                                    @if($avatarIcon === 'robot')
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2M7.5 13A1.5 1.5 0 006 14.5 1.5 1.5 0 007.5 16 1.5 1.5 0 009 14.5 1.5 1.5 0 007.5 13m9 0a1.5 1.5 0 00-1.5 1.5 1.5 1.5 0 001.5 1.5 1.5 1.5 0 001.5-1.5 1.5 1.5 0 00-1.5-1.5M12 9a5 5 0 00-5 5v1h10v-1a5 5 0 00-5-5z" />
                                        </svg>
                                    @elseif($avatarIcon === 'support')
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h3c1.66 0 3-1.34 3-3v-7c0-4.97-4.03-9-9-9z" />
                                        </svg>
                                    @else
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" />
                                        </svg>
                                    @endif
                                @endif
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Current: {{ $avatarType === 'url' ? 'Custom Image' : ucfirst($avatarIcon) . ' Icon' }}
                            </div>
                        </div>

                        {{-- Icon Selector --}}
                        <div class="mb-4">
                            <p class="text-sm text-muted-foreground mb-2">{{ __('admin.s.choose_icon') }}</p>
                            <div class="flex gap-2">
                                @foreach(['robot', 'support', 'user'] as $icon)
                                    <button type="button" wire:click="selectAvatarIcon('{{ $icon }}')"
                                        class="w-12 h-12 rounded-full flex items-center justify-center border-2 transition {{ $avatarIcon === $icon && $avatarType === 'icon' ? 'border-primary bg-primary/10' : 'border-gray-200 hover:border-gray-300' }}">
                                        @if($icon === 'robot')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2M7.5 13A1.5 1.5 0 006 14.5 1.5 1.5 0 007.5 16 1.5 1.5 0 009 14.5 1.5 1.5 0 007.5 13m9 0a1.5 1.5 0 00-1.5 1.5 1.5 1.5 0 001.5 1.5 1.5 1.5 0 001.5-1.5 1.5 1.5 0 00-1.5-1.5M12 9a5 5 0 00-5 5v1h10v-1a5 5 0 00-5-5z" />
                                            </svg>
                                        @elseif($icon === 'support')
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h3c1.66 0 3-1.34 3-3v-7c0-4.97-4.03-9-9-9z" />
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" />
                                            </svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Upload Custom Avatar --}}
                        <div>
                            <p class="text-sm text-muted-foreground mb-2">{{ __('admin.s.or_upload_custom_image') }}</p>
                            <div class="flex items-center gap-2">
                                <input type="file" wire:model="avatarUpload" accept="image/*"
                                    class="text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                @if($avatarUpload)
                                    <button type="button" wire:click="uploadAvatar"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                                        <i class="fa-solid fa-upload mr-1"></i>{{ __('admin.s.upload') }}</button>
                                @endif
                            </div>
                            @error('avatarUpload') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <div wire:loading wire:target="avatarUpload" class="text-sm text-muted-foreground mt-1">{{ __('admin.s.uploading') }}</div>
                        </div>
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_settings') }}</button>
                </form>
            </div>

            {{-- Lead Collection Tab --}}
            <div x-show="activeTab === 'lead'" x-cloak>
                @include('channels.tabs.lead', ['chatbot' => $widget])
            </div>

            {{-- Analytics Tab --}}
            <div x-show="activeTab === 'analytics'" x-cloak>
                @include('channels.tabs.analytics', ['chatbot' => $widget])
            </div>


        </div>
    </div>
</div>
