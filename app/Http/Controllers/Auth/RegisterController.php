<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'plan_tier' => 'starter', // Default plan
            'monthly_message_quota' => 100,
            'monthly_message_used' => 0,
        ]);

        // Create default widget for user
        $widget = $user->widgets()->create([
            'name' => $user->name . "'s Widget",
            'slug' => 'widget-' . $user->id . '-' . \Str::random(8),
            'is_active' => true,
        ]);

        // Create knowledge base for widget
        $widget->knowledgeBase()->create([
            'company_name' => $user->name,
            'persona_name' => 'AI Assistant',
            'persona_tone' => 'friendly',
        ]);

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to Cekat! Your account has been created successfully.');
    }
}
