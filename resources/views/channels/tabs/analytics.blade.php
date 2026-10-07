{{-- Analytics Tab --}}
<div>
    @php
        // Check if this is admin context (landing page widget or admin user)
        $isAdminContext = $chatbot->slug === 'landing-page-default' ||
            (auth()->check() && auth()->user()->role === 'admin');

        // If admin context, unlock all features
        if ($isAdminContext) {
            $isLocked = false;
        } else {
            // Advanced analytics level comes from the plan through PlanLimitService
            $analyticsValue = app(\App\Services\Billing\PlanLimitService::class)
                ->featureValue($chatbot->user ?? auth()->user(), 'analytics');

            // Unlock if analytics is 'advanced' (string) OR true (boolean)
            $isLocked = !($analyticsValue === 'advanced' || $analyticsValue === true);
        }

        $totalConversations = 0;
        $messagesThisMonth = 0;
        $avgMessages = 0;
        $recentSessions = collect();

        if (!$isLocked) {
            $totalConversations = $chatbot->chatSessions()->count();
            $messagesThisMonth = $chatbot->messages()->whereMonth('chat_messages.created_at', now()->month)->count();

            $totalMessages = $chatbot->messages()->count();
            $avgMessages = $totalConversations > 0 ? round($totalMessages / $totalConversations, 1) : 0;

            $recentSessions = $chatbot->chatSessions()->withCount('messages')->latest()->take(5)->get();
        }
    @endphp

    <x-feature-locked :locked="$isLocked" feature-name="Advanced Analytics"
        description="Upgrade to Pro or Business plan to view detailed conversation insights, usage statistics, and engagement metrics.">
        <h3 class="text-lg font-bold mb-4">{{ __('admin.s.analytics') }}</h3>
        <p class="text-muted-foreground mb-6">{{ __('channels.s.view_usage_statistics_and_conversation_analytics') }}</p>

        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <div class="bg-muted/30 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('channels.s.total_conversations') }}</span>
                    <i class="fa-solid fa-message text-blue-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $totalConversations }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('channels.s.all_time') }}</p>
            </div>

            <div class="bg-muted/30 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('channels.s.messages_this_month') }}</span>
                    <i class="fa-solid fa-paper-plane text-green-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $messagesThisMonth }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ now()->format('F Y') }}</p>
            </div>

            <div class="bg-muted/30 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-muted-foreground text-sm">{{ __('channels.s.avg_messages_session') }}</span>
                    <i class="fa-solid fa-comments text-purple-500"></i>
                </div>
                <p class="text-3xl font-bold">{{ $avgMessages }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ __('channels.s.engagement_rate') }}</p>
            </div>
        </div>

        <div class="bg-muted/30 rounded-xl p-6">
            <h4 class="text-lg font-medium mb-4">{{ __('channels.s.recent_conversations') }}</h4>

            @if($recentSessions->isEmpty())
                <div class="text-center py-12 text-muted-foreground">
                    <i class="fa-solid fa-chart-line text-4xl mb-4"></i>
                    <p>{{ __('channels.s.no_conversations_yet') }}</p>
                    <p class="text-sm">{{ __('channels.s.analytics_will_appear_here_once_users_start_chat') }}</p>
                </div>
            @else
                <div class="divide-y">
                    @foreach($recentSessions as $session)
                        <div class="flex justify-between items-center py-3">
                            <div>
                                <p class="font-medium text-sm">{{ $session->visitor_name ?? 'Guest' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $session->started_at ? $session->started_at->diffForHumans() : 'Unknown' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium">{{ $session->messages_count }} msgs</span>
                                <span
                                    class="block text-xs {{ $session->is_lead ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                                    {{ $session->is_lead ? 'Lead' : 'Visitor' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-feature-locked>
</div>