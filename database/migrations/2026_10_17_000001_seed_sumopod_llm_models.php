<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the SumoPod gateway's models into the LLM catalogue so they
     * can be picked per AI tier in Admin > AI Models & Tiers. Rows are
     * marked provider=sumopod; the tier manager stores their selection
     * as "sumopod:<model_id>" and LlmProviderChain routes accordingly.
     * Prices are SumoPod-account-specific, so they stay 0 here and the
     * tier dropdown shows no price suffix for SumoPod rows.
     */
    private array $models = [
        ['model_id' => 'kimi-k2-5-260127', 'name' => 'Kimi K2.5', 'context_length' => 128000, 'popularity' => 90],
        ['model_id' => 'qwen3.6-plus', 'name' => 'Qwen 3.6 Plus', 'context_length' => 131072, 'popularity' => 85],
        ['model_id' => 'claude-sonnet-4-6', 'name' => 'Claude Sonnet 4.6', 'context_length' => 200000, 'popularity' => 80],
        ['model_id' => 'deepseek-v4-pro', 'name' => 'DeepSeek V4 Pro', 'context_length' => 131072, 'popularity' => 75],
        ['model_id' => 'qwen3.6-flash', 'name' => 'Qwen 3.6 Flash', 'context_length' => 131072, 'popularity' => 70],
        ['model_id' => 'gpt-4o-mini', 'name' => 'GPT-4o mini', 'context_length' => 128000, 'popularity' => 65],
    ];

    public function up(): void
    {
        foreach ($this->models as $model) {
            DB::table('llm_models')->updateOrInsert(
                ['model_id' => $model['model_id']],
                array_merge($model, [
                    'provider' => 'sumopod',
                    'description' => 'Model di gateway SumoPod (ai.sumopod.com). Ketersediaan mengikuti akun SumoPod yang terhubung; admin dapat menambah/mengubah model di pengelola model.',
                    'input_price' => 0,
                    'output_price' => 0,
                    'allowed_tiers' => json_encode(['basic', 'standard', 'advanced', 'premium']),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]),
            );
        }
    }

    public function down(): void
    {
        DB::table('llm_models')
            ->where('provider', 'sumopod')
            ->whereIn('model_id', array_column($this->models, 'model_id'))
            ->delete();
    }
};
