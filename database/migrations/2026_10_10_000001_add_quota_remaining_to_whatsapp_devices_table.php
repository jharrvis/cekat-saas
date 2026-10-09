<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fonnte reports each device's remaining message quota on every
     * getDevices call, but Cekat never stored it, so the Device
     * Monitor could not show it. Refreshed at every sync.
     */
    public function up(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->integer('quota_remaining')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->dropColumn('quota_remaining');
        });
    }
};
