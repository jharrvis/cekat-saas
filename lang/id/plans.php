<?php

/*
 * Pesan batas paket (remediasi T-05, fondasi lokalisasi T-12).
 *
 * Semua penolakan karena batas paket memakai pola tunggal ini agar pengguna
 * selalu melihat: paketnya, batasnya, dan jalan keluarnya. Kunci yang sama
 * WAJIB ada di lang/en/plans.php.
 *
 * Catatan transisi: selama locale bawaan aplikasi belum 'id' (T-12),
 * PlanLimitService::limitMessage() memanggil kunci ini dengan locale 'id'
 * secara eksplisit agar pengguna selalu menerima Bahasa Indonesia.
 */

return [
    'limit_reached' => 'Paket :plan Anda terbatas :limit :unit. Tingkatkan paket untuk menambah.',
    'view_plans' => 'Lihat Paket',

    // Satuan per ability PlanLimitService::LIMITS
    'unit_total_agents' => 'agen',
    'unit_total_channels' => 'channel',
    'unit_active_channels' => 'channel aktif',
    'unit_knowledge_documents' => 'dokumen',
    'unit_faqs' => 'FAQ',
    'unit_whatsapp_devices' => 'perangkat WhatsApp',
    'unit_monthly_messages' => 'pesan per bulan',
];
