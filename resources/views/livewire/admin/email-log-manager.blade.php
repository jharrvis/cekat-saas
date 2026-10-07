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
            <h2 class="text-2xl font-bold">{{ __('admin.s.log_email') }}</h2>
            <p class="text-muted-foreground">{{ __('admin.s.semua_email_keluar_aplikasi_notifikasi_otp_pemba') }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-card rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">{{ __('admin.s.hari_ini') }}</p>
            <p class="text-xl font-bold">{{ $stats['today'] }}</p>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">{{ __('admin.s.terkirim_hari_ini') }}</p>
            <p class="text-xl font-bold text-green-600">{{ $stats['sentToday'] }}</p>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">{{ __('admin.s.gagal_hari_ini') }}</p>
            <p class="text-xl font-bold text-red-600">{{ $stats['failedToday'] }}</p>
        </div>
        <div class="bg-card rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">{{ __('admin.s.total_tersimpan') }}</p>
            <p class="text-xl font-bold">{{ $stats['total'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-card rounded-xl border p-4 mb-6">
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('admin.s.cari_penerima_atau_subjek') }}"
                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20">
            </div>
            <div>
                <select wire:model.live="category" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">{{ __('admin.s.semua_kategori') }}</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="status" class="w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">{{ __('admin.s.semua_status') }}</option>
                    <option value="sent">{{ __('admin.s.terkirim') }}</option>
                    <option value="failed">{{ __('admin.s.gagal') }}</option>
                </select>
            </div>
        </div>
        @if ($search || $category || $status || $campaignId)
            <div class="mt-3 flex items-center gap-3 flex-wrap">
                @if ($campaignId)
                    <span class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700">
                        Kampanye #{{ $campaignId }}
                        <button wire:click="resetFilters" class="ml-1 underline">{{ __('admin.s.times') }}</button>
                    </span>
                @endif
                <button wire:click="resetFilters" class="text-sm text-primary hover:underline">
                    <i class="fa-solid fa-times mr-1"></i>{{ __('admin.s.reset_filter') }}</button>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-card rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.waktu') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.kategori') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.penerima') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.subjek') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('channels.s.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium">{{ __('admin.s.aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-muted/30 transition">
                            <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ \App\Models\EmailLog::categoryLabel($log->category) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $log->recipient }}</td>
                            <td class="px-4 py-3 text-sm max-w-xs truncate" title="{{ $log->subject }}">{{ $log->subject }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($log->status === 'sent')
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ __('admin.s.terkirim') }}</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ __('admin.s.gagal') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <button wire:click="openDetail({{ $log->id }})"
                                    class="text-primary hover:underline text-sm">
                                    <i class="fa-solid fa-eye mr-1"></i>{{ __('admin.s.detail') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">{{ __('admin.s.belum_ada_email_tercatat') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $logs->links() }}
        </div>
    </div>

    {{-- Detail modal --}}
    @if ($showDetail && $detail)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeDetail">
            <div class="bg-card rounded-xl border w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">{{ __('admin.s.detail_email') }}</h3>
                    <button wire:click="closeDetail" class="text-muted-foreground hover:text-foreground">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div class="grid md:grid-cols-2 gap-3">
                        <div><span class="text-muted-foreground">{{ __('admin.s.waktu_2') }}</span>
                            {{ $detail->created_at->format('d/m/Y H:i:s') }}</div>
                        <div><span class="text-muted-foreground">{{ __('admin.s.kategori_2') }}</span>
                            {{ \App\Models\EmailLog::categoryLabel($detail->category) }}</div>
                        <div><span class="text-muted-foreground">{{ __('admin.s.penerima_2') }}</span> {{ $detail->recipient }}</div>
                        <div><span class="text-muted-foreground">{{ __('admin.s.status') }}</span>
                            {{ $detail->status === 'sent' ? 'Terkirim' : 'Gagal' }}</div>
                        <div class="md:col-span-2"><span class="text-muted-foreground">{{ __('admin.s.subjek_2') }}</span>
                            {{ $detail->subject }}</div>
                        @if ($detail->mailable)
                            <div class="md:col-span-2"><span class="text-muted-foreground">{{ __('admin.s.mailable') }}</span>
                                <code class="text-xs">{{ $detail->mailable }}</code></div>
                        @endif
                        @if ($detail->campaign_id)
                            <div class="md:col-span-2">
                                <a href="{{ route('admin.email-center', ['tab' => 'log', 'campaign' => $detail->campaign_id]) }}"
                                    class="text-primary hover:underline">
                                    Lihat log kampanye #{{ $detail->campaign_id }} &rarr;
                                </a>
                            </div>
                        @endif
                    </div>

                    @if ($detail->status === 'failed' && $detail->error)
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            <span class="font-medium">{{ __('admin.s.error') }}</span> {{ $detail->error }}
                        </div>
                    @endif

                    @if ($detail->body)
                        <div>
                            <p class="text-muted-foreground mb-2">{{ __('admin.s.isi_email') }}</p>
                            <iframe sandbox srcdoc="{{ $detail->body }}"
                                class="w-full h-96 border rounded-lg bg-white"></iframe>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
