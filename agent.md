# Universal AI Agent SOP

This file defines the standard operating procedure for any AI agent, LLM, or automated coding assistant working on this project. Follow it before, during, and after every change.

## Mission

Improve Cekat SaaS as a professional Laravel and Livewire application without damaging existing behavior, user data, documentation, or developer workflow.

The product domain is an AI customer service SaaS. The core concepts are:

- AI Agent: the brain, persona, prompt behavior, and AI behavior settings.
- Knowledge Base: the agent's business knowledge and reference material.
- Channel: a deployment surface such as Web Widget, WhatsApp, or Landing Chatbot.
- Conversation: a chat session from any channel.
- Lead: customer contact or business opportunity captured from a conversation.
- Plan and Billing: quota, limits, feature access, payment, and AI quality tier.
- Admin Operations: global settings, users, plans, model tiers, integrations, and monitoring.

## Non-Negotiable Rules

- Do not overwrite or delete user changes unless the user explicitly asks.
- Always inspect relevant files before editing.
- Keep changes scoped to the user's request.
- Prefer existing project patterns over new abstractions.
- Do not put large business logic in Blade views or route closures.
- Do not add new product concepts when an existing domain concept fits.
- Do not expose AI model or provider identity to users at all (owner policy, T-21): never name a model/provider in UI copy, public API responses, emails, or docs; do not surface internal AI tier names to users either — plans are communicated through quota and features. Model/tier mapping is admin-only (see `docs/i18n-inventory.md` and `ModelMaskingTest`).
- Do not change database relationships without a migration and compatibility plan.
- Do not leave documentation outdated after behavior, routes, environment variables, migrations, or workflows change.
- Do not claim tests passed unless they were actually run.

## Exploration Before Editing

Before making changes, inspect the current implementation:

- Check `git status --short`.
- Read relevant routes in `routes/`.
- Read related models in `app/Models/`.
- Read related controllers, Livewire components, jobs, services, and views.
- Read related migrations and seeders when data shape may change.
- Read relevant docs in `docs/` and plans in `plan/`.
- Search with `rg` before assuming a file or symbol is unused.

If unclear whether something is product intent or legacy behavior, preserve compatibility and document the assumption.

## Architecture Standards

Use this responsibility split:

- Controllers: request orchestration only.
- Form Requests: validation and authorization for incoming requests.
- Services: business logic and integrations.
- Models: relationships, casts, simple domain helpers, and query scopes.
- Policies/Gates: ownership and permission decisions.
- Jobs: long-running work such as document parsing, summaries, crawling, and outbound integrations.
- Livewire components: UI state and interaction logic, not core domain policy.
- Blade views: presentation only.

For chat workflows, prefer service boundaries such as:

- Chat orchestration.
- Prompt building.
- Model resolution.
- Quota enforcement.
- Domain validation.
- Lead capture.
- Webhook actions.
- Conversation persistence.

## Product Workflow Standards

The main user workflow should remain:

1. Create or choose an AI Agent.
2. Add Knowledge Base content.
3. Attach the agent to one or more Channels.
4. Monitor Conversations, Leads, Analytics, quota, and Billing.

Avoid UI or code that makes `AI Agent`, `Chatbot Widget`, `Knowledge Base`, and `WhatsApp` look like unrelated products. They are connected parts of one system.

## UI/UX Standards

Use an operational SaaS dashboard style:

- Clear hierarchy.
- Dense but readable layouts.
- Predictable navigation.
- One primary action per screen.
- Helpful empty states.
- Loading, error, success, and disabled states.
- Filterable tables for admin and monitoring pages.
- Confirmation dialogs for destructive actions.
- Inline validation near the affected field.

Accessibility requirements:

- Visible focus states.
- Real labels for form controls.
- Keyboard-accessible controls.
- Touch targets at least 44px.
- Text contrast suitable for light and dark mode.
- Status must not rely on color alone.
- Icon-only buttons need accessible names.

Avoid:

