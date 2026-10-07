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
    <title>Syarat &amp; Ketentuan — Cekat.biz.id</title>
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
            <h1>Syarat &amp; Ketentuan</h1>
            <p class="text-sm text-gray-500">Terakhir diperbarui: 7 Oktober 2026</p>

            <p>Dengan membuat akun dan menggunakan Cekat.biz.id ("Cekat", "layanan"), Anda menyetujui syarat-syarat berikut.</p>

            <h2>1. Layanan</h2>
            <p>Cekat adalah layanan perangkat lunak (SaaS) yang menyediakan chatbot AI untuk situs web dan WhatsApp, yang menjawab berdasarkan basis pengetahuan yang Anda kelola. Jawaban dihasilkan secara otomatis oleh sistem AI dan dapat mengandung ketidakakuratan; Anda bertanggung jawab meninjau konten basis pengetahuan dan kesesuaian jawaban bagi pengunjung Anda.</p>

            <h2>2. Akun</h2>
            <ul>
                <li>Anda wajib memberikan data pendaftaran yang benar dan menjaga kerahasiaan kata sandi.</li>
                <li>Akun wajib diverifikasi melalui kode sekali pakai (OTP) yang dikirim ke email Anda.</li>
                <li>Anda bertanggung jawab atas seluruh aktivitas yang terjadi melalui akun Anda.</li>
            </ul>

            <h2>3. Paket, kuota, dan pembayaran</h2>
            <ul>
                <li>Fitur dan batas pemakaian mengikuti paket yang Anda pilih sebagaimana ditampilkan pada halaman harga, termasuk batas pesan bulanan, jumlah agen, channel, dan perangkat WhatsApp.</li>
                <li>Pembayaran diproses melalui Midtrans. Paket aktif setelah pembayaran terkonfirmasi, untuk masa berlaku sesuai paket (satu bulan sejak aktivasi), dan dapat diperpanjang atau ditingkatkan kapan pun.</li>
                <li>Kuota pesan diperbarui setiap siklus penagihan sesuai paket yang aktif.</li>
            </ul>

            <h2>4. Penggunaan yang dilarang</h2>
            <ul>
                <li>Menggunakan layanan untuk spam, penipuan, atau konten yang melanggar hukum Republik Indonesia.</li>
                <li>Mengunggah konten yang melanggar hak kekayaan intelektual atau privasi pihak lain.</li>
                <li>Mencoba membongkar, mengotomatisasi penyalahgunaan, atau mengganggu keamanan layanan, termasuk memalsukan asal permintaan ke API publik.</li>
            </ul>
            <p>Pelanggaran dapat berakibat penangguhan atau penghentian akun.</p>

            <h2>5. Konten Anda</h2>
            <p>Basis pengetahuan, dokumen, dan konfigurasi widget yang Anda buat tetap milik Anda. Anda memberi Cekat izin terbatas untuk memproses konten tersebut semata-mata untuk menjalankan layanan bagi Anda.</p>

            <h2>6. Ketersediaan dan perubahan layanan</h2>
            <p>Kami berupaya menjaga layanan tersedia, namun tidak menjamin layanan bebas gangguan. Fitur dapat berubah dengan pemberitahuan yang wajar melalui situs atau email.</p>

            <h2>7. Batasan tanggung jawab</h2>
            <p>Sepanjang diizinkan hukum, tanggung jawab Cekat atas kerugian yang timbul dari penggunaan layanan dibatasi pada jumlah yang Anda bayarkan untuk layanan dalam 3 (tiga) bulan terakhir. Cekat tidak bertanggung jawab atas keputusan bisnis yang diambil berdasarkan jawaban otomatis chatbot.</p>

            <h2>8. Hukum yang berlaku</h2>
            <p>Syarat ini diatur oleh hukum Republik Indonesia. Perselisihan diselesaikan pertama-tama melalui musyawarah.</p>

            <h2>9. Kontak</h2>
            <p>Pertanyaan seputar syarat ini: <a href="mailto:support@cekat.ai">support@cekat.ai</a>.</p>
        </article>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-slate-800">
        &copy; {{ date('Y') }} Cekat.biz.id — <a href="{{ route('legal.privacy') }}" class="hover:text-brand-600">Kebijakan Privasi</a>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
