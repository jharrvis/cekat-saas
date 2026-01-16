<div>
    {{-- Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            <i class="fa-solid fa-times-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">💳 Transaction Monitor</h2>
            <p class="text-muted-foreground">Monitor dan kelola semua transaksi pembayaran</p>
        </div>
        <button wire:click="export" class="btn-secondary">
            <i class="fa-solid fa-download mr-2"></i>Export CSV
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        {{-- Today Revenue --}}
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-day text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Revenue Hari Ini</p>
                    <p class="text-xl font-bold text-green-600">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Month Revenue --}}
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-calendar text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Revenue Bulan Ini</p>
                    <p class="text-xl font-bold text-blue-600">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                    <i class="fa-solid fa-coins text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Total Revenue</p>
                    <p class="text-xl font-bold text-purple-600">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Transaction Count --}}
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                    <i class="fa-solid fa-receipt text-amber-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Total Transaksi</p>
                    <div class="flex gap-2 text-sm">
                        <span class="text-green-600" title="Success">✓{{ $statusCounts['success'] ?? 0 }}</span>
                        <span class="text-amber-600" title="Pending">⏳{{ $statusCounts['pending'] ?? 0 }}</span>
                        <span class="text-red-600" title="Failed">✗{{ $statusCounts['failed'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-card rounded-xl border p-4 mb-6">
        <div class="grid md:grid-cols-6 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Cari</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Order ID, nama, email..."
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select wire:model.live="statusFilter"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Semua Status</option>
                    <option value="success">Success</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="expired">Expired</option>
                    <option value="challenge">Challenge</option>
                </select>
            </div>

            {{-- Plan Filter --}}
            <div>
                <label class="block text-sm font-medium mb-1">Plan</label>
                <select wire:model.live="planFilter"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Semua Plan</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-sm font-medium mb-1">Dari Tanggal</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-sm font-medium mb-1">Sampai Tanggal</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
            </div>
        </div>

        {{-- Reset Button --}}
        @if($search || $statusFilter || $planFilter || $dateFrom || $dateTo)
            <div class="mt-4">
                <button wire:click="resetFilters" class="text-sm text-primary hover:underline">
                    <i class="fa-solid fa-times mr-1"></i>Reset Filter
                </button>
            </div>
        @endif
    </div>

    {{-- Transactions Table --}}
    <div class="bg-card rounded-xl border overflow-hidden">
        <table class="w-full">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium">Order ID</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">User</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Plan</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Amount</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Payment</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Tanggal</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-muted/30 transition">
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm">{{ $tx->order_id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium">{{ $tx->user->name ?? '-' }}</div>
                            <div class="text-xs text-muted-foreground">{{ $tx->user->email ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $tx->plan->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs px-2 py-1 rounded bg-muted capitalize">{{ $tx->payment_type ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($tx->status === 'success')
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">
                                    <i class="fa-solid fa-check mr-1"></i>Success
                                </span>
                            @elseif($tx->status === 'pending')
                                <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">
                                    <i class="fa-solid fa-clock mr-1"></i>Pending
                                </span>
                            @elseif($tx->status === 'expired')
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    <i class="fa-solid fa-hourglass-end mr-1"></i>Expired
                                </span>
                            @elseif($tx->status === 'challenge')
                                <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                    <i class="fa-solid fa-shield mr-1"></i>Challenge
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                                    <i class="fa-solid fa-times mr-1"></i>Failed
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-muted-foreground">
                            {{ $tx->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <button wire:click="viewDetail({{ $tx->id }})" class="p-2 rounded hover:bg-muted transition"
                                    title="View Detail">
                                    <i class="fa-solid fa-eye text-muted-foreground"></i>
                                </button>
                                <button wire:click="refreshStatus({{ $tx->id }})" wire:loading.attr="disabled"
                                    class="p-2 rounded hover:bg-muted transition" title="Refresh Status">
                                    <i class="fa-solid fa-sync text-blue-500"></i>
                                </button>
                                @if($tx->status === 'pending')
                                    <button wire:click="markAsSuccess({{ $tx->id }})"
                                        wire:confirm="Yakin ingin menandai transaksi ini sebagai sukses?"
                                        class="p-2 rounded hover:bg-muted transition" title="Mark as Success">
                                        <i class="fa-solid fa-check-circle text-green-500"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
                            <i class="fa-solid fa-receipt text-4xl mb-4 block"></i>
                            <p>Tidak ada transaksi ditemukan</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="px-4 py-3 border-t">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedTransaction)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeDetail">
            <div class="bg-card rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-auto">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold">Detail Transaksi</h3>
                    <button wire:click="closeDetail" class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    {{-- Order Info --}}
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Order ID</p>
                            <p class="font-mono font-bold">{{ $selectedTransaction->order_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Status</p>
                            <p class="font-bold capitalize">{{ $selectedTransaction->status }}</p>
                        </div>
                    </div>

                    {{-- User Info --}}
                    <div class="bg-muted/30 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">User Info</h4>
                        <div class="grid md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-muted-foreground">Nama</p>
                                <p>{{ $selectedTransaction->user->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Email</p>
                                <p>{{ $selectedTransaction->user->email ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Info --}}
                    <div class="bg-muted/30 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Payment Info</h4>
                        <div class="grid md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-muted-foreground">Plan</p>
                                <p>{{ $selectedTransaction->plan->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Amount</p>
                                <p class="font-bold">Rp {{ number_format($selectedTransaction->amount, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Payment Type</p>
                                <p class="capitalize">{{ $selectedTransaction->payment_type ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Snap Token</p>
                                <p class="font-mono text-xs truncate">{{ $selectedTransaction->snap_token ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Timeline --}}
                    <div class="bg-muted/30 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Timeline</h4>
                        <div class="grid md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-muted-foreground">Created At</p>
                                <p>{{ $selectedTransaction->created_at->format('d M Y H:i:s') }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Paid At</p>
                                <p>{{ $selectedTransaction->paid_at?->format('d M Y H:i:s') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Midtrans Response --}}
                    @if($selectedTransaction->midtrans_response)
                        <div class="bg-muted/30 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">Midtrans Response</h4>
                            <pre
                                class="text-xs bg-gray-900 text-green-400 p-3 rounded overflow-auto max-h-48">{{ json_encode($selectedTransaction->midtrans_response, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
                <div class="p-6 border-t flex justify-end gap-2">
                    <button wire:click="refreshStatus({{ $selectedTransaction->id }})" class="btn-secondary">
                        <i class="fa-solid fa-sync mr-2"></i>Refresh Status
                    </button>
                    @if($selectedTransaction->status === 'pending')
                        <button wire:click="markAsSuccess({{ $selectedTransaction->id }})" wire:confirm="Yakin?"
                            class="btn-primary">
                            <i class="fa-solid fa-check mr-2"></i>Mark Success
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>