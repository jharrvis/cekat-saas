# 🧠 Rencana Knowledge Base & Data Training

## ❓ 1. Apakah bisa training pakai PDF?

**Bisa!** Menggunakan teknik **RAG (Retrieval Augmented Generation)**. AI membaca dokumen Anda seperti mahasiswa "Open Book" saat ujian.

## 🔗 2. Apakah bisa mengirimkan Link Website untuk dipelajari?

**Sangat Bisa!** Ini adalah fitur umum di chatbot modern.

### Cara Kerja Fitur "Learn from URL":
1.  **Input**: User memasukkan URL (misal: `https://perusahaan.com/kebijakan-return`).
2.  **Crawling**: Sistem kita akan mengunjungi link tersebut sebagai bot.
3.  **Scraping**: Sistem mengambil teks utama (artikel/konten) dan membuang bagian tidak penting (menu, footer, iklan).
4.  **Learning**: Teks bersih tadi diproses (chunking & embedding) sama seperti file PDF, lalu disimpan ke Database Pengetahuan.

> **Catatan Teknis**: Web scraping bisa gagal jika website target memblokir bot atau menggunakan JavaScript berat (SPA). Untuk tahap awal, kita akan gunakan scrapper sederhana untuk halaman statis.

---

## ⚖️ 3. Mana yang lebih baik: Upload File (PDF) vs Isi Form Manual?

Ini adalah perdebatan klasik antara **Convenience (Kemudahan)** vs **Quality (Kualitas)**. Berikut analisisnya:

### 📄 Opsi A: Upload File (PDF/Doc/Link)
*Cocok untuk: Dokumen yang sudah ada, Materi banyak (SOP 50 halaman).*

| Kelebihan ✅ | Kekurangan ❌ |
|:---|:---|
| **Cepat**: User tinggal upload, selesai dalam detik. | **Hasil "Kotor"**: PDF sering punya layout aneh (tabel terpotong, header/footer terbaca berulang-ulang), yang bisa bikin AI bingung. |
| **Mudah**: Tidak perlu copy-paste manual. | **Informasi Tidak Terstruktur**: AI harus "menebak" mana poin penting. |

### 📝 Opsi B: Isi Form Standar (Q&A Manual)
*Cocok untuk: FAQ spesifik, Informasi krusial yang pendek.*

| Kelebihan ✅ | Kekurangan ❌ |
|:---|:---|
| **Akurasi Tinggi**: Data bersih, langsung _to the point_. AI tidak perlu menebak konteks. | **Capek**: User harus input satu-satu. |
| **Terkontrol**: Anda tahu persis apa yang akan dijawab AI. | **Lama**: Butuh waktu untuk memindahkan data dari dokumen ke form. |

### 💡 Rekomendasi Saya: Pendekatan "Hybrid"

Jangan pilih salah satu, gunakan keduanya untuk tujuan berbeda:

1.  **Gunakan PDF/URL untuk "Dasar Pengetahuan Luas"**: Upload manual produk, company profile, atau SOP agar AI tahu konteks umum perusahaan secara instan.
2.  **Gunakan Form Q&A untuk "Koreksi & Detail Penting"**: Jika AI salah menjawab hal spesifik, buatkan entry Q&A manual untuk "menimpa" pengetahuan umumnya.

**Kesimpulan**: Mulai dengan PDF/URL agar user cepat _onboard_, lalu sediakan fitur "Edit Knowledge" (Form) untuk menyempurnakan jawaban AI seiring waktu.

---

## 🛠️ Rencana Implementasi Teknis (Updated)

### Phase 1: Input Data
- [ ] **Upload PDF/Doc**: Menggunakan `smalot/pdfparser` untuk ekstrak teks.
- [ ] **Link Scraper**: Menggunakan `Guzzle` + `DOMDocument` atau library seperti `spatie/crawler` untuk ambil teks dari URL.
- [ ] **Manual Entry**: Form sederhana Input Pertanyaan & Jawaban.

### Phase 2: Processing (Otak)
- [ ] **Teks Cleaning**: Bersihkan spasi berlebih, header/footer PDF.
- [ ] **Embedding**: Kirim teks ke OpenAI/OpenRouter untuk dapat vector.
- [ ] **Vector Storage**: Simpan ke database (bisa MySQL sederhana untum MVP atau Vector DB).

### Phase 3: Retrieval (Pencarian)
- [ ] Saat user chat, cari potongan teks paling relevan dari PDF/URL/Manual.
- [ ] Gabungkan potongan teks itu sebagai "Konteks" untuk AI menjawab.
