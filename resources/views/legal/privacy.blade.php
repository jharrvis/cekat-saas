{{--
    T-10: Halaman Kebijakan Privasi publik.
    CATATAN PEMILIK: teks ini adalah templat awal yang ditulis sesuai
    perilaku sistem yang sebenarnya (data yang memang dikumpulkan aplikasi).
    Tinjau dan sesuaikan dengan kebutuhan hukum bisnis sebelum dianggap final.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('legal.s.kebijakan_privasi_cekat_biz_id') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4',
                            400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e',
                            800: '#115e59', 900: '#134e4a', 950: '#042f2e',
                        }
                    }
                }
            }
        }
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-slate-950 dark:text-gray-100 antialiased selection:bg-brand-500 selection:text-white min-h-screen flex flex-col">
    <nav class="w-full bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-16">
                <a href="/" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
                        <i data-lucide="bot" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Cekat<span class="text-brand-600 dark:text-brand-400">.biz.id</span></span>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-1 px-4 py-14">
        <article class="max-w-3xl mx-auto prose prose-slate dark:prose-invert prose-headings:font-bold prose-a:text-brand-600">
            <h1>{{ __('legal.s.kebijakan_privasi') }}</h1>
            <p class="text-sm text-gray-500">{{ __('legal.s.terakhir_diperbarui_7_oktober_2026') }}</p>

            <p>{{ __('legal.s.kebijakan_ini_menjelaskan_data_apa_yang_dikumpul') }}</p>

            <h2>{{ __('legal.s.1_data_yang_kami_kumpulkan') }}</h2>
            <ul>
                <li><strong>{{ __('legal.s.data_akun') }}</strong> {{ __('legal.s.nama_alamat_email_dan_kata_sandi_tersimpan_dalam') }}</li>
                <li><strong>{{ __('legal.s.konten_yang_anda_kelola') }}</strong> {{ __('legal.s.informasi_bisnis_faq_dan_dokumen_yang_anda_ungga') }}</li>
                <li><strong>{{ __('legal.s.percakapan_pengunjung') }}</strong> {{ __('legal.s.pesan_yang_dikirim_pengunjung_melalui_widget_cha') }}</li>
                <li><strong>{{ __('legal.s.data_prospek_lead') }}</strong> {{ __('legal.s.nama_email_atau_nomor_telepon_yang_diisi_pengunj') }}</li>
                <li><strong>{{ __('legal.s.data_transaksi') }}</strong> {{ __('legal.s.riwayat_pembayaran_dan_status_paket_anda_pemrose') }}</li>
                <li><strong>{{ __('legal.s.log_email') }}</strong> {{ __('legal.s.setiap_email_yang_dikirim_sistem_misalnya_kode_v') }}</li>
            </ul>

            <h2>{{ __('legal.s.2_bagaimana_data_digunakan') }}</h2>
            <ul>
                <li>{{ __('legal.s.menjalankan_layanan_menghasilkan_jawaban_chatbot') }}</li>
                <li>{{ __('legal.s.mengirim_email_transaksional_verifikasi_akun_atu') }}</li>
                <li>{{ __('legal.s.keamanan_dan_pencegahan_penyalahgunaan_termasuk') }}</li>
            </ul>
            <p>{{ __('legal.s.kami_tidak_menjual_data_pribadi_anda') }}</p>

            <h2>{{ __('legal.s.3_pihak_ketiga_yang_memproses_data') }}</h2>
            <ul>
                <li><strong>{{ __('legal.s.penyedia_layanan_ai') }}</strong> {{ __('legal.s.pesan_percakapan_diproses_untuk_menghasilkan_jaw') }}</li>
                <li><strong>Midtrans</strong> {{ __('legal.s.memproses_pembayaran_paket_berlangganan') }}</li>
                <li><strong>Fonnte</strong> {{ __('legal.s.menghubungkan_nomor_whatsapp_anda_bila_anda_meng') }}</li>
                <li><strong>{{ __('legal.s.penyedia_email') }}</strong> {{ __('legal.s.mengirim_email_transaksional_atas_nama_cekat') }}</li>
            </ul>

            <h2>{{ __('legal.s.4_penyimpanan_dan_keamanan') }}</h2>
            <p>{{ __('legal.s.data_disimpan_selama_akun_anda_aktif_atau_selama') }}</p>

            <h2>{{ __('legal.s.5_hak_anda') }}</h2>
            <p>{{ __('legal.s.anda_dapat_meminta_akses_perbaikan_atau_penghapu') }}</p>

            <h2>{{ __('legal.s.6_perubahan_kebijakan') }}</h2>
            <p>{{ __('legal.s.perubahan_material_pada_kebijakan_ini_akan_kami') }}</p>

            <h2>{{ __('legal.s.7_kontak') }}</h2>
            <p>{{ __('legal.s.pertanyaan_seputar_privasi') }} <a href="mailto:support@cekat.biz.id">support@cekat.biz.id</a>.</p>
        </article>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-slate-800">
        &copy; {{ date('Y') }} Cekat.biz.id — <a href="{{ route('legal.terms') }}" class="hover:text-brand-600">{{ __('legal.s.syarat_amp_ketentuan') }}</a>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
