# Cekat.biz.id — AI Customer Service SaaS

Laravel 11 + Livewire 3 application for AI customer service agents: users create an
**AI Agent**, add **Knowledge Base** content, attach the agent to **Channels**
(Web Widget, WhatsApp, Landing Chatbot), then monitor conversations, leads,
analytics, quota, and billing. Admins manage users, plans, AI model tiers,
system settings, and operational monitoring.

Workflow: **Agent → Knowledge → Channel → Monitor**. See `agent.md` (agent SOP),
`docs/ARCHITECTURE.md` (system design), and `plan/` (planning documents).

## Requirements

- PHP ^8.2 (composer), Node 18+ (npm), a database (MySQL in production, SQLite for tests)
- OpenRouter API key (LLM), Midtrans keys (billing), Google OAuth (optional login), Fonnte token (WhatsApp, stored via Settings)

## Local setup

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
npm run dev          # vite
php artisan serve    # app (http://localhost:8000)
```

See `.env.example` for all required variables (`OPENROUTER_*`, `MIDTRANS_*`,
`GOOGLE_*`, `FONNTE_ACCOUNT_TOKEN`, mail, queue, cache).

## Queue, jobs & scheduler

Long work runs on the `database` queue (`QUEUE_CONNECTION=database`):

```bash
php artisan queue:listen --tries=1
```

Jobs: `ProcessDocumentJob` (PDF/DOCX/TXT parsing + chunking),
`GenerateChatSummary` (per-session summaries).

Scheduler (`php artisan schedule:run` every minute, see `routes/console.php`):

- `quota:reset` — reset `monthly_message_used` for all users (monthly)
- `plans:check-expiry` — H-7/H-3/H-1 reminders, expiry downgrade to free plan

## Widget build

The embeddable widget is hand-built JS (not Vite):

```bash
npm run build:widget   # terser public/widget/widget.js -> widget.min.js (+map)
```

Rules: every change to `public/widget/widget.js` **must** rebuild `widget.min.js`
and bump the `?v=` query in all embed sources
(`user/integration`, `channels/tabs/embed`, `widget-customizer` preview,
WordPress plugin `CEKAT_WIDGET_VERSION`). Regression check:

```bash
node tests/widget/parseMarkdown.regression.mjs   # 9 cases, tests the real function
```

WordPress plugin ZIP for `/downloads`:

```bash
php artisan plugin:build-wp
```

## Tests

```bash
php artisan test                                          # full suite (sqlite :memory:)
php artisan test --filter=ChatApiTest                    # chat API incl. quota/domain/suspend
php artisan test --filter=PolicyTest                     # ownership + admin boundaries
php artisan test --filter=UiSmokeTest                    # page rendering + legacy redirects
node tests/widget/parseMarkdown.regression.mjs           # widget link rendering
```

## Chat API (stable shape)

`POST /api/chat` `{message, widgetId, history[], sessionId}` →
`{success, response, sessionId, usage, meta{model, tokens_used}}`.
Failures keep legacy fields and add `error_code`:
`widget_not_found`, `domain_blocked`, `owner_missing`, `account_suspended`,
`quota_exceeded` (429), `provider_error` (fallback message, 200).

## Deployment notes

- `php artisan migrate --force` on release; never remove legacy FK columns
  (`knowledge_bases.widget_id`) until data is migrated (see `docs/ARCHITECTURE.md`)
- `public/widget/widget.min.js` cache: version query is the busting mechanism;
  CDN/Cloudflare must allow query strings through
- CSRF-exempt: `/api/chat`, `/api/payment/notification`, `/api/widget/*`,
  `/api/whatsapp/webhook/*` (see `bootstrap/app.php`)
- Widget testing with ngrok for Fonnte webhooks: `docs/whatsapp-ngrok-testing.md`
- Server deploy (HestiaCP): `docs/deployment-guide.md`
