<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Visitor identity columns are encrypted at rest (ChatSession custom
     * setters): the base64 ciphertext of even a short phone number is
     * ~104 chars, which overflowed visitor_phone (varchar 50) and could
     * overflow visitor_name/visitor_email (varchar 255) for longer
     * values — Data too long (1406) aborted SendLeadNotification's
     * persist, so captured leads were never saved.
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->text('visitor_name')->nullable()->change();
            $table->text('visitor_email')->nullable()->change();
            $table->text('visitor_phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->string('visitor_name')->nullable()->change();
            $table->string('visitor_email')->nullable()->change();
            $table->string('visitor_phone', 50)->nullable()->change();
        });
    }
};
