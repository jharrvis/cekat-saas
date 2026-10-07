<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('docs.s.dokumentasi_api_cekat_biz_id') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        pre { border-radius: 0.75rem; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 antialiased">

    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 font-bold text-xl">
                <div class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                Cekat<span class="text-slate-400 font-normal">.biz.id</span>
            </a>
            <div class="flex items-center gap-4 text-sm">
                <a href="/docs/webhooks" class="text-slate-600 hover:text-slate-900">{{ __('docs.s.webhooks') }}</a>
                <a href="/dashboard" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-700">{{ __('general.s.dashboard') }}</a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-12">
        <h1 class="text-3xl font-bold">{{ __('docs.s.public_api_v1') }}</h1>
        <p class="text-slate-600 mt-2">
            {{ __('docs.s.api_read_only_untuk_mengambil_data_leads_riwayat') }} <strong>{{ __('docs.s.pro_ke_atas') }}</strong>.
        </p>

        <section id="auth" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">{{ __('docs.s.autentikasi') }}</h2>
            <p class="mt-3 text-slate-700">
                {{ __('docs.s.buat_api_key_di') }} <a href="/settings/api-keys" class="text-indigo-600 underline">{{ __('docs.s.dashboard_rarr_api_keys') }}</a>{{ __('docs.s.kirim_key_sebagai_bearer_token_di_setiap_request') }}
            </p>
            <pre class="mt-3"><code class="language-bash">curl -H "Authorization: Bearer ck_live_..." \
  https://cekat.biz.id/api/v1/leads</code></pre>
            <p class="mt-3 text-slate-700">{{ __('docs.s.header_alternatif') }} <code class="px-1 bg-slate-200 rounded">X-API-Key: ck_live_...</code></p>
            <ul class="list-disc ml-6 mt-2 text-slate-700 space-y-1">
                <li>{{ __('docs.s.secret_hanya_ditampilkan_satu_kali_saat_pembuata') }}</li>
                <li>{{ __('docs.s.rate_limit') }} <strong>{{ __('docs.s.120_request_menit') }}</strong> per key (header <code class="px-1 bg-slate-200 rounded">Retry-After</code> pada 429).</li>
                <li>{{ __('docs.s.cabut_key_kapan_saja_dari_halaman_yang_sama_efek') }}</li>
            </ul>
        </section>

        <section id="endpoints" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">{{ __('docs.s.endpoints') }}</h2>
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-sm bg-white border rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-slate-100 text-left">
                            <th class="p-3">{{ __('docs.s.method') }}</th>
                            <th class="p-3">{{ __('docs.s.path') }}</th>
                            <th class="p-3">{{ __('agents.s.deskripsi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/leads</td>
                            <td class="p-3">Daftar leads (sesi dengan data kontak) - tiap item memuat data lengkap: kontak, summary AI, device, lokasi, IP, halaman &amp; referrer. Filter: <code>widget_id</code> (ID numerik atau slug widget), <code>search</code>, <code>since</code>, <code>until</code>, <code>cursor</code>, <code>limit</code></td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/leads/{id}</td>
                            <td class="p-3">{{ __('docs.s.detail_satu_lead_field_sama_dengan_list_plus_dat') }}</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/sessions</td>
                            <td class="p-3">Semua sesi chat - tiap item memuat data lengkap (kontak, summary, device, lokasi, IP, halaman &amp; referrer). Filter: <code>widget_id</code> (ID numerik atau slug widget), <code>is_lead</code>, <code>since</code>, <code>until</code></td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/sessions/{id}/messages</td>
                            <td class="p-3">{{ __('docs.s.pesan_dalam_sesi_role_content_waktu') }}</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/widgets</td>
                            <td class="p-3">{{ __('docs.s.daftar_widget_channel_milik_anda') }}</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/stats</td>
                            <td class="p-3">{{ __('docs.s.statistik_lead_total_minggu_bulan_ini_conversion') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="pagination" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">{{ __('docs.s.pagination') }}</h2>
            <p class="mt-3 text-slate-700">
                <code class="px-1 bg-slate-200 rounded">/leads</code> dan <code class="px-1 bg-slate-200 rounded">/sessions</code>
                memakai cursor (id menurun - stabil untuk data baru). Kirim ulang
                <code class="px-1 bg-slate-200 rounded">cursor=meta.next_cursor</code> sampai bernilai <code>null</code>.
                Default 50, maksimum 100 per halaman (<code class="px-1 bg-slate-200 rounded">limit</code>).
            </p>
            <pre class="mt-3"><code class="language-json">{
  "data": [
    {
      "id": 465,
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "phone": "08123456789",
      "is_lead": true,
      "is_converted": false,
      "status": "ended",
      "summary": "Budi menanyakan harga paket Pro dan cara integrasi ke CRM.",
      "summary_generated_at": "2026-09-30T12:52:10+07:00",
      "source_url": "https://example.com/pricing",
      "referer_url": "https://google.com/search?q=harga+chatbot",
      "ip_address": "203.0.113.42",
      "device": {
        "type": "desktop",
        "label": "Chrome 153 · Windows",
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ..."
      },
      "location": {
        "country_code": "ID",
        "country": "Indonesia",
        "region": "Jakarta",
        "city": "Jakarta",
        "isp": "PT Telkom"
      },
      "widget": { "id": 3, "slug": "widget-3-CPbqbm3C", "name": "Rahma Assistant" },
      "started_at": "2026-09-30T12:44:11+07:00",
      "ended_at": "2026-09-30T12:51:00+07:00",
      "created_at": "2026-09-30T12:51:00+07:00",
      "updated_at": "2026-09-30T12:52:10+07:00"
    }
  ],
  "meta": { "per_page": 50, "next_cursor": 460 }
}</code></pre>
        </section>

        <section id="errors" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">{{ __('docs.s.error') }}</h2>
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-sm bg-white border rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-slate-100 text-left">
                            <th class="p-3">{{ __('channels.s.status') }}</th>
                            <th class="p-3">error_code</th>
                            <th class="p-3">{{ __('docs.s.arti') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr><td class="p-3 font-mono">400</td><td class="p-3 font-mono">invalid_param</td><td class="p-3">{{ __('docs.s.parameter_query_tidak_valid_mis_widget_id_bukan') }}</td></tr>
                        <tr><td class="p-3 font-mono">401</td><td class="p-3 font-mono">unauthenticated</td><td class="p-3">{{ __('docs.s.key_tidak_ada_salah_dicabut_atau_kedaluwarsa') }}</td></tr>
                        <tr><td class="p-3 font-mono">403</td><td class="p-3 font-mono">feature_locked</td><td class="p-3">{{ __('docs.s.paket_anda_belum_memiliki_akses_api') }}</td></tr>
                        <tr><td class="p-3 font-mono">403</td><td class="p-3 font-mono">account_suspended</td><td class="p-3">{{ __('docs.s.akun_tidak_aktif') }}</td></tr>
                        <tr><td class="p-3 font-mono">404</td><td class="p-3 font-mono">not_found</td><td class="p-3">{{ __('docs.s.data_tidak_ada_atau_bukan_milik_anda') }}</td></tr>
                        <tr><td class="p-3 font-mono">429</td><td class="p-3 font-mono">rate_limited</td><td class="p-3">{{ __('docs.s.melebihi_120_request_menit_coba_lagi_setelah_ret') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <pre class="mt-4"><code class="language-json">{
  "error": "unauthenticated",
  "error_code": "unauthenticated",
  "message": "API key wajib. Kirim header Authorization: Bearer &lt;api_key&gt;."
}</code></pre>
        </section>

        <section id="privacy" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">{{ __('docs.s.catatan_privasi') }}</h2>
            <ul class="list-disc ml-6 mt-3 text-slate-700 space-y-1">
                <li>{{ __('docs.s.api_hanya_mengembalikan_data_milik_pemilik_key_s') }}</li>
                <li>{{ __('docs.s.setiap_lead_sesi_memuat_data_lengkap_milik_anda') }}</li>
                <li>{{ __('docs.s.nama_email_phone_summary_ter_deskripsi_otomatis') }}</li>
            </ul>
        </section>
    </main>

    <footer class="border-t border-slate-200 mt-12 py-6 text-center text-sm text-slate-500">
        {{ __('docs.s.copy_2026_cekat_biz_id') }}
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-json.min.js"></script>
</body>

</html>
