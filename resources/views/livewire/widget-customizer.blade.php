<div>
    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Settings Panel --}}
        <div class="space-y-6">
            {{-- Basic Settings --}}
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold mb-4">Widget Settings</h3>

                <form wire:submit.prevent="saveSettings" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Widget Name *</label>
                        <input type="text" wire:model="name"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="My Widget">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Primary Color *</label>
                        <div class="flex gap-3 items-center">
                            <input type="color" wire:model.live="primaryColor"
                                class="h-12 w-20 rounded border cursor-pointer">
                            <input type="text" wire:model.live="primaryColor"
                                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="#0f172a">
                        </div>
                        @error('primaryColor') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Avatar Settings --}}
                    <div>
                        <label class="block text-sm font-medium mb-3">Avatar</label>
                        <div class="flex gap-4 mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="avatarType" value="icon"
                                    class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                                <span class="text-sm">Default Icon</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="avatarType" value="image"
                                    class="w-4 h-4 text-primary focus:ring-primary border-gray-300">
                                <span class="text-sm">Custom Image</span>
                            </label>
                        </div>

                        @if ($avatarType === 'icon')
                            <div class="grid grid-cols-5 gap-3">
                                @foreach (['robot', 'comments', 'headset', 'user', 'bell', 'comment-dots', 'message', 'circle-question'] as $icon)
                                    <button type="button" wire:click="$set('avatarIcon', '{{ $icon }}')"
                                        class="h-12 border rounded-lg flex items-center justify-center transition {{ $avatarIcon === $icon ? 'ring-2 ring-primary border-primary bg-primary/5' : 'hover:bg-gray-50' }}">
                                        <i class="fa-solid fa-{{ $icon }} text-xl text-gray-700"></i>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="space-y-3">
                                @if ($avatarUrl || $avatarUpload)
                                    <div class="flex items-center gap-4">
                                        @if ($avatarUpload)
                                            <img src="{{ $avatarUpload->temporaryUrl() }}"
                                                class="w-16 h-16 rounded-full object-cover border">
                                        @elseif($avatarUrl)
                                            <img src="{{ $avatarUrl }}" class="w-16 h-16 rounded-full object-cover border">
                                        @endif
                                        <div class="text-xs text-muted-foreground">
                                            <p class="font-medium text-foreground">Current Preview</p>
                                            <p>This image will be used as launcher & header avatar.</p>
                                        </div>
                                    </div>
                                @endif

                                <input type="file" wire:model="avatarUpload" accept="image/*" class="block w-full text-sm text-slate-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-xs file:font-semibold
                                        file:bg-primary/10 file:text-primary
                                        hover:file:bg-primary/20
                                      " />
                                <p class="text-xs text-muted-foreground">Max 1MB. Recommended 100x100px (JPG/PNG).</p>
                                @error('avatarUpload') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Greeting Message *</label>
                        <textarea wire:model.live="greeting" rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Halo! 👋 Ada yang bisa saya bantu?"></textarea>
                        @error('greeting') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Position *</label>
                        <select wire:model.live="position"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="bottom-right">Bottom Right</option>
                            <option value="bottom-left">Bottom Left</option>
                            <option value="top-right">Top Right</option>
                            <option value="top-left">Top Left</option>
                        </select>
                    </div>



                    <button type="submit"
                        class="w-full bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                        <i class="fa-solid fa-save mr-2"></i> Save Settings
                    </button>
                </form>
            </div>

            {{-- Test Widget Button --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-2">
                    <i class="fa-solid fa-vial mr-2"></i>Test Your Widget
                </h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Test your chatbot with real AI responses before publishing.
                </p>
                <button onclick="testWidget()"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-medium">
                    <i class="fa-solid fa-play mr-2"></i> Launch Widget Test
                </button>
            </div>


        </div>

        {{-- Preview Panel --}}
        <div class="bg-card rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-semibold mb-4">Live Preview</h3>

            <div class="border-2 rounded-lg h-[600px] relative overflow-hidden bg-slate-50">
                {{-- Mock Website --}}
                <div class="p-8">
                    <div class="h-8 w-48 bg-slate-200 rounded mb-4"></div>
                    <div class="space-y-2">
                        <div class="h-4 bg-slate-200 rounded w-full"></div>
                        <div class="h-4 bg-slate-200 rounded w-5/6"></div>
                        <div class="h-4 bg-slate-200 rounded w-4/6"></div>
                    </div>
                </div>

                {{-- Widget Preview --}}
                <div class="absolute {{ $position === 'bottom-right' ? 'bottom-4 right-4' : '' }}
                            {{ $position === 'bottom-left' ? 'bottom-4 left-4' : '' }}
                            {{ $position === 'top-right' ? 'top-4 right-4' : '' }}
                            {{ $position === 'top-left' ? 'top-4 left-4' : '' }}">

                    {{-- Widget Button --}}
                    <button class="w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white"
                        style="background: {{ $primaryColor }}">
                        <i class="fa-solid fa-comment text-xl"></i>
                    </button>

                    {{-- Widget Window (Mini Preview) --}}
                    <div class="absolute bottom-20 {{ str_contains($position, 'right') ? 'right-0' : 'left-0' }} 
                                w-80 bg-white rounded-2xl shadow-2xl border overflow-hidden">
                        {{-- Header --}}
                        <div class="p-4 text-white flex items-center gap-3" style="background: {{ $primaryColor }}">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-robot"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold">{{ $name }}</p>
                                <p class="text-xs opacity-90">Online</p>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div class="p-4 space-y-3 bg-slate-50 h-64">
                            <div class="bg-white p-3 rounded-lg rounded-tl-none shadow-sm text-sm">
                                {{ $greeting }}
                            </div>
                        </div>

                        {{-- Input --}}
                        <div class="p-3 bg-white border-t">
                            <div class="flex gap-2">
                                <input type="text" placeholder="Type a message..."
                                    class="flex-1 px-3 py-2 border rounded-full text-sm" disabled>
                                <button class="w-10 h-10 rounded-full flex items-center justify-center text-white"
                                    style="background: {{ $primaryColor }}">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Powered By --}}
                        <div class="text-center py-2 text-xs text-gray-400">
                            Powered by <a href="https://cekat.biz.id" target="_blank"
                                class="text-primary hover:underline">cekat.biz.id</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        let widgetLoaded = false;

        function testWidget() {
            if (!widgetLoaded) {
                // Load widget config
                window.CSAIConfig = {
                    widgetId: '{{ $widget->slug }}',
                    apiUrl: '{{ config("app.url") }}/api/chat',
                    position: '{{ $position }}',
                    primaryColor: '{{ $primaryColor }}',
                    title: '{{ addslashes($name) }}',
                    subtitle: 'Online • Reply cepat',
                    greeting: `{!! addslashes($greeting) !!}`,
                    avatar_type: '{{ $avatarType }}',
                    avatar_icon: '{{ $avatarIcon }}',
                    avatar_url: '{{ $avatarUrl }}',
                    showBranding: true
                };

                // Load widget script
                const script = document.createElement('script');
                script.src = '{{ asset("widget/widget.js") }}?v={{ time() }}';
                document.body.appendChild(script);

                widgetLoaded = true;

                // Wait for widget to load then open it
                setTimeout(() => {
                    if (window.CSAI && window.CSAI.open) {
                        window.CSAI.open();
                    }
                }, 1000);
            } else {
                // Widget already loaded, just open it
                if (window.CSAI && window.CSAI.open) {
                    window.CSAI.open();
                }
            }
        }
    </script>
@endpush