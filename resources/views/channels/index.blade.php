@extends('layouts.dashboard')

@section('title', __('nav.channels'))
@section('page-title', __('nav.channels'))

@section('content')
    <div>
        {{-- Messages --}}
        @if (session()->has('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if (session()->has('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-6">
                <i class="fa-solid fa-circle-info mr-1"></i>{{ session('info') }}
            </div>
        @endif

        {{-- Activation prompt: all channels inactive (e.g. after plan expiry) --}}
        @php($activeCount = $chatbots->where('status', 'active')->count())
        @if($chatbots->count() > 0 && $activeCount === 0)
            <div class="bg-card rounded-xl border border-dashed border-primary/30 p-6 mb-6 text-center">
                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-lock text-primary text-xl"></i>
                </div>
                <h3 class="text-lg font-bold mb-1">{{ __('channels.s.aktifkan_channel_anda') }}</h3>
                <p class="text-sm text-muted-foreground mb-4 max-w-xl mx-auto">
                    {{ __('channels.s.semua_channel_sedang_nonaktif_sehingga_widget_ti') }} <strong>{{ $plan->name ?? 'Free' }}</strong> {{ __('channels.s.mendukung_maksimal') }}
                    <strong>{{ app(\App\Services\Billing\PlanLimitService::class)->limit($plan, 'active_channels') }} channel aktif</strong>
                    @if(!app(\App\Services\Billing\PlanLimitService::class)->feature($plan, 'whatsapp'))
                        dan <strong>{{ __('channels.s.tidak_termasuk_whatsapp_gateway') }}</strong>
                    @endif
                    — pilih salah satu channel lalu klik <em>{{ __('channels.s.aktifkan') }}</em>.
                </p>
                <a href="{{ route('billing') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                    <i class="fa-solid fa-rocket mr-2"></i> {{ __('channels.s.upgrade_plan') }}
                </a>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold">{{ __('agents.s.channels') }}</h2>
                <p class="text-muted-foreground">{{ __('channels.s.kelola_channel_web_widget_anda') }}</p>
            </div>
            <a href="{{ route('channels.create') }}"
                class="bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-plus mr-2"></i> {{ __('agents.s.buat_channel_baru') }}
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('channels.s.total_channel') }}</span>
                    <i class="fa-solid fa-robot text-blue-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $chatbots->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">of {{ app(\App\Services\Billing\PlanLimitService::class)->limit($plan, 'total_channels') }} allowed</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('channels.s.channel_aktif') }}</span>
                    <i class="fa-solid fa-check-circle text-green-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $chatbots->where('status', 'active')->count() }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('channels.s.live_on_websites') }}</p>
            </div>

            <div class="bg-card rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('channels.s.messages_used') }}</span>
                    <i class="fa-solid fa-message text-purple-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ auth()->user()->monthly_message_used ?? 0 }}</p>
                <p class="text-xs text-muted-foreground mt-1">of {{ app(\App\Services\Billing\PlanLimitService::class)->limit(auth()->user(), 'monthly_messages') }} this
                    month</p>
            </div>
        </div>

        {{-- Channels Table --}}
        <div class="bg-card rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {{ __('agents.s.channel') }}</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {{ __('channels.s.status') }}</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {{ __('agents.s.faqs') }}</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {{ __('channels.s.created') }}</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                {{ __('channels.s.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($chatbots as $chatbot)
                            <tr class="hover:bg-muted/30 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                            <i class="fa-solid fa-robot"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ $chatbot->display_name ?? $chatbot->name }}</p>

                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <i class="fa-solid fa-hashtag text-[10px] text-muted-foreground"></i>
                                                <code
                                                    class="text-xs bg-muted px-1.5 py-0.5 rounded font-mono text-muted-foreground">{{ $chatbot->slug }}</code>
                                                <button onclick="copyWidgetId('{{ $chatbot->slug }}')"
                                                    class="text-xs text-muted-foreground hover:text-primary transition p-1"
                                                    title="{{ __('channels.s.copy_widget_id') }}">
                                                    <i class="fa-regular fa-copy"></i>
                                                </button>
                                            </div>

                                            <p class="text-sm text-muted-foreground mt-1">
                                                {{ Str::limit($chatbot->description, 40) ?? 'No description' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-medium {{ $chatbot->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($chatbot->status ?? 'draft') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $chatbot->knowledgeBase?->faqs()->count() ?? 0 }} {{ __('agents.s.faqs') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                    {{ $chatbot->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex gap-2 justify-end">
                                        @if(($chatbot->status ?? 'draft') !== 'active')
                                            <form action="{{ route('channels.activate', $chatbot->id) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition text-sm">
                                                    <i class="fa-solid fa-play mr-1"></i> {{ __('channels.s.aktifkan') }}
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('channels.edit', $chatbot->id) }}"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg transition text-sm">
                                            <i class="fa-solid fa-edit mr-1"></i> {{ __('agents.s.edit') }}
                                        </a>
                                        <form action="{{ route('channels.destroy', $chatbot->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus channel ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition text-sm">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-robot text-6xl text-muted-foreground mb-4"></i>
                                    <p class="text-lg font-medium mb-2">{{ __('channels.s.belum_ada_channel') }}</p>
                                    <p class="text-muted-foreground mb-4">{{ __('channels.s.buat_channel_pertama_untuk_memulai') }}</p>
                                    <a href="{{ route('channels.create') }}"
                                        class="inline-flex items-center bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition">
                                        <i class="fa-solid fa-plus mr-2"></i> {{ __('channels.s.buat_channel_pertama') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function copyWidgetId(id) {
                navigator.clipboard.writeText(id).then(() => {
                    // You can replace this with a nice toast notification
                    alert('Widget ID copied to clipboard: ' + id);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }
        </script>
    @endpush
@endsection
