<div>
    {{-- Success/Error Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold flex items-center gap-3">
                <i class="fa-brands fa-whatsapp text-green-500"></i>{{ __('admin.s.whatsapp_integration') }}</h2>
            <p class="text-muted-foreground">{{ __('admin.s.manage_whatsapp_module_settings_and_monitor_devi') }}</p>
        </div>

        {{-- Module Toggle --}}
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium {{ $moduleEnabled ? 'text-green-600' : 'text-muted-foreground' }}">
                {{ $moduleEnabled ? 'Enabled' : 'Disabled' }}
            </span>
            <button wire:click="toggleModule" class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary
                {{ $moduleEnabled ? 'bg-green-500' : 'bg-gray-300' }}">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition-transform duration-200
                    {{ $moduleEnabled ? 'translate-x-8' : 'translate-x-1' }}"></span>
            </button>
        </div>
    </div>

    {{-- Warning Banner --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-amber-800">{{ __('admin.s.unofficial_api_warning') }}</h4>
                <p class="text-sm text-amber-700">{{ __('admin.s.fonnte_uses_an') }}<strong>{{ __('admin.s.unofficial_whatsapp_api') }}</strong>{{ __('admin.s.there_is_a_risk_of_accounts_being_banned_by_what') }}</p>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">{{ __('admin.s.total_devices') }}</p>
                    <p class="text-2xl font-bold">{{ $totalDevices }}</p>
                </div>
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-mobile-screen text-primary text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">{{ __('admin.s.connected') }}</p>
                    <p class="text-2xl font-bold text-green-600">{{ $connectedDevices }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-link text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">{{ __('admin.s.messages_sent') }}</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($totalMessagesSent) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-paper-plane text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">{{ __('admin.s.messages_received') }}</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($totalMessagesReceived) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-inbox text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
        <div class="border-b">
            <nav class="flex">
                <button wire:click="setTab('settings')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'settings' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-cog mr-2"></i>{{ __('general.s.settings') }}</button>
                <button wire:click="setTab('monitor')"
                    class="px-6 py-4 font-medium transition {{ $activeTab === 'monitor' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                    <i class="fa-solid fa-chart-line mr-2"></i>{{ __('admin.s.device_monitor') }}</button>
            </nav>
        </div>

        <div class="p-6">
            {{-- Settings Tab --}}
            @if($activeTab === 'settings')
                <form wire:submit.prevent="saveSettings" class="space-y-6 max-w-2xl">
                    {{-- Fonnte Account Token --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.fonnte_account_token') }}<span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="password" wire:model="fonnteAccountToken"
                                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="{{ __('admin.s.enter_your_fonnte_account_token') }}">
                            <button type="button" wire:click="testConnection"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                                <i class="fa-solid fa-plug mr-1"></i>{{ __('admin.s.test') }}</button>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.get_your_account_token_from') }}<a href="https://md.fonnte.com" target="_blank" class="text-blue-600 hover:underline">{{ __('admin.s.md_fonnte_com_settings') }}</a>
                        </p>
                        @if($testResult)
                            @php
                                $isSuccess = str_starts_with($testResult, 'success:');
                                $message = substr($testResult, strpos($testResult, ':') + 1);
                            @endphp
                            <div
                                class="mt-2 p-3 rounded-lg {{ $isSuccess ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                <i class="fa-solid {{ $isSuccess ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $message }}
                            </div>
                        @endif
                    </div>

                    {{-- Lead Notification Device Token --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.lead_notif_device_token') }}
                        </label>
                        <input type="password" wire:model="leadNotifDeviceToken"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('admin.s.enter_device_token') }}">
                        <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.lead_notif_device_token_help') }}</p>
                    </div>

                    {{-- Fallback Message --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('agents.s.fallback_message') }}</label>
                        <textarea wire:model="fallbackMessage" rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="{{ __('admin.s.message_to_send_when_ai_fails') }}"></textarea>
                        <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.this_message_is_sent_when_the_ai_fails_to_genera') }}</p>
                    </div>

                    {{-- Auto Reply --}}
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="autoReplyEnabled" id="autoReply"
                            class="rounded border-gray-300 text-primary focus:ring-primary">
                        <label for="autoReply" class="text-sm font-medium">{{ __('admin.s.enable_auto_reply_with_ai') }}</label>
                    </div>

                    {{-- Max Devices Per User --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ __('admin.s.max_devices_per_user_free_plan') }}</label>
                        <input type="number" wire:model="maxDevicesPerUser" min="1" max="10"
                            class="w-32 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.higher_plans_can_have_different_limits_configure') }}</p>
                    </div>

                    {{-- Save Button --}}
                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                            class="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:bg-primary/90 transition">
                            <i class="fa-solid fa-save mr-2"></i>{{ __('admin.s.save_settings') }}</button>
                    </div>
                </form>
            @endif

            {{-- Monitor Tab --}}
            @if($activeTab === 'monitor')
                <div>
                    {{-- Actions --}}
                    <div class="flex gap-3 mb-4">
                        <button wire:click="syncDevices"
                            class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition">
                            <i class="fa-solid fa-sync mr-1"></i>{{ __('admin.s.sync_from_fonnte') }}</button>
                        <button wire:click="disconnectAllDevices"
                            onclick="return confirm('Are you sure you want to disconnect all devices?')"
                            class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg transition">
                            <i class="fa-solid fa-plug-circle-xmark mr-1"></i>{{ __('admin.s.disconnect_all') }}</button>
                        <button wire:click="loadDevices"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                            <i class="fa-solid fa-refresh mr-1"></i>{{ __('admin.s.refresh') }}</button>
                    </div>

                    {{-- Devices Table --}}
                    @if(count($devices) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.device') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.user') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('agents.s.widget') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.phone') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('channels.s.status') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.messages') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('emails.s.plan') }}</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('channels.s.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($devices as $device)
                                        <tr class="hover:bg-muted/30">
                                            <td class="px-4 py-3">
                                                <div class="font-medium">{{ $device->device_name }}</div>
                                                <div class="text-xs text-muted-foreground">ID: {{ $device->id }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm">{{ $device->is_platform ? __('admin.s.platform_device') : ($device->user->name ?? 'Unknown') }}</div>
                                                <div class="text-xs text-muted-foreground">{{ $device->is_platform ? '' : ($device->user->email ?? '') }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-sm">{{ $device->widget->name ?? '-' }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-mono text-sm">{{ $device->formatted_phone }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $statusColors = [
                                                        'connected' => 'bg-green-100 text-green-700',
                                                        'connecting' => 'bg-blue-100 text-blue-700',
                                                        'disconnected' => 'bg-red-100 text-red-700',
                                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                                        'expired' => 'bg-gray-100 text-gray-700',
                                                    ];
                                                    $color = $statusColors[$device->status] ?? 'bg-gray-100 text-gray-700';
                                                @endphp
                                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">
                                                    {{ ucfirst($device->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm">
                                                    <span class="text-green-600">↓{{ $device->messages_received }}</span>
                                                    <span class="text-muted-foreground mx-1">/</span>
                                                    <span class="text-blue-600">↑{{ $device->messages_sent }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-sm capitalize">{{ str_replace('_', ' ', $device->plan) }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex gap-1">
                                                    @if($device->is_platform && $device->status !== 'connected')
                                                        <button wire:click="connectDevice({{ $device->id }})"
                                                            title="{{ __('admin.s.connect_platform_device') }}"
                                                            class="px-2 py-1 bg-green-100 text-green-600 hover:bg-green-200 rounded text-xs">
                                                            <i class="fa-solid fa-qrcode"></i>
                                                        </button>
                                                    @endif
                                                    @if($device->status === 'connected')
                                                        <button wire:click="disconnectDevice({{ $device->id }})"
                                                            onclick="return confirm('Disconnect this device?')"
                                                            class="px-2 py-1 bg-amber-100 text-amber-600 hover:bg-amber-200 rounded text-xs">
                                                            <i class="fa-solid fa-plug-circle-xmark"></i>
                                                        </button>
                                                    @endif
                                                    <button wire:click="deleteDevice({{ $device->id }})"
                                                        onclick="return confirm('DELETE this device permanently? This will remove all messages.')"
                                                        class="px-2 py-1 bg-red-100 text-red-600 hover:bg-red-200 rounded text-xs">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 text-muted-foreground">
                            <i class="fa-solid fa-mobile-screen text-4xl mb-4 opacity-50"></i>
                            <p>{{ __('admin.s.no_whatsapp_devices_found') }}</p>
                            <p class="text-sm mt-1">{{ __('admin.s.devices_will_appear_here_when_users_connect_thei') }}</p>
                        </div>
                    @endif

                    @if($connectDeviceId)
                        <div class="mt-4 bg-card border rounded-xl p-6" @if($connectState === 'waiting') wire:poll.5s="refreshConnectStatus" @endif>
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-semibold">{{ __('admin.s.connect_platform_device') }}</h4>
                                    <p class="text-sm text-muted-foreground">{{ $connectDeviceLabel }}</p>
                                </div>
                                <button wire:click="closeConnect" class="text-muted-foreground hover:text-foreground text-xl leading-none">&times;</button>
                            </div>

                            @if($connectState === 'waiting')
                                <div class="flex flex-col items-center py-4">
                                    @if($connectQrImage)
                                        <img src="data:image/png;base64,{{ $connectQrImage }}" alt="QR Code" class="w-56 h-56">
                                    @endif
                                    <p class="text-sm mt-3 text-center">{{ __('admin.s.scan_qr_to_connect') }}</p>
                                    <p class="text-xs text-muted-foreground mt-1">{{ __('admin.s.waiting_for_scan') }}</p>
                                </div>
                            @elseif($connectState === 'connected')
                                <div class="mt-4 bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm">
                                    <i class="fa-solid fa-circle-check mr-1"></i> {{ __('admin.s.device_connected_success') }}
                                </div>
                            @elseif($connectState === 'error')
                                <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
                                    {{ $connectError }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>