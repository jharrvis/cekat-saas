<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Models\WhatsAppDevice;
use InvalidArgumentException;

/**
 * Single source of truth for plan tier limits, features, and AI tier.
 * All data comes from the plans table; no tier rules live in callers.
 */
class PlanLimitService
{
    public const LIMITS = [
        'total_agents' => 'max_agents',
        'total_channels' => 'max_widgets',
        'active_channels' => 'max_widgets',
        'monthly_messages' => 'max_messages_per_month',
        'knowledge_documents' => 'max_documents',
        'file_size_mb' => 'max_file_size_mb',
        'faqs' => 'max_faqs',
        'chat_history_days' => 'chat_history_days',
        'whatsapp_devices' => 'max_whatsapp_devices',
    ];

    public const FEATURES = [
        'leads',
        'whatsapp',
        'custom_branding',
        'analytics',
        'priority_support',
        'api_access',
        'white_label',
    ];

    public const ABILITIES = [
        'ai_summarize',
    ];

    private const SCHEMA_DEFAULTS = [
        'name' => 'Free',
        'slug' => 'free',
        'price' => 0,
        'max_widgets' => 1,
        'max_agents' => 1,
        'max_messages_per_month' => 100,
        'max_documents' => 3,
        'max_file_size_mb' => 5,
        'max_faqs' => 10,
        'chat_history_days' => 7,
        'max_whatsapp_devices' => 1,
        'can_export_leads' => false,
        'can_use_whatsapp' => false,
        'ai_tier' => 'basic',
        'allowed_models' => null,
        'features' => null,
        'is_active' => true,
    ];

    public function defaultPlan(): ?Plan
    {
        return Plan::query()
            ->where('is_active', true)
            ->where('price', 0)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public function planFor(User $user): Plan
    {
        if ($user->plan) {
            return $user->plan;
        }

        return $this->defaultPlan() ?? new Plan(self::SCHEMA_DEFAULTS);
    }

    public function limit(Plan|User $subject, string $key): int
    {
        if (! isset(self::LIMITS[$key])) {
            throw new InvalidArgumentException("Unknown plan limit [{$key}].");
        }

        return (int) ($this->resolve($subject)->{self::LIMITS[$key]} ?? 0);
    }

    public function featureValue(Plan|User $subject, string $key): mixed
    {
        if (! in_array($key, self::FEATURES, true)) {
            throw new InvalidArgumentException("Unknown plan feature [{$key}].");
        }

        $plan = $this->resolve($subject);

        return match ($key) {
            'leads' => (bool) $plan->can_export_leads,
            'whatsapp' => (bool) $plan->can_use_whatsapp,
            default => is_array($plan->features) ? ($plan->features[$key] ?? null) : null,
        };
    }

    public function feature(Plan|User $subject, string $key): bool
    {
        if ($subject instanceof User
            && $subject->isAdmin()
            && in_array($key, ['leads', 'whatsapp'], true)) {
            return true;
        }

        $value = $this->featureValue($subject, $key);

        if ($key === 'analytics') {
            return $value === true || $value === 'advanced';
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return (bool) $value;
    }

    public function aiTier(Plan|User $subject): string
    {
        return $this->resolve($subject)->ai_tier ?? 'basic';
    }

    public function allowsModel(Plan|User $subject, string $model): bool
    {
        return in_array($model, $this->resolve($subject)->allowed_models ?? [], true);
    }

    public function check(User $user, string $ability, array $context = []): array
    {
        $plan = $this->planFor($user);

        $result = [
            'allowed' => false,
            'code' => 'denied',
            'message' => '',
            'used' => null,
            'limit' => null,
            'remaining' => null,
            'plan' => $plan->slug,
        ];

        if (array_key_exists($ability, self::LIMITS)) {
            $limit = $this->limit($plan, $ability);
            $used = $this->usedFor($user, $ability, $context);
            $allowed = $ability === 'file_size_mb'
                ? $used <= $limit
                : $used < $limit;

            return array_merge($result, [
                'allowed' => $allowed,
                'code' => $allowed ? 'allowed' : 'limit_exceeded',
                'message' => $allowed ? '' : "Batas {$ability} pada paket Anda tercapai.",
                'used' => $used,
                'limit' => $limit,
                'remaining' => max(0, $limit - $used),
            ]);
        }

        if (in_array($ability, self::FEATURES, true)) {
            $allowed = $this->feature($user, $ability);

            return array_merge($result, [
                'allowed' => $allowed,
                'code' => $allowed ? 'allowed' : 'feature_locked',
                'message' => $allowed ? '' : "Fitur {$ability} tidak tersedia pada paket Anda.",
            ]);
        }

        if ($ability === 'ai_summarize') {
            $allowed = (float) $plan->price > 0;

            return array_merge($result, [
                'allowed' => $allowed,
                'code' => $allowed ? 'allowed' : 'paid_required',
                'message' => $allowed ? '' : 'Fitur ini hanya tersedia pada paket berbayar.',
            ]);
        }

        throw new InvalidArgumentException("Unknown plan ability [{$ability}].");
    }

    /**
     * The single user-facing message for a plan-limit denial (T-05):
     * "Paket {plan} Anda terbatas {limit} {unit}. Tingkatkan paket untuk menambah."
     * Rendered via the plan-limit-alert component from the 'plan_limit_error'
     * flash key. Locale is pinned to 'id' until the app default locale
     * becomes Indonesian (T-12).
     */
    public function limitMessage(User $user, string $ability): string
    {
        $plan = $this->planFor($user);

        return (string) __('plans.limit_reached', [
            'plan' => $plan->name ?? 'Free',
            'limit' => $this->limit($plan, $ability),
            'unit' => (string) __('plans.unit_' . $ability, [], 'id'),
        ], 'id');
    }

    public function usage(User $user, string $key, array $context = []): array
    {
        if (! array_key_exists($key, self::LIMITS)) {
            throw new InvalidArgumentException("Unknown plan limit [{$key}].");
        }

        $plan = $this->planFor($user);
        $limit = $this->limit($plan, $key);
        $used = $this->usedFor($user, $key, $context);

        return [
            'key' => $key,
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'plan' => $plan->slug,
        ];
    }

    private function resolve(Plan|User $subject): Plan
    {
        return $subject instanceof Plan ? $subject : $this->planFor($subject);
    }

    private function usedFor(User $user, string $key, array $context): int
    {
        if (array_key_exists('used', $context)) {
            return (int) $context['used'];
        }

        if ($key === 'monthly_messages') {
            return (int) $user->monthly_message_used;
        }

        if ($key === 'file_size_mb') {
            if (array_key_exists('size_bytes', $context)) {
                return (int) ceil(((int) $context['size_bytes']) / 1024 / 1024);
            }

            return (int) ($context['size_mb'] ?? 0);
        }

        if ($key === 'active_channels') {
            return (int) $user->widgets()->where('status', 'active')->count();
        }

        if ($key === 'total_agents') {
            return (int) $user->aiAgents()->count();
        }

        if ($key === 'total_channels') {
            return (int) $user->widgets()->count();
        }

        if ($key === 'whatsapp_devices') {
            return (int) WhatsAppDevice::where('user_id', $user->id)->count();
        }

        return 0;
    }
}
