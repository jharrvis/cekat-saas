<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Widget;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeFaq;

class LandingPageChatbotSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update landing page widget
        $widget = Widget::updateOrCreate(
            ['slug' => 'landing-page-default'],
            [
                'user_id' => null,
                'name' => 'landing-page-default',
                'display_name' => 'Cekat AI Assistant',
                'description' => 'Default AI chatbot for cekat.biz.id landing page',
                'is_active' => true,
                'status' => 'active',
                'settings' => [
                    'model' => 'nvidia/llama-3.1-nemotron-70b-instruct:free',
                    'theme' => 'dark',
                    'position' => 'bottom-right',
                ],
            ]
        );

        // Create or update knowledge base
        $kb = KnowledgeBase::updateOrCreate(
            ['widget_id' => $widget->id],
            [
                'company_name' => 'Cekat.biz.id',
                'company_description' => 'Platform SaaS untuk membuat AI Chatbot Customer Service yang bisa di-training dengan data bisnis Anda. Cocok untuk website, e-commerce, dan berbagai jenis bisnis online.',
                'persona_name' => 'Cekat AI',
                'persona_tone' => 'friendly',
                'custom_instructions' => 'Kamu adalah AI Assistant untuk Cekat.biz.id, platform pembuatan chatbot AI untuk customer service. Selalu promosikan fitur-fitur Cekat dan arahkan pengunjung untuk mendaftar. Gunakan bahasa Indonesia yang casual dan friendly.',
            ]
        );

        // Clear existing FAQs for this knowledge base
        KnowledgeFaq::where('knowledge_base_id', $kb->id)->delete();

        // Add FAQs about Cekat.biz.id
        $faqs = [
            // Tentang Platform
            [
                'question' => 'Apa itu Cekat.biz.id?',
                'answer' => 'Cekat.biz.id adalah platform SaaS untuk membuat AI Chatbot Customer Service. Dengan Cekat, kamu bisa membuat chatbot yang pintar menjawab pertanyaan pelanggan berdasarkan data bisnis kamu sendiri. Cocok untuk website, toko online, atau bisnis apa pun yang butuh customer service 24/7!',
                'category' => 'general',
            ],
            [
                'question' => 'Bagaimana cara kerja Cekat?',
                'answer' => 'Cara kerjanya simple: 1) Daftar akun gratis, 2) Buat chatbot baru, 3) Training chatbot dengan FAQ, dokumen, atau crawl website kamu, 4) Pasang widget di website kamu dengan copy-paste kode. Chatbot langsung aktif 24/7!',
                'category' => 'general',
            ],
            [
                'question' => 'Apa bedanya Cekat dengan chatbot biasa?',
                'answer' => 'Chatbot biasa cuma bisa jawab dari template yang sudah ditentukan. Cekat menggunakan AI (seperti GPT-4, Claude, dll) yang bisa memahami konteks dan menjawab dengan natural. Plus, kamu bisa training dengan data bisnis sendiri jadi jawabannya akurat sesuai produk/layananmu!',
                'category' => 'general',
            ],

            // Fitur
            [
                'question' => 'Fitur apa saja yang ada di Cekat?',
                'answer' => 'Fitur utama Cekat: ✅ Knowledge Base Training (FAQ, dokumen, website crawl), ✅ Pilihan berbagai model AI (GPT-4, Claude, Llama, dll), ✅ Widget yang bisa di-customize, ✅ Analytics untuk tracking performa, ✅ Multi-chatbot support, ✅ Integrasi WordPress & website lainnya.',
                'category' => 'features',
            ],
            [
                'question' => 'Bisa training dengan dokumen?',
                'answer' => 'Bisa banget! Kamu bisa upload dokumen PDF, Word, atau text untuk training chatbot. AI akan mempelajari isi dokumen dan bisa menjawab pertanyaan berdasarkan konten tersebut. Perfect untuk product manual, SOP, atau dokumen FAQ yang sudah ada!',
                'category' => 'features',
            ],
            [
                'question' => 'Apakah bisa crawl website untuk training?',
                'answer' => 'Ya! Fitur Website Crawler memungkinkan kamu memasukkan URL website dan Cekat akan otomatis mengambil konten halaman tersebut untuk training. Cocok kalau kamu punya banyak halaman produk atau artikel yang ingin dipelajari chatbot.',
                'category' => 'features',
            ],
            [
                'question' => 'Model AI apa saja yang tersedia?',
                'answer' => 'Cekat menyediakan berbagai model AI: 🆓 Gratis: Llama 3.1, Gemini Flash ⭐ Pro: GPT-4o Mini, Claude Haiku 💎 Business: GPT-4o, Claude 3.5 Sonnet. Kamu bisa pilih sesuai kebutuhan dan budget!',
                'category' => 'features',
            ],

            // Harga
            [
                'question' => 'Berapa harga langganan Cekat?',
                'answer' => 'Cekat punya 3 paket: 🆓 STARTER (Gratis): 1 chatbot, 100 pesan/bulan, model AI gratis. ⭐ PRO (Rp 99.000/bulan): 3 chatbot, 1000 pesan, model AI premium. 💎 BUSINESS (Rp 299.000/bulan): 10 chatbot, 5000 pesan, semua model AI. Ada promo Early Access diskon 50% lho!',
                'category' => 'pricing',
            ],
            [
                'question' => 'Apakah ada versi gratis?',
                'answer' => 'Ada! Paket Starter gratis selamanya dengan 1 chatbot dan 100 pesan per bulan. Cocok untuk testing atau bisnis yang baru mulai. Kalau sudah ketagihan, bisa upgrade ke Pro atau Business kapan saja 😄',
                'category' => 'pricing',
            ],
            [
                'question' => 'Bagaimana cara bayar?',
                'answer' => 'Pembayaran bisa melalui transfer bank, e-wallet (GoPay, OVO, Dana), atau kartu kredit. Setelah pembayaran dikonfirmasi, paket akan langsung aktif. Untuk langganan, bisa pilih bulanan atau tahunan (lebih hemat!).',
                'category' => 'pricing',
            ],

            // Teknis
            [
                'question' => 'Bagaimana cara pasang di website?',
                'answer' => 'Gampang banget! Setelah buat chatbot, kamu akan dapat kode JavaScript. Tinggal copy-paste kode tersebut ke website kamu sebelum tag </body>. Untuk WordPress, bisa pakai plugin resmi Cekat yang tinggal install dan masukkan Widget ID.',
                'category' => 'technical',
            ],
            [
                'question' => 'Apakah support WordPress?',
                'answer' => 'Support banget! Kami punya plugin WordPress resmi yang bisa diinstall langsung dari dashboard. Tinggal masukkan Widget ID dari akun Cekat kamu, dan chatbot langsung muncul di website WordPress. Mudah kan?',
                'category' => 'technical',
            ],
            [
                'question' => 'Bisa untuk Shopify atau Wix?',
                'answer' => 'Untuk Shopify dan Wix, kamu bisa pakai metode JavaScript embed. Copy kode widget dari dashboard Cekat, lalu paste ke bagian custom code di Shopify/Wix. Tutorial lengkap ada di halaman Integration setelah login.',
                'category' => 'technical',
            ],
            [
                'question' => 'Apakah data saya aman?',
                'answer' => 'Keamanan data adalah prioritas kami! Data training dan percakapan disimpan dengan enkripsi. Kami tidak membagikan data kamu ke pihak ketiga. Server menggunakan SSL dan di-host di data center terpercaya.',
                'category' => 'technical',
            ],

            // Support
            [
                'question' => 'Bagaimana cara menghubungi support?',
                'answer' => 'Ada beberapa cara: 1) Chat dengan saya di sini untuk pertanyaan umum, 2) Email ke support@cekat.biz.id untuk bantuan teknis, 3) WhatsApp di +62 812-xxxx-xxxx untuk respon cepat. Tim support aktif Senin-Jumat jam 9-18 WIB.',
                'category' => 'support',
            ],
            [
                'question' => 'Apakah ada tutorial atau dokumentasi?',
                'answer' => 'Ada! Setelah login ke dashboard, kamu bisa akses dokumentasi lengkap di bagian Help Center. Ada tutorial video, artikel step-by-step, dan FAQ. Kalau masih bingung, langsung chat tim support ya!',
                'category' => 'support',
            ],

            // Kompetitor/Perbandingan
            [
                'question' => 'Kenapa pilih Cekat daripada kompetitor?',
                'answer' => 'Alasan pilih Cekat: ✅ Harga terjangkau (mulai gratis!), ✅ Bahasa Indonesia native, ✅ Support lokal yang responsif, ✅ Pilihan model AI lengkap, ✅ Mudah digunakan tanpa coding, ✅ Integrasi WordPress plugin. Plus, dikembangkan oleh tim Indonesia yang paham kebutuhan bisnis lokal!',
                'category' => 'general',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            KnowledgeFaq::create([
                'knowledge_base_id' => $kb->id,
                'question' => $faq['question'],
                'answer' => $faq['answer'],
            ]);
        }

        $this->command->info('✅ Landing page chatbot seeded with ' . count($faqs) . ' FAQs!');
        $this->command->info('   Widget ID: ' . $widget->id);
        $this->command->info('   Slug: ' . $widget->slug);
    }
}
