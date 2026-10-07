<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ChannelController;

// Landing Page — harga dirender dari tabel plans (sumber kebenaran)
Route::get('/', function () {
    return view('welcome', [
        'plans' => App\Models\Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get(),
    ]);
});

// Documentation
Route::get('/docs/webhooks', function () {
    return view('docs.webhooks');
})->name('docs.webhooks');
Route::get('/docs/api', function () {
    return view('docs.api');
})->name('docs.api');

// API Routes
Route::prefix('api')->middleware(App\Http\Middleware\WidgetApiCors::class)->group(function () {
    // CORS preflight (the middleware answers it before the controller)
    Route::options('/chat', fn () => response()->noContent());

    Route::post('/chat', [App\Http\Controllers\Api\ChatController::class, 'chat'])
        ->middleware('throttle:chat');

    // Widget Config API - returns widget settings by slug
    // NOTE: inside prefix('api'), so the path must NOT repeat /api
    Route::options('/widget/{slug}/config', fn () => response()->noContent());

    Route::get('/widget/{slug}/config', function ($slug) {
        $widget = App\Models\Widget::where('slug', $slug)->first();

        if (!$widget) {
            return response()->json(['error' => 'Widget not found'], 404);
        }

        // Public visibility gate: only active widgets are served
        if (($widget->status ?? 'active') !== 'active' || !$widget->is_active) {
            return response()->json(['error' => 'Widget disabled', 'error_code' => 'widget_inactive'], 404);
        }

        // Domain Validation (Security) - shared with ChatOrchestrator
        $origin = request()->header('Origin') ?? request()->header('Referer');
        if (!app(App\Services\Chat\DomainAccessService::class)->isAllowed($widget->settings['allowed_domains'] ?? null, $origin)) {
            return response()->json(['error' => 'Domain not allowed'], 403);
        }

        $settings = $widget->settings ?? [];

        // T-12: default widget strings follow the widget OWNER's language
        // preference; explicit per-widget settings always win.
        $ownerLocale = $widget->user?->locale ?: 'id';
        $t = fn (string $key) => __($key, [], $ownerLocale);

        return response()->json([
            'widgetId' => $widget->slug,
            'title' => $widget->name,
            'subtitle' => $settings['subtitle'] ?? $t('widget.subtitle_online'),
            'greeting' => $settings['greeting'] ?? $t('widget.greeting_default'),
            'primaryColor' => $settings['color'] ?? '#6366f1',
            'position' => $settings['position'] ?? 'bottom-right',
            'placeholder' => $settings['placeholder'] ?? $t('widget.placeholder'),
            // Localized chrome strings for the embed script (T-12 step 7).
            'i18n' => [
                'poweredBy' => $t('widget.powered_by'),
                'typing' => $t('widget.typing'),
                'offline' => $t('widget.offline'),
                'prechatTitle' => $t('widget.prechat_title'),
                'prechatName' => $t('widget.prechat_name'),
                'prechatEmail' => $t('widget.prechat_email'),
                'prechatPhone' => $t('widget.prechat_phone'),
                'prechatSubmit' => $t('widget.prechat_submit'),
            ],
            'avatarType' => $settings['avatar_type'] ?? 'icon',
            'avatarIcon' => $settings['avatar_icon'] ?? 'robot',
            'avatarUrl' => $settings['avatar_url'] ?? '',
            'showBranding' => true,
            'allowedDomain' => $settings['allowed_domains'] ?? '',
            // AI model this widget chats with (settings override, default
            // falls back to the app-wide default model).
            'model' => $settings['model'] ?? config('services.openrouter.default_model'),
            // Pre-chat form (Strategy 3): widget shows it once per browser
            // before the visitor's first message.
            'leadForm' => [
                'enabled' => (bool) ($settings['lead_form_enabled'] ?? false),
                'requireName' => (bool) ($settings['lead_form_require_name'] ?? true),
                'requireEmail' => (bool) ($settings['lead_form_require_email'] ?? false),
                'requirePhone' => (bool) ($settings['lead_form_require_phone'] ?? false),
            ],
        ]);
    });

    // Midtrans Webhook (no CSRF, no auth)
    Route::post('/payment/notification', [App\Http\Controllers\PaymentController::class, 'webhook']);

    // WhatsApp Webhook from Fonnte (no CSRF, no auth)
    Route::post('/whatsapp/webhook/{device_id}', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);

    // Visitor "forget my conversation" (widget button): deletes the chat
    // session + messages when the caller proves ownership (signed sessionId
    // + IP/user-agent fingerprint). Path matches the /api/widget/* CSRF
    // exception and rides the same throttle as /api/chat.
    // OPTIONS companion required: without it the cross-origin preflight
    // hits the router's 405 (no CORS headers) and the browser cancels
    // the actual DELETE (net::ERR_FAILED) - same as /chat above.
    Route::options('/widget/session', fn () => response()->noContent());

    Route::delete('/widget/session', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'widgetId' => 'required|string|max:100',
            'sessionId' => 'required|string|max:200',
        ]);

        $widget = App\Models\Widget::where('slug', $data['widgetId'])->first();
        if (!$widget) {
            return response()->json(['success' => false, 'error' => 'Widget not found'], 404);
        }

        $sessions = app(App\Services\Chat\SessionIdService::class);
        if (!$sessions->isSigned($data['sessionId'])) {
            return response()->json(['success' => false, 'error' => 'Invalid session', 'error_code' => 'invalid_session'], 403);
        }

        $session = App\Models\ChatSession::where('widget_id', $widget->id)
            ->where('visitor_uuid', $data['sessionId'])
            ->first();

        // Idempotent: nothing stored yet (or already forgotten).
        if (!$session) {
            return response()->json(['success' => true]);
        }

        // Same fingerprint gate as continuing a conversation: only the
        // original visitor (IP + user agent) may wipe it.
        if ($session->ip_address !== App\Support\HttpClientIp::get()
            || $session->user_agent !== $request->userAgent()) {
            return response()->json(['success' => false, 'error' => 'Forbidden', 'error_code' => 'fingerprint_mismatch'], 403);
        }

        $session->delete();

        return response()->json(['success' => true]);
    })->middleware('throttle:chat');

    // Visitor conversation auto-close (inactivity timeout or the manual
    // "Tutup percakapan" button): marks the session ended and generates the
    // AI summary synchronously (production has no queue worker). Same
    // ownership gate as the DELETE above (signed sessionId + IP/UA
    // fingerprint); idempotent when already ended or never started.
    // OPTIONS companion: the widget calls this cross-origin (bmp.net.id ->
    // cekat.biz.id), so the preflight must be answered by WidgetApiCors
    // instead of the router 405, or the POST is cancelled by the browser.
    Route::options('/widget/session/close', fn () => response()->noContent());

    Route::post('/widget/session/close', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'widgetId' => 'required|string|max:100',
            'sessionId' => 'required|string|max:200',
        ]);

        $widget = App\Models\Widget::where('slug', $data['widgetId'])->first();
        if (!$widget) {
            return response()->json(['success' => false, 'error' => 'Widget not found'], 404);
        }

        $sessions = app(App\Services\Chat\SessionIdService::class);
        if (!$sessions->isSigned($data['sessionId'])) {
            return response()->json(['success' => false, 'error' => 'Invalid session', 'error_code' => 'invalid_session'], 403);
        }

        $session = App\Models\ChatSession::where('widget_id', $widget->id)
            ->where('visitor_uuid', $data['sessionId'])
            ->first();

        // Nothing to close: the visitor never started a conversation.
        if (!$session) {
            return response()->json(['success' => true, 'noop' => true, 'summary' => null]);
        }

        if ($session->ip_address !== App\Support\HttpClientIp::get()
            || $session->user_agent !== $request->userAgent()) {
            return response()->json(['success' => false, 'error' => 'Forbidden', 'error_code' => 'fingerprint_mismatch'], 403);
        }

        if ($session->status === 'ended') {
            return response()->json(['success' => true, 'summary' => $session->summary]);
        }

        $session->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        $summary = null;
        if ($session->messages()->exists()) {
            \App\Jobs\GenerateChatSummary::dispatchSync($session);
            $summary = $session->fresh()->summary;
        }

        return response()->json(['success' => true, 'summary' => $summary]);
    })->middleware('throttle:chat');
});

