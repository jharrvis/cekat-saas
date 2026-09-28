<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ChannelController;

// Landing Page
Route::get('/', function () {
    return view('welcome');
});

// Documentation
Route::get('/docs/webhooks', function () {
    return view('docs.webhooks');
})->name('docs.webhooks');

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

        return response()->json([
            'widgetId' => $widget->slug,
            'title' => $widget->name,
            'subtitle' => $settings['subtitle'] ?? 'Online • Reply cepat',
            'greeting' => $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?',
            'primaryColor' => $settings['color'] ?? '#6366f1',
            'position' => $settings['position'] ?? 'bottom-right',
            'placeholder' => $settings['placeholder'] ?? 'Ketik pesan...',
            'avatarType' => $settings['avatar_type'] ?? 'icon',
            'avatarIcon' => $settings['avatar_icon'] ?? 'robot',
            'avatarUrl' => $settings['avatar_url'] ?? '',
            'showBranding' => true,
            'allowedDomain' => $settings['allowed_domains'] ?? '',
        ]);
    });

    // Midtrans Webhook (no CSRF, no auth)
    Route::post('/payment/notification', [App\Http\Controllers\PaymentController::class, 'webhook']);

    // WhatsApp Webhook from Fonnte (no CSRF, no auth)
    Route::post('/whatsapp/webhook/{device_id}', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle']);
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


    // User Account Settings
    Route::get('/settings', function () {
        return view('user.settings');
    })->name('settings');

    Route::put('/settings/profile', function (App\Http\Requests\UpdateProfileRequest $request) {
        auth()->user()->update($request->validated());
        return back()->with('success', 'Profil berhasil diperbarui!');
    })->name('settings.update-profile');

    Route::put('/settings/password', function (App\Http\Requests\UpdatePasswordRequest $request) {
        auth()->user()->update(['password' => Hash::make($request->validated()['password'])]);
        return back()->with('success', 'Password berhasil diubah!');
    })->name('settings.update-password');

    // User Integration (view embed code)
    Route::get('/integration', function () {
        $widgets = auth()->user()->widgets()->get();
        return view('user.integration', compact('widgets'));
    })->name('integration');

    // Chat History
    Route::get('/chats', [App\Http\Controllers\ChatHistoryController::class, 'index'])->name('chats.index');
    Route::get('/chats/export', [App\Http\Controllers\ChatHistoryController::class, 'export'])->name('chats.export');
    Route::get('/chats/{id}', [App\Http\Controllers\ChatHistoryController::class, 'show'])->name('chats.show');
    Route::post('/chats/{id}/summary', [App\Http\Controllers\ChatHistoryController::class, 'generateSummary'])->name('chats.summary');

    // Leads (Pro+ feature)
    Route::get('/leads', [App\Http\Controllers\LeadController::class, 'index'])
        ->middleware('plan.feature:leads')
        ->name('leads.index');
    Route::get('/leads/export', [App\Http\Controllers\LeadController::class, 'export'])
        ->middleware('plan.feature:leads')
        ->name('leads.export');

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
    Route::get('/payment/finish', [App\Http\Controllers\PaymentController::class, 'finish'])->name('payment.finish');
});

// Admin Routes - PROTECTED: Only admin users can access
Route::middleware(['auth', 'is.admin'])->prefix('admin')->group(function () {
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
Route::middleware(['auth', 'is.admin'])->prefix('admin')->group(function () {
    Route::get('/whatsapp', App\Livewire\Admin\WhatsAppSettings::class)->name('admin.whatsapp');
});

// Auth Routes
require __DIR__ . '/auth.php';

