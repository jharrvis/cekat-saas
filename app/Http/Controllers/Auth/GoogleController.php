<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Update Google ID if not set
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }

                // Unverified account (incl. legacy users) - email the OTP now
                if (! $user->hasVerifiedEmail()
                    && ! app(\App\Services\Auth\EmailOtpService::class)->hasLiveCode($user)) {
                    try {
                        $user->sendEmailVerificationNotification();
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send verification code', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } else {
                // Create new user - email is NOT auto-verified: every new
                // account must enter the OTP sent to their inbox
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(32)), // Random password
                    'role' => 'user',
                    'plan_id' => app(\App\Services\Billing\PlanLimitService::class)->defaultPlan()?->id,
                    'monthly_message_used' => 0,
                ]);

                // Create default widget
                $widget = $user->widgets()->create([
                    'name' => $user->name . "'s Widget",
                    'slug' => 'widget-' . $user->id . '-' . Str::random(8),
                    'is_active' => true,
                ]);

                // Create knowledge base
                $widget->knowledgeBase()->create([
                    'company_name' => $user->name,
                    'persona_name' => 'AI Assistant',
                    'persona_tone' => 'friendly',
                ]);

                try {
                    $user->sendEmailVerificationNotification();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send verification code', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Auth::login($user);

            return redirect()->route('dashboard')->with('success', 'Welcome back!');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Failed to login with Google. Please try again.');
        }
    }
}
