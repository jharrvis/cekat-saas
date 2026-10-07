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
    <title>Kebijakan Privasi — Cekat.biz.id</title>
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
            <h1>Kebijakan Privasi</h1>
            <p class="text-sm text-gray-500">Terakhir diperbarui: 7 Oktober 2026</p>

            <p>Kebijakan ini menjelaskan data apa yang dikumpulkan Cekat.biz.id ("Cekat", "kami") saat Anda menggunakan layanan chatbot AI kami, bagaimana data itu digunakan, dan hak Anda atas data tersebut.</p>

            <h2>1. Data yang kami kumpulkan</h2>
            <ul>
                <li><strong>Data akun.</strong> Nama, alamat email, dan kata sandi (tersimpan dalam bentuk hash) saat Anda mendaftar.</li>
                <li><strong>Konten yang Anda kelola.</strong> Informasi bisnis, FAQ, dan dokumen yang Anda unggah ke basis pengetahuan (knowledge base) milik akun Anda.</li>
                <li><strong>Percakapan pengunjung.</strong> Pesan yang dikirim pengunjung melalui widget chat di situs Anda, beserta data teknis seperti halaman asal, peramban, dan alamat IP — ditampilkan kepada Anda sebagai pemilik widget di Riwayat Chat.</li>
                <li><strong>Data prospek (lead).</strong> Nama, email, atau nomor telepon yang diisi pengunjung melalui formulir pra-chat atau yang terdeteksi dari percakapan, bila fitur lead diaktifkan.</li>
                <li><strong>Data transaksi.</strong> Riwayat pembayaran dan status paket Anda. Pemrosesan pembayaran dilakukan oleh penyedia pembayaran pihak ketiga; kami tidak menyimpan nomor kartu Anda.</li>
                <li><strong>Log email.</strong> Setiap email yang dikirim sistem (misalnya kode verifikasi dan notifikasi) tercatat di log pengiriman untuk keperluan operasional dan audit administrator.</li>
            </ul>

            <h2>2. Bagaimana data digunakan</h2>
            <ul>
                <li>Menjalankan layanan: menghasilkan jawaban chatbot dari basis pengetahuan Anda, menampilkan riwayat dan analitik, serta mengelola kuota paket.</li>
                <li>Mengirim email transaksional (verifikasi akun, atur ulang kata sandi, bukti pembayaran, notifikasi lead).</li>
                <li>Keamanan dan pencegahan penyalahgunaan, termasuk pembatasan domain widget dan verifikasi tanda tangan webhook pembayaran.</li>
            </ul>
            <p>Kami tidak menjual data pribadi Anda.</p>

            <h2>3. Pihak ketiga yang memproses data</h2>
            <ul>
                <li><strong>Penyedia layanan AI</strong> — pesan percakapan diproses untuk menghasilkan jawaban chatbot. Pemilihan penyedia dan model dikelola di sisi server oleh Cekat.</li>
                <li><strong>Midtrans</strong> — memproses pembayaran paket berlangganan.</li>
                <li><strong>Fonnte</strong> — menghubungkan nomor WhatsApp Anda bila Anda mengaktifkan channel WhatsApp.</li>
                <li><strong>Penyedia email</strong> — mengirim email transaksional atas nama Cekat.</li>
            </ul>

            <h2>4. Penyimpanan dan keamanan</h2>
            <p>Data disimpan selama akun Anda aktif atau selama diperlukan untuk menjalankan layanan. Kata sandi disimpan sebagai hash; kode verifikasi email bersifat sekali pakai dan berumur pendek; identitas pengunjung pada riwayat chat disimpan terenkripsi. Akses administratif ke data dibatasi untuk keperluan operasional.</p>

            <h2>5. Hak Anda</h2>
            <p>Anda dapat meminta akses, perbaikan, atau penghapusan data akun Anda dengan menghubungi kami. Konten basis pengetahuan dan widget sepenuhnya milik Anda dan dapat Anda ubah atau hapus kapan pun dari dasbor.</p>

            <h2>6. Perubahan kebijakan</h2>
            <p>Perubahan material pada kebijakan ini akan kami umumkan melalui situs atau email. Penggunaan layanan setelah perubahan berlaku berarti Anda menyetujui kebijakan yang diperbarui.</p>

            <h2>7. Kontak</h2>
            <p>Pertanyaan seputar privasi: <a href="mailto:support@cekat.ai">support@cekat.ai</a>.</p>
        </article>
    </main>

    <footer class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-slate-800">
        &copy; {{ date('Y') }} Cekat.biz.id — <a href="{{ route('legal.terms') }}" class="hover:text-brand-600">Syarat &amp; Ketentuan</a>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
