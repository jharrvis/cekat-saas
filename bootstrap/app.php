<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // T-12: locale resolution runs for web pages and API routes alike.
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SetLocale::class);

        $middleware->validateCsrfTokens(except: [
            '/api/chat',
            '/api/payment/notification', // Midtrans webhook
            '/api/widget/*', // Widget config API
            '/api/whatsapp/webhook/*', // Fonnte WhatsApp webhook
            '/api/v1/*', // Public read API (bearer API key, no session/CSRF)
        ]);

        // Middleware aliases
        $middleware->alias([
            'user.status' => \App\Http\Middleware\CheckUserStatus::class,
            'is.admin' => \App\Http\Middleware\IsAdmin::class,
            'plan.feature' => \App\Http\Middleware\PlanFeatureGate::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/chat') || $request->is('api/widget/*') || $request->is('api/v1/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'rate_limited',
                    'error_code' => 'rate_limited',
                    'message' => __('api.rate_limited'),
                ], 429, $e->getHeaders());
            }
        });
    })->create();