// Public read API v1 - server-to-server, bearer API key (no session, no
// CORS: this is NOT a browser endpoint). Auth in ApiKeyAuth (alias
// api.key) binds the key's owner; throttle keys on the API key id.
Route::prefix('api/v1')->middleware(['api.key', 'throttle:api-key'])->group(function () {
    Route::get('/leads', [App\Http\Controllers\Api\V1\LeadController::class, 'index']);
    Route::get('/leads/{id}', [App\Http\Controllers\Api\V1\LeadController::class, 'show']);
    Route::get('/sessions', [App\Http\Controllers\Api\V1\SessionController::class, 'index']);
    Route::get('/sessions/{id}/messages', [App\Http\Controllers\Api\V1\SessionController::class, 'messages']);
    Route::get('/widgets', [App\Http\Controllers\Api\V1\WidgetController::class, 'index']);
    Route::get('/stats', [App\Http\Controllers\Api\V1\StatsController::class, 'index']);
});

// Suspended/Banned Account Info Page
Route::get('/account/suspended', function () {
    $user = auth()->user();
    $type = request('type', 'suspended');
    $reason = $user->suspended_reason ?? null;
    return view('auth.suspended', compact('type', 'reason'));
})->middleware('auth')->name('account.suspended');

// User Dashboard Routes (Protected by auth + status check)
Route::middleware(['auth', 'user.status'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Channel CRUD (Web Widget = channel; legacy /chatbots URLs redirect below)
    Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::get('/channels/create', [ChannelController::class, 'create'])->name('channels.create');
    Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('/channels/{channel}/edit', [ChannelController::class, 'edit'])->name('channels.edit');
    Route::get('/channels/{channel}/edit/{tab?}', [ChannelController::class, 'edit'])->name('channels.edit.tab');
    Route::put('/channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');
    Route::delete('/channels/{channel}', [ChannelController::class, 'destroy'])->name('channels.destroy');
    Route::post('/channels/{channel}/unlink-agent', [ChannelController::class, 'unlinkAgent'])->name('channels.unlink-agent');
    Route::post('/channels/{channel}/activate', [ChannelController::class, 'activate'])->name('channels.activate');

    // Legacy redirects (bookmarks / old embed docs)
    Route::redirect('/chatbots/create', '/channels/create', 301);
    Route::redirect('/chatbots/{any}', '/channels', 301)->where('any', '.*');
    Route::redirect('/chatbots', '/channels', 301);


    // AI Agents
    Route::get('/agents', [App\Http\Controllers\AiAgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/create', [App\Http\Controllers\AiAgentController::class, 'create'])->name('agents.create');
    Route::post('/agents', [App\Http\Controllers\AiAgentController::class, 'store'])->name('agents.store');
    Route::get('/agents/{agent}/edit', [App\Http\Controllers\AiAgentController::class, 'edit'])->name('agents.edit');
    Route::get('/agents/{agent}/knowledge', [App\Http\Controllers\AiAgentController::class, 'knowledge'])->name('agents.knowledge');
    Route::put('/agents/{agent}', [App\Http\Controllers\AiAgentController::class, 'update'])->name('agents.update');
    Route::delete('/agents/{agent}', [App\Http\Controllers\AiAgentController::class, 'destroy'])->name('agents.destroy');
    Route::post('/agents/{agent}/toggle-status', [App\Http\Controllers\AiAgentController::class, 'toggleStatus'])->name('agents.toggle-status');
    Route::post('/agents/{agent}/attach-default-widget', [App\Http\Controllers\AiAgentController::class, 'attachDefaultWidget'])->name('agents.attach-default-widget');


    // User Account Settings
    Route::get('/settings', function () {
        return view('user.settings');
    })->name('settings');

    Route::put('/settings/profile', function (App\Http\Requests\UpdateProfileRequest $request) {
        auth()->user()->update($request->validated());
        return back()->with('success', __('settings.profile_updated'));
    })->name('settings.update-profile');

    Route::put('/settings/password', function (App\Http\Requests\UpdatePasswordRequest $request) {
        auth()->user()->update(['password' => Hash::make($request->validated()['password'])]);

        try {
            \App\Services\Email\EmailSender::send(
                auth()->user()->email,
                new \App\Mail\PasswordChanged(auth()->user(), request()->ip()),
                'password-changed',
                ['user_id' => auth()->id()]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send password changed alert', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Password berhasil diubah!');
    })->name('settings.update-password');

    Route::put('/settings/email', [\App\Http\Controllers\SettingsController::class, 'updateEmail'])
        ->name('settings.update-email');
    Route::get('/settings/email/confirm', [\App\Http\Controllers\SettingsController::class, 'confirmEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('settings.email.confirm');

    // User Integration (view embed code)
    Route::get('/integration', function () {
        $widgets = auth()->user()->widgets()->get();
        return view('user.integration', compact('widgets'));
    })->name('integration');

    // API Keys - manage personal keys for the public read API (/api/v1)
    Route::get('/settings/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'index'])
        ->middleware('plan.feature:api_access')
        ->name('api-keys.index');
    Route::post('/settings/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'store'])
        ->middleware('plan.feature:api_access')
        ->name('api-keys.store');
    Route::delete('/settings/api-keys/{key}', [\App\Http\Controllers\ApiKeyController::class, 'destroy'])
        ->middleware('plan.feature:api_access')
        ->name('api-keys.destroy');

    // Chat History
    Route::get('/chats', [App\Http\Controllers\ChatHistoryController::class, 'index'])->name('chats.index');
    Route::get('/chats/export', [App\Http\Controllers\ChatHistoryController::class, 'export'])->name('chats.export');
    Route::get('/chats/{id}', [App\Http\Controllers\ChatHistoryController::class, 'show'])->name('chats.show');
    Route::delete('/chats/{id}', [App\Http\Controllers\ChatHistoryController::class, 'destroy'])->name('chats.destroy');
    Route::post('/chats/{id}/summary', [App\Http\Controllers\ChatHistoryController::class, 'generateSummary'])->name('chats.summary');

    // Leads (Pro+ feature)
    Route::get('/leads', [App\Http\Controllers\LeadController::class, 'index'])
        ->middleware('plan.feature:leads')
        ->name('leads.index');
    Route::get('/leads/export', [App\Http\Controllers\LeadController::class, 'export'])
        ->middleware('plan.feature:leads')
        ->name('leads.export');
    // Alias: a lead IS a chat session - same scoped deletion as /chats/{id}
    Route::delete('/leads/{id}', [App\Http\Controllers\ChatHistoryController::class, 'destroy'])->name('leads.destroy');

    // Billing
    Route::get('/billing', function () {
        $user = auth()->user()->load('plan');
        $plans = \App\Models\Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
        return view('user.billing', compact('user', 'plans'));
    })->name('billing');

    // Payment Routes (Midtrans)
    Route::post('/billing/pay/{plan}', [App\Http\Controllers\PaymentController::class, 'createTransaction'])->name('billing.pay');
    Route::get('/billing/transactions/{transaction}/status', [App\Http\Controllers\PaymentController::class, 'transactionStatus'])->name('billing.transaction.status');
    Route::get('/payment/finish', [App\Http\Controllers\PaymentController::class, 'finish'])->name('payment.finish');
});

// Admin Routes - PROTECTED: Only admin users can access
Route::middleware(['auth', 'is.admin', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    // Landing Page Chatbot (Admin Only) - Standalone page with all tabs
    Route::get('/landing-chatbot', function () {
        // Get or create landing page widget
        $widget = \App\Models\Widget::firstOrCreate(
            ['slug' => 'landing-page-default'],
            [
                'user_id' => 1, // Admin user
                'name' => 'Landing Page Widget',
                'is_active' => true,
                'settings' => [
                    'model' => config('services.openrouter.default_model'),
                ],
            ]
        );

        return view('admin.landing-chatbot', ['widget' => $widget]);
    })->name('admin.landing-chatbot');

    // Transaction Monitor
    Route::get('/transactions', function () {
        return view('admin.transactions');
    })->name('admin.transactions');

    // User Manager
    Route::get('/users', function () {
        return view('admin.users');
    })->name('admin.users');

    // Plan Manager
    Route::get('/plans', function () {
        return view('admin.plans');
    })->name('admin.plans');

    Route::post('/landing-chatbot/update-model', function () {
        $widget = \App\Models\Widget::where('slug', 'landing-page-default')->first();
        if ($widget) {
            $settings = $widget->settings ?? [];
            $settings['model'] = request('model_id');
            $widget->update(['settings' => $settings]);
        }
        return redirect()->back()->with('success', 'Model updated successfully!');
    })->name('admin.landing-chatbot.update-model');

    Route::post('/landing-chatbot/update-lead', function () {
        $widget = \App\Models\Widget::where('slug', 'landing-page-default')->first();
        if ($widget) {
            $settings = $widget->settings ?? [];

            // Strategy 1: Prompt Engineering
            $settings['lead_prompt_enabled'] = request()->has('lead_prompt_enabled');
            $settings['lead_ask_name'] = request()->has('lead_ask_name');
            $settings['lead_ask_email'] = request()->has('lead_ask_email');
            $settings['lead_ask_phone'] = request()->has('lead_ask_phone');

            // Strategy 2: Trigger System
            $settings['lead_trigger_enabled'] = request()->has('lead_trigger_enabled');
            $settings['lead_trigger_after_message'] = (int) request('lead_trigger_after_message', 3);
            $settings['lead_trigger_keywords'] = request('lead_trigger_keywords', '');

            // Strategy 3: Pre-chat Form
            $settings['lead_form_enabled'] = request()->has('lead_form_enabled');
            $settings['lead_form_require_name'] = request()->has('lead_form_require_name');
            $settings['lead_form_require_email'] = request()->has('lead_form_require_email');
            $settings['lead_form_require_phone'] = request()->has('lead_form_require_phone');

            // Email notification per channel (no FormRequest on this admin
            // route; the listener falls back to the owner email when the
            // stored address is empty or invalid).
            $notifEnabled = request()->has('lead_email_notif_enabled');
            $settings['lead_email_notif_enabled'] = $notifEnabled;
            $settings['lead_email_notif'] = trim((string) request('lead_email_notif', ''));
            $settings['lead_email_new_lead'] = $notifEnabled ? request()->has('lead_email_new_lead') : true;

            $widget->update(['settings' => $settings]);
        }
        return redirect()->back()->with('success', 'Lead collection settings saved!');
    })->name('admin.landing-chatbot.update-lead');

    // Admin Integration (upload plugin, instructions)
    Route::get('/integration', function () {
        return view('admin.integration');
    })->name('admin.integration');

    // AI Models & Tiers (LLM catalogue + tier mapping used by ModelResolver)
    Route::get('/models', function () {
        return view('admin.models');
    })->name('admin.models');

    // Email Center: mail log, newsletter, pengumuman, template
    Route::get('/email-center', function () {
        return view('admin.email-center');
    })->name('admin.email-center');

    Route::get('/settings', \App\Livewire\Admin\SystemSettings::class)->name('admin.settings');
    Route::get('/billing', \App\Livewire\Admin\BillingMonitoring::class)->name('admin.billing');
    Route::get('/chat-inbox', \App\Livewire\Admin\ChatInbox::class)->name('admin.chat-inbox');
});

// WhatsApp Routes (User) - Pro+ feature (plan.feature gate)
    Route::middleware(['auth', 'user.status', 'plan.feature:whatsapp'])->prefix('whatsapp')->group(function () {
    Route::get('/', [App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/create', [App\Http\Controllers\WhatsAppController::class, 'create'])->name('whatsapp.create');
    Route::get('/{device}/connect', [App\Http\Controllers\WhatsAppController::class, 'connect'])->name('whatsapp.connect');
    Route::get('/{device}/qr', [App\Http\Controllers\WhatsAppController::class, 'getQR'])->name('whatsapp.qr');
    Route::get('/{device}/status', [App\Http\Controllers\WhatsAppController::class, 'refreshStatus'])->name('whatsapp.status');
    Route::put('/{device}', [App\Http\Controllers\WhatsAppController::class, 'update'])->name('whatsapp.update');
    Route::post('/{device}/disconnect', [App\Http\Controllers\WhatsAppController::class, 'disconnect'])->name('whatsapp.disconnect');
    Route::delete('/{device}', [App\Http\Controllers\WhatsAppController::class, 'destroy'])->name('whatsapp.destroy');
    Route::get('/{device}/messages', [App\Http\Controllers\WhatsAppController::class, 'messages'])->name('whatsapp.messages');
});

// WhatsApp Admin Settings
Route::middleware(['auth', 'is.admin', 'verified'])->prefix('admin')->group(function () {
    Route::get('/whatsapp', App\Livewire\Admin\WhatsAppSettings::class)->name('admin.whatsapp');
});

// Auth Routes
require __DIR__ . '/auth.php';

