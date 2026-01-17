@extends('layouts.dashboard')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-3">
                    <i class="fa-brands fa-whatsapp text-green-500"></i>
                    WhatsApp Integration
                </h1>
                <p class="text-muted-foreground">Connect your WhatsApp to enable AI-powered customer service</p>
            </div>

            @if(count($devices) < 10)
                <button onclick="document.getElementById('createDeviceModal').showModal()"
                    class="bg-green-500 text-white px-5 py-2 rounded-lg hover:bg-green-600 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    Connect WhatsApp
                </button>
            @endif
        </div>

        {{-- Warning Banner (Always Show) --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl mt-0.5"></i>
                <div>
                    <h4 class="font-semibold text-amber-800">Important Notice</h4>
                    <p class="text-sm text-amber-700">
                        WhatsApp integration uses an <strong>unofficial API</strong>. There is a risk that your WhatsApp
                        account
                        may be banned by WhatsApp. We recommend using a <strong>dedicated phone number</strong> for this
                        integration,
                        not your personal WhatsApp.
                    </p>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        {{-- Devices List --}}
        @if(count($devices) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($devices as $device)
                    <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                        {{-- Header --}}
                        <div
                            class="p-4 border-b bg-gradient-to-r {{ $device->status === 'connected' ? 'from-green-50 to-green-100' : 'from-gray-50 to-gray-100' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full {{ $device->status === 'connected' ? 'bg-green-500' : 'bg-gray-400' }} flex items-center justify-center text-white">
                                        <i class="fa-brands fa-whatsapp text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">{{ $device->device_name }}</h3>
                                        <p class="text-xs text-muted-foreground">
                                            {{ $device->phone_number ? '+' . $device->phone_number : 'Not connected' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Status Badge --}}
                                @php
                                    $statusColors = [
                                        'connected' => 'bg-green-100 text-green-700',
                                        'connecting' => 'bg-blue-100 text-blue-700',
                                        'disconnected' => 'bg-red-100 text-red-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                    ];
                                    $color = $statusColors[$device->status] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">
                                    {{ ucfirst($device->status) }}
                                </span>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="p-4 space-y-3">
                            {{-- Linked Widget --}}
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Linked Widget:</span>
                                <span class="font-medium">{{ $device->widget->name ?? 'None' }}</span>
                            </div>

                            {{-- Stats --}}
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Messages:</span>
                                <span>
                                    <span class="text-green-600">↓{{ $device->messages_received }}</span>
                                    <span class="text-muted-foreground mx-1">/</span>
                                    <span class="text-blue-600">↑{{ $device->messages_sent }}</span>
                                </span>
                            </div>

                            {{-- Plan --}}
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Plan:</span>
                                <span class="capitalize">{{ str_replace('_', ' ', $device->plan) }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="p-4 border-t bg-muted/30">
                            <div class="flex gap-2 mb-2">
                                @if($device->status === 'pending' || $device->status === 'disconnected')
                                    <a href="{{ route('whatsapp.connect', $device->id) }}"
                                        class="flex-1 text-center bg-green-500 text-white px-3 py-2 rounded-lg hover:bg-green-600 transition text-sm">
                                        <i class="fa-solid fa-qrcode mr-1"></i> Connect
                                    </a>
                                @else
                                    <a href="{{ route('whatsapp.messages', $device->id) }}"
                                        class="flex-1 text-center bg-primary text-white px-3 py-2 rounded-lg hover:bg-primary/90 transition text-sm">
                                        <i class="fa-solid fa-message mr-1"></i> Messages
                                    </a>
                                    <form action="{{ route('whatsapp.disconnect', $device->id) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Disconnect this device?')"
                                            class="w-full bg-amber-100 text-amber-600 px-3 py-2 rounded-lg hover:bg-amber-200 transition text-sm">
                                            <i class="fa-solid fa-plug-circle-xmark mr-1"></i> Disconnect
                                        </button>
                                    </form>
                                @endif
                            </div>
                            {{-- Delete Button (always visible) --}}
                            <form action="{{ route('whatsapp.destroy', $device->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Are you sure you want to DELETE this device permanently? This will remove all message history.')"
                                    class="w-full text-center bg-red-50 text-red-600 px-3 py-2 rounded-lg hover:bg-red-100 transition text-sm border border-red-200">
                                    <i class="fa-solid fa-trash mr-1"></i> Delete Device
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-card rounded-xl border p-12 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-brands fa-whatsapp text-4xl text-green-500"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">No WhatsApp Connected</h3>
                <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                    Connect your WhatsApp to start receiving and replying to customer messages with AI-powered responses.
                </p>
                <button onclick="document.getElementById('createDeviceModal').showModal()"
                    class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    Connect WhatsApp
                </button>
            </div>
        @endif
    </div>

    {{-- Create Device Modal --}}
    <dialog id="createDeviceModal" class="rounded-xl shadow-xl p-0 backdrop:bg-black/50 w-full max-w-md">
        <form action="{{ route('whatsapp.create') }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Connect WhatsApp</h3>
                    <button type="button" onclick="document.getElementById('createDeviceModal').close()"
                        class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Device Name</label>
                        <input type="text" name="device_name" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="e.g., Support WhatsApp">
                        <p class="text-xs text-muted-foreground mt-1">A name to identify this WhatsApp connection</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">WhatsApp Number <span
                                class="text-red-500">*</span></label>
                        <div class="flex">
                            <span
                                class="inline-flex items-center px-3 bg-muted border border-r-0 rounded-l-lg text-muted-foreground">
                                +62
                            </span>
                            <input type="text" name="phone_number" required
                                class="flex-1 px-4 py-2 border rounded-r-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="8123456789" pattern="[0-9]{8,13}"
                                title="Enter 8-13 digit phone number without leading 0">
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Enter your WhatsApp number without +62 or leading 0. Example: 8123456789
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Link to Widget (Optional)</label>
                        <select name="widget_id"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">-- Select Widget --</option>
                            @foreach($widgets as $widget)
                                <option value="{{ $widget->id }}">{{ $widget->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1">AI will use knowledge base from the selected widget
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t bg-muted/30 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('createDeviceModal').close()"
                    class="px-4 py-2 text-muted-foreground hover:text-foreground transition">
                    Cancel
                </button>
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition">
                    <i class="fa-solid fa-plus mr-1"></i> Create
                </button>
            </div>
        </form>
    </dialog>
@endsection