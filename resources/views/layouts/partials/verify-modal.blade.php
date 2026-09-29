{{-- Blocking email-verification modal (OTP). Shown to every logged-in,
     unverified user on all dashboard pages. Cannot be dismissed. --}}
@if(auth()->check() && !auth()->user()->email_verified_at)
    @php
        $otpLive = app(\App\Services\Auth\EmailOtpService::class)->hasLiveCode(auth()->user());
    @endphp
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div x-data="verifyOtpModal({{ $otpLive ? 'sent' : 'idle' }})" x-init="init()"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background/95 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="verify-otp-title">
        <div class="w-full max-w-md rounded-2xl border border-border bg-card shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-8 pt-8 pb-6 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/15 text-3xl">🔐
                </div>
                <h2 id="verify-otp-title" class="text-xl font-bold text-white">Verifikasi Email Anda</h2>
            </div>

            <div class="px-8 py-6">
                <p class="text-sm text-muted-foreground leading-relaxed mb-1">
                    Kami telah mengirim <strong class="text-foreground">kode verifikasi 6 digit</strong> ke:
                </p>
                <p class="text-sm font-semibold text-foreground break-all mb-4">
                    {{ auth()->user()->email }}
                </p>

                @if($errors->any())
                    <div class="mb-4 rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if(session('otp_success'))
                    <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
                        {{ session('otp_success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('verification.verify') }}">
                    @csrf
                    <label for="otp-code" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Masukkan Kode Verifikasi
                    </label>
                    <input id="otp-code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}"
                        maxlength="6" autocomplete="one-time-code" required autofocus
                        placeholder="••••••"
                        class="mb-4 w-full rounded-lg border border-input bg-background px-4 py-3 text-center text-2xl font-bold tracking-[0.5em] focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                    <button type="submit" :disabled="submitting" x-show="!verified"
                        class="w-full rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                        <span x-show="!submitting">Verifikasi Sekarang</span>
                        <span x-show="submitting">Memeriksa…</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('verification.resend') }}" class="mt-3" x-ref="resendForm">
                    @csrf
                    <button type="submit" x-show="!verified"
                        :disabled="resendState !== 'idle'"
                        class="w-full rounded-lg border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:bg-muted disabled:opacity-60"
                        x-text="resendState === 'idle'
                            ? 'Kirim Ulang Kode'
                            : (resendState === 'sending' ? 'Mengirim…' : 'Kode terkirim — kirim ulang dalam ' + countdown + 's')">
                    </button>
                </form>

                <p class="mt-4 text-center text-xs text-muted-foreground">
                    Kode berlaku 5 menit · periksa folder spam bila tidak terlihat
                </p>

                <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                    @csrf
                    <button type="submit" class="text-xs text-muted-foreground underline underline-offset-2 hover:text-foreground">
                        Keluar dan daftar ulang dengan email lain
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function verifyOtpModal(initialState) {
            return {
                resendState: initialState,
                countdown: 60,
                submitting: false,
                verified: false,
                timer: null,

                init() {
                    const codeInput = document.getElementById('otp-code');
                    if (codeInput) codeInput.focus();

                    if (this.resendState === 'idle') {
                        this.sendCode(true);
                    } else {
                        this.startCountdown();
                    }
                },

                sendCode(auto) {
                    this.resendState = 'sending';
                    const token = document.querySelector('meta[name="csrf-token"]');
                    fetch(@json(route('verification.resend')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token ? token.content : '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    }).then(() => {
                        // Redirect back (200/302 followed) or throttled (429) -
                        // either way the next manual resend is allowed later.
                        this.resendState = 'sent';
                        this.startCountdown();
                    }).catch(() => {
                        this.resendState = 'sent';
                        this.startCountdown();
                    });
                },

                startCountdown() {
                    this.countdown = 60;
                    if (this.timer) clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(this.timer);
                            this.resendState = 'idle';
                        }
                    }, 1000);
                },
            };
        }
    </script>
@endif
