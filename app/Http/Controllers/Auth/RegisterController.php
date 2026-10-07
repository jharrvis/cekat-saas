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
            'plan_id' => app(\App\Services\Billing\PlanLimitService::class)->defaultPlan()?->id,
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
            'company_name' => '', // T-15: jangan isi nama pemilik sebagai nama bisnis; pengguna mengisinya di Info Bisnis
            'persona_name' => 'AI Assistant',
            'persona_tone' => 'friendly',
        ]);

        // Log the user in
        Auth::login($user);

        // Send the OTP verification code (login is allowed; the dashboard
        // shows a blocking verify modal until the code is entered)
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send verification code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Pendaftaran berhasil! Kami mengirim kode verifikasi 6 digit ke email Anda.');
    }
}
