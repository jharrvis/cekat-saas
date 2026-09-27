<?php

namespace App\Listeners;

use App\Events\AdminSettingsChanged;
use App\Events\ChatRequestProcessed;
use App\Events\DocumentStatusChanged;
use App\Events\DomainBlocked;
use App\Events\LeadCaptured;
use App\Events\QuotaExceeded;
use App\Events\WebhookActionTriggered;
use Illuminate\Support\Facades\Log;

/**
 * Structured audit log for chat/billing/admin lifecycle events.
 * Never logs secrets, tokens, message content, or lead PII.
 */
class LogSystemEvent
{
    public function subscribe($events): void
    {
        $events->listen(ChatRequestProcessed::class, [self::class, 'onChatProcessed']);
        $events->listen(QuotaExceeded::class, [self::class, 'onQuotaExceeded']);
        $events->listen(DomainBlocked::class, [self::class, 'onDomainBlocked']);
        $events->listen(WebhookActionTriggered::class, [self::class, 'onWebhookAction']);
        $events->listen(LeadCaptured::class, [self::class, 'onLeadCaptured']);
        $events->listen(DocumentStatusChanged::class, [self::class, 'onDocumentStatus']);
        $events->listen(AdminSettingsChanged::class, [self::class, 'onAdminSettings']);
    }

    public function onChatProcessed(ChatRequestProcessed $e): void
    {
        Log::info('chat.request_processed', [
            'widget' => $e->widgetSlug,
            'user_id' => $e->userId,
            'session' => $e->sessionId,
            'model' => $e->model,
            'tokens' => $e->tokensUsed,
        ]);
    }

    public function onQuotaExceeded(QuotaExceeded $e): void
    {
        Log::warning('chat.quota_exceeded', [
            'user_id' => $e->userId,
            'widget' => $e->widgetSlug,
            'used' => $e->used,
            'limit' => $e->limit,
        ]);
    }

    public function onDomainBlocked(DomainBlocked $e): void
    {
        Log::warning('chat.domain_blocked', [
            'widget' => $e->widgetSlug,
            'origin' => $e->origin,
        ]);
    }

    public function onWebhookAction(WebhookActionTriggered $e): void
    {
        Log::info('chat.webhook_action', [
            'widget' => $e->widgetSlug,
            'action' => $e->action,
        ]);
    }

    public function onLeadCaptured(LeadCaptured $e): void
    {
        Log::info('chat.lead_captured', [
            'widget' => $e->widgetSlug,
            'fields' => $e->fieldsPresent,
        ]);
    }

    public function onDocumentStatus(DocumentStatusChanged $e): void
    {
        Log::info('knowledge.document_status', [
            'document_id' => $e->documentId,
            'knowledge_base_id' => $e->knowledgeBaseId,
            'from' => $e->from,
            'to' => $e->to,
        ]);
    }

    public function onAdminSettings(AdminSettingsChanged $e): void
    {
        Log::info('admin.settings_changed', [
            'group' => $e->group,
            'user_id' => $e->userId,
        ]);
    }
}
