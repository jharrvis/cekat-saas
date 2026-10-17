<?php

/**
 * SumoPod public list prices (USD per 1M tokens, input/output).
 *
 * The account API (GET /v1/models) carries no pricing, and the
 * public catalogue feed (api-gate.sumopod.com) was inactive when
 * this snapshot was taken on 2026-10-17 from the last published
 * catalogue compilation. Treat these as list prices, not
 * account-specific rates: admins can override any model price
 * in the Models Manager, and Fetch from SumoPod refreshes the
 * values for ids present here.
 */
return [
    'MiniMax-M2.7-highspeed' => ['input' => 0.02, 'output' => 0.06],
    'MiniMax-M3' => ['input' => 0.3, 'output' => 1.2],
    'claude-haiku-4-5' => ['input' => 1.0, 'output' => 5.0],
    'claude-opus-4-6' => ['input' => 5.0, 'output' => 25.0],
    'claude-opus-4-7' => ['input' => 5.0, 'output' => 25.0],
    'claude-opus-4-8' => ['input' => 5.0, 'output' => 25.0],
    'claude-sonnet-4-6' => ['input' => 3.0, 'output' => 15.0],
    'deepseek-v3-2' => ['input' => 0.28, 'output' => 0.42],
    'deepseek-v4-flash' => ['input' => 0.14, 'output' => 0.28],
    'deepseek-v4-pro' => ['input' => 0.435, 'output' => 0.87],
    'gemini/gemini-2.5-flash' => ['input' => 0.3, 'output' => 2.5],
    'gemini/gemini-2.5-flash-lite' => ['input' => 0.1, 'output' => 0.4],
    'gemini/gemini-2.5-pro' => ['input' => 1.25, 'output' => 10.0],
    'gemini/gemini-3-flash-preview' => ['input' => 0.5, 'output' => 3.0],
    'gemini/gemini-3.1-flash-lite' => ['input' => 0.25, 'output' => 1.5],
    'gemini/gemini-3.1-pro-preview' => ['input' => 2.0, 'output' => 12.0],
    'gemini/gemini-3.5-flash' => ['input' => 1.5, 'output' => 9.0],
    'gemma-4-31b-it' => ['input' => 0.12, 'output' => 0.37],
    'glm-4-7' => ['input' => 0.6, 'output' => 2.2],
    'glm-5' => ['input' => 0.1, 'output' => 0.32],
    'glm-5-turbo' => ['input' => 0.1, 'output' => 0.32],
    'glm-5.1' => ['input' => 0.1, 'output' => 0.32],
    'gpt-4.1' => ['input' => 2.0, 'output' => 8.0],
    'gpt-4.1-mini' => ['input' => 0.4, 'output' => 1.6],
    'gpt-4.1-nano' => ['input' => 0.1, 'output' => 0.4],
    'gpt-4o' => ['input' => 2.5, 'output' => 10.0],
    'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.6],
    'gpt-5' => ['input' => 1.25, 'output' => 10.0],
    'gpt-5-mini' => ['input' => 0.25, 'output' => 2.0],
    'gpt-5-nano' => ['input' => 0.05, 'output' => 0.4],
    'gpt-5.1' => ['input' => 1.25, 'output' => 10.0],
    'gpt-5.1-codex' => ['input' => 1.25, 'output' => 10.0],
    'gpt-5.1-codex-mini' => ['input' => 0.25, 'output' => 2.0],
    'gpt-5.2' => ['input' => 1.75, 'output' => 14.0],
    'gpt-5.2-codex' => ['input' => 1.75, 'output' => 14.0],
    'gpt-5.3-codex' => ['input' => 1.75, 'output' => 14.0],
    'gpt-5.4' => ['input' => 2.5, 'output' => 15.0],
    'gpt-5.4-mini' => ['input' => 0.75, 'output' => 4.5],
    'gpt-5.4-nano' => ['input' => 0.2, 'output' => 1.25],
    'kimi-k2.6' => ['input' => 0.08, 'output' => 0.35],
    'mimo-v2.5' => ['input' => 0.14, 'output' => 0.28],
    'mimo-v2.5-pro' => ['input' => 0.435, 'output' => 0.87],
    'qwen3.6-27b' => ['input' => 0.32, 'output' => 3.2],
    'qwen3.6-flash' => ['input' => 0.125, 'output' => 0.75],
    'qwen3.6-plus' => ['input' => 0.25, 'output' => 1.5],
    'qwen3.7-max' => ['input' => 1.25, 'output' => 3.75],
    'seed-2-0-code' => ['input' => 0.5, 'output' => 3.0],
    'seed-2-0-lite' => ['input' => 0.25, 'output' => 2.0],
    'seed-2-0-mini' => ['input' => 0.1, 'output' => 0.4],
    'seed-2-0-pro' => ['input' => 0.5, 'output' => 3.0],
    'text-embedding-3-large' => ['input' => 0.13, 'output' => 0.0],
    'text-embedding-3-small' => ['input' => 0.02, 'output' => 0.0],
];
