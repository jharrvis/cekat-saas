<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API - Cekat.biz.id</title>
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
                <a href="/docs/webhooks" class="text-slate-600 hover:text-slate-900">Webhooks</a>
                <a href="/dashboard" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-700">Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-12">
        <h1 class="text-3xl font-bold">Public API v1</h1>
        <p class="text-slate-600 mt-2">
            API read-only untuk mengambil data leads, riwayat chat, widgets, dan statistik
            secara programatik (integrasi CRM, Zapier, data warehouse, dsb).
            Tersedia untuk paket <strong>Pro ke atas</strong>.
        </p>

        <section id="auth" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">Autentikasi</h2>
            <p class="mt-3 text-slate-700">
                Buat API key di <a href="/settings/api-keys" class="text-indigo-600 underline">Dashboard &rarr; API Keys</a>.
                Kirim key sebagai bearer token di setiap request:
            </p>
            <pre class="mt-3"><code class="language-bash">curl -H "Authorization: Bearer ck_live_..." \
  https://cekat.biz.id/api/v1/leads</code></pre>
            <p class="mt-3 text-slate-700">Header alternatif: <code class="px-1 bg-slate-200 rounded">X-API-Key: ck_live_...</code></p>
            <ul class="list-disc ml-6 mt-2 text-slate-700 space-y-1">
                <li>Secret hanya ditampilkan satu kali saat pembuatan - hanya hash (SHA-256) yang disimpan server.</li>
                <li>Rate limit: <strong>120 request/menit</strong> per key (header <code class="px-1 bg-slate-200 rounded">Retry-After</code> pada 429).</li>
                <li>Cabut key kapan saja dari halaman yang sama; efektif seketika.</li>
            </ul>
        </section>

        <section id="endpoints" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">Endpoints</h2>
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-sm bg-white border rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-slate-100 text-left">
                            <th class="p-3">Method</th>
                            <th class="p-3">Path</th>
                            <th class="p-3">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/leads</td>
                            <td class="p-3">Daftar leads (sesi dengan data kontak). Filter: <code>widget_id</code>, <code>search</code>, <code>since</code>, <code>until</code>, <code>cursor</code>, <code>limit</code></td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/leads/{id}</td>
                            <td class="p-3">Detail satu lead termasuk summary AI</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/sessions</td>
                            <td class="p-3">Semua sesi chat. Filter: <code>widget_id</code>, <code>is_lead</code>, <code>since</code>, <code>until</code></td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/sessions/{id}/messages</td>
                            <td class="p-3">Pesan dalam sesi (role + content + waktu)</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/widgets</td>
                            <td class="p-3">Daftar widget/channel milik Anda</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono">GET</td>
                            <td class="p-3 font-mono">/api/v1/stats</td>
                            <td class="p-3">Statistik lead: total, minggu/bulan ini, conversion rate</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="pagination" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">Pagination</h2>
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
      "widget": { "id": 3, "slug": "widget-3-CPbqbm3C", "name": "Rahma Assistant" },
      "created_at": "2026-09-30T12:51:00+07:00"
    }
  ],
  "meta": { "per_page": 50, "next_cursor": 460 }
}</code></pre>
        </section>

        <section id="errors" class="mt-10">
            <h2 class="text-xl font-semibold border-b pb-2">Error</h2>
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-sm bg-white border rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-slate-100 text-left">
                            <th class="p-3">Status</th>
                            <th class="p-3">error_code</th>
                            <th class="p-3">Arti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr><td class="p-3 font-mono">401</td><td class="p-3 font-mono">unauthenticated</td><td class="p-3">Key tidak ada, salah, dicabut, atau kedaluwarsa</td></tr>
                        <tr><td class="p-3 font-mono">403</td><td class="p-3 font-mono">feature_locked</td><td class="p-3">Paket Anda belum memiliki akses API</td></tr>
                        <tr><td class="p-3 font-mono">403</td><td class="p-3 font-mono">account_suspended</td><td class="p-3">Akun tidak aktif</td></tr>
                        <tr><td class="p-3 font-mono">404</td><td class="p-3 font-mono">not_found</td><td class="p-3">Data tidak ada atau bukan milik Anda</td></tr>
                        <tr><td class="p-3 font-mono">429</td><td class="p-3 font-mono">rate_limited</td><td class="p-3">Melebihi 120 request/menit - coba lagi setelah Retry-After</td></tr>
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
            <h2 class="text-xl font-semibold border-b pb-2">Catatan Privasi</h2>
            <ul class="list-disc ml-6 mt-3 text-slate-700 space-y-1">
                <li>API hanya mengembalikan data milik pemilik key (scope per user).</li>
                <li>IP dan user-agent mentah pengunjung <strong>tidak</strong> diekspos.</li>
                <li>Nama/email/phone ter-deskripsi otomatis (enkripsi at-rest).</li>
            </ul>
        </section>
    </main>

    <footer class="border-t border-slate-200 mt-12 py-6 text-center text-sm text-slate-500">
        &copy; 2026 Cekat.biz.id
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-json.min.js"></script>
</body>

</html>
