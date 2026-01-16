<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

                // Limits
                $table->integer('max_widgets')->default(1);
                $table->integer('max_messages_per_month')->default(100);
                $table->integer('max_documents')->default(3);
                $table->integer('max_file_size_mb')->default(5);
                $table->integer('max_faqs')->default(10);

                // Features (JSON)
                $table->json('allowed_models')->nullable();
                $table->json('features')->nullable();

                // Status
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);

                $table->timestamps();
            });
        }

        // Add plan_id to users table if not exists
        if (!Schema::hasColumn('users', 'plan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('plan_id')->nullable()->after('role')->constrained('plans')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });

        Schema::dropIfExists('plans');
    }
};
