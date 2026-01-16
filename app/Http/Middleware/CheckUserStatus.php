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
        }

        return $next($request);
    }
}
