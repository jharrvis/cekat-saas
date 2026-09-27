# Cekat SaaS Architecture

Single source of truth for the domain model, relationships, and lifecycles.
SOP for changes: `agent.md`. Plans: `plan/`.

## Domain model

- **AI Agent** (`ai_agents`): the brain — persona, behavior, prompt config,
  temperature, greeting/fallback. Primary user-facing object.
- **Knowledge Base** (`knowledge_bases` + `knowledge_faqs`, `knowledge_documents`,
  `document_chunks`): source data owned by an AI Agent via `ai_agent_id`.
  Legacy `widget_id` is kept nullable for backward compatibility only;
  new code must use `ai_agent_id`.
- **Channel**: where an agent is deployed. Today: **Web Widget** (`widgets`,
  `slug`-addressed, `settings` JSON: appearance, allowed domains, webhook,
  lead config) and **WhatsApp** (`whatsapp_devices`, `whatsapp_messages`
  via Fonnte — separate history, not `chat_sessions`). Landing Chatbot is a
  reserved widget (`slug = landing-page-default`, admin-managed).
- **Conversation**: `chat_sessions` (one per `widget_id` + `visitor_uuid`) with
  `chat_messages`. `current_agent_id` / `messages.ai_agent_id` record which
  agent served the turn.
- **Lead**: qualified outcome from a conversation. Captured two ways:
  prompt/trigger instruction (Strategy 1–2 in widget `settings`) and
  AI-emitted JSON actions (`save_lead`) forwarded to the widget webhook
  (`WebhookActionService`). Session visitor columns (`visitor_name/email/phone`,
  `is_lead`) are the read/export surface (`LeadController`).
- **Plan & Billing** (`plans`, `transactions`, Midtrans Snap): quota
  (`max_messages_per_month`, `max_widgets`, `max_documents`, ...), feature
  flags (`can_export_leads`, `can_use_whatsapp`), and AI quality tier
  (`ai_tier`: basic/standard/advanced/premium → model via `ai_tier_mapping`
  Setting, resolved by `ModelResolver`). Users never pick raw model names
  (except the admin landing widget).
- **Admin Operations**: users, plans, LLM catalogue (`llm_models`) + tier
  mapping, system `settings`, landing chatbot, WhatsApp settings, audit via
  structured logs (`LogSystemEvent`).

```
User ─┬─ AiAgent ─┬─ KnowledgeBase ─┬─ KnowledgeFaq
      │           │                 ├─ KnowledgeDocument ─┬─ content/chunks (json)
      │           │                 │                    └─ DocumentChunk (relational)
      │           │                 └─ (legacy) Widget ─┐
      │           └─ Widget (channel) ─┬─ ChatSession ─┬─ ChatMessage
      │                                └─ WhatsAppDevice ─┬─ WhatsAppMessage
      └─ Plan ─┬─ quota/limits/features/ai_tier         └─ (Fonnte traffic)
               └─ Transaction (Midtrans)
```

## Chat lifecycle (`ChatOrchestrator::handle`)

1. Resolve `Widget` by `slug` (+ `aiAgent.knowledgeBase`, `user.plan`).
   Unknown slug → demo JSON (`storage/app/data/knowledge-base.json`) or 404
   `widget_not_found`.
2. `DomainAccessService`: `allowed_domains` vs Origin/Referer (localhost bypass,
   empty header allowed — legacy). Deny → 403 `domain_blocked` + `DomainBlocked`.
3. `QuotaService`: landing slug bypasses; missing owner → 404 `owner_missing`;
   suspended/banned → 403 `account_suspended`; exhausted plan → 429
   `quota_exceeded` + `QuotaExceeded`.
4. `PromptBuilder`: agent KB first, widget KB fallback; agent `system_prompt`
   branch or default persona prompt + FAQ + first 2 chunks/doc + lead prompt +
   function-calling instructions.
5. `LeadCaptureService`: count/keyword trigger appends the ask-contact instruction.
6. `ModelResolver`: plan `ai_tier` → `ai_tier_mapping` → model; agent supplies
   only `temperature`.
7. OpenRouter call (60s timeout) → `responseText`; provider error/empty →
   `provider_error` fallback body (HTTP 200, legacy shape).
8. `WebhookActionService`: strict-JSON action → dispatch + friendly replacement;
   mixed reply → JSON stripped. `save_lead` additionally fires `LeadCaptured`
   (field names only, no PII) and `WebhookActionTriggered`.
9. Persist session + both messages (with `current_agent_id`/`ai_agent_id`),
   `QuotaService::consume` (skip landing), fire `ChatRequestProcessed`.

Response shape: `{success, response, sessionId, usage, meta{model,tokens_used}}`;
failures add `error_code`. All legacy fields are preserved for old widgets.

## Billing & quota lifecycle

- Purchase: `PaymentController::createTransaction` (Snap token) → Midtrans
  `notification` webhook / `finish` → `activatePlan` (`plan_id`, `+1 month`
  expiry, quota reset, `PaymentSuccess` mail). Admin can activate manually
  (`TransactionMonitor`).
- Usage: `monthly_message_used` increments per assistant reply (web + WA paths
  must both call `QuotaService::consume`).
- Scheduler: `quota:reset` (monthly), `plans:check-expiry` (reminders H-7/3/1,
  downgrade to price-0 plan on expiry).

## Admin boundaries

- `is.admin` middleware gates `/admin/*`; `IsAdmin` = `role === 'admin'`.
  No Gate policies for admin modules (single-role model).
- User routes additionally pass `user.status` (suspended/banned → info page).
- Resource ownership is enforced by Policies
  (`AiAgent`, `Widget`, `KnowledgeBase`, `ChatSession`, `WhatsAppDevice`)
  via `Gate::authorize`, plus scoped queries and ownership-aware
  Form Requests (`Rule::exists()->where(user_id)`).
- `AdminSettingsChanged`, `DocumentStatusChanged` (observer), and all chat
  lifecycle events are logged structurally without secrets or PII.

## Conventions & constraints

- Controllers orchestrate; Requests validate+authorize; Services hold logic;
  Policies own permissions; Jobs do slow/external work; Livewire holds UI
  state; Blade presents (`agent.md`).
- Never put large logic in Blade or route closures; never expose raw model
  names to users; migrations stay reversible and cross-driver
  (mysql/pgsql/sqlite); `knowledge_bases.widget_id` stays until the
  transition is declared complete.
