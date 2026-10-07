<div class="max-w-4xl">
    <div class="bg-card rounded-xl border p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">{{ __('channels.s.webhook_configuration') }}</h3>
        <p class="text-muted-foreground text-sm mb-6">
            {{ __('channels.s.configure_a_webhook_to_receive_real_time_updates') }}
        </p>

        <form action="{{ route('channels.update', ['channel' => $chatbot->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab" value="webhook">

            <div class="grid gap-6">
                {{-- Webhook URL --}}
                <div class="grid gap-2">
                    <label for="webhook_url" class="text-sm font-medium">{{ __('channels.s.webhook_url') }}</label>
                    <input type="url" id="webhook_url" name="webhook_url"
                        value="{{ $chatbot->settings['webhook_url'] ?? '' }}"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="https://your-website.com/api/webhook">
                    <p class="text-xs text-muted-foreground">
                        {{ __('channels.s.we_will_send_a_post_request_to_this_url_when_an') }}
                    </p>
                </div>

                {{-- Webhook Secret --}}
                <div class="grid gap-2">
                    <label for="webhook_secret" class="text-sm font-medium">{{ __('channels.s.secret_key') }}</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="password" id="webhook_secret" name="webhook_secret"
                                value="{{ $chatbot->settings['webhook_secret'] ?? '' }}"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('channels.s.generate_a_strong_secret_key') }}">
                            <button type="button" onclick="toggleSecret()"
                                class="absolute right-3 top-2.5 text-muted-foreground hover:text-foreground">
                                <i class="fa-solid fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                        <button type="button" onclick="generateSecret()" 
                                class="bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 px-4 rounded-md text-sm font-medium transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> {{ __('channels.s.generate') }}
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Use this key to verify the <code>X-Cekat-Signature</code> header in your backend.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 rounded-md font-medium text-sm transition-colors">
                    {{ __('channels.s.save_webhook_settings') }}
                </button>
            </div>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h4 class="font-semibold text-blue-900 mb-2">{{ __('channels.s.how_it_works') }}</h4>
        <ul class="list-disc list-inside text-sm text-blue-800 space-y-2">
            <li>{{ __('channels.s.when_ai_detects_an_intent_e_g_save_lead_it_trigg') }}</li>
            <li>{{ __('channels.s.we_send_a_json_payload_containing_the_action_and') }}</li>
            <li>{{ __('channels.s.you_should_verify_the_request_using_the_secret_k') }}</li>
            <li>{{ __('channels.s.read_the') }} <a href="{{ route('docs.webhooks') }}" class="underline font-bold"
                    target="_blank">{{ __('admin.s.documentation') }}</a> {{ __('channels.s.for_payload_examples') }}</li>
        </ul>
    </div>
</div>

<script>
    function toggleSecret() {
        const input = document.getElementById('webhook_secret');
        const icon = document.getElementById('eye-icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function generateSecret() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let result = '';
        for (let i = 0; i < 40; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const input = document.getElementById('webhook_secret');
        input.value = result;
        input.type = 'text'; // Show the generated key
        
        // Update icon state
        const icon = document.getElementById('eye-icon');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
</script>
