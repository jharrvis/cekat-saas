<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('docs.s.dokumentasi_webhook_cekat_biz_id') }}</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Prism.js for Syntax Highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css" rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-white text-slate-900 font-sans antialiased selection:bg-slate-200 selection:text-slate-900">

    <!-- NAVBAR (Copied from welcome.blade.php) -->
    <nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-100" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 font-bold text-xl tracking-tight text-slate-900">
                <div class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                Cekat<span class="text-slate-400 font-normal">.biz.id</span>
            </a>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="/#features" class="hover:text-slate-900 transition">{{ __('docs.s.fitur') }}</a>
                <a href="/#pricing" class="hover:text-slate-900 transition">{{ __('docs.s.harga') }}</a>
                <a href="#" class="text-slate-900 font-semibold">{{ __('docs.s.docs') }}</a>
            </div>

            <div class="hidden md:flex items-center gap-4">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow-sm hover:shadow-md">
                        <i class="fa-solid fa-gauge mr-2"></i>{{ __('general.s.dashboard') }}
                    </a>
                @else
                    <a href="/login" class="text-sm font-medium text-slate-600 hover:text-slate-900">{{ __('docs.s.sign_in') }}</a>
                    <a href="/register"
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow-sm hover:shadow-md">
                        {{ __('docs.s.buat_chatbot') }} <i class="fa-solid fa-arrow-right ml-1 text-xs text-slate-400"></i>
                    </a>
                @endauth
            </div>

            <button @click="open = !open" class="md:hidden text-slate-600">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div x-show="open" class="md:hidden bg-white border-t border-slate-100 p-4 space-y-4 shadow-lg">
            <a href="/" class="block text-slate-600 font-medium">{{ __('docs.s.home') }}</a>
            <a href="/#features" class="block text-slate-600 font-medium">{{ __('docs.s.fitur') }}</a>
            <a href="/#pricing" class="block text-slate-600 font-medium">{{ __('docs.s.harga') }}</a>
        </div>
    </nav>

    <!-- HEADER SECTION -->
    <div class="pt-32 pb-12 bg-slate-50 border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-5xl font-bold tracking-tight text-slate-900 mb-6">
                {{ __('docs.s.dokumentasi_webhook') }}
            </h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                {{ __('docs.s.hubungkan_cekat_ai_chatbot_dengan_sistem_ekstern') }}
            </p>
        </div>
    </div>

    <!-- CONTENT SECTION -->
    <div class="py-16 bg-white min-h-screen">
        <div class="max-w-4xl mx-auto px-6">

            <!-- Intro Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 mb-12 hover:shadow-md transition">
                <h3 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-yellow-500"></i> {{ __('docs.s.cara_kerja') }}
                </h3>
                <p class="text-slate-600 mb-4 leading-relaxed">
                    {{ __('docs.s.ketika_percakapan_mencapai_titik_tertentu_misaln') }} <strong>{{ __('docs.s.http_post_request') }}</strong> {{ __('docs.s.ke_url_yang_anda_tentukan') }}
                </p>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-sm text-slate-700">
                    <ol class="list-decimal list-inside space-y-2">
                        <li>{{ __('docs.s.user_chatting_dengan_chatbot') }}</li>
                        <li>{{ __('docs.s.ai_mendeteksi_intent_misal') }} <strong>{{ __('docs.s.simpan_lead') }}</strong>).</li>
                        <li>{{ __('docs.s.server_cekat_mengirim_post_request_ke_server_and') }}</li>
                        <li>{{ __('docs.s.server_anda_memproses_data_simpan_ke_db_kirim_em') }}</li>
                    </ol>
                </div>
            </div>

            <div class="space-y-8" x-data="{ activeTab: 'wp' }">

                <!-- Tab Navigation -->
                <div class="flex gap-4 border-b border-slate-200 mb-6">
                    <button @click="activeTab = 'wp'" class="pb-3 px-1 text-sm font-medium transition relative"
                        :class="activeTab === 'wp' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-500 hover:text-slate-700'">
                        <i class="fa-brands fa-wordpress mr-2"></i>{{ __('admin.s.wordpress') }}
                    </button>
                    <button @click="activeTab = 'laravel'" class="pb-3 px-1 text-sm font-medium transition relative"
                        :class="activeTab === 'laravel' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-500 hover:text-slate-700'">
                        <i class="fa-brands fa-laravel mr-2"></i>{{ __('docs.s.laravel') }}
                    </button>
                    <button @click="activeTab = 'custom'" class="pb-3 px-1 text-sm font-medium transition relative"
                        :class="activeTab === 'custom' ? 'text-slate-900 border-b-2 border-slate-900' : 'text-slate-500 hover:text-slate-700'">
                        <i class="fa-solid fa-code mr-2"></i>{{ __('docs.s.manual_native') }}
                    </button>
                </div>

                <!-- WordPress Tab -->
                <div x-show="activeTab === 'wp'" class="space-y-6 animate-fade-in">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-6">
                        <h4 class="font-bold text-blue-900 mb-2">{{ __('docs.s.panduan_plugin_wordpress') }}</h4>
                        <p class="text-blue-800 text-sm mb-4">
                            {{ __('docs.s.plugin') }} <strong>{{ __('docs.s.cekat_ai_chatbot') }}</strong> {{ __('docs.s.v1_1_sudah_memiliki_fitur_penerima_webhook_bawaa') }}
                        </p>
                        <ol class="list-decimal list-inside space-y-3 text-blue-900 text-sm">
                            <li>{{ __('docs.s.download_update_plugin_terbaru') }} <a href="/downloads/cekat-ai-chatbot.zip"
                                    class="underline font-bold hover:text-blue-700">cekat-ai-chatbot.zip</a></li>
                            <li>{{ __('docs.s.buka') }} <strong>WP Admin > Settings > Cekat AI</strong>.</li>
                            <li>{{ __('docs.s.scroll_ke_bagian') }} <strong>{{ __('docs.s.webhook_integration') }}</strong>.</li>
                            <li>{{ __('docs.s.copy') }} <strong>{{ __('channels.s.webhook_url') }}</strong> yang muncul (e.g.,
                                <code>https://web.com/wp-json/cekat/v1/webhook</code>).</li>
                            <li>{{ __('docs.s.buat_copy') }} <strong>{{ __('channels.s.secret_key') }}</strong> {{ __('docs.s.di_halaman_tersebut') }}</li>
                            <li>{{ __('docs.s.masuk_ke') }} <strong>Dashboard Cekat > Widget Settings</strong>.</li>
                            <li>{{ __('docs.s.paste_url_secret_key_di_kolom_webhook') }}</li>
                        </ol>
                    </div>
                </div>

                <!-- Laravel Tab -->
                <div x-show="activeTab === 'laravel'" class="space-y-8 animate-fade-in" style="display: none;">

                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200">
                        <h4 class="font-bold text-slate-900 mb-4">{{ __('docs.s.1_setup_route_bypass_csrf') }}</h4>
                        <p class="text-slate-600 text-sm mb-4">{{ __('docs.s.karena_webhook_dikirim_dari_server_luar_anda_per') }}</p>

                        <div class="mb-4">
                            <p class="text-xs font-mono text-slate-500 mb-1">bootstrap/app.php (Laravel 11)</p>
                            <div class="rounded-xl overflow-hidden border border-slate-200 bg-[#2d2d2d] shadow-sm">
                                <pre><code class="language-php">->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'cekat/webhook', // <-- Tambahkan route ini
    ]);
})</code></pre>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-mono text-slate-500 mb-1">routes/api.php</p>
                            <div class="rounded-xl overflow-hidden border border-slate-200 bg-[#2d2d2d] shadow-sm">
                                <pre><code class="language-php">use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CekatWebhookController;

