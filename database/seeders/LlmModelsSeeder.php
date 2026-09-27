<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LlmModel;

/**
 * Model catalogue for admin AI Models & Tiers.
 * Every model_id below was verified live against
 * https://openrouter.ai/api/v1/models on 2026-09-27.
 */
class LlmModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            // Free Models
            [
                'model_id' => 'openrouter/free',
                'name' => 'Free Models Router',
                'provider' => 'OpenRouter',
                'description' => 'Router that picks an available free model automatically',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 200000,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 95,
            ],
            [
                'model_id' => 'nvidia/nemotron-3.5-lightning:free',
                'name' => 'Nemotron 3.5 Lightning (free)',
                'provider' => 'NVIDIA',
                'description' => 'Fast free model with 1M context',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 1000000,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 90,
            ],
            [
                'model_id' => 'nvidia/nemotron-3-super-120b-a12b:free',
                'name' => 'Nemotron 3 Super 120B (free)',
                'provider' => 'NVIDIA',
                'description' => 'Free 120B MoE model for strong general answers',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 262144,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 85,
            ],
            [
                'model_id' => 'qwen/qwen3.8-27b:free',
                'name' => 'Qwen 3.8 27B (free)',
                'provider' => 'Qwen',
                'description' => 'Free multilingual model with 256K context',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 262144,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 80,
            ],

            // Pro Models
            [
                'model_id' => 'openai/gpt-4o-mini',
                'name' => 'GPT-4o Mini',
                'provider' => 'OpenAI',
                'description' => 'Affordable and capable model for most tasks',
                'input_price' => 0.15,
                'output_price' => 0.60,
                'context_length' => 128000,
                'allowed_tiers' => ['pro', 'business'],
                'is_active' => true,
                'popularity' => 92,
            ],
            [
                'model_id' => 'anthropic/claude-haiku-4.5',
                'name' => 'Claude Haiku 4.5',
                'provider' => 'Anthropic',
                'description' => 'Fast and efficient Claude model',
                'input_price' => 1.00,
                'output_price' => 5.00,
                'context_length' => 200000,
                'allowed_tiers' => ['pro', 'business'],
                'is_active' => true,
                'popularity' => 88,
            ],
            [
                'model_id' => 'google/gemini-3.7-flash',
                'name' => 'Gemini 3.7 Flash',
                'provider' => 'Google',
                'description' => 'Fast Google model with 1M context',
                'input_price' => 0.75,
                'output_price' => 3.75,
                'context_length' => 1048576,
                'allowed_tiers' => ['pro', 'business'],
                'is_active' => true,
                'popularity' => 84,
            ],

            // Business Models
            [
                'model_id' => 'openai/gpt-4o',
                'name' => 'GPT-4o',
                'provider' => 'OpenAI',
                'description' => 'Most capable OpenAI model with vision',
                'input_price' => 2.50,
                'output_price' => 10.00,
                'context_length' => 128000,
                'allowed_tiers' => ['business'],
                'is_active' => true,
                'popularity' => 98,
            ],
            [
                'model_id' => 'anthropic/claude-sonnet-4.5',
                'name' => 'Claude Sonnet 4.5',
                'provider' => 'Anthropic',
                'description' => 'Best for complex reasoning and coding',
                'input_price' => 3.00,
                'output_price' => 15.00,
                'context_length' => 1000000,
                'allowed_tiers' => ['business'],
                'is_active' => true,
                'popularity' => 97,
            ],
        ];

        foreach ($models as $model) {
            LlmModel::updateOrCreate(
                ['model_id' => $model['model_id']],
                $model
            );
        }

        // Retire catalogue rows whose model id OpenRouter no longer serves
        LlmModel::whereIn('model_id', [
            'nvidia/llama-3.1-nemotron-70b-instruct:free',
            'deepseek/deepseek-r1:free',
            'google/gemini-2.0-flash-exp:free',
            'anthropic/claude-3.5-haiku',
            'anthropic/claude-3.5-sonnet',
            'anthropic/claude-3-opus',
            'google/gemini-pro-1.5',
        ])->update(['is_active' => false]);

        $this->command->info('✅ Seeded ' . count($models) . ' LLM models');
    }
}
