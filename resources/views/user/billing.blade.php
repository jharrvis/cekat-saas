@extends('layouts.dashboard')

@section('title', 'Billing & Subscription')

@push('scripts')
    {{-- Midtrans Snap JS --}}
    @if(config('services.midtrans.is_production'))
        <script type="text/javascript" src="https://app.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @else
        <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif
@endpush

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div>
            <h1 class="text-2xl font-bold">Billing & Subscription</h1>
            <p class="text-muted-foreground">Kelola langganan dan pembayaran Anda</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Current Plan --}}
            <div class="md:col-span-2 space-y-6">
                {{-- Plan Card --}}
                <div class="bg-card rounded-xl border p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">Plan Saat Ini</h3>
                            <p class="text-muted-foreground text-sm">Status langganan Anda</p>
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-700">
                            <i class="fa-solid fa-check-circle mr-1"></i>Active
                        </span>
                    </div>

                    <div class="flex items-center gap-4 p-4 bg-primary/5 rounded-xl border border-primary/20 mb-4">
                        <div class="w-12 h-12 bg-primary/20 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-crown text-primary text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold">{{ $user->plan->name ?? 'Free Plan' }}</h4>
                            <p class="text-sm text-muted-foreground">
                                Rp {{ number_format($user->plan->price ?? 0, 0, ',', '.') }} / bulan
                            </p>
                        </div>
                        <a href="#plans" class="btn-primary">
                            <i class="fa-solid fa-arrow-up mr-2"></i>Upgrade
                        </a>
                    </div>

                    {{-- Plan Features --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-robot text-primary"></i>
                            <span>{{ $user->plan->max_widgets ?? 1 }} Chatbot Widget</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-message text-primary"></i>
                            <span>{{ number_format($user->plan->max_messages_per_month ?? 100, 0, ',', '.') }}
                                Pesan/bulan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                            <span>{{ $user->plan->chat_history_days ?? 7 }} Hari Chat History</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-brain text-primary"></i>
                            <span>AI Quality: {{ ucfirst($user->plan->ai_tier ?? 'Basic') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Usage --}}
                <div class="bg-card rounded-xl border p-6">
                    <h3 class="text-lg font-semibold mb-4">Penggunaan Bulan Ini</h3>

                    @php
                        $quota = $user->plan->max_messages_per_month ?? 100;
                        $used = $user->monthly_message_used ?? 0;
                        $percentage = $quota > 0 ? min(($used / $quota) * 100, 100) : 0;
                        $isWarning = $percentage >= 80;
                        $isDanger = $percentage >= 100;
                    @endphp

                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span>Pesan AI</span>
                            <span
                                class="{{ $isDanger ? 'text-red-500' : ($isWarning ? 'text-amber-500' : 'text-muted-foreground') }}">
                                {{ number_format($used, 0, ',', '.') }} / {{ number_format($quota, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-3">
                            <div class="h-3 rounded-full transition-all {{ $isDanger ? 'bg-red-500' : ($isWarning ? 'bg-amber-500' : 'bg-primary') }}"
                                style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>

                    @if($isDanger)
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                            Kuota pesan Anda telah habis! Upgrade plan untuk melanjutkan.
                        </div>
                    @elseif($isWarning)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                            <i class="fa-solid fa-exclamation-circle mr-2"></i>
                            Kuota hampir habis ({{ number_format($percentage, 0) }}%). Pertimbangkan upgrade.
                        </div>
                    @endif

                    <p class="text-xs text-muted-foreground mt-4">
                        <i class="fa-solid fa-info-circle mr-1"></i>
                        Kuota akan reset pada tanggal 1 bulan depan
                    </p>
                </div>

                {{-- Billing History --}}
                <div class="bg-card rounded-xl border overflow-hidden">
                    <div class="p-4 border-b">
                        <h3 class="text-lg font-semibold">Riwayat Pembayaran</h3>
                    </div>
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium">Tanggal</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Deskripsi</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Jumlah</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @php
                                $transactions = $user->transactions()->with('plan')->latest()->take(10)->get();
                            @endphp
                            @forelse($transactions as $tx)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $tx->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-sm">Plan {{ $tx->plan->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($tx->status === 'success')
                                            <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Sukses</span>
                                        @elseif($tx->status === 'pending')
                                            <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Pending</span>
                                        @elseif($tx->status === 'expired')
                                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">Expired</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Gagal</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($tx->status === 'pending' && $tx->snap_token)
                                            <button onclick="continuePay('{{ $tx->snap_token }}', '{{ $tx->order_id }}')"
                                                class="px-3 py-1 text-xs bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition">
                                                <i class="fa-solid fa-credit-card mr-1"></i>Lanjutkan
                                            </button>
                                        @elseif($tx->status === 'success')
                                            <span class="text-green-600"><i class="fa-solid fa-check"></i></span>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        <i class="fa-solid fa-file-invoice text-4xl mb-4 block"></i>
                                        <p>Belum ada riwayat pembayaran</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Quick Actions --}}
                <div class="bg-card rounded-xl border p-4">
                    <h3 class="font-semibold mb-3">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="#" class="block w-full text-left px-3 py-2 rounded-lg hover:bg-muted transition text-sm">
                            <i class="fa-solid fa-credit-card w-5 mr-2"></i>Kelola Metode Bayar
                        </a>
                        <a href="#" class="block w-full text-left px-3 py-2 rounded-lg hover:bg-muted transition text-sm">
                            <i class="fa-solid fa-receipt w-5 mr-2"></i>Download All Invoices
                        </a>
                        <a href="#"
                            class="block w-full text-left px-3 py-2 rounded-lg hover:bg-muted transition text-sm text-red-500">
                            <i class="fa-solid fa-times-circle w-5 mr-2"></i>Cancel Subscription
                        </a>
                    </div>
                </div>

                {{-- Next Billing --}}
                <div class="bg-card rounded-xl border p-4">
                    <h3 class="font-semibold mb-3">Pembayaran Berikutnya</h3>
                    @if($user->plan && $user->plan->price > 0)
                        <div class="text-center py-4">
                            <p class="text-2xl font-bold">Rp {{ number_format($user->plan->price, 0, ',', '.') }}</p>
                            <p class="text-sm text-muted-foreground mt-1">
                                Jatuh tempo: {{ $user->plan_expires_at?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground text-center py-4">
                            Anda menggunakan Free Plan
                        </p>
                    @endif
                </div>

                {{-- Support --}}
                <div class="bg-card rounded-xl border p-4">
                    <h3 class="font-semibold mb-3">Butuh Bantuan?</h3>
                    <p class="text-sm text-muted-foreground mb-3">
                        Ada pertanyaan tentang billing? Tim kami siap membantu.
                    </p>
                    <a href="mailto:support@cekat.biz.id" class="btn-secondary w-full text-center">
                        <i class="fa-solid fa-headset mr-2"></i>Hubungi Support
                    </a>
                </div>
            </div>
        </div>

        {{-- Available Plans --}}
        <div id="plans" class="bg-card rounded-xl border p-6">
            <h3 class="text-lg font-semibold mb-6 text-center">Upgrade Plan Anda</h3>

            <div class="grid md:grid-cols-{{ count($plans) }} gap-4">
                @foreach($plans as $plan)
                    @php
                        $isCurrent = $user->plan_id == $plan->id;
                        $isPopular = $plan->slug === 'pro';
                    @endphp
                    <div class="relative p-4 border rounded-xl {{ $isPopular ? 'border-primary ring-2 ring-primary/20' : '' }} {{ $isCurrent ? 'bg-primary/5' : '' }}">
                        @if($isPopular)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-primary text-primary-foreground text-xs rounded-full">
                                Popular
                            </div>
                        @endif
                        @if($isCurrent)
                            <div class="absolute -top-3 right-2 px-3 py-1 bg-green-500 text-white text-xs rounded-full">
                                <i class="fa-solid fa-check mr-1"></i>Current
                            </div>
                        @endif
                        <h4 class="font-semibold text-lg mb-2">{{ $plan->name }}</h4>
                        <p class="text-2xl font-bold mb-4">
                            Rp {{ number_format($plan->price, 0, ',', '.') }}
                            <span class="text-sm font-normal text-muted-foreground">/bulan</span>
                        </p>
                        <ul class="space-y-2 text-sm mb-4">
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>{{ $plan->max_widgets == -1 ? 'Unlimited' : $plan->max_widgets }} Widget</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>{{ number_format($plan->max_messages_per_month, 0, ',', '.') }} Pesan</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>AI {{ ucfirst($plan->ai_tier ?? 'Basic') }}</li>
                            <li><i class="fa-solid fa-check text-green-500 mr-2"></i>{{ $plan->chat_history_days ?? 7 }} Hari History</li>
                        </ul>
                        @if($isCurrent)
                            <button class="w-full py-2 rounded-lg text-sm bg-green-100 text-green-700 cursor-default" disabled>
                                <i class="fa-solid fa-check mr-1"></i>Plan Aktif
                            </button>
                        @elseif($plan->price <= 0)
                            <button class="w-full py-2 rounded-lg text-sm bg-muted text-muted-foreground cursor-default" disabled>
                                Free Plan
                            </button>
                        @else
                            <button onclick="payPlan({{ $plan->id }})" 
                                class="w-full py-2 rounded-lg text-sm {{ $isPopular ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'bg-muted hover:bg-muted/80' }} transition">
                                <i class="fa-solid fa-credit-card mr-1"></i> Pilih & Bayar
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Payment Script --}}
    <script>
        function payPlan(planId) {
            // Create transaction and get snap token
            fetch(`/billing/pay/${planId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Open Snap payment popup
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            window.location.href = '/payment/finish?order_id=' + data.order_id;
                        },
                        onPending: function(result) {
                            window.location.href = '/payment/finish?order_id=' + data.order_id;
                        },
                        onError: function(result) {
                            alert('Pembayaran gagal. Silakan coba lagi.');
                        },
                        onClose: function() {
                            console.log('Payment popup closed');
                        }
                    });
                } else {
                    alert(data.message || 'Gagal membuat transaksi');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }

        // Continue payment for pending transactions
        function continuePay(snapToken, orderId) {
            window.snap.pay(snapToken, {
                onSuccess: function(result) {
                    window.location.href = '/payment/finish?order_id=' + orderId;
                },
                onPending: function(result) {
                    window.location.href = '/payment/finish?order_id=' + orderId;
                },
                onError: function(result) {
                    alert('Pembayaran gagal. Silakan coba lagi.');
                },
                onClose: function() {
                    console.log('Payment popup closed');
                }
            });
        }
    </script>
@endsection