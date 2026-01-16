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
            <h2 class="text-2xl font-bold">👥 User Management</h2>
            <p class="text-muted-foreground">Kelola users dan subscription mereka</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="export" class="btn-secondary">
                <i class="fa-solid fa-download mr-2"></i>Export
            </button>
            <button wire:click="openModal" class="btn-primary">
                <i class="fa-solid fa-plus mr-2"></i>Add User
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-users text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Total</p>
                    <p class="text-xl font-bold">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                    <i class="fa-solid fa-user-check text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Active</p>
                    <p class="text-xl font-bold">{{ $stats['active'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i class="fa-solid fa-user-clock text-amber-600"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Suspended</p>
                    <p class="text-xl font-bold">{{ $stats['suspended'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fa-solid fa-user-shield text-purple-600"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Admins</p>
                    <p class="text-xl font-bold">{{ $stats['admins'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fa-solid fa-calendar text-orange-600"></i>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Bulan Ini</p>
                    <p class="text-xl font-bold">{{ $stats['thisMonth'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-card rounded-xl border p-4 mb-6">
        <div class="grid md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..."
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <select wire:model.live="roleFilter" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">Semua Role</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <select wire:model.live="planFilter" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">Semua Plan</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="banned">Banned</option>
                </select>
            </div>
        </div>
        @if($search || $roleFilter || $planFilter || $statusFilter)
            <div class="mt-3">
                <button wire:click="resetFilters" class="text-sm text-primary hover:underline">
                    <i class="fa-solid fa-times mr-1"></i>Reset Filter
                </button>
            </div>
        @endif
    </div>

    {{-- Users Table --}}
    <div class="bg-card rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">User</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Role</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Plan</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Messages</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Joined</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($users as $user)
                        <tr class="hover:bg-muted/30 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar }}" class="w-10 h-10 rounded-full">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium">{{ $user->name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 rounded text-xs font-medium {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $user->plan->name ?? 'Free' }}</td>
                            <td class="px-4 py-3">
                                @php $status = $user->status ?? 'active'; @endphp
                                @if($status === 'active')
                                    <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Active</span>
                                @elseif($status === 'suspended')
                                    <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Suspended</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Banned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                {{ $user->monthly_message_used ?? 0 }} / {{ $user->plan->max_messages_per_month ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1" x-data="{ open: false }">
                                    <button wire:click="viewDetail({{ $user->id }})"
                                        class="p-2 rounded hover:bg-muted transition" title="View Detail">
                                        <i class="fa-solid fa-eye text-muted-foreground"></i>
                                    </button>
                                    <button wire:click="openModal({{ $user->id }})"
                                        class="p-2 rounded hover:bg-muted transition" title="Edit">
                                        <i class="fa-solid fa-edit text-blue-500"></i>
                                    </button>

                                    {{-- Dropdown Actions --}}
                                    <div class="relative">
                                        <button @click="open = !open" class="p-2 rounded hover:bg-muted transition">
                                            <i class="fa-solid fa-ellipsis-v text-muted-foreground"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-cloak
                                            class="absolute right-0 mt-1 w-48 bg-card border rounded-lg shadow-lg z-10">
                                            <div class="py-1">
                                                <button wire:click="resetQuota({{ $user->id }})" @click="open = false"
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-muted transition">
                                                    <i class="fa-solid fa-rotate-right mr-2"></i>Reset Quota
                                                </button>
                                                <button wire:click="sendPasswordReset({{ $user->id }})"
                                                    @click="open = false"
                                                    class="w-full text-left px-4 py-2 text-sm hover:bg-muted transition">
                                                    <i class="fa-solid fa-key mr-2"></i>Reset Password
                                                </button>
                                                <hr class="my-1">
                                                @if(($user->status ?? 'active') === 'active')
                                                    <button wire:click="openSuspendModal({{ $user->id }})" @click="open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-amber-600 hover:bg-muted transition">
                                                        <i class="fa-solid fa-pause mr-2"></i>Suspend User
                                                    </button>
                                                    <button wire:click="banUser({{ $user->id }})"
                                                        wire:confirm="Yakin ban user ini?" @click="open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-muted transition">
                                                        <i class="fa-solid fa-ban mr-2"></i>Ban User
                                                    </button>
                                                @else
                                                    <button wire:click="activateUser({{ $user->id }})" @click="open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-muted transition">
                                                        <i class="fa-solid fa-check mr-2"></i>Activate User
                                                    </button>
                                                @endif
                                                <hr class="my-1">
                                                <button wire:click="confirmDelete({{ $user->id }})"
                                                    wire:confirm="Yakin hapus user ini? Semua data akan hilang."
                                                    @click="open = false"
                                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-muted transition">
                                                    <i class="fa-solid fa-trash mr-2"></i>Delete User
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                                <i class="fa-solid fa-users text-4xl mb-4 block"></i>
                                <p>Tidak ada user ditemukan</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-4 py-3 border-t">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Add/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeModal">
            <div class="bg-card rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-bold">{{ $isEditing ? 'Edit User' : 'Add New User' }}</h3>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nama</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 border rounded-lg">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" wire:model="email" class="w-full px-3 py-2 border rounded-lg">
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Password
                            {{ $isEditing ? '(kosongkan jika tidak ganti)' : '' }}</label>
                        <input type="password" wire:model="password" class="w-full px-3 py-2 border rounded-lg">
                        @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Role</label>
                            <select wire:model="role" class="w-full px-3 py-2 border rounded-lg">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Status</label>
                            <select wire:model="status" class="w-full px-3 py-2 border rounded-lg">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="banned">Banned</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Plan</label>
                        <select wire:model="planId" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Free Plan</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">
                            {{ $isEditing ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Suspend Modal --}}
    @if($showSuspendModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            wire:click.self="closeSuspendModal">
            <div class="bg-card rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-bold text-amber-600"><i class="fa-solid fa-pause mr-2"></i>Suspend User</h3>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-muted-foreground">User tidak akan bisa login atau menggunakan layanan.</p>
                    <div>
                        <label class="block text-sm font-medium mb-1">Alasan (opsional)</label>
                        <textarea wire:model="suspendReason" rows="3" class="w-full px-3 py-2 border rounded-lg"
                            placeholder="Alasan suspend..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button wire:click="closeSuspendModal" class="btn-secondary">Cancel</button>
                        <button wire:click="suspendUser"
                            class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600 transition">
                            Suspend
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedUser)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeDetailModal">
            <div class="bg-card rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-auto">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold">User Detail</h3>
                    <button wire:click="closeDetailModal" class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    {{-- User Info --}}
                    <div class="flex items-center gap-4">
                        @if($selectedUser->avatar)
                            <img src="{{ $selectedUser->avatar }}" class="w-16 h-16 rounded-full">
                        @else
                            <div
                                class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-2xl">
                                {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <h4 class="text-xl font-bold">{{ $selectedUser->name }}</h4>
                            <p class="text-muted-foreground">{{ $selectedUser->email }}</p>
                        </div>
                    </div>

                    {{-- Details Grid --}}
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-muted/30 rounded-lg p-3">
                            <p class="text-sm text-muted-foreground">Role</p>
                            <p class="font-medium capitalize">{{ $selectedUser->role }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-lg p-3">
                            <p class="text-sm text-muted-foreground">Status</p>
                            <p class="font-medium capitalize">{{ $selectedUser->status ?? 'active' }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-lg p-3">
                            <p class="text-sm text-muted-foreground">Plan</p>
                            <p class="font-medium">{{ $selectedUser->plan->name ?? 'Free' }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-lg p-3">
                            <p class="text-sm text-muted-foreground">Messages Used</p>
                            <p class="font-medium">{{ $selectedUser->monthly_message_used ?? 0 }} /
                                {{ $selectedUser->plan->max_messages_per_month ?? 0 }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-lg p-3">
                            <p class="text-sm text-muted-foreground">Widgets</p>
                            <p class="font-medium">{{ $selectedUser->widgets->count() }}</p>
                        </div>
                        <div class="bg-muted/30 rounded-lg p-3">
                            <p class="text-sm text-muted-foreground">Joined</p>
                            <p class="font-medium">{{ $selectedUser->created_at->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Recent Transactions --}}
                    @if($selectedUser->transactions && $selectedUser->transactions->count() > 0)
                        <div>
                            <h5 class="font-semibold mb-2">Recent Transactions</h5>
                            <div class="bg-muted/30 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-muted/50">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Plan</th>
                                            <th class="px-3 py-2 text-left">Amount</th>
                                            <th class="px-3 py-2 text-left">Status</th>
                                            <th class="px-3 py-2 text-left">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach($selectedUser->transactions as $tx)
                                            <tr>
                                                <td class="px-3 py-2">{{ $tx->plan->name ?? '-' }}</td>
                                                <td class="px-3 py-2">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2 capitalize">{{ $tx->status }}</td>
                                                <td class="px-3 py-2">{{ $tx->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Quick Actions --}}
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="openModal({{ $selectedUser->id }})" class="btn-primary text-sm">
                            <i class="fa-solid fa-edit mr-1"></i>Edit
                        </button>
                        <button wire:click="resetQuota({{ $selectedUser->id }})" class="btn-secondary text-sm">
                            <i class="fa-solid fa-rotate-right mr-1"></i>Reset Quota
                        </button>
                        <button wire:click="sendPasswordReset({{ $selectedUser->id }})" class="btn-secondary text-sm">
                            <i class="fa-solid fa-key mr-1"></i>Reset Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>