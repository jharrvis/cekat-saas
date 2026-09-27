# Business Workflow, UI/UX, Robustness, Documentation, and Agent SOP Plan

## Summary

Cekat SaaS is a Laravel 11 and Livewire 3 application for AI customer service agents, knowledge bases, embeddable web widgets, WhatsApp integration, billing, admin operations, and chat inbox workflows.

The main improvement goal is to remove overlapping product concepts and make the system easier to understand, operate, extend, test, and document. The intended product workflow is:

1. Create or choose an AI Agent.
2. Add knowledge to the agent.
3. Attach the agent to one or more channels.
4. Monitor conversations, leads, analytics, quota, and billing.

## Branch and Production Safety

- Do all workflow, UI/UX, and robustness implementation work on a new branch, not directly on the production branch.
- Recommended branch name: `feature/business-workflow-ui-ux-robustness`.
- Keep production hotfixes separate from workflow refactors unless the hotfix is explicitly being backported into the branch.
- Before merging the workflow branch, confirm that any production hotfixes made directly on the server are represented in the branch or intentionally documented as server-only exceptions.
- Do not deploy this workflow branch to production until widget rendering, chat API, channel settings, and dashboard smoke tests pass.

## Domain Model

- AI Agent: the brain, persona, behavior, prompt configuration, and agent-level settings.
- Knowledge Base: the source data and context owned by an AI Agent.
- Channel: where an AI Agent is deployed, such as Web Widget, WhatsApp, or Landing Chatbot.
- Conversation: a chat session created through any channel.
- Lead: a qualified contact or business outcome captured from a conversation.
- Plan and Billing: limits, quota, feature access, transactions, and model quality tier.
- Admin Operations: system-wide configuration, users, plans, model tiers, billing, WhatsApp settings, and operational monitoring.

## Workflow Changes

- Make AI Agent the primary user-facing object.
- Reposition Chatbot Widget as a channel, not a separate core concept.
- Move the user mental model from "create chatbot" to "create agent, then publish it to channels".
- Keep Knowledge Base editing inside the AI Agent workflow.
- Keep embed code, allowed domains, widget appearance, webhook, and channel analytics inside Web Widget channel settings.
- Keep WhatsApp setup inside Channel settings, while preserving dedicated WhatsApp operational screens where needed.

Recommended user navigation:

- Dashboard
- AI Agents
- Channels
- Inbox
- Leads
- Analytics
- Billing
- Settings
- Integration

Recommended admin navigation:

- Overview
- Users
- Plans and Billing
- AI Models and Tiers
- System Settings
- Landing Chatbot
- WhatsApp
- Integrations
- Audit and Logs

## Backend Refactor Plan

- Move large chat business logic out of `App\Http\Controllers\Api\ChatController`.
- Add dedicated services:
  - `ChatOrchestrator`
  - `PromptBuilder`
  - `ModelResolver`
  - `QuotaService`
  - `DomainAccessService`
  - `LeadCaptureService`
  - `WebhookActionService`
- Use Form Request classes for validation on chat, agents, channels, billing, admin settings, and integrations.
- Add Policy or Gate checks for AI Agents, channels/widgets, knowledge bases, chat sessions, WhatsApp devices, and admin modules.
- Keep backward compatibility for existing `knowledge_bases.widget_id` only during transition.
- Target final ownership: Knowledge Base belongs to AI Agent through `ai_agent_id`.
- Standardize chat API responses with `success`, `response`, `sessionId`, `meta`, and `error_code` for failures.
- Preserve and harden Web Widget message rendering during the refactor:
  - Assistant replies containing plain URLs must render as clickable links.
  - Markdown links such as `[label](https://example.com)` must render once as valid anchors, without nested anchors or encoded fragments like `%3Ca%20href=`.
  - Auto-linking plain URLs must not re-process URLs already inside generated anchor `href` attributes.
  - All generated links should include `target="_blank"` and `rel="noopener noreferrer"`.
  - Keep user messages rendered as plain text to avoid XSS.
- Add system events or logs for:
  - chat request processed,
  - quota exceeded,
  - domain blocked,
  - webhook action triggered,
  - lead captured,
  - document processing status changed,
  - admin settings changed.

## UI/UX Standard

- Use an operational SaaS dashboard style: calm, dense, readable, and task-first.
- Avoid marketing-style layouts inside the product dashboard.
- Every main screen should include:
  - clear page title,
  - concise status summary,
  - one primary action,
  - useful empty state,
  - loading state,
  - error state,
  - success feedback.
- Agent editor tabs:
  - Profile
  - Behavior
  - Knowledge
  - Channels
  - Testing
  - Advanced
- Web Widget channel editor tabs:
  - General
  - Appearance
  - Lead Capture
  - Allowed Domains
  - Embed
  - Webhook
  - Analytics
- Admin UI should prioritize monitoring and operational control with filterable tables, status badges, safe bulk actions, and confirmation dialogs for destructive actions.
- Accessibility requirements:
  - visible focus states,
  - real labels on form fields,
  - touch targets at least 44px,
  - field-level errors,
  - readable contrast,
  - no color-only status meaning,
  - keyboard-accessible controls.

## Documentation Plan

- Replace the default Laravel README with a Cekat SaaS README that includes:
  - product overview,
  - local setup,
  - required environment variables,
  - queue and job instructions,
  - widget build instructions,
  - test commands,
  - deployment notes.
- Create `docs/ARCHITECTURE.md` as the single source of truth for:
  - domain model,
  - Agent-Knowledge-Channel-Conversation relationships,
  - chat lifecycle,
  - billing and quota lifecycle,
  - admin responsibility boundaries.
- Keep detailed planning documents in `plan/`.
- Archive or mark outdated plans once implemented.
- Keep `agent.md` as the universal SOP for future AI agent development work.

## Test Plan

- Feature tests:
  - user creates, edits, activates, and deletes AI Agents,
  - user creates and edits Web Widget channels,
  - user links and unlinks agent to channel,
  - chat API success path,
  - quota exceeded,
  - domain blocked,
  - inactive agent,
  - suspended user.
- Widget rendering regression tests:
  - plain URL reply: `https://www.floodbar.id/order`,
  - markdown link reply: `[halaman order Floodbar](https://www.floodbar.id/order)`,
  - numbered list containing both markdown links and plain URLs,
  - trailing punctuation after URLs,
  - no rendered output contains `%3Ca%20href=`, `&lt;a href`, or nested anchor tags.
- Unit tests:
  - `ModelResolver`,
  - `QuotaService`,
  - `PromptBuilder`,
  - `DomainAccessService`,
  - `WebhookActionService`.
- Policy tests:
  - user cannot access another user's agent, channel, session, or knowledge base,
  - admin routes require admin role,
  - suspended or banned users cannot use protected workflows.
- UI smoke tests:
  - user dashboard,
  - agent editor,
  - channel editor,
  - admin users,
  - admin plans,
  - admin settings.
- Documentation acceptance:
  - a new developer can run the project from README instructions,
  - architecture is understandable from `docs/ARCHITECTURE.md`,
  - AI agents can follow project standards from `agent.md`.

## Assumptions

- Product name remains Cekat.biz.id.
- Main market remains Indonesian AI customer service SaaS.
- AI Agent is the primary product concept.
- Web Widget is a channel, not the core product object.
- Agent handoff is not required in the first cleanup phase.
- Existing data should be preserved with transitional compatibility.
