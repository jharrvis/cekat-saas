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
        Schema::create('whatsapp_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('widget_id')->nullable()->constrained()->onDelete('set null');

            // Fonnte Device Info
            $table->string('fonnte_device_id')->nullable();
            $table->string('fonnte_device_token')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('device_name')->nullable();

            // Connection Status
            $table->enum('status', ['pending', 'connecting', 'connected', 'disconnected', 'expired'])->default('pending');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();

            // Plan & Quota
            $table->enum('plan', ['free', 'lite', 'regular', 'regular_pro', 'master', 'super', 'advanced', 'ultra'])->default('free');
            $table->integer('messages_sent')->default(0);
            $table->integer('messages_received')->default(0);
            $table->timestamp('plan_expires_at')->nullable();

            // Settings
            $table->json('settings')->nullable(); // Custom settings per device
            $table->boolean('is_active')->default(true);
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('fonnte_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_devices');
    }
};
