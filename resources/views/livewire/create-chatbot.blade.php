<div>
    <x-plan-limit-alert />
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="create" class="space-y-6">
        <div>
            <label class="block text-sm font-medium mb-2">{{ __('channels.s.chatbot_name') }}</label>
            <input type="text" wire:model="display_name"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="{{ __('channels.s.e_g_customer_support_bot') }}">
            @error('display_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">{{ __('channels.s.description_optional') }}</label>
            <textarea wire:model="description" rows="3"
                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="{{ __('channels.s.brief_description_of_what_this_chatbot_does') }}"></textarea>
            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <i class="fa-solid fa-info-circle mr-2"></i>{{ __('livewire.s.you_can_create_up_to') }} <strong>{{ app(\App\Services\Billing\PlanLimitService::class)->limit(auth()->user(), 'total_channels') }}</strong> {{ __('livewire.s.channels_with_your_current_plan') }}</p>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 bg-primary text-primary-foreground px-6 py-3 rounded-lg hover:bg-primary/90 transition font-medium">
                <i class="fa-solid fa-plus mr-2"></i>{{ __('livewire.s.create_chatbot') }}</button>
            <a href="{{ route('dashboard') }}"
                class="px-6 py-3 border rounded-lg hover:bg-muted/30 transition font-medium">{{ __('channels.s.cancel') }}</a>
        </div>
    </form>
</div>