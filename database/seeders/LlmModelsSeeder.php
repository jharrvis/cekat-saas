<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LlmModel;

class LlmModelsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            // Free Models
            [
                'model_id' => 'nvidia/llama-3.1-nemotron-70b-instruct:free',
                'name' => 'Llama 3.1 Nemotron 70B',
                'provider' => 'NVIDIA',
                'description' => 'Fast and free model optimized for instruction following',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 131072,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 90,
            ],
            [
                'model_id' => 'deepseek/deepseek-r1:free',
                'name' => 'DeepSeek R1',
                'provider' => 'DeepSeek',
                'description' => 'Free reasoning model with strong capabilities',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 64000,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 95,
            ],
            [
                'model_id' => 'google/gemini-2.0-flash-exp:free',
                'name' => 'Gemini 2.0 Flash',
                'provider' => 'Google',
                'description' => 'Fast and efficient model from Google',
                'input_price' => 0,
                'output_price' => 0,
                'context_length' => 1048576,
                'allowed_tiers' => ['starter', 'pro', 'business'],
                'is_active' => true,
                'popularity' => 85,
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
                'model_id' => 'anthropic/claude-3.5-haiku',
                'name' => 'Claude 3.5 Haiku',
                'provider' => 'Anthropic',
                'description' => 'Fast and efficient Claude model',
                'input_price' => 0.80,
                'output_price' => 4.00,
                'context_length' => 200000,
                'allowed_tiers' => ['pro', 'business'],
                'is_active' => true,
                'popularity' => 88,
            ],
            [
                'model_id' => 'google/gemini-pro-1.5',
                'name' => 'Gemini Pro 1.5',
                'provider' => 'Google',
                'description' => 'Advanced Google model with 1M context',
                'input_price' => 1.25,
                'output_price' => 5.00,
                'context_length' => 1000000,
                'allowed_tiers' => ['pro', 'business'],
                'is_active' => true,
                'popularity' => 80,
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
                'model_id' => 'anthropic/claude-3.5-sonnet',
                'name' => 'Claude 3.5 Sonnet',
                'provider' => 'Anthropic',
                'description' => 'Best for complex reasoning and coding',
                'input_price' => 3.00,
                'output_price' => 15.00,
                'context_length' => 200000,
                'allowed_tiers' => ['business'],
                'is_active' => true,
                'popularity' => 97,
            ],
            [
                'model_id' => 'anthropic/claude-3-opus',
                'name' => 'Claude 3 Opus',
                'provider' => 'Anthropic',
                'description' => 'Most powerful Claude model',
                'input_price' => 15.00,
                'output_price' => 75.00,
                'context_length' => 200000,
                'allowed_tiers' => ['business'],
                'is_active' => true,
                'popularity' => 85,
            ],
        ];

        foreach ($models as $model) {
            LlmModel::updateOrCreate(
                ['model_id' => $model['model_id']],
                $model
            );
        }

        $this->command->info('✅ Seeded ' . count($models) . ' LLM models');
    }
}
