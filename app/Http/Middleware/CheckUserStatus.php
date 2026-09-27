<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     * Block access for suspended or banned users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $status = $user->status ?? 'active';

            // Allow access to logout and suspended info page
            if ($request->routeIs('logout') || $request->routeIs('account.suspended')) {
                return $next($request);
            }

            // Redirect suspended/banned users to info page
            if ($status === 'suspended') {
                return redirect()->route('account.suspended', ['type' => 'suspended']);
            }

            if ($status === 'banned') {
                return redirect()->route('account.suspended', ['type' => 'banned']);
            }

            // Lazy plan expiry: downgrade expired paid users to free
            // (and deactivate their channels) on their next request/login.
            if (\App\Services\Billing\PlanExpiryService::downgrade($user)) {
                $channels = $user->widgets()->select('id', 'status')->get();

                if ($channels->isNotEmpty()
                    && $channels->where('status', 'active')->isEmpty()
                    && $request->isMethod('get')
                    && ! $request->routeIs('channels.*')) {
                    return redirect()->route('channels.index')->with('info', 'Masa aktif langganan Anda telah berakhir. Akun Anda kini menggunakan paket Free dan semua channel dinonaktifkan — silakan aktifkan kembali satu channel untuk mulai digunakan.');
                }
            }
        }

        return $next($request);
    }
}
