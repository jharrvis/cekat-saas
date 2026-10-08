<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External-sync identity for knowledge documents: integrations (the
 * WordPress/WooCommerce plugin first) upsert one document per external
 * item, keyed by (knowledge_base_id, source, external_ref) - e.g.
 * source "woocommerce", external_ref "wc-product-123". Manually added
 * documents keep both columns null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('type');
            $table->string('external_ref', 191)->nullable()->after('source');
            $table->index(['knowledge_base_id', 'source', 'external_ref'], 'kb_docs_source_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->dropIndex('kb_docs_source_ref_idx');
            $table->dropColumn(['source', 'external_ref']);
        });
    }
};
