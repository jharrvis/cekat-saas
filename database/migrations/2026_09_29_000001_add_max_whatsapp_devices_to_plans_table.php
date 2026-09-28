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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'max_whatsapp_devices')) {
                $table->integer('max_whatsapp_devices')->default(1)->after('can_use_whatsapp');
            }
        });

        // Preserve the previously hard-coded slug-based device limits:
        // starter/free => 1 (column default), pro => 3, business => 10.
        \DB::table('plans')->whereIn('slug', ['pro', 'professional'])->update(['max_whatsapp_devices' => 3]);
        \DB::table('plans')->whereIn('slug', ['business', 'enterprise'])->update(['max_whatsapp_devices' => 10]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_whatsapp_devices');
        });
    }
};
