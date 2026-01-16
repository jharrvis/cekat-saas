{{-- Analytics Tab --}}
<div>
    <h3 class="text-lg font-bold mb-4">Analytics</h3>
    <p class="text-muted-foreground mb-6">View usage statistics and conversation analytics</p>

    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <div class="bg-muted/30 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-muted-foreground text-sm">Total Conversations</span>
                <i class="fa-solid fa-message text-blue-500"></i>
            </div>
            <p class="text-3xl font-bold">{{ $chatbot->chatSessions()->count() }}</p>
            <p class="text-xs text-muted-foreground mt-1">All time</p>
        </div>

        <div class="bg-muted/30 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-muted-foreground text-sm">Messages This Month</span>
                <i class="fa-solid fa-paper-plane text-green-500"></i>
            </div>
            <p class="text-3xl font-bold">0</p>
            <p class="text-xs text-muted-foreground mt-1">{{ now()->format('F Y') }}</p>
        </div>

        <div class="bg-muted/30 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-muted-foreground text-sm">Avg Response Time</span>
                <i class="fa-solid fa-clock text-purple-500"></i>
            </div>
            <p class="text-3xl font-bold">1.2s</p>
            <p class="text-xs text-muted-foreground mt-1">Average</p>
        </div>
    </div>

    <div class="bg-muted/30 rounded-xl p-6">
        <h4 class="text-lg font-medium mb-4">Recent Conversations</h4>
        <div class="text-center py-12 text-muted-foreground">
            <i class="fa-solid fa-chart-line text-4xl mb-4"></i>
            <p>No conversations yet</p>
            <p class="text-sm">Analytics will appear here once users start chatting</p>
        </div>
    </div>
</div>