{{--
    T-10: Halaman Syarat & Ketentuan publik.
    CATATAN PEMILIK: teks ini adalah templat awal yang ditulis sesuai
    perilaku sistem yang sebenarnya. Tinjau dan sesuaikan dengan kebutuhan
    hukum bisnis sebelum dianggap final.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('legal.s.syarat_amp_ketentuan_cekat_biz_id') }}</title>
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
            <h1>{{ __('legal.s.syarat_amp_ketentuan') }}</h1>
            <p class="text-sm text-gray-500">{{ __('legal.s.terakhir_diperbarui_7_oktober_2026') }}</p>

            <p>{{ __('legal.s.dengan_membuat_akun_dan_menggunakan_cekat_biz_id') }}</p>

            <h2>{{ __('legal.s.1_layanan') }}</h2>
            <p>{{ __('legal.s.cekat_adalah_layanan_perangkat_lunak_saas_yang_m') }}</p>

            <h2>{{ __('legal.s.2_akun') }}</h2>
            <ul>
                <li>{{ __('legal.s.anda_wajib_memberikan_data_pendaftaran_yang_bena') }}</li>
                <li>{{ __('legal.s.akun_wajib_diverifikasi_melalui_kode_sekali_paka') }}</li>
                <li>{{ __('legal.s.anda_bertanggung_jawab_atas_seluruh_aktivitas_ya') }}</li>
            </ul>

            <h2>{{ __('legal.s.3_paket_kuota_dan_pembayaran') }}</h2>
            <ul>
                <li>{{ __('legal.s.fitur_dan_batas_pemakaian_mengikuti_paket_yang_a') }}</li>
                <li>{{ __('legal.s.pembayaran_diproses_melalui_midtrans_paket_aktif') }}</li>
                <li>{{ __('legal.s.kuota_pesan_diperbarui_setiap_siklus_penagihan_s') }}</li>
            </ul>

            <h2>{{ __('legal.s.4_penggunaan_yang_dilarang') }}</h2>
            <ul>
                <li>{{ __('legal.s.menggunakan_layanan_untuk_spam_penipuan_atau_kon') }}</li>
                <li>{{ __('legal.s.mengunggah_konten_yang_melanggar_hak_kekayaan_in') }}</li>
                <li>{{ __('legal.s.mencoba_membongkar_mengotomatisasi_penyalahgunaa') }}</li>
            </ul>
            <p>{{ __('legal.s.pelanggaran_dapat_berakibat_penangguhan_atau_pen') }}</p>

            <h2>{{ __('legal.s.5_konten_anda') }}</h2>
            <p>{{ __('legal.s.basis_pengetahuan_dokumen_dan_konfigurasi_widget') }}</p>

            <h2>{{ __('legal.s.6_ketersediaan_dan_perubahan_layanan') }}</h2>
            <p>{{ __('legal.s.kami_berupaya_menjaga_layanan_tersedia_namun_tid') }}</p>

            <h2>{{ __('legal.s.7_batasan_tanggung_jawab') }}</h2>
            <p>{{ __('legal.s.sepanjang_diizinkan_hukum_tanggung_jawab_cekat_a') }}</p>

            <h2>{{ __('legal.s.8_hukum_yang_berlaku') }}</h2>
            <p>{{ __('legal.s.syarat_ini_diatur_oleh_hukum_republik_indonesia') }}</p>

            <h2>{{ __('legal.s.9_kontak') }}</h2>
            <p>{{ __('legal.s.pertanyaan_seputar_syarat_ini') }} <a href="mailto:support@cekat.biz.id">support@cekat.biz.id</a>.</p>
        </article>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-slate-800">
        &copy; {{ date('Y') }} Cekat.biz.id — <a href="{{ route('legal.privacy') }}" class="hover:text-brand-600">{{ __('legal.s.kebijakan_privasi') }}</a>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
