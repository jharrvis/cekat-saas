{{-- Blocking email-verification modal (OTP). Shown to every logged-in,
     unverified user on all dashboard pages. Cannot be dismissed.
     Design: landing-page teal brand (brand.* scale in the dashboard
     tailwind config) on top of the HSL design tokens, so light and dark
     mode both work. Durations come from EmailOtpService constants via
     window globals - NOT as arguments to verifyOtpModal(), so the x-data
     expression stays exactly verifyOtpModal('idle'|'sent'). --}}
@if(auth()->check() && !auth()->user()->email_verified_at)
    @php
        $otpService = app(\App\Services\Auth\EmailOtpService::class);
        $otpLive = $otpService->hasLiveCode(auth()->user());
        $otpRemaining = $otpService->remaining(auth()->user());
    @endphp
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Alpine hasn't resolved x-show yet on first paint; cloak prevents the
         countdown chip and resend button from flashing together. --}}
    <style>[x-cloak] { display: none !important; }</style>
    <div x-data="verifyOtpModal('{{ $otpLive ? 'sent' : 'idle' }}')" x-init="init()"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background/95 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="verify-otp-title">
        <div class="w-full max-w-md rounded-2xl border border-border bg-card shadow-2xl overflow-hidden">
            {{-- brand accent bar --}}
            <div class="h-1 bg-gradient-to-r from-brand-500 via-cyan-400 to-brand-600"></div>

            {{-- header --}}
            <div class="px-8 pt-8 pb-5 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-600 dark:text-brand-400">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l7.5 3.6v4.9c0 4.6-3.1 8.4-7.5 9.5-4.4-1.1-7.5-4.9-7.5-9.5V6.6L12 3z" />
                        <path d="M9 12l2 2 4-4" />
                    </svg>
                </div>
                <h2 id="verify-otp-title" class="text-xl font-bold">{{ __('general.s.verifikasi_email_anda') }}</h2>
                <p class="mt-1.5 text-sm text-muted-foreground">{{ __('general.s.masukkan_kode_6_digit_yang_kami_kirim_ke') }}</p>
                <p class="mt-3 inline-flex max-w-full items-center gap-2 rounded-full border border-border bg-muted px-3.5 py-1.5">
                    <i class="fa-solid fa-envelope text-xs text-brand-600 dark:text-brand-400"></i>
                    <span class="truncate text-xs font-semibold">{{ auth()->user()->email }}</span>
                </p>
            </div>

            <div class="px-8 pb-7">
                @if($errors->any())
                    <div class="mb-4 flex items-start gap-2 rounded-xl border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                @if(session('otp_success'))
                    <div class="mb-4 flex items-start gap-2 rounded-xl border border-brand-500/40 bg-brand-500/10 px-4 py-3 text-sm font-medium text-brand-700 dark:text-brand-300">
                        <i class="fa-solid fa-circle-check mt-0.5 shrink-0"></i>
                        <span>{{ session('otp_success') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.verify') }}" x-ref="verifyForm"
                    @submit.prevent="submit()">
                    @csrf
                    <label for="otp-code" class="mb-3 block text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ __('general.s.masukkan_kode_verifikasi') }}
                    </label>

                    {{-- 6 segmented digit boxes: auto-advance, backspace back,
                         paste distributes, 6th digit auto-submits --}}
                    <div class="flex justify-between gap-2" @paste="onPaste($event)">
                        <input x-ref="otp0" id="otp-code" type="text" inputmode="numeric"
                            pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" required autofocus
                            aria-label="{{ __('general.s.digit_1') }}" @input="onInput($event, 0)" @keydown="onKeydown($event, 0)"
                            class="h-14 w-full rounded-xl border border-input bg-background text-center text-2xl font-bold caret-brand-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <input x-ref="otp1" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                            autocomplete="one-time-code" required aria-label="{{ __('general.s.digit_2') }}"
                            @input="onInput($event, 1)" @keydown="onKeydown($event, 1)"
                            class="h-14 w-full rounded-xl border border-input bg-background text-center text-2xl font-bold caret-brand-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <input x-ref="otp2" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                            autocomplete="one-time-code" required aria-label="{{ __('general.s.digit_3') }}"
                            @input="onInput($event, 2)" @keydown="onKeydown($event, 2)"
                            class="h-14 w-full rounded-xl border border-input bg-background text-center text-2xl font-bold caret-brand-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <input x-ref="otp3" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                            autocomplete="one-time-code" required aria-label="{{ __('general.s.digit_4') }}"
                            @input="onInput($event, 3)" @keydown="onKeydown($event, 3)"
                            class="h-14 w-full rounded-xl border border-input bg-background text-center text-2xl font-bold caret-brand-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <input x-ref="otp4" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                            autocomplete="one-time-code" required aria-label="{{ __('general.s.digit_5') }}"
                            @input="onInput($event, 4)" @keydown="onKeydown($event, 4)"
                            class="h-14 w-full rounded-xl border border-input bg-background text-center text-2xl font-bold caret-brand-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <input x-ref="otp5" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                            autocomplete="one-time-code" required aria-label="{{ __('general.s.digit_6') }}"
                            @input="onInput($event, 5)" @keydown="onKeydown($event, 5)"
                            class="h-14 w-full rounded-xl border border-input bg-background text-center text-2xl font-bold caret-brand-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>
                    <input type="hidden" name="code" :value="digits.join('')">

                    <button type="submit" :disabled="!complete || submitting"
                        class="mt-5 w-full rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="!submitting">{{ __('general.s.verifikasi_sekarang') }}</span>
                        <span x-show="submitting" class="inline-flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            {{ __('general.s.memeriksa') }}
                        </span>
                    </button>
                </form>

                {{-- live code-expiry countdown (EmailOtpService::TTL) --}}
                <div class="mt-3 text-center text-xs">
                    <span x-show="expiry > 0 && !expired" class="text-muted-foreground">
                        <i class="fa-regular fa-clock mr-1"></i>{{ __('emails.s.kode_berlaku') }}
                        <span class="font-mono font-semibold tabular-nums" x-text="fmt(expiry)"></span>
                    </span>
                    <span x-show="expired" class="font-medium text-amber-600 dark:text-amber-400">
                        {{ __('general.s.kode_kedaluwarsa_klik_kirim_ulang_kode_untuk_kod') }}
                    </span>
                </div>

                {{-- resend cooldown chip with progress bar (EmailOtpService::RESEND_DELAY) --}}
                <div class="mt-4" x-show="resendState === 'sent' && !expired" x-cloak>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-500/40 bg-brand-500/10 px-4 py-3">
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 dark:text-brand-300">
                            <i class="fa-solid fa-rotate text-xs"></i>
                            {{ __('general.s.kode_terkirim_kirim_ulang_dalam') }}
                        </span>
                        <span class="font-mono text-sm font-bold tabular-nums text-brand-700 dark:text-brand-300"
                            x-text="fmt(countdown)"></span>
                    </div>
                    <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-brand-500 transition-[width] duration-1000 ease-linear"
                            :style="'width:' + Math.max(0, Math.min(100, countdown / OTP_RESEND_DELAY * 100)) + '%'"></div>
                    </div>
                </div>

                {{-- resend action (native POST -> full reload keeps otp_success flash --}}
                <form method="POST" action="{{ route('verification.resend') }}" class="mt-3"
                    x-show="resendState !== 'sent' || expired" x-cloak>
                    @csrf
                    <button type="submit" :disabled="resendState !== 'idle'"
                        class="w-full rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="resendState === 'idle'">{{ __('general.s.kirim_ulang_kode') }}</span>
                        <span x-show="resendState === 'sending'">{{ __('general.s.mengirim') }}</span>
                    </button>
                </form>

                <p class="mt-4 text-center text-xs text-muted-foreground">
                    {{ __('general.s.tidak_menerima_email_periksa_folder') }} <strong>{{ __('general.s.spam') }}</strong> {{ __('general.s.bila_tidak_terlihat') }}
                </p>

                <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-border pt-4 text-center">
                    @csrf
                    <button type="submit" class="text-xs text-muted-foreground underline underline-offset-2 transition hover:text-foreground">
                        {{ __('general.s.keluar_dan_daftar_ulang_dengan_email_lain') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.OTP_RESEND_DELAY = @json(\App\Services\Auth\EmailOtpService::RESEND_DELAY);
        window.OTP_TTL = @json(\App\Services\Auth\EmailOtpService::TTL);
        window.OTP_INITIAL_EXPIRY = @json($otpRemaining);

        function verifyOtpModal(initialState) {
            return {
                resendState: initialState, // idle | sending | sent
                countdown: window.OTP_RESEND_DELAY,
                expiry: window.OTP_INITIAL_EXPIRY,
                expired: false,
                submitting: false,
                digits: ['', '', '', '', '', ''],
                timer: null,
                expiryTimer: null,

                get complete() {
                    return this.digits.join('').length === 6;
                },

                init() {
                    const firstEmpty = this.digits.findIndex((d) => !d);
                    this.focusBox(firstEmpty === -1 ? 0 : firstEmpty);

                    if (window.OTP_INITIAL_EXPIRY > 0) {
                        this.startExpiry(window.OTP_INITIAL_EXPIRY);
                    }

                    if (this.resendState === 'idle') {
                        this.sendCode(true);
                    } else {
                        this.startCountdown();
                    }
                },

                fmt(total) {
                    const s = Math.max(0, total | 0);
                    return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                },

                focusBox(i) {
                    const el = this.$refs['otp' + i];
                    if (el) {
                        el.focus();
                        el.select();
                    }
                },

                setBox(i, value) {
                    this.digits[i] = value;
                    const el = this.$refs['otp' + i];
                    if (el) el.value = value;
                },

                onInput(e, i) {
                    const v = (e.target.value || '').replace(/\D/g, '').slice(-1);
                    this.digits[i] = v;
                    if (e.target.value !== v) e.target.value = v;
                    if (v && i < 5) this.focusBox(i + 1);
                    if (this.complete) this.submit();
                },

                onKeydown(e, i) {
                    if (e.key === 'Backspace' && !this.digits[i] && i > 0) {
                        this.setBox(i - 1, '');
                        this.focusBox(i - 1);
                    } else if (e.key === 'ArrowLeft' && i > 0) {
                        this.focusBox(i - 1);
                    } else if (e.key === 'ArrowRight' && i < 5) {
                        this.focusBox(i + 1);
                    }
                },

                onPaste(e) {
                    const text = ((e.clipboardData || window.clipboardData).getData('text') || '')
                        .replace(/\D/g, '').slice(0, 6);
                    if (!text) return;
                    e.preventDefault();
                    for (let i = 0; i < 6; i++) this.setBox(i, text[i] || '');
                    this.focusBox(Math.min(text.length, 5));
                    if (this.complete) this.submit();
                },

                submit() {
                    if (this.submitting || !this.complete) return;
                    this.submitting = true;
                    this.$refs.verifyForm.submit();
                },

                sendCode(auto) {
                    if (this.resendState === 'sending') return;
                    this.resendState = 'sending';
                    const token = document.querySelector('meta[name="csrf-token"]');
                    fetch(@json(route('verification.resend')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token ? token.content : '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    }).then((res) => {
                        if (!res.ok) {
                            // throttled / CSRF / server error - keep the button
                            // usable so the user can retry manually.
                            this.resendState = 'idle';
                            return;
                        }
                        this.resendState = 'sent';
                        this.startCountdown();
                        this.startExpiry(window.OTP_TTL);
                    }).catch(() => {
                        this.resendState = 'idle';
                    });
                },

                startCountdown() {
                    this.countdown = window.OTP_RESEND_DELAY;
                    if (this.timer) clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(this.timer);
                            this.timer = null;
                            this.resendState = 'idle';
                        }
                    }, 1000);
                },

                startExpiry(seconds) {
                    this.expiry = seconds;
                    this.expired = false;
                    if (this.expiryTimer) clearInterval(this.expiryTimer);
                    if (seconds <= 0) return;
                    this.expiryTimer = setInterval(() => {
                        this.expiry--;
                        if (this.expiry <= 0) {
                            clearInterval(this.expiryTimer);
                            this.expiryTimer = null;
                            this.expired = true;
                            // the code is dead now - free the resend action
                            if (this.timer) {
                                clearInterval(this.timer);
                                this.timer = null;
                            }
                            this.resendState = 'idle';
                        }
                    }, 1000);
                },
            };
        }
    </script>
@endif
