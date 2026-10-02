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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['newsletter', 'announcement']);
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            // Segment filters resolved to a recipient snapshot on start:
            // {verified: bool, status: '', 'active'|'suspended'|'banned', plan_id: int|'', role: '', 'user'|'admin'}
            $table->json('segment')->nullable();
            $table->json('recipients')->nullable(); // snapshot of user ids at start
            $table->enum('status', ['draft', 'sending', 'sent', 'stopped', 'failed'])->default('draft');
            $table->unsignedInteger('cursor')->default(0);
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('status');
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