- Marketing-style dashboard screens.
- Duplicate navigation concepts.
- Placeholder-only form labels.
- Hidden or ambiguous destructive actions.
- UI text that explains implementation details instead of helping the user complete the task.

## Laravel and Livewire Standards

- Use Laravel conventions for routing, validation, authorization, services, jobs, and events.
- Use named routes consistently.
- Use route model binding where appropriate.
- Keep multi-tenant ownership checks explicit.
- Use transactions for multi-step writes that must stay consistent.
- Use queues for slow or external work.
- Use config and settings models for configurable behavior instead of hardcoding values.
- Use casts for JSON fields.
- Use eager loading to avoid obvious N+1 queries.
- Keep Livewire components small enough to understand and test.

## Database and Migration Standards

- Migrations must be reversible unless there is a clear reason.
- Data migrations must be idempotent.
- Preserve existing production data.
- For ownership changes, support a transition path before removing legacy columns.
- Add indexes for frequent lookups, foreign keys, filters, and ownership checks.
- Do not remove legacy fields until code paths and data have been migrated.

## Integration Standards

- External API calls must have timeouts.
- Log integration failures with enough context to debug without leaking secrets.
- Never log API keys, tokens, secrets, passwords, or full sensitive payloads.
- Webhook payloads should be signed when a secret exists.
- Slow or unreliable outbound calls should be queued where possible.
- User-facing errors should explain recovery, not internal stack details.

## Testing Standards

Add or update tests when behavior changes.

Minimum expected coverage:

- Feature tests for user-facing workflows.
- Unit tests for service classes.
- Policy tests for ownership and admin boundaries.
- Regression tests for bug fixes.
- API tests for stable response shapes.

Important scenarios:

- User cannot access another user's agent, channel, session, or knowledge base.
- Suspended or banned users cannot use protected workflows.
- Quota exceeded returns the expected response.
- Domain-restricted widgets reject unauthorized origins.
- Admin routes require admin access.
- Chat API handles inactive agent, missing widget, missing owner, and provider failure.

## Documentation Standards

Update documentation when any of these change:

- Setup steps.
- Environment variables.
- Routes or public APIs.
- Database schema.
- Queue or scheduler behavior.
- Deployment steps.
- Business workflow.
- Admin settings.
- Feature flags or plan limits.

Use:

- `README.md` for project setup and developer onboarding.
- `docs/ARCHITECTURE.md` for system architecture and domain model.
- `plan/` for planning documents and implementation plans.
- `agent.md` for this SOP.

## Pre-Final Checklist

Before reporting completion:

- Confirm changed files are intentional.
- Run relevant tests or explain exactly why they were not run.
- Run build commands if frontend or widget assets changed.
- Check for broken links or route names when moving UI/navigation.
- Check that docs match behavior.
- Summarize changes in plain language.
- Mention remaining risks or follow-up work.

## Communication Standard

Be concise, factual, and specific.

When reporting work:

- Say what changed.
- Say what was verified.
- Say what was not verified.
- Mention file paths when useful.
- Do not overstate confidence.
- Do not hide assumptions.

## Cekat SaaS Defaults

When implementation details are not specified, use these defaults:

- Treat AI Agent as the primary product object.
- Treat Web Widget and WhatsApp as channels.
- Keep model names and AI tier names hidden from normal users entirely; they exist only in admin surfaces, logs, and the `ai_model_used` column.
- Keep admin controls explicit and operational.
- Keep user workflows simple and guided.
- Localization is mandatory, not cosmetic (owner policy, T-12): Indonesian (`id`) is the default locale; every user-facing string goes through Laravel localization keys in `lang/id/` with an identical-key mirror in `lang/en/` (key parity is guarded by `LocaleTest`). Never hardcode user-facing copy in Blade, controllers, Livewire, mailables, or JS — add a lang key instead. Per-user language lives in `users.locale`; adding a language means adding a `lang/<code>` folder.
- Preserve existing data and backward compatibility unless the user approves a breaking migration.
