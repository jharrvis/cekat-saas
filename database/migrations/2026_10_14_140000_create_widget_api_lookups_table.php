<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API Lookup: definisi sumber data eksternal per channel yang bisa
 * dipanggil chatbot saat percakapan (mis. cari nomor perkara ke
 * api.pa-salatiga.go.id). Fase 1: GET-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_api_lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained('widgets')->cascadeOnDelete();
            $table->string('key', 64)->comment('ID unik per channel, dipakai AI di blok JSON');
            $table->string('name', 120);
            $table->text('description')->nullable()->comment('Kapan AI boleh memakai lookup ini');
            $table->string('method', 8)->default('GET');
            $table->text('url_template')->comment('URL dengan placeholder {param}');
            $table->json('params')->nullable()->comment('[{name,label,required,description,example}]');
            $table->json('headers')->nullable()->comment('[{name,value}] — terenkripsi');
            $table->string('auth_type', 32)->default('none');
            $table->text('auth_secret')->nullable()->comment('Terenkripsi');
            $table->json('auth_config')->nullable()->comment('{header_name} atau {param_name}');
            $table->unsignedSmallInteger('timeout_seconds')->default(8);
            $table->json('response_mapping')->nullable()->comment('{field: dot.path}');
            $table->string('empty_path', 255)->nullable()->comment('Dot-path untuk deteksi hasil kosong');
            $table->text('reply_template')->nullable()->comment('Template balasan chat dengan {field}');
            $table->string('empty_message', 500)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->unsignedInteger('cache_ttl_seconds')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['widget_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_api_lookups');
    }
};
