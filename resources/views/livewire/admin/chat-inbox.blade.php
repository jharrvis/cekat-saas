<div class="flex gap-6 h-[calc(100vh-180px)]">
    {{-- Session List Panel --}}
    <div class="w-96 bg-card rounded-xl border shadow-sm flex flex-col overflow-hidden">
        {{-- Search & Filter --}}
        <div class="p-4 border-b space-y-3">
            <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="{{ __('admin.s.cari_nama_email_atau_id') }}"
                class="w-full px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            <select wire:model.live="statusFilter" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="all">{{ __('admin.s.semua_status') }}</option>
                <option value="active">{{ __('channels.s.active') }}</option>
                <option value="ended">{{ __('admin.s.ended') }}</option>
            </select>
        </div>

        {{-- Sessions List --}}
        <div class="flex-1 overflow-y-auto divide-y">
            @forelse($sessions as $session)
                <div wire:key="session-{{ $session->id }}" wire:click="selectSession({{ $session->id }})"
                    class="p-4 hover:bg-muted/30 cursor-pointer transition {{ $selectedSession && $selectedSession->id === $session->id ? 'bg-primary/5 border-l-4 border-primary' : '' }}">
                    <div class="flex justify-between items-start mb-1">
                        <div class="font-medium text-sm truncate">
                            {{ $session->visitor_name ?? 'Visitor ' . Str::limit($session->visitor_uuid, 8) }}
                        </div>
                        <span class="text-xs text-muted-foreground whitespace-nowrap">
                            {{ $session->updated_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="text-xs text-muted-foreground truncate mb-2">
                        {{ $session->widget->display_name ?? $session->widget->name ?? 'Unknown Widget' }}
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-medium 
                                        {{ $session->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($session->status ?? 'active') }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ $session->messages_count }} messages
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-muted-foreground">
                    <i class="fa-solid fa-inbox text-4xl mb-4"></i>
                    <p>{{ __('admin.s.no_chat_sessions_found') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="p-3 border-t">
            {{ $sessions->links('pagination::simple-tailwind') }}
        </div>
    </div>

    {{-- Chat Detail Panel --}}
    <div class="flex-1 bg-card rounded-xl border shadow-sm flex flex-col overflow-hidden">
        @if($selectedSession)
            {{-- Chat Header --}}
            <div class="p-4 border-b flex justify-between items-center">
                <div>
                    <h3 class="font-bold">
                        {{ $selectedSession->visitor_name ?? 'Visitor ' . Str::limit($selectedSession->visitor_uuid, 12) }}
                    </h3>
                    <div class="flex gap-4 text-xs text-muted-foreground mt-1">
                        @if($selectedSession->visitor_email)
                            <span><i class="fa-solid fa-envelope mr-1"></i> {{ $selectedSession->visitor_email }}</span>
                        @endif
                        @if($selectedSession->visitor_phone)
                            <span><i class="fa-solid fa-phone mr-1"></i> {{ $selectedSession->visitor_phone }}</span>
                        @endif
                        <span><i class="fa-solid fa-globe mr-1"></i> {{ $selectedSession->ip_address ?? 'Unknown' }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if($selectedSession->status !== 'ended')
                        <button wire:click="closeSession({{ $selectedSession->id }})"
                            class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                            <i class="fa-solid fa-times mr-1"></i>{{ __('admin.s.end_session') }}</button>
                    @endif
                </div>
            </div>

            {{-- Chat Messages --}}
            <div class="flex-1 p-4 overflow-y-auto space-y-4 bg-muted/20">
                @foreach($messages as $msg)
                    <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[70%] px-4 py-3 rounded-2xl {{ $msg['role'] === 'user' ? 'bg-primary text-primary-foreground rounded-br-sm' : 'bg-card border rounded-bl-sm' }}">
                            <p class="text-sm whitespace-pre-wrap">{!! nl2br(e($msg['content'])) !!}</p>
                            <p
                                class="text-[10px] mt-1 {{ $msg['role'] === 'user' ? 'text-primary-foreground/70' : 'text-muted-foreground' }}">
                                {{ \Carbon\Carbon::parse($msg['created_at'])->format('H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- AI Summary Section --}}
            <div class="p-4 border-t bg-blue-50/50">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-semibold text-sm">
                        <i class="fa-solid fa-robot mr-1 text-primary"></i>{{ __('admin.s.ai_summary') }}</h4>
                    <button wire:click="generateSummary({{ $selectedSession->id }})" wire:loading.attr="disabled"
                        class="px-3 py-1 text-xs bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="generateSummary">
                            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>{{ __('channels.s.generate') }}</span>
                        <span wire:loading wire:target="generateSummary">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>{{ __('admin.s.processing') }}</span>
                    </button>
                </div>
                @if($selectedSession->summary)
                    <p class="text-sm text-muted-foreground">{{ $selectedSession->summary }}</p>
                @else
                    <p class="text-sm text-muted-foreground italic">{{ __('admin.s.no_summary_yet_click_generate_to_create_ai_summa') }}</p>
                @endif
            </div>

            {{-- Session Metadata --}}
            <div class="p-3 border-t bg-muted/30 text-xs text-muted-foreground flex gap-6">
                <span><strong>{{ __('admin.s.widget_2') }}</strong> {{ $selectedSession->widget->display_name ?? 'Unknown' }}</span>
                <span><strong>{{ __('admin.s.started') }}</strong>
                    {{ $selectedSession->started_at ? $selectedSession->started_at->format('d M Y H:i') : $selectedSession->created_at->format('d M Y H:i') }}</span>
                <span><strong>{{ __('admin.s.user_agent') }}</strong> {{ Str::limit($selectedSession->user_agent ?? 'Unknown', 50) }}</span>
            </div>
        @else
            {{-- Empty State --}}
            <div class="flex-1 flex items-center justify-center text-muted-foreground">
                <div class="text-center">
                    <i class="fa-solid fa-comments text-6xl mb-4"></i>
                    <p class="text-lg">{{ __('admin.s.select_a_chat_session_to_view') }}</p>
                    <p class="text-sm mt-1">{{ __('admin.s.click_on_a_session_from_the_left_panel') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>