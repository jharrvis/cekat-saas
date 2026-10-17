<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill SumoPod list prices (config/sumopod_prices.php, USD per
     * 1M tokens) onto the already-seeded/imported provider=sumopod
     * catalogue rows, so the Models Manager shows real prices instead
     * of a misleading "Free". Ids absent from the snapshot keep 0,
     * which the UI renders as unknown for SumoPod rows.
     */
    public function up(): void
    {
        foreach (config('sumopod_prices', []) as $modelId => $price) {
            DB::table('llm_models')
                ->where('provider', 'sumopod')
                ->where('model_id', $modelId)
                ->update([
                    'input_price' => $price['input'],
                    'output_price' => $price['output'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('llm_models')
            ->where('provider', 'sumopod')
            ->whereIn('model_id', array_keys(config('sumopod_prices', [])))
            ->update([
                'input_price' => 0,
                'output_price' => 0,
                'updated_at' => now(),
            ]);
    }
};
