<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Email Verification (OTP code, rendered as the blocking dashboard modal)
Route::get('/email/verify', [App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])
    ->middleware('auth')
    ->name('verification.notice');
Route::post('/email/verify', [App\Http\Controllers\Auth\EmailVerificationController::class, 'verifyCode'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.resend');

// Google OAuth Routes
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');
