<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Decision 2026-09-27: WhatsApp Gateway available for Pro and Business.
        DB::table('plans')->whereIn('slug', ['pro', 'business'])->update(['can_use_whatsapp' => true]);
    }

    public function down(): void
    {
        // Only revert Pro; Business is seeded with can_use_whatsapp=true by DefaultPlansSeeder.
        DB::table('plans')->where('slug', 'pro')->update(['can_use_whatsapp' => false]);
    }
};
