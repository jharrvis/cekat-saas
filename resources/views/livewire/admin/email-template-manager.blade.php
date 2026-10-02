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
            <h2 class="text-2xl font-bold">📝 Template Email</h2>
            <p class="text-muted-foreground">Buat template email sekali, pakai berulang untuk newsletter & pengumuman</p>
        </div>
        <button wire:click="openCreate" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:opacity-90 transition">
            <i class="fa-solid fa-plus mr-2"></i>Buat Template
        </button>
    </div>

    {{-- Search --}}
    <div class="bg-card rounded-xl border p-4 mb-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau subjek template..."
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
                        <th class="px-4 py-3 text-left text-sm font-medium">Kategori</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Diubah</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($templates as $template)
                        <tr class="hover:bg-muted/30 transition">
                            <td class="px-4 py-3 text-sm font-medium">{{ $template->name }}</td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate" title="{{ $template->subject }}">{{ $template->subject }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $template->category }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $template->updated_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <button wire:click="openEdit({{ $template->id }})" class="text-primary hover:underline mr-3">
                                    <i class="fa-solid fa-pen mr-1"></i>Edit
                                </button>
                                <button wire:click="delete({{ $template->id }})"
                                    wire:confirm="Hapus template ini?"
                                    class="text-red-600 hover:underline">
                                    <i class="fa-solid fa-trash mr-1"></i>Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">
                                Belum ada template. Klik "Buat Template" untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $templates->links() }}
        </div>
    </div>

    {{-- Form modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="resetForm">
            <div class="bg-card rounded-xl border w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">{{ $isEditing ? 'Edit Template' : 'Buat Template' }}</h3>
                    <button wire:click="resetForm" class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Template</label>
                            <input type="text" wire:model="name" placeholder="mis. Newsletter Bulanan"
                                class="w-full px-3 py-2 border rounded-lg text-sm">
                            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Kategori</label>
                            <select wire:model="category" class="w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="general">General</option>
                                <option value="newsletter">Newsletter</option>
                                <option value="announcement">Pengumuman</option>
                            </select>
                            @error('category') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
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
                        <textarea wire:model="body" rows="12"
                            placeholder="<p>Halo @{{name}}, ...</p>"
                            class="w-full px-3 py-2 border rounded-lg text-sm font-mono"></textarea>
                        @error('body') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground mb-2">Token (otomatis diganti saat dikirim):</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tokens as $token => $label)
                                <span class="px-2 py-1 rounded bg-muted text-xs font-mono"
                                    title="{{ $label }}">{{ $token }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t">
                    <button wire:click="preview"
                        class="px-4 py-2 border rounded-lg text-sm font-medium hover:bg-muted transition">
                        <i class="fa-solid fa-eye mr-2"></i>Pratinjau
                    </button>
                    <button wire:click="sendTest"
                        class="px-4 py-2 border rounded-lg text-sm font-medium hover:bg-muted transition">
                        <i class="fa-solid fa-paper-plane mr-2"></i>Kirim Uji
                    </button>
                    <button wire:click="save"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:opacity-90 transition">
                        <i class="fa-solid fa-save mr-2"></i>Simpan
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
