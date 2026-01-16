<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->enum('status', ['active', 'ended', 'abandoned'])->default('active')->after('ended_at');
            $table->text('summary')->nullable()->after('status'); // AI-generated summary
            $table->string('device_type', 50)->nullable()->after('user_agent'); // Mobile/Desktop
            $table->json('location_data')->nullable()->after('ip_address'); // GeoIP data
            $table->text('referer_url')->nullable()->after('source_url');
            $table->boolean('is_lead')->default(false)->after('is_converted'); // Marked as promising lead

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'summary', 'device_type', 'location_data', 'referer_url', 'is_lead']);
        });
    }
};
