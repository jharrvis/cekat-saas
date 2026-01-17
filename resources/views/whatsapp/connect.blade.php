@extends('layouts.dashboard')

@section('content')
    <div class="max-w-2xl mx-auto">
        {{-- Back Button --}}
        <a href="{{ route('whatsapp.index') }}"
            class="text-muted-foreground hover:text-foreground mb-4 inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            Back to WhatsApp
        </a>

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-brands fa-whatsapp text-4xl text-green-500"></i>
            </div>
            <h1 class="text-2xl font-bold">Connect WhatsApp</h1>
            <p class="text-muted-foreground">{{ $device->device_name }}</p>
        </div>

        {{-- QR Code Card --}}
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <div class="p-6">
                {{-- Status Indicator --}}
                <div class="text-center mb-6">
                    @if($device->status === 'connected')
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <i class="fa-solid fa-check-circle text-green-500 text-4xl mb-2"></i>
                            <h3 class="text-green-700 font-semibold">WhatsApp Connected!</h3>
                            <p class="text-green-600 text-sm">{{ $device->phone_number ? '+' . $device->phone_number : '' }}</p>
                        </div>
                    @else
                        {{-- QR Code Container --}}
                        <div id="qrContainer" class="relative">
                            <div id="qrLoading" class="flex flex-col items-center justify-center py-12">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mb-4"></div>
                                <p class="text-muted-foreground">Loading QR Code...</p>
                            </div>

                            <div id="qrCode" class="hidden">
                                <img id="qrImage" src="" alt="QR Code"
                                    class="w-64 h-64 mx-auto border-4 border-white shadow-lg rounded-xl">
                            </div>

                            <div id="qrError" class="hidden bg-red-50 border border-red-200 rounded-xl p-4">
                                <i class="fa-solid fa-times-circle text-red-500 text-2xl mb-2"></i>
                                <p class="text-red-700" id="qrErrorMessage"></p>
                                <button onclick="loadQR()" class="mt-3 text-sm text-red-600 underline">Try Again</button>
                            </div>
                        </div>

                        {{-- Instructions --}}
                        <div class="mt-6 text-left bg-muted/30 rounded-xl p-4">
                            <h4 class="font-medium mb-3">How to connect:</h4>
                            <ol class="text-sm text-muted-foreground space-y-2">
                                <li class="flex items-start gap-2">
                                    <span
                                        class="bg-primary text-white w-5 h-5 rounded-full flex items-center justify-center text-xs flex-shrink-0">1</span>
                                    <span>Open WhatsApp on your phone</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="bg-primary text-white w-5 h-5 rounded-full flex items-center justify-center text-xs flex-shrink-0">2</span>
                                    <span>Tap <strong>Menu</strong> (⋮) or <strong>Settings</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="bg-primary text-white w-5 h-5 rounded-full flex items-center justify-center text-xs flex-shrink-0">3</span>
                                    <span>Tap <strong>Linked Devices</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="bg-primary text-white w-5 h-5 rounded-full flex items-center justify-center text-xs flex-shrink-0">4</span>
                                    <span>Tap <strong>Link a Device</strong></span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span
                                        class="bg-primary text-white w-5 h-5 rounded-full flex items-center justify-center text-xs flex-shrink-0">5</span>
                                    <span>Point your phone camera at the QR code above</span>
                                </li>
                            </ol>
                        </div>

                        {{-- Webhook Info --}}
                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4 text-left">
                            <h4 class="font-medium text-blue-800 mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-link"></i>
                                Webhook URL
                            </h4>
                            <p class="text-xs text-blue-700 mb-2">
                                Set this URL in Fonnte dashboard to receive messages:
                            </p>
                            <code class="text-xs bg-blue-100 px-2 py-1 rounded block overflow-x-auto">
                                        {{ url('/api/whatsapp/webhook/' . $device->id) }}
                                    </code>
                            <p class="text-xs text-blue-600 mt-2">
                                <i class="fa-solid fa-info-circle"></i>
                                Note: For local testing, use <a href="https://ngrok.com" target="_blank"
                                    class="underline">ngrok</a> to expose your local server.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="p-4 border-t bg-muted/30 flex justify-between items-center">
                <div id="statusText" class="text-sm text-muted-foreground">
                    @if($device->status === 'connected')
                        <span class="text-green-600 flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            Connected
                        </span>
                    @else
                        <span id="statusWaiting" class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                            Waiting for scan...
                        </span>
                    @endif
                </div>

                <div class="flex gap-2">
                    @if($device->status !== 'connected')
                        <button onclick="loadQR()" id="refreshQR"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition text-sm">
                            <i class="fa-solid fa-refresh mr-1"></i> Refresh QR
                        </button>
                    @endif
                    <a href="{{ route('whatsapp.index') }}"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm">
                        Done
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const deviceId = {{ $device->id }};
            let checkInterval;

            function loadQR() {
                document.getElementById('qrLoading').classList.remove('hidden');
                document.getElementById('qrCode').classList.add('hidden');
                document.getElementById('qrError').classList.add('hidden');

                fetch(`/whatsapp/${deviceId}/qr`)
                    .then(res => res.json())
                    .then(data => {
                        document.getElementById('qrLoading').classList.add('hidden');

                        if (data.success) {
                            // Check if device is actually connected (set by our backend, not Fonnte's status:true)
                            // data.device_status is from our database
                            // data.data.status === 'connected' is explicitly set when device is connected
                            if (data.device_status === 'connected' ||
                                (data.data && data.data.status === 'connected' && typeof data.data.status === 'string')) {
                                // Already connected, reload page
                                location.reload();
                            } else if (data.data && data.data.url) {
                                // Fonnte returns QR as base64 PNG string in 'url' field
                                // Check if it's already a data URI or just base64
                                let qrImageSrc = data.data.url;
                                if (!qrImageSrc.startsWith('data:') && !qrImageSrc.startsWith('http')) {
                                    // It's a raw base64 string, add the data URI prefix
                                    qrImageSrc = 'data:image/png;base64,' + qrImageSrc;
                                }
                                document.getElementById('qrImage').src = qrImageSrc;
                                document.getElementById('qrCode').classList.remove('hidden');
                                startStatusCheck();
                            } else {
                                // No QR code available, might be connected or error
                                document.getElementById('qrErrorMessage').textContent = 'QR code not available. Device may already be connected.';
                                document.getElementById('qrError').classList.remove('hidden');
                            }
                        } else {
                            document.getElementById('qrErrorMessage').textContent = data.error || 'Failed to load QR code';
                            document.getElementById('qrError').classList.remove('hidden');
                        }
                    })
                    .catch(err => {
                        document.getElementById('qrLoading').classList.add('hidden');
                        document.getElementById('qrErrorMessage').textContent = 'Network error. Please try again.';
                        document.getElementById('qrError').classList.remove('hidden');
                    });
            }

            function startStatusCheck() {
                // Clear any existing interval
                if (checkInterval) clearInterval(checkInterval);

                // Check status every 3 seconds
                checkInterval = setInterval(() => {
                    fetch(`/whatsapp/${deviceId}/status`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.data.connected) {
                                clearInterval(checkInterval);
                                location.reload();
                            }
                        });
                }, 3000);
            }

            // Load QR on page load
            @if($device->status !== 'connected')
                document.addEventListener('DOMContentLoaded', loadQR);
            @endif
        </script>
    @endpush
@endsection