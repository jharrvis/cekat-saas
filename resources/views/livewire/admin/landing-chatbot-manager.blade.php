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
            <h2 class="text-2xl font-bold">🏠 Landing Page Chatbot</h2>
            <p class="text-muted-foreground">Manage the default chatbot for Cekat.biz.id website</p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
            <i class="fa-solid fa-infinity mr-1"></i> Unlimited Quota
        </span>
    </div>

    {{-- Info Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <p class="text-blue-800 text-sm">
            <i class="fa-solid fa-info-circle mr-2"></i>
            <strong>Widget ini spesial:</strong> Admin dapat memilih LLM model langsung (tanpa batasan tier).
            Tidak ada batasan quota untuk widget landing page.
        </p>
    </div>

    {{-- Tabs --}}
    <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b flex overflow-x-auto">
            <button @click="activeTab = 'knowledge'"
                :class="activeTab === 'knowledge' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-brain mr-2"></i> Knowledge Base
            </button>
            <button @click="activeTab = 'model'"
                :class="activeTab === 'model' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-robot mr-2"></i> AI Model
            </button>
            <button @click="activeTab = 'widget'"
                :class="activeTab === 'widget' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-paintbrush mr-2"></i> Widget Settings
            </button>
            <button @click="activeTab = 'lead'"
                :class="activeTab === 'lead' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-user-plus mr-2"></i> Lead Collection
            </button>
            <button @click="activeTab = 'analytics'"
                :class="activeTab === 'analytics' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
                class="px-6 py-4 font-medium text-sm border-b-2 transition whitespace-nowrap">
                <i class="fa-solid fa-chart-line mr-2"></i> Analytics
            </button>

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
                <h3 class="text-lg font-bold mb-4">Widget Settings</h3>

                <form wire:submit.prevent="saveSettings" class="space-y-4 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium mb-2">Widget Name</label>
                        <input type="text" wire:model="widgetName"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Greeting Message</label>
                        <textarea wire:model="greeting" rows="2"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Primary Color</label>
                        <div class="flex gap-2">
                            <input type="color" wire:model="primaryColor"
                                class="w-12 h-10 border rounded cursor-pointer">
                            <input type="text" wire:model="primaryColor"
                                class="flex-1 px-4 py-2 border rounded-lg font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Position</label>
                        <select wire:model="position"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="bottom-right">Bottom Right</option>
                            <option value="bottom-left">Bottom Left</option>
                        </select>
                    </div>

                    <button type="submit"
                        class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                        <i class="fa-solid fa-save mr-2"></i> Save Settings
                    </button>
                </form>
            </div>

            {{-- Lead Collection Tab --}}
            <div x-show="activeTab === 'lead'" x-cloak>
                @include('chatbots.tabs.lead', ['chatbot' => $widget])
            </div>

            {{-- Analytics Tab --}}
            <div x-show="activeTab === 'analytics'" x-cloak>
                @include('chatbots.tabs.analytics', ['chatbot' => $widget])
            </div>


        </div>
    </div>
</div>