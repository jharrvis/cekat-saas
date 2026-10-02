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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50); // otp, welcome, new-lead, campaign-newsletter, ...
            $table->string('mailable', 191)->nullable();
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error')->nullable();
            $table->longText('body')->nullable(); // rendered HTML for preview (purged after 90 days)
            $table->json('meta')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained('email_campaigns')->onDelete('set null');
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('recipient');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