Route::post('/cekat/webhook', [CekatWebhookController::class, 'handle']);</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200">
                        <h4 class="font-bold text-slate-900 mb-4">{{ __('docs.s.2_buat_controller') }}</h4>
                        <p class="text-slate-600 text-sm mb-4">{{ __('docs.s.pastikan_anda_memverifikasi_signature_agar_aman') }}</p>

                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-[#2d2d2d] shadow-sm">
                            <pre><code class="language-php">namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CekatWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil Signature
        $signature = $request->header('X-Cekat-Signature');
        $timestamp = $request->header('X-Cekat-Timestamp');
        $secret = config('services.cekat.secret'); // Simpan di .env

        // 2. Verifikasi HMAC SHA256
        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // 3. Proses Action
        $action = $request->input('action');

        switch ($action) {
            case 'save_lead':
                // Simpan ke database Lead
                Log::info('New Lead:', $request->all());
                break;
                
            case 'create_order':
                // Buat pesanan baru
                break;
        }

        return response()->json(['status' => 'success']);
    }
}</code></pre>
                        </div>
                    </div>

                    <div
                        class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl flex gap-3 text-sm text-yellow-800">
                        <i class="fa-solid fa-lightbulb mt-1"></i>
                        <div>
                            <strong>{{ __('docs.s.tips') }}</strong> URL Webhook Anda jadinya adalah
                            <code>https://aplikasi-anda.com/api/cekat/webhook</code>. <br>
                            Masukkan URL ini di Dashboard Cekat > Widget Settings.
                        </div>
                    </div>
                </div>

                <!-- Custom Integration Tab -->
                <div x-show="activeTab === 'custom'" class="space-y-8 animate-fade-in" style="display: none;">

                    <div>
                        <h4 class="font-bold text-slate-900 mb-4">{{ __('docs.s.payload_format') }}</h4>
                        <p class="text-slate-600 mb-3 text-sm">{{ __('docs.s.cekat_mengirim_request_dengan_header_signature_u') }}</p>
                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-[#2d2d2d] shadow-sm">
                            <pre><code class="language-http">POST /your-webhook-endpoint
Content-Type: application/json
X-Cekat-Signature: 8d969eef6ecad3c29a3a629280e686cf0c3f5d5...
X-Cekat-Timestamp: 1678892233

{
  "action": "save_lead",
  "widget_id": "widget-123",
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "phone": "08123456789"
}</code></pre>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-slate-900 mb-4">{{ __('docs.s.verifikasi_signature_php') }}</h4>
                        <p class="text-slate-600 mb-3 text-sm">{{ __('docs.s.validasi_request_menggunakan_hmac_sha256') }}</p>
                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-[#2d2d2d] shadow-sm">
                            <pre><code class="language-php">$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CEKAT_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_CEKAT_TIMESTAMP'];
$secret = 'YOUR_SECRET_KEY';

// Generate Expected Signature
$dataToSign = $timestamp . '.' . $payload;
$expected = hash_hmac('sha256', $dataToSign, $secret);

if (hash_equals($expected, $signature)) {
    // Valid Request from Cekat
    $data = json_decode($payload, true);
    // Process $data['action']
}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Available Actions Table -->
                <div class="mt-12">
                    <h3 class="text-xl font-bold text-slate-900 mb-6">{{ __('docs.s.daftar_action_available') }}</h3>
                    <div class="overflow-hidden border border-slate-200 rounded-xl shadow-sm">
                        <table class="w-full text-sm text-left text-slate-600">
                            <thead class="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3">{{ __('docs.s.action_key') }}</th>
                                    <th class="px-6 py-3">{{ __('docs.s.trigger_condition') }}</th>
                                    <th class="px-6 py-3">{{ __('docs.s.fields_received') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono font-medium text-blue-600">save_lead</td>
                                    <td class="px-6 py-4">{{ __('docs.s.user_memberikan_nama_email_atau_no_hp') }}</td>
                                    <td class="px-6 py-4 font-mono text-xs">name, email, phone</td>
                                </tr>
                                <tr class="bg-white border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono font-medium text-blue-600">check_status</td>
                                    <td class="px-6 py-4">{{ __('docs.s.user_bertanya_status_pesanan_tiket') }}</td>
                                    <td class="px-6 py-4 font-mono text-xs">reference_id</td>
                                </tr>
                                <tr class="bg-white hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono font-medium text-blue-600">create_order</td>
                                    <td class="px-6 py-4">{{ __('docs.s.user_ingin_membuat_pesanan_baru') }}</td>
                                    <td class="px-6 py-4 font-mono text-xs">items[], notes</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- FOOTER (Copied from welcome.blade.php) -->
    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-slate-500 text-sm">
                    {{ __('docs.s.copy_2026_cekat_biz_id_all_rights_reserved') }}
                </div>
                <div class="flex gap-6 text-slate-400">
                    <a href="#" class="hover:text-slate-900"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="hover:text-slate-900"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- PrismJS script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-json.min.js"></script>
</body>

</html>