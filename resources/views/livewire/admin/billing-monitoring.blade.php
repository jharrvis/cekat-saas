<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ __('admin.s.billing_usage_monitoring') }}</h2>
            <p class="text-muted-foreground">{{ __('admin.s.monitor_openrouter_credits_and_user_consumption') }}</p>
        </div>
        <button wire:click="fetchData"
            class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition">
            <i class="fa-solid fa-sync mr-2 {{ $isLoading ? 'fa-spin' : '' }}"></i>{{ __('admin.s.refresh_data') }}</button>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        {{-- OpenRouter Balance --}}
        <div class="bg-card rounded-xl border p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-wallet text-6xl text-primary"></i>
            </div>
            <h3 class="text-sm font-medium text-muted-foreground mb-2">{{ __('admin.s.openrouter_balance') }}</h3>
            <p class="text-3xl font-bold flex items-baseline gap-1">
                ${{ number_format($openRouterCredits, 4) }}
                <span class="text-sm font-normal text-muted-foreground">{{ __('admin.s.credits') }}</span>
            </p>
            <p class="text-xs text-muted-foreground mt-2">{{ __('admin.s.remaining_credits_on_openrouter_ai') }}</p>
        </div>

        {{-- Est. Monthly Cost --}}
        <div class="bg-card rounded-xl border p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-chart-line text-6xl text-blue-500"></i>
            </div>
            <h3 class="text-sm font-medium text-muted-foreground mb-2">{{ __('admin.s.internal_est_cost_this_month') }}</h3>
            <p class="text-3xl font-bold text-blue-600">
                ${{ number_format($internalCost, 4) }}
            </p>
            <p class="text-xs text-muted-foreground mt-2">{{ __('admin.s.based_on_recorded_token_usage_model_prices') }}</p>
        </div>

        {{-- Total Requests --}}
        <div class="bg-card rounded-xl border p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-message text-6xl text-green-500"></i>
            </div>
            <h3 class="text-sm font-medium text-muted-foreground mb-2">{{ __('admin.s.total_messages_this_month') }}</h3>
            <p class="text-3xl font-bold text-green-600">
                {{ number_format(collect($usageByModel)->sum('requests')) }}
            </p>
            <p class="text-xs text-muted-foreground mt-2">{{ __('admin.s.total_ai_responses_generated') }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- Usage by Model --}}
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="p-4 border-b">
                <h3 class="font-bold">{{ __('admin.s.usage_by_model') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('admin.s.model') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.s.requests') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.s.tokens') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.s.est_cost') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($usageByModel as $usage)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $usage['name'] }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $usage['model'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ number_format($usage['requests']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($usage['tokens']) }}</td>
                                <td class="px-4 py-3 text-right font-medium">${{ number_format($usage['cost'], 4) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ __('admin.s.no_usage_recorded_this_month') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Spending Chatbots --}}
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="p-4 border-b">
                <h3 class="font-bold">{{ __('admin.s.top_chatbots_users') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('admin.s.chatbot_user') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.s.messages') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.s.tokens') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('admin.s.est_cost') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($topUsers as $user)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $user->widget_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $user->user_name ?? 'System/Guest' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ number_format($user->total_messages) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($user->total_tokens) }}</td>
                                <td class="px-4 py-3 text-right font-medium">${{ number_format($user->estimated_cost, 4) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">{{ __('admin.s.no_usage_data_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>