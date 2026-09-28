<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plan-based feature gate for user pages (Lead Collection, WhatsApp).
 * Free/Starter users get a lock page styled like the channel Lead tab
 * feature lock; non-GET requests are redirected to the feature's index.
 */
class PlanFeatureGate
{
    protected array $features = [
        'leads' => [
            'route' => 'leads.index',
            'name' => 'Lead Collection',
            'description' => 'Fitur Lead Collection tersedia untuk paket Pro ke atas. Upgrade paket Anda untuk melihat dan mengekspor leads dari percakapan chat.',
        ],
        'whatsapp' => [
            'route' => 'whatsapp.index',
            'name' => 'WhatsApp Gateway',
            'description' => 'WhatsApp Gateway hanya tersedia untuk paket Pro ke atas. Upgrade paket Anda untuk menghubungkan nomor WhatsApp bisnis Anda.',
        ],
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = auth()->user();

        $allowed = isset($this->features[$feature])
            ? $user && app(\App\Services\Billing\PlanLimitService::class)->check($user, $feature)['allowed']
            : true;

        if (! $allowed) {
            if ($request->isMethod('GET')) {
                return response()->view('user.plan-locked', [
                    'featureName' => $this->features[$feature]['name'] ?? 'Fitur',
                    'description' => $this->features[$feature]['description'] ?? 'Upgrade plan Anda untuk mengakses fitur ini.',
                ]);
            }

            return redirect()->route($this->features[$feature]['route'] ?? 'dashboard');
        }

        return $next($request);
    }
}
