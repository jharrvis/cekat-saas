<div @if ($hasSending) wire:poll.5s @endif>
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
            <h2 class="text-2xl font-bold">📨 {{ $typeLabel }}</h2>
            <p class="text-muted-foreground">
                {{ $type === 'newsletter'
                    ? 'Kirim email berkala ke user tersegmentasi (fitur, tips, update produk)'
                    : 'Kirim pengumuman resmi ke user (rilis fitur, maintenance, kebijakan)' }}
            </p>
        </div>
        <button wire:click="openCreate"
            class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:opacity-90 transition">
            <i class="fa-solid fa-plus mr-2"></i>Buat {{ $typeLabel }}
        </button>
    </div>

    {{-- Search --}}
    <div class="bg-card rounded-xl border p-4 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau subjek..."
            class="w-full md:w-96 px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
    </div>

    {{-- Table --}}
    <div class="bg-card rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">Nama</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Subjek</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Progres</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Dibuat</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($campaigns as $campaign)
                        <tr class="hover:bg-muted/30 transition">
                            <td class="px-4 py-3 text-sm font-medium">{{ $campaign->name }}</td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate" title="{{ $campaign->subject }}">{{ $campaign->subject }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($campaign->status === 'sent')
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Terkirim</span>
                                @elseif ($campaign->status === 'sending')
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Mengirim</span>
                                @elseif ($campaign->status === 'stopped')
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Dihentikan</span>
                                @elseif ($campaign->status === 'failed')
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Gagal</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if (in_array($campaign->status, ['sending', 'stopped', 'sent']))
                                    <div class="w-40">
                                        <div class="flex justify-between text-xs text-muted-foreground mb-1">
                                            <span>{{ $campaign->sent_count }}/{{ $campaign->total_recipients }} terkirim</span>
                                            <span>{{ $campaign->progress() }}%</span>
                                        </div>
                                        <div class="w-full bg-muted rounded-full h-2">
                                            <div class="bg-primary h-2 rounded-full transition-all"
                                                style="width: {{ $campaign->progress() }}%"></div>
                                        </div>
                                        @if ($campaign->failed_count > 0)
                                            <span class="text-xs text-red-600">{{ $campaign->failed_count }} gagal</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted-foreground text-sm">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $campaign->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-3 flex-wrap">
                                    @if ($campaign->status === 'draft')
                                        <button wire:click="openEdit({{ $campaign->id }})" class="text-primary hover:underline">
                                            <i class="fa-solid fa-pen mr-1"></i>Edit
                                        </button>
                                    @endif
                                    <button wire:click="sendTest({{ $campaign->id }})" class="text-slate-600 hover:underline">
                                        <i class="fa-solid fa-paper-plane mr-1"></i>Uji
                                    </button>
                                    @if (in_array($campaign->status, ['draft', 'stopped']))
                                        <button wire:click="start({{ $campaign->id }})"
                                            class="text-green-600 hover:underline font-medium">
                                            <i class="fa-solid fa-play mr-1"></i>{{ $campaign->status === 'stopped' ? 'Lanjutkan' : 'Kirim' }}
                                        </button>
                                    @elseif ($campaign->status === 'sending')
                                        <button wire:click="stop({{ $campaign->id }})" class="text-amber-600 hover:underline">
                                            <i class="fa-solid fa-stop mr-1"></i>Hentikan
                                        </button>
                                    @endif
                                    <button wire:click="delete({{ $campaign->id }})"
                                        wire:confirm="Hapus kampanye ini?"
                                        class="text-red-600 hover:underline">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                                Belum ada {{ strtolower($typeLabel) }}. Klik "Buat {{ $typeLabel }}" untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $campaigns->links() }}
        </div>
    </div>

    {{-- Form modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="resetForm">
            <div class="bg-card rounded-xl border w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">{{ $isEditing ? 'Edit' : 'Buat' }} {{ $typeLabel }}</h3>
                    <button wire:click="resetForm" class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Kampanye</label>
                            <input type="text" wire:model="name" placeholder="mis. Update Oktober 2026"
                                class="w-full px-3 py-2 border rounded-lg text-sm">
                            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Muat dari Template</label>
                            <select wire:change="loadTemplate($event.target.value)" class="w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="">— Pilih template (opsional) —</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Subjek Email</label>
                        <input type="text" wire:model="subject" placeholder="Subjek yang tampil di inbox"
                            class="w-full px-3 py-2 border rounded-lg text-sm">
                        @error('subject') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Isi Email (HTML)</label>
                        <textarea wire:model="body" rows="10"
                            placeholder="<p>Halo @{{name}}, ...</p>"
                            class="w-full px-3 py-2 border rounded-lg text-sm font-mono"></textarea>
                        @error('body') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Segment --}}
                    <div class="border rounded-xl p-4 space-y-3">
                        <p class="text-sm font-semibold">Penerima</p>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="segmentVerified" class="rounded">
                            Hanya user yang sudah verifikasi email
                        </label>
                        <div class="grid md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-muted-foreground mb-1">Status akun</label>
                                <select wire:model="segmentStatus" class="w-full px-3 py-2 border rounded-lg text-sm">
                                    <option value="active">Aktif (default)</option>
                                    <option value="all">Semua status</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="banned">Banned</option>
                                </select>
                                @error('segmentStatus') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-muted-foreground mb-1">Plan</label>
                                <select wire:model="segmentPlanId" class="w-full px-3 py-2 border rounded-lg text-sm">
                                    <option value="">Semua plan</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-muted-foreground mb-1">Role</label>
                                <select wire:model="segmentRole" class="w-full px-3 py-2 border rounded-lg text-sm">
                                    <option value="user">User (default)</option>
                                    <option value="">Semua role</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-sm">
                            Estimasi penerima:
                            <span class="font-bold {{ count($previewCount) > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ count($previewCount) }}
                            </span> user
                            @if ($segmentStatus === 'active')
                                <span class="text-muted-foreground text-xs">(perlu verifikasi email & tidak suspended/banned)</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 rounded bg-muted text-xs font-mono">@{{name}}</span>
                        <span class="px-2 py-1 rounded bg-muted text-xs font-mono">@{{email}}</span>
                        <span class="px-2 py-1 rounded bg-muted text-xs font-mono">@{{plan}}</span>
                        <span class="px-2 py-1 rounded bg-muted text-xs font-mono">@{{app_name}}</span>
                        <span class="px-2 py-1 rounded bg-muted text-xs font-mono">@{{login_url}}</span>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t">
                    <button wire:click="preview"
                        class="px-4 py-2 border rounded-lg text-sm font-medium hover:bg-muted transition">
                        <i class="fa-solid fa-eye mr-2"></i>Pratinjau
                    </button>
                    <button wire:click="save"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:opacity-90 transition">
                        <i class="fa-solid fa-save mr-2"></i>Simpan Draft
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Preview modal --}}
    @if ($previewShow)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closePreview">
            <div class="bg-card rounded-xl border w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Pratinjau Email</h3>
                    <button wire:click="closePreview" class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <iframe sandbox srcdoc="{{ $previewHtml }}" class="w-full flex-1 min-h-[60vh] bg-white"></iframe>
            </div>
        </div>
    @endif
</div>
