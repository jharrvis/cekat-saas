<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(32)), // Random password
                    'role' => 'user',
                    'plan_tier' => 'starter',
                    'monthly_message_quota' => 100,
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
            }

            Auth::login($user);

            return redirect()->route('dashboard')->with('success', 'Welcome back!');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Failed to login with Google. Please try again.');
        }
    }
}
