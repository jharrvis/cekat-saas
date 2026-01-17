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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_device_id')->constrained()->onDelete('cascade');
            $table->foreignId('widget_id')->nullable()->constrained()->onDelete('set null');

            // Message Info
            $table->string('sender_phone'); // Phone number of sender
            $table->string('sender_name')->nullable(); // Push name from WhatsApp
            $table->enum('direction', ['inbound', 'outbound']); // inbound = received, outbound = sent
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'audio', 'video', 'document', 'location', 'contact'])->default('text');
            $table->string('media_url')->nullable(); // For media messages

            // Status
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('fonnte_message_id')->nullable();
            $table->text('error_message')->nullable();

            // AI Processing
            $table->boolean('is_ai_response')->default(false);
            $table->string('ai_model_used')->nullable();
            $table->integer('tokens_used')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['whatsapp_device_id', 'created_at']);
            $table->index('sender_phone');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
