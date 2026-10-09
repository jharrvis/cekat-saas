<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Account-level Fonnte devices have no tenant owner: allow
     * user_id to be null and flag those rows as platform devices so
     * the admin sync can import them (previously sync was update-only
     * and could never surface a device that was not created locally).
     */
    public function up(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->boolean('is_platform')->default(false)->after('user_id');
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->dropColumn('is_platform');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
