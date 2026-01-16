<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Widget;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeFaq;

class DemoWidgetSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@cekat.biz.id'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'plan_tier' => 'pro',
                'monthly_message_quota' => 2000,
                'monthly_message_used' => 150,
            ]
        );

        // Create demo widget
        $widget = Widget::firstOrCreate(
            ['slug' => 'default'],
            [
                'user_id' => $user->id,
                'name' => 'Cekat Demo Widget',
                'settings' => [
                    'model' => 'nvidia/nemotron-3-nano-30b-a3b:free',
                    'color' => '#0f172a',
                    'greeting' => 'Halo! 👋 Selamat datang. Kami lagi ada promo **Early Access Diskon 50%**. Mau info lengkapnya?',
                    'position' => 'bottom-right',
                ],
                'is_active' => true,
            ]
        );

        // Create knowledge base
        $kb = KnowledgeBase::firstOrCreate(
            ['widget_id' => $widget->id],
            [
                'company_name' => 'Cekat.biz.id',
                'company_description' => 'Platform SaaS untuk membuat chatbot AI customer service cerdas yang bisa di-embed ke website manapun dalam hitungan menit.',
                'persona_name' => 'Chika',
                'persona_tone' => 'friendly, helpful, casual',
                'custom_instructions' => 'Jelaskan dengan bahasa yang santai dan mudah dimengerti. Fokus pada kemudahan penggunaan (no-code) dan kecepatan setup. Gunakan emoji sesekali.',
            ]
        );

        // Create FAQs
        $faqs = [
            [
                'question' => 'Apa itu Cekat.biz.id?',
                'answer' => 'Cekat.biz.id adalah platform yang memungkinkan kamu membuat chatbot AI untuk customer service. Chatbot ini bisa belajar dari dokumen PDF atau link website kamu, dan bisa dipasang di website mana saja!',
                'sort_order' => 1,
            ],
            [
                'question' => 'Bisa belajar dari file PDF?',
                'answer' => 'Bisa banget, Kak! Kamu tinggal upload file PDF manual produk atau SOP perusahaan, nanti AI kami akan mempelajarinya (seperti sistem Open Book) untuk menjawab pertanyaan pelanggan.',
                'sort_order' => 2,
            ],
            [
                'question' => 'Apakah bisa belajar dari Link Website?',
                'answer' => 'Tentu bisa! Cukup masukkan URL website kamu, dan sistem kami akan scanning kontennya untuk dijadikan pengetahuan si chatbot. Praktis kan?',
                'sort_order' => 3,
            ],
            [
                'question' => 'Bagaimana cara pasangnya di website saya?',
                'answer' => 'Gampang banget. Setelah bikin bot, kamu akan dapat kode script kecil. Tinggal copy-paste kode itu ke halaman website kamu, dan widget chat akan langsung muncul.',
                'sort_order' => 4,
            ],
            [
                'question' => 'Apakah ada free trial?',
                'answer' => 'Ada dong! Kamu bisa coba paket Starter GRATIS selamanya dengan kuota 100 chat per bulan. Cocok buat coba-coba dulu.',
                'sort_order' => 5,
            ],
            [
                'question' => 'Berapa harga paket Pro?',
                'answer' => 'Paket Pro cuma Rp 299.000/bulan. Kamu dapat 3 Widget, 2.000 Chat/bulan, Custom Branding (Hapus logo Cekat), dan Model Lebih Pintar (GPT-4o-mini).',
                'sort_order' => 6,
            ],
            [
                'question' => 'Berapa harga paket Business?',
                'answer' => 'Paket Business Rp 799.000/bulan. Fitur lengkap: 10 Widget, 10.000 Chat/bulan, Analytics Lengkap, Model Premium (GPT-4o/Claude Sonnet).',
                'sort_order' => 7,
            ],
        ];

        foreach ($faqs as $faq) {
            KnowledgeFaq::firstOrCreate(
                [
                    'knowledge_base_id' => $kb->id,
                    'question' => $faq['question'],
                ],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $faq['sort_order'],
                ]
            );
        }

        $this->command->info('✅ Demo widget seeded successfully!');
        $this->command->info('   User: demo@cekat.biz.id / password');
        $this->command->info('   Widget: default');
        $this->command->info('   FAQs: ' . count($faqs) . ' created');
    }
}
