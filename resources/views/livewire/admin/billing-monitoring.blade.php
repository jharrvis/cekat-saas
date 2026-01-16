<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold">Billing & Usage Monitoring</h2>
            <p class="text-muted-foreground">Monitor OpenRouter credits and user consumption stats</p>
        </div>
        <button wire:click="fetchData"
            class="bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:bg-primary/90 transition">
            <i class="fa-solid fa-sync mr-2 {{ $isLoading ? 'fa-spin' : '' }}"></i> Refresh Data
        </button>
    </div>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        {{-- OpenRouter Balance --}}
        <div class="bg-card rounded-xl border p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-wallet text-6xl text-primary"></i>
            </div>
            <h3 class="text-sm font-medium text-muted-foreground mb-2">OpenRouter Balance</h3>
            <p class="text-3xl font-bold flex items-baseline gap-1">
                ${{ number_format($openRouterCredits, 4) }}
                <span class="text-sm font-normal text-muted-foreground">credits</span>
            </p>
            <p class="text-xs text-muted-foreground mt-2">
                Remaining credits on OpenRouter.ai
            </p>
        </div>

        {{-- Est. Monthly Cost --}}
        <div class="bg-card rounded-xl border p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-chart-line text-6xl text-blue-500"></i>
            </div>
            <h3 class="text-sm font-medium text-muted-foreground mb-2">Internal Est. Cost (This Month)</h3>
            <p class="text-3xl font-bold text-blue-600">
                ${{ number_format($internalCost, 4) }}
            </p>
            <p class="text-xs text-muted-foreground mt-2">
                Based on recorded token usage & model prices
            </p>
        </div>

        {{-- Total Requests --}}
        <div class="bg-card rounded-xl border p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-message text-6xl text-green-500"></i>
            </div>
            <h3 class="text-sm font-medium text-muted-foreground mb-2">Total Messages (This Month)</h3>
            <p class="text-3xl font-bold text-green-600">
                {{ number_format(collect($usageByModel)->sum('requests')) }}
            </p>
            <p class="text-xs text-muted-foreground mt-2">
                Total AI responses generated
            </p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- Usage by Model --}}
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="p-4 border-b">
                <h3 class="font-bold">Usage by Model</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left">Model</th>
                            <th class="px-4 py-3 text-right">Requests</th>
                            <th class="px-4 py-3 text-right">Tokens</th>
                            <th class="px-4 py-3 text-right">Est. Cost</th>
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
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No usage recorded this
                                    month</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Spending Chatbots --}}
        <div class="bg-card rounded-xl border shadow-sm">
            <div class="p-4 border-b">
                <h3 class="font-bold">Top Chatbots / Users</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left">Chatbot / User</th>
                            <th class="px-4 py-3 text-right">Messages</th>
                            <th class="px-4 py-3 text-right">Tokens</th>
                            <th class="px-4 py-3 text-right">Est. Cost</th>
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
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No usage data found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>