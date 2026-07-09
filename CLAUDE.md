# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **📌 Product source of truth & live task board (Notion): [BlackDesck — Project Status & Roadmap](https://app.notion.com/p/38058300fb49810b9f72ed4a3f195fd2)** — project overview, the remaining roadmap (Phases P1–P9), and the **BlackDesck — Tasks** database (72 tasks, each with an overview + checklist). Scope and day-to-day task status live there; mirror status changes when you finish work. This `CLAUDE.md` documents the system **as built** and *how* to build it.
>
> **Docs in `docs/`:** [`AI_FEATURES.md`](docs/AI_FEATURES.md) (complete AI-features walkthrough) · [`DIGITALOCEAN_DEPLOY.md`](docs/DIGITALOCEAN_DEPLOY.md) (production deploy runbook).
> *(The former spec/roadmap/research docs were removed 2026-07 — the roadmap now lives on the Notion board. Two decisions worth remembering from them: strategy is a **managed, verified, white-glove expert network** — GLG-style, not a self-serve gig board; and money-out shipped as **Stripe Connect (Express)** with W-8/W-9 contractor tax-form automation consciously deferred — revisit a Trolley/Tipalti-style provider if that becomes a hard requirement.)*
>
> **🛠 How-we-build conventions: [`.claude/skills/blackdesck-conventions/SKILL.md`](.claude/skills/blackdesck-conventions/SKILL.md)** — the authoritative coding conventions (Actions/DTOs/enums, where logic belongs, money, the verification gate). There is also a `code-auditor` subagent (`~/.claude/agents/code-auditor.md`) that reviews changes + writes/runs tests.

## Project Overview

**BlackDesck** is an international consultation platform connecting clients with experts. The platform is the intermediary: it owns the relationship, money flow, and all communication. Experts have no platform access — managed entirely by admins.

**Roles:** `admin`, `client` (via `spatie/laravel-permission`). New self-registered users default to `client`.

**Key patterns:**
- `lorisleiva/laravel-actions` — all business logic in single-action classes under `app/Actions/`. Actions use `AsAction` trait and can run as controllers, jobs, or listeners.
- `spatie/laravel-data` — all DTOs in `app/Data/`. Define `rules()` for validation; use `fromModel()` static factory for model → DTO conversion.
- **Thin controllers** — Actions registered directly as route handlers (`Route::post(..., MyAction::class)`). No dedicated controller classes for new features. (Exception: legacy `Settings/ProfileController` from the starter kit still backs `routes/settings.php`.)
- **Money** — always `brick/money`, never floats. Store as integer minor units + currency column. Format via `Money::ofMinor()`.
- **UUIDs** for public-facing route binding — never expose auto-increment IDs in URLs.
- **Code organization** — `app/Models` and `app/Support` stay **flat** (`Support` = small cross-cutting, stateless helpers like `Uploads`, `DashboardMetrics`). `app/Services` holds integrations/wrappers and gets a **domain subfolder once it has 2+ related classes** (e.g. `Services/Payment/*`); a lone service stays flat (e.g. `Services/SettingsService.php`). Don't create single-file folders just for uniformity — group by domain, not per class.

## Commands

**Development (runs all services concurrently):**
```bash
composer dev
```
Starts: Laravel server, queue worker, Pail log viewer, Vite dev server.

**Additional services (run separately):**
```bash
php artisan horizon          # queue monitoring dashboard
php artisan reverb:start     # WebSocket server for real-time notifications
php artisan schedule:work    # run scheduled tasks (dev); prod uses a `schedule:run` cron
```
Scheduled tasks live in `routes/console.php` (e.g. the daily lead follow-up digest at 08:00). In production they require the server cron entry `* * * * * php artisan schedule:run`.

**Run tests:**
```bash
composer test                          # lint check + PHPUnit
php artisan test                       # PHPUnit only
php artisan test --filter TestName     # single test
composer ci:check                      # full gate: PHP lint + ESLint + Prettier + tsc + tests
```
Tests run on in-memory SQLite (see `phpunit.xml`); the app's primary DB is MySQL. Framework is **PHPUnit** (not Pest). Tests live in `tests/Unit` (pure logic — unsaved models, no DB) and `tests/Feature` (`RefreshDatabase` + HTTP; `Role::findOrCreate(...)` for spatie roles, `Notification::fake()`, `Inertia\Testing\AssertableInertia` for page props).

- **Mock external APIs with `Http::fake([...])`** — Stripe/PayPal/**Gemini** must never be hit. Critically, also assert the **request you send** with `Http::assertSent(...)`, not just the canned response (a real PayPal capture bug — empty body → `MALFORMED_REQUEST_JSON` — was invisible to response-only mocks). AI tests fake `generativelanguage.googleapis.com/*` and assert the prompt/headers.
- `php artisan test` runs locally (the `pdo_sqlite` extension is installed). If a future environment lacks it, the run fails with "could not find driver" — install `php8.5-sqlite3`, or run against MySQL: `DB_CONNECTION=mysql DB_DATABASE=consultdesk_test DB_USERNAME=root DB_PASSWORD=… php artisan test`.

**Linting / formatting:**
```bash
composer lint          # PHP (Pint, auto-fix)
composer lint:check    # PHP lint dry-run
npm run lint           # ESLint (auto-fix)
npm run format         # Prettier (auto-fix)
npm run types:check    # TypeScript type check
```

**Wayfinder (regenerate after route changes):**
```bash
php artisan wayfinder:generate --with-form
```

## Architecture

### Request lifecycle
All routing in Laravel. `HandleInertiaRequests` (`app/Http/Middleware/HandleInertiaRequests.php`) injects shared props (`auth.user`, `name`, `sidebarOpen`) into every Inertia page. Root Blade view: `resources/views/app.blade.php`; React mounts from `resources/js/app.tsx`.

### Layout resolution
`app.tsx` auto-assigns layouts by Inertia page name (switch in `createInertiaApp`):
- `welcome` → no layout
- `consultation/*` → no layout (standalone guest checkout flow)
- `portal/*` → `ClientLayout` (client portal shell)
- `auth/*` → `AuthLayout`
- `settings/*` → `[AppLayout, SettingsLayout]` (nested)
- everything else (incl. all `admin/*`) → `AppLayout` (sidebar shell)

Layout files live in `resources/js/layouts/`: `app-layout.tsx`, `auth-layout.tsx`, `client-layout.tsx`, and `settings/layout.tsx`. `app.tsx` also wraps every page in `TooltipProvider` + Sonner `Toaster`, and intercepts 419 (CSRF-expired) responses to silently refresh the token and prompt a resubmit.

### Authentication
**Laravel Fortify** (`FortifyServiceProvider`). Features: registration, email verification, 2FA (TOTP), passkeys, password confirmation. Views rendered via `Inertia::render()` in `configureViews()`. Auth pages: `resources/js/pages/auth/`.

### Roles & Permissions
`spatie/laravel-permission`: roles `admin`, `client`. Protect admin routes with `->middleware('role:admin')`. Granular permissions follow the pattern `resource.action` (e.g. `tickets.view`, `invoices.create`).

### Enums
Live in `app/Enums/`. Current enums:
- `BillingInterval` — `PerEngagement | PerMission | Monthly | Yearly`.
- `ConsultationStatus` — `Pending | Paid | Cancelled` (payment state on `ConsultationRequest.status`).
- `ConsultationWorkStatus` — `New | InProgress | Resolved | Closed` (fulfilment state on `ConsultationRequest.work_status`; also exposes `options()` for selects).
- `LeadStage` — `New | Contacted | Quoted | Won | Lost` (CRM pipeline stage on `ConsultationRequest.lead_stage`; `badge()` tones, `isTerminal()`, `options()`). Won is reached only by payment. See Leads / CRM pipeline.
- `ContactChannel` — `Note | Phone | Video | Email` (consultation contact/interaction channel; `isCall()` + `options()`).
- `PaymentStatus` — `Paid | Refunded | Failed | Disputed` (status on the `Payment` model; `FlagPayment` moves it off `Paid`) (+ `options()`).
- `PayoutMethod` — `Stripe` (money-out rail key) (+ `options()`).
- `PayoutStatus` — `Pending | Processing | Paid | Failed`.
- `ProjectStatus` — `Planning | Active | OnHold | Completed | Cancelled` (on `Project.status`; enum-guarded `allowedTransitions()`/`canTransitionTo()`/`transitionOptions()` + `badge()`). In practice only `Active`/`Completed` are reached — projects are auto-managed (see Projects & Milestones).
- `MilestoneStatus` — `Pending | InProgress | Completed` (`isComplete()`, transition guards). Any status is reachable in one step (Pending → Completed allowed).
- `InvoiceStatus` — `Paid | Refunded` (invoices are created already-paid; `badge()`).
- `LeadScoreBand` — `Hot | Warm | Cold` (AI lead-score band; `fromScore(?int)` owns the `70/40` thresholds, plus `label()`/`badge()`). See AI assistance.

All enums are backed (string) and implement `label(): string`. Several also expose `badge(): string` returning a shadcn Badge **variant** (`success|secondary|outline|destructive`) so status colours come from the backend, not hardcoded in the frontend (the DTOs emit a `*_tone` field). There is **no** state-machine package — status transitions are plain backed-enum assignments inside Actions, guarded by the enum's own `canTransitionTo()`.

### Shared model concerns & helpers
- **`app/Concerns/HasUuidKey`** — assigns a `uuid` on create + `getRouteKeyName() = 'uuid'`. **`app/Concerns/HasReference`** — assigns a human reference on insert from per-model `const REFERENCE_PREFIX` + `REFERENCE_PADDING` (e.g. `CD-2026-000123`, `PRJ-2026-0007`, `INV-2026-0004`). Both used by `ConsultationRequest`, `Project`, `Invoice`. (Replaces the old per-model `booted()` uuid/reference closures.)
- **`app/Concerns/FormatsMoney`** — `formattedAmount()`, static `formatMoneyMinor()` (whole units) and `formatMoneyMinorExact()` (with cents, used by invoices). Symbol/code come from `App\Services\SettingsService::currencySymbol()/currencyCode()` (settings-driven), never a hardcoded `'USD'`.
- **`app/Support/Uploads`** — the single source of truth for file uploads: validation profiles (`documentRules()`, `imageRules()`, `proofRules()` — extension-based, allow modern image formats incl. HEIC/AVIF, block svg/exe) **and** `acceptMaps()` (react-dropzone `accept`, shared to the frontend via `HandleInertiaRequests` so dropzones never hardcode types). All uploads go through `app/Services/Media/MediaUploader`.

### Consultation flow (implemented)
Two guest-first entry paths (no auth required), both ending at a gateway-agnostic checkout page that offers every configured rail. A third path, the **custom-plan lead**, has no upfront price and is routed through the admin instead of straight to checkout:

```
GET  /                                       → welcome page with active plans
POST /plans/{plan:slug}/buy                  → StartPlanCheckout → redirect to checkout
GET  /plans/{plan:slug}/consult              → consultation/request form page
POST /plans/{plan:slug}/consult              → StoreConsultationRequest → checkout
GET  /custom-plan                            → consultation/custom-plan form page
POST /custom-plan                            → RequestCustomPlan → unpriced lead (admin prices it, then sends a link)
GET  /consultation/{uuid}/checkout           → ShowCheckout → renders all configured gateways
POST /consultation/{uuid}/pay/{gateway}/session  → CreateGatewaySession (lazy begin() — only when buyer picks a rail; returns clientSecret or order id)
POST /consultation/{uuid}/pay/{gateway}/capture  → CaptureGatewayOrder ("buttons" rails, e.g. PayPal onApprove)
GET  /consultation/{uuid}/pay/{gateway}/return   → ReturnFromGateway (embedded rails redirect here; verify + record)
GET  /consultation/{uuid}/complete           → CompleteConsultation (render-only; payment already settled upstream)
POST /paypal/webhook                         → PaypalWebhook (signature-verified, idempotent)
```

`ConsultationRequest` uses UUID for route binding (`getRouteKeyName() = 'uuid'`). Nothing is initialized on page load — `CreateGatewaySession` lazily calls the gateway's `begin()` only when the buyer selects a method. `ReturnFromGateway` (embedded/redirect rails) and `CaptureGatewayOrder` ("buttons" rails) both verify the callback and delegate to `RecordConsultationPayment`; `CompleteConsultation` is now render-only. Recording is idempotent on the payment `reference`. On successful payment, `ProvisionClientFromConsultation` provisions the client — it runs for a guest with no account **and** for a lead that was linked up-front but is still email-unverified (the custom-plan case below); a freshly-created client is auto-logged-in. Already-verified repeat clients are left untouched. Stripe settles on browser-return (`ReturnFromGateway`); a **signature-verified `stripe/webhook`** (`App\Actions\Stripe\StripeWebhook`, `checkout.session.completed`) is the **server-to-server backstop** that records a settled payment when the buyer's browser never returns — it funnels through the same idempotent `RecordConsultationPayment`, so it's safe alongside the return path (whichever fires first records, the other no-ops). Both `paypal/webhook` and `stripe/webhook` are CSRF-exempt (`bootstrap/app.php`). The Stripe webhook only handles money-in recording; **refund/dispute events are not Stripe-webhook-driven** (Stripe's cumulative refund amounts need separate handling — see Post-payment events).

**Custom-plan leads** (`RequestCustomPlan`): a prospect requests a tailored plan with no plan selected. This creates an unpriced `ConsultationRequest` (`plan_id = null`, `amount = 0`, `Pending`) and notifies admins (`CustomPlanRequestedNotification`). **No account is created or linked up-front** — the submitted email is unverified, so (exactly like the priced guest path) the client is provisioned/verified and the consultation linked only on payment (`ProvisionClientFromConsultation`); binding a lead to an existing account on an unverified, client-supplied email would let anyone attach a request to a stranger's portal. The admin sets a price (`SetConsultationPrice`) and sends the payment link (`SendPaymentLink`, which routes the mail to the lead's email when no account is linked yet). (The old admin-side `Admin\CreateCustomConsultation` flow was replaced by this client-initiated one.)

### Client Portal & magic login (implemented)
Clients never set a password during checkout. After provisioning, they receive a **signed magic-login URL** (`GET /portal/access/{user}`, `MagicLogin`, `signed` middleware) that logs them in, marks the email verified, and redirects to the portal. The authenticated portal lives under `/portal/*`:

```
/portal/dashboard       ShowClientDashboard
/portal/consultations   ShowClientConsultations (+ /{uuid} detail)
/portal/payments        ShowClientPayments
/portal/notifications   ShowClientNotifications
/portal/profile         ShowClientProfile (PATCH UpdateClientProfile)
```

Pages live in `resources/js/pages/portal/`.

### Admin area (implemented)
All under `/admin/*` with `role:admin` middleware, pages in `resources/js/pages/admin/`. Covers consultations (list/show/status/notes), leads (pipeline), payments, clients, experts (full CRUD + Stripe Connect payout onboarding), payouts (list/send), plans, notifications, and the **activity audit trail** (`/admin/activity`, `ListActivity` — read-only, filterable by type/search). Admin dashboard at `/dashboard` (`ShowAdminDashboard`).

**Plans CRUD** — `ListPlans`, `ShowPlanForm`, `StorePlan`, `UpdatePlan`, `DeletePlan`, plus `ReorderPlans` (`POST /admin/plans/reorder`) for drag-and-drop ordering. The frontend uses `@dnd-kit/*` for sortable rows; the form is `resources/js/pages/admin/plans/form.tsx`.

### Leads / CRM pipeline (implemented)
A **lead is a `ConsultationRequest`**, not a separate model — the same row flows lead → paid consultation → project, so nothing is converted/duplicated. A consultation is "in the pipeline" when `lead_stage` is set (`scopeLeads()` / `isLead()`); plan-based checkouts stay out of it. Stages are the `LeadStage` enum **New → Contacted → Quoted → Won → Lost** (`badge()` tones, `isTerminal()`). **Won is reached only by a recorded payment** — `RecordConsultationPayment` auto-advances a paid lead to Won and clears its follow-up; `UpdateLeadStage` rejects a manual move to Won. Leads carry `lead_source` (`form` inbound vs `manual`), `next_follow_up_at` (overdue = past date on a non-terminal lead, see `isFollowUpOverdue()`), and `lost_reason`. Admin works the pipeline at `/admin/leads` (`ListLeads`) as a **kanban board** (`@dnd-kit` cross-column drag) or a list: `StoreLead` (add a manual prospect), `UpdateLeadStage` (`POST /admin/leads/{c}/stage`; dropping into Lost captures a reason), `ScheduleLeadFollowUp` (`POST /admin/leads/{c}/follow-up`). Every change writes an `activity('lead')` entry. Inbound custom-plan requests are backfilled into the pipeline by the `2026_06_29_*` migration. DTO: `app/Data/Lead/LeadData.php`; page: `resources/js/pages/admin/leads.tsx`. (Note: a custom lead also still appears under the consultations list's "requests" filter — same entity, two views. Pricing/sending a link reuse `SetConsultationPrice`/`SendPaymentLink`.)

**Follow-up agenda + digest.** `/admin/follow-ups` (`ListFollowUps`) groups open leads with a `next_follow_up_at` into **overdue / due today / upcoming** (page: `resources/js/pages/admin/follow-ups.tsx`; shortcut beside the header bell; the dashboard "Overdue follow-ups" tile links here). A daily scheduled job (`routes/console.php` → `SendLeadFollowUpDigest`, 08:00) notifies admins via the bell (`LeadFollowUpsDueNotification`) of follow-ups due or overdue.

**Follow-ups** have a dedicated agenda at `/admin/follow-ups` (`ListFollowUps`) — every open lead with a `next_follow_up_at`, grouped **overdue / today / upcoming** — reached from the app-header bell area, the dashboard "Overdue follow-ups" tile, and the daily digest. `SendLeadFollowUpDigest` (scheduled daily, see Scheduling) sends a single `LeadFollowUpsDueNotification` (database channel) to admins when any are due or overdue — a digest, not one-per-lead. `LeadData` carries `follow_up_overdue` for board/agenda flagging.

### Contacts — the CRM person (implemented)
A **`Contact`** (`app/Models/Contact.php`, `HasUuidKey`) is the reusable **person** behind leads and consultations — name/email/company/country/phone, **unique email**. Crucially a contact is **NOT a login**: it exists so an admin reuses an existing person instead of re-typing details, without breaking the *"provision identity only after payment"* rule. `Contact::resolve(['email' => …, …])` is the single entry point — find-by-email-or-create, enriching provided fields but **never wiping existing values with blanks**. `ConsultationRequest` gets a nullable `contact_id`; both lead paths (`StoreLead`, `RequestCustomPlan`) resolve-or-create a contact and link it. On payment, `ProvisionClientFromConsultation` provisions the login-capable **client (`User`)** and links it to the contact (`contact.user_id`) — one person = one contact, graduating into a client (the Clients list stays *paying clients only*). Admin reuse is powered by `SearchContacts` (`GET /admin/contacts/search?q=`, JSON, min 2 chars) behind the add-lead dialog's async search (`react-select/async`), prefilling name/email/company/country. The `2026_07_06_*` migration creates `contacts` + `contact_id` and **backfills a contact per distinct existing consultation email** (linking rows + any matching user). Adding a manual lead also **emails the prospect** (`LeadCreatedNotification`, queued) — a lead is not an account, so the mail is routed on-demand to their address.

### Projects & Milestones (implemented)
Every consultation becomes a **Project** (delivery workspace). `app/Actions/Admin/CreateProjectFromConsultation` is idempotent (one project per consultation, enforced by a unique index on `projects.consultation_request_id`) and **status-aware**: a lead/custom-request creates the project pre-payment in **`Planning`** (from `StoreLead` / `RequestCustomPlan`), and `RecordConsultationPayment` re-runs it to **flip `Planning → Active`** and link the now-provisioned client's `user_id`. A directly-paid consultation is created straight to `Active`. So the "Active projects" metric stays payment-gated even though unpaid leads have (Planning) projects. Assigning an expert on the consultation (`AssignExpert`) syncs the consultant onto the existing project. Each `Project hasMany Milestone` (ordered); `Project::recomputeCompletion()` derives `completion_percentage` from milestones and **auto-flips status** Active↔Completed at 100%. Milestone create/edit happens in a **dialog**; status changes inline. Admin manages at `/admin/projects/{uuid}` (list + show only — no manual create/edit; projects are consultation-driven). Clients see a read-only timeline at `/portal/projects`. Admins can upload **documents** to a project (private disk, gated download via `DownloadProjectDocument` — admin or owner); the consultation's own intake **attachments** are surfaced read-only on the project page.

### Invoicing (implemented)
Invoices are **auto-issued, already-paid receipts** — built for the prepaid model (there is *no* manual draft/overdue lifecycle). `CreateInvoiceFromConsultation` (from `RecordConsultationPayment`, idempotent, unique on `invoices.consultation_request_id`) creates a `Invoice` + single `InvoiceItem` from the paid consultation, status `Paid`. A later refund flips it to `Refunded` via `FlagPayment`. `Invoice` has `subtotal/tax_rate/tax_amount/total` (integer minor units + `tax_rate` decimal); `recalculateTotals()` recomputes from items. Read-only everywhere: admin list/show (`/admin/invoices`), client list/show (`/portal/invoices`), and an on-demand **PDF** (`DownloadInvoicePdf`, gated to admin or owner). The on-screen + PDF document is the shared `resources/js/components/invoice-document.tsx` style; PDF template at `resources/views/pdf/invoice.blade.php`.

### Notifications (implemented)
Database notifications (Laravel `notifications` table). `MarkNotificationsRead` handles both read-all (`POST /notifications/read-all`) and single (`POST /notifications/{id}/read`). Both admin and portal expose a notifications page.

**Real-time (live bell) — wired.** Every in-app notification's `via()` includes `'broadcast'` (alongside `'database'`/`'mail'`), so saving a notification also fires Laravel's `BroadcastNotificationCreated` on the recipient's **private** channel `App.Models.User.{id}` (authorized in `routes/channels.php`). The frontend `RealtimeNotifications` component (`resources/js/components/realtime-notifications.tsx`, mounted in `app-layout.tsx` + `client-layout.tsx`) subscribes via `useEchoNotification` (`@laravel/echo-react`, configured in `app.tsx` with `configureEcho({ broadcaster: 'reverb' })`) and, on receipt, `router.reload({ only: ['notifications'] })` to refresh the shared bell prop — no full reload. Display type/colour metadata is keyed off `NotificationData.type` (see `resources/js/constants/notifications.ts`). **Running it:** needs `php artisan reverb:start` (WS server) + a built frontend; config in the `REVERB_*` / `VITE_REVERB_*` env block (`.env.example`). In tests `BROADCAST_CONNECTION=null` (phpunit.xml) so no real socket is opened. `PayoutNotification` stays mail-only (experts have no platform login). For prod, set real `REVERB_*` creds and run Reverb behind TLS.

### Domain events (`app/Events/` + `app/Listeners/`)
A thin domain-events layer decouples side-effects from the Actions that cause them (Phase 4 foundation; will also power AI/CRM/real-time later). Actions **dispatch a typed event** instead of doing the side-effect inline; **listeners** react. Listeners are **auto-discovered** (Laravel 11+ scans `app/Listeners` by the `handle()` type-hint — no manual registration). Events dispatch **synchronously**, so a test that runs an Action and asserts `Notification::assertSentTo(...)` still passes (the listener fires in-process).

Iteration 1 migrated the five *notify-all-admins* dispatches behind events: `ConsultationPaid` (RecordConsultationPayment), `PaymentFlagged` (FlagPayment), `ConsultationStatusChanged` (UpdateConsultationStatus), `ExpertAssigned` (AssignExpert), `CustomPlanRequested` (RequestCustomPlan) — each handled by a `NotifyAdminsOf…` listener that sends the corresponding `*Notification`. The Action no longer calls `Notification::send` for these. **Activity-log calls stay inline in the Actions** (the audit pass is a separate Phase-4 step), and client/expert-targeted notifications (payment link, payout, dashboard-access) are **not** yet event-driven. When adding a new admin-notified transition, add an event + a listener rather than calling `Notification::send` from the Action. Tests: `tests/Feature/DomainEventsTest.php` (dispatch assertions via `Event::fake([...])` + a real listener→notification test).

### AI assistance (multi-provider: Groq / Gemini / OpenAI)
All AI features go through **one seam** — `App\Services\AI\Ai` (static entry point) → the `AiProvider` interface, container-bound in `AppServiceProvider` from `config('services.ai.default')` (`AI_PROVIDER` env: `groq` default | `gemini` | `openai`). Drivers: `Gemini` (native `responseSchema` structured output — UPPERCASE types `OBJECT|ARRAY|STRING|INTEGER`, `enum` to constrain) and `OpenAiCompatibleProvider` (shared by `Groq`/`OpenAi`; `json_object` mode with the schema appended to the system prompt). Three call shapes: `generate($prompt, $system)` (prose), `generateJson($prompt, $system, $schema)` (validated JSON), `chat($turns, $system)` (multi-turn; roles `user|model`). `Ai::configured()` gates every surface via the shared `aiEnabled` page prop. Keys/models per provider in `config/services.php` + `.env`. **Gemini model must stay `gemini-flash-latest`** (2.5-flash 503s on structured output; zero quota on 2.0-flash).

Six features, all grounded (real DB facts + closed "toolbox" lists so the model can't hallucinate capabilities):
- `Admin\GenerateAiDraft` (`POST …/ai-draft`, kind `proposal|email`) — editable draft; system prompt forbids placeholders/fabricated dates/letterhead.
- `Admin\SuggestNextSteps` (`POST …/next-steps`) — `{stage, summary, steps[]}` coaching (each step `now|soon|later`), actions constrained to a closed toolbox.
- `Admin\ScoreLead` (`POST …/leads/{c}/score`, leads only — 404 otherwise) — 0–100 score **persisted** on `consultation_requests` (`lead_score*` cols), banded by `LeadScoreBand`, shape via `LeadScoreData`. Same class runs `asJob` with 429 backoff re-queue (max 4, linear delay); auto-dispatched on inbound leads (`RequestCustomPlan`, `StoreLead`); `ScoreAllLeads` backfills (cap 50, 6 s stagger).
- `Admin\AssessProjectHealth` (`POST …/projects/{p}/health`) + `AssessAllProjects` — `on_track|at_risk|stalled` verdict persisted on `projects` (`health*` cols), `ProjectHealth` enum + `ProjectHealthData`; mirrors the ScoreLead endpoint+job pattern.
- `Consultations\AskConcierge` (`POST /concierge/ask`, public, `throttle:8,1`) — pre-sales chat grounded ONLY in published plan data (`GetActivePlans`); nothing private in context.
- `Clients\AskAssistant` (`POST /portal/assistant/ask`, auth portal, `throttle:15,1`) — client chat whose context is built ONLY from `$user->consultationRequests()` — **query scoping is the isolation boundary**, not the prompt.

These are **JSON fetch endpoints, not Inertia** — the frontend uses `resources/js/lib/fetch-json.ts` (`postJson` with CSRF header; `aiErrorMessage` maps status→message). They **validate manually and return `response()->json(..., 422)`** because `bootstrap/app.php` restricts JSON error rendering to `api/*` (`shouldRenderJsonWhen`), so a thrown `ValidationException` would otherwise render as an Inertia redirect and break the XHR. Provider failures throw `AiException` (carries the upstream status); `bootstrap/app.php` maps it to JSON — **429 ⇒ 429** (rate-limited, wait), anything else ⇒ **502**; drivers retry once on a 5xx, never on a 4xx. Frontend: cards in `components/consultation/` (`ai-assist-card`, `next-steps-card`, `lead-score-card`) + `components/project/health-card` + the reusable `components/chat/chat-widget` (client-side ephemeral history; full history posted each turn; backend re-injects grounded context). Full walkthrough: `docs/AI_FEATURES.md`. Tests fake all provider hosts with `Http::fake([...])` and assert the **request sent** (prompt contents, schema, turns), never hitting a real API.

### Payment — gateway-agnostic checkout (`app/Payments/`)
Checkout is **multi-gateway** (Stripe + PayPal, branch `feat/consultation-tickets`). The rest of the app never names a concrete provider — it goes through one abstraction:

- **`PaymentGateway` contract** (`app/Payments/Contracts/PaymentGateway.php`) — `key()`, `label()`, `title()` (buyer-facing CTA), `mode()` (the `MODE_EMBEDDED` = Stripe inline | `MODE_BUTTONS` = PayPal SDK constants on the interface), `isConfigured()`, `publicConfig()` (frontend-safe, never secrets), `begin()` → `CheckoutSession`, `verify()` → `?VerifiedPayment`.
- **`PaymentManager`** (extends Laravel's `Manager`) — resolves drivers by key. `gateway($key)` returns one; `configured()` returns all enabled+configured rails in config order. Registered as a singleton in `AppServiceProvider::register()`. **Adding a rail = a new `Gateway` class + a `create<Key>Driver()` method + listing the key in `config('services.payments.gateways')`. No schema change, no edits elsewhere.**
- **DTOs:** `CheckoutSession` (mode + reference + optional `clientSecret`), `VerifiedPayment` (normalized confirmed-payment snapshot — every gateway maps its API response into this shape), `GatewayDescriptor` (what the checkout frontend needs to render each option).
- **Concrete wrappers** stay in `app/Services/Payment/`: `StripeCheckout` (embedded), `PayPalCheckout` (money-in), `StripeConnect` (money-out). The gateway adapters in `app/Payments/Gateways/` wrap the money-in ones; the `PayoutRail` adapters in `app/Payments/Rails/` wrap `StripeConnect`. Always use `config()`, never `env()`. Stripe's return URL must still contain the `{CHECKOUT_SESSION_ID}` placeholder.

**Recording is single & gateway-agnostic:** every confirmed payment funnels through `app/Actions/Payments/RecordConsultationPayment` — validates charged amount/currency vs. the consultation, writes the `Payment` idempotently (unique `reference`), copies payer name/email/phone/country onto the consultation, marks it paid, notifies admins, and provisions the client (`ProvisionClientFromConsultation`, which now marks the client email-verified on payment). Both webhooks and the on-return/capture actions call it; never re-implement recording.

**Gateway fee + net** are captured per payment (minor units, nullable `payments.fee`/`payments.net`). Each gateway maps its own breakdown into `VerifiedPayment::$feeMinor/$netMinor`: PayPal reads `seller_receivable_breakdown.paypal_fee`/`net_amount` off the capture; Stripe expands `payment_intent.latest_charge.balance_transaction` (fee/net, settlement currency) on `retrieveSession`. `payments.amount` stays **gross**. The dashboard surfaces `fees`, `netReceived` (= received − fees) and a true `net` margin (= received − fees − payouts) — see `DashboardMetrics`. Historical rows have null fee/net (treated as 0 in aggregates).

**Post-payment events** (refund / dispute / denial) funnel through the same single place — `app/Actions/Payments/FlagPayment` — which idempotently updates the `Payment` status, stamps the payload, logs an activity entry, and alerts admins (`PaymentFlaggedNotification`). `PaypalWebhook` (`PAYMENT.CAPTURE.REFUNDED` / `.DENIED` / `CUSTOMER.DISPUTE.CREATED`) calls it. **Partial refunds** are not full refunds: `FlagPayment` takes the refunded minor amount + event id, and when the refund is *less* than the charge it accumulates a `refunded_total` on the payment payload (idempotent on the refund event id) and alerts admins **without** flipping the `Payment`/`Invoice` to `Refunded` — only a full-amount refund does that, so the records never overstate what was returned. The PayPal webhook is **signature-verified** (via PayPal's verify-webhook-signature API + `PAYPAL_WEBHOOK_ID`) and CSRF-exempt in `bootstrap/app.php`. Stripe post-payment events (refund / dispute) are **not** webhook-driven — the `stripe/webhook` handles money-in recording only; Stripe sends *cumulative* `amount_refunded` on `charge.refunded`, which would need set-not-accumulate semantics distinct from PayPal's per-event deltas, so refund handling there is deliberately deferred. Crucial payment operations also emit structured `Log::*` lines tagged `[payment]`.

Config: `config/services.php` → `paypal` block + `payments` (`default`, `gateways`). Env keys in `.env.example` (`PAYPAL_CLIENT_ID/SECRET/MODE/WEBHOOK_ID`). Frontend uses `@paypal/react-paypal-js` for buttons; Stripe still uses `@stripe/react-stripe-js`.

### Expert Payouts (money-out via managed rails — Stripe Connect)
Experts are paid out by admins; experts have no platform login. Money-out runs through a **`PayoutRail` driver seam** that mirrors the money-in `PaymentGateway` pattern — no per-provider branching in the flow. **Stripe Connect (Express)** is the only live rail; add another (e.g. a card/push specialist) with one adapter + a config key.

- **Rail seam** (`app/Payments/`): `Contracts/PayoutRail` (key/label/isConfigured/`ensureRecipient`/`onboardingUrl`/`recipientReady`/`pay`/`paymentStatus`), `PayoutRailManager extends Manager` (resolves rails by key from `config('services.payouts.rails')`; `configured()` + `rail($key)`), `Rails/StripeRail`, and the `PayoutResult` DTO. Registered as a singleton in `AppServiceProvider`.
- `app/Services/Payment/StripeConnect.php` is the only Connect wrapper: `ensureAccount()` (creates an Express connected account — the expert needs no Stripe login), `onboardingLink()` (hosted account-link), `refreshStatus()` (reads `payouts_enabled`), `transfer()` (idempotent). Container-bound (`AppServiceProvider`) so it can be mocked in tests; `StripeRail` resolves it via `app(StripeConnect::class)`.
- `Expert` model payout fields: `preferred_payout_method` (rail key), `payout_notes`, `stripe_account_id` (`acct_…`), `stripe_payouts_enabled`. `hasMany` `Payout`.
- `Payout` model: integer `amount` (minor units) + `currency`, `method` (rail key), `status`, `reference` (Stripe transfer id `tr_…`), `idempotency_key` (unique), `note`, `paid_at`. Uses `HasMedia` for a **proof** collection and a `FormatsMoney` concern.
- `app/Actions/Admin/CreatePayout.php` is the core flow: resolves the rail, requires `recipientReady($expert)` (live `payouts_enabled` check, else a validation error), validates a client-supplied `idempotency_key` (also passed to Stripe so it dedupes), parses money via `brick/money`, calls `rail->pay()`. A Stripe transfer settles **synchronously**, so a sent payout lands directly in `Paid` (the rail returns `paid` via `PayoutStatus::fromProvider`); a batch rail would land in `Processing`.
- Onboarding: `OnboardExpert` redirects to the Stripe hosted onboarding; `StripeReturn`/`StripeRefresh` are the account-link return/refresh landings; `RefreshPayoutStatus` re-checks status. Onboarding is surfaced **inline** in the payout-create flow and per-row on the experts list (no need to open each expert's edit page). Status is also auto-refreshed on screen load via the `RefreshesPayoutStatus` concern (webhook-independent).
- Frontend: `resources/js/pages/admin/payouts.tsx` (list), `resources/js/pages/admin/payouts/create.tsx` (send form with inline onboarding), `resources/js/pages/admin/experts*` (payout-readiness badge + onboarding action).

### Status transitions (plain enums — no state-machine package)
We deliberately do **not** use a state-machine package. `spatie/laravel-model-states` was removed; status transitions are plain backed-enum assignments inside Actions, with the legal-transition rules living on the enum itself (e.g. `ConsultationWorkStatus::allowedTransitions()` / `canTransitionTo()` / `transitionOptions()`). Each transition Action writes an activity-log entry and (where relevant) notifies the admin team.
- **Consultation work status:** `New → InProgress → Resolved → Closed` (`ConsultationWorkStatus`).
- **Project:** `Planning → Active → {OnHold | Completed | Cancelled}` (`ProjectStatus`), but auto-managed in practice (created Active, flips to/from Completed as milestones complete).
- **Milestone:** `Pending ↔ InProgress ↔ Completed` (`MilestoneStatus`) — any single-step move allowed.
- **Invoice:** `Paid ↔ Refunded` only (`InvoiceStatus`) — created Paid, flipped by `FlagPayment` on refund. No draft/overdue lifecycle (prepaid model).

(`app/Actions/` is organized by area: `Admin/`, `Clients/`, `Concerns/`, `Consultations/`, `Fortify/`, `Notifications/`, `Payments/`, `Paypal/`, `Plans/`, `Stripe/`, `Tickets/`. Project, milestone and invoice Actions live under `Admin/` (e.g. `CreateProjectFromConsultation`, `AddMilestone`, `CreateInvoiceFromConsultation`, `DownloadInvoicePdf`) — there is no separate `Invoices/` or `Projects/` dir.)

### File Attachments & Storage
`spatie/laravel-medialibrary` for all attachments and generated PDFs. Local for dev; S3/Cloudflare R2 (`league/flysystem-aws-s3-v3`) for prod. Define media collections on models.

### PDF generation
`barryvdh/laravel-dompdf`. Blade templates in `resources/views/pdf/`. Single invoices render **on demand** in `DownloadInvoicePdf` (fast, always current — see `pdf/invoice.blade.php`). For **bulk/heavy** generation, use a **queued job** (via Horizon) — never block the request. dompdf needs table-based layout (no flex/grid) and embeds full DejaVu fonts (large PDFs); enable font subsetting if size matters.

### Excel Export
`phpoffice/phpspreadsheet` v5.x directly (`maatwebsite/excel` does not support PHP 8.5). `app/Exports/SpreadsheetExport` is a reusable streamer (headings + rows → a styled, header-frozen `.xlsx` `StreamedResponse`). The admin list exports — `ExportPayments` / `ExportConsultations` / `ExportPayouts` (`GET /admin/{resource}/export`, each registered **before** its `{id}` route) — re-declare the matching list's `QueryBuilder` `allowedFilters` so the download reflects exactly what's on screen, and format money via `formatMoneyMinorExact`. Frontend `components/export-button.tsx` is a plain download anchor (not an Inertia visit) that appends the page's current query string, so the active filters carry into the file.

### Real-time Notifications
`laravel/reverb` WebSocket server; frontend `laravel-echo` + `pusher-js` via `@laravel/echo-react`. The live bell is **wired** — see the Notifications section above for the full flow (`'broadcast'` channel → `App.Models.User.{id}` private channel → `RealtimeNotifications` reloads the `notifications` prop). Run `php artisan reverb:start` alongside `composer dev`.

### Activity & Audit Logs
`spatie/laravel-activitylog`. Manual entries: `activity('log_name')->causedBy($user)->performedOn($model)->withProperties([...])->log('...')`. All admin mutations must log. Log names in use: `payment | consultation | lead | project | invoice | payout | settings`. The properties shape (`type`, `message`, `from_label`/`to_label`, `amount`, `reason`, …) is read by both the per-consultation timeline (`TimelineEventData`) and the global **audit-trail viewer** at `/admin/activity` (`ListActivity` + `ActivityLogData`, which resolves the subject to an admin link). When adding an admin mutation, log it in this shape so it shows up correctly in both.

### Platform Settings (implemented)
`spatie/laravel-settings` stores editable platform config in the DB `settings` table. `app/Settings/PlatformSettings.php` (group `platform`) holds `currency_code`/`currency_symbol`, `tax_rate`, and company/invoice fields (`company_name`, `company_email`, `company_address`, `vat_number`, `invoice_footer`); defaults seeded by a settings migration in `database/settings/`. Admin edits at `/admin/settings` (`ShowSettings`/`UpdateSettings`). **Currency** is read via `App\Services\SettingsService::currencyCode()/currencySymbol()` (safe accessor over `PlatformSettings`, falling back to defaults when settings can't resolve, e.g. pure unit tests) — it's a *set-once* operating currency (stored money is minor units and is never re-converted). **Tax** is **inclusive**: `Invoice::recalculateTotals()` breaks the snapshotted `tax_rate` out *of* the amount charged so a receipt total always equals what the client paid; `CreateInvoiceFromConsultation` snapshots the current rate onto each invoice. Company details are shared to the frontend via `HandleInertiaRequests` (like `currency`) and rendered on the invoice document + PDF (`DownloadInvoicePdf` passes them to `pdf/invoice.blade.php`).

### Search
`laravel/scout` with `SCOUT_DRIVER=database`. Add `Searchable` trait to `Ticket`, `Invoice`, `User` models.

### Installed but not yet wired
These packages are in `composer.json` ahead of the features that will use them — don't assume they're integrated: `adyen/php-api-library` (alternative payment rail), `spatie/laravel-query-builder` (filterable index endpoints). Live payment rails are **Stripe + PayPal** (via `app/Payments/`). Projects, Milestones and Invoicing are now **implemented** (see their sections above); the old empty `app/Actions/Invoices/` / `Users/` scaffolding dirs are gone — dedicated user-management Actions remain unbuilt.

### Routing
Split: `routes/web.php` (main app) + `routes/settings.php` (settings). Register Actions directly as route handlers. After route changes, run `php artisan wayfinder:generate --with-form`. Import in TS: `import { route } from '@/routes'`.

### Frontend conventions
- **Pages** in `resources/js/pages/` map 1:1 to Inertia page names.
- **`components/ui/`** — shadcn/ui primitives. Add via `npx shadcn@latest add <component>`, never edit directly.
- **`components/`** — app-specific composed components.
- **`hooks/`** — `useFlashToast` (Inertia flash → Sonner toasts), `useCurrentUrl` (active nav state).
- **Types** in `resources/js/types/`, re-exported from `index.ts`.
- `react-select` for country/timezone/language dropdowns. Data: `countries-list`, `@vvo/tzdb`.
- `react-hook-form` + `zod` for all forms. Define Zod schema alongside its form component.
- `@tanstack/react-table` for all admin data tables.
- `recharts` for dashboard/analytics charts.
- `react-dropzone` for file upload inputs.
- **Deferred props** for heavy, below-the-fold page data: server-side `Inertia::defer(fn () => ..., 'group')` (group keys load in one background request), rendered with `<Deferred data="..." fallback={<Skeleton/>}>`. The admin consultation show page defers `timeline` + `payments` (group `'detail'`). `<Deferred>` is a pure renderer — the core router fires one reload per group, so multiple `<Deferred>` on the same key is safe.
- **Large pages are split into a `components/<feature>/` dir**, each card owning its own `useForm`/state; the page is a thin composition (see `components/consultation/` + `pages/admin/consultation.tsx`).

### Shared UI surfaces (house style)
The "warm paper + gold + serif" identity is encoded in a few composed
components — **use these instead of hand-rolling surface markup**:
- **`StatCard`** (`components/stat-card.tsx`) — metric tile: tone-coloured icon chip, corner glow, hover lift, optional trend `delta`. Used on both dashboards.
- **`Panel`** (`components/panel.tsx`) — the elevated section card (`rounded-2xl border bg-card shadow-card edge-highlight`) with an optional serif title / eyebrow / action header and a `flush` mode for tables & lists. Use the exported `panelLinkClass` on an Inertia `<Link>` for header "View all →" actions (keeps SPA nav).
- **`DataTable` + `THead`/`TH`/`TBody`/`TR`/`TD`/`TableEmpty`** (`components/data-table.tsx`) — the single styled table shell (reveal-on-scroll, gradient header, consistent cell rhythm). Every admin/portal list table composes these; don't write raw `<table>` markup.
- **`PageHeader`** (`components/page-header.tsx`) — gold eyebrow + serif title + description + actions slot, at the top of every page.

Surface tokens (in `resources/css/app.css`): a warm-ink shadow scale overrides Tailwind's cool-gray defaults (`--shadow-xs/sm/md`) plus bespoke `shadow-card`/`shadow-elevated`/`shadow-glow`; `edge-highlight` (lit top inner edge); `bg-grain`/`bg-app-ambient`/`bg-guide` textures; `text-gold-gradient`. Display headings use `font-serif` (Fraunces); figures use `font-mono tabular-nums`.

### Flash toasts
Backend: `Inertia::flash('toast', ['type' => 'success', 'message' => '...'])`. `useFlashToast` hook (in app shell) fires Sonner toasts.

### Navigation
Edit `NavMain` items array in `app-sidebar.tsx`. Each item: `NavItem` (`title`, `href`, optional `icon` from lucide-react). Active state via `useCurrentUrl`.

### Database
MySQL (`consultdesk` database, root / no password). Run `php artisan migrate` after pulling new migrations. Migrations exist for: users, cache, jobs, passkeys, 2FA columns, permission tables, activity\_log, notifications, media, plans, consultation\_requests (+ `work_status`, + `checkout_gateway`/`checkout_reference`), payments, experts, payouts (+ `idempotency_key`), `projects` + `milestones` (unique `projects.consultation_request_id`), `invoices` + `invoice_items` (unique `invoices.consultation_request_id`), and `countries`. A later migration **generalizes payments for multi-gateway**: per-provider id columns (`stripe_session_id`, `stripe_payment_intent`, `paypal_order_id`) are replaced by a single unique `payments.reference` + `gateway`, and `consultation_requests` gets `checkout_gateway`/`checkout_reference`. Provider-specific ids now live inside `payments.payload` (JSON). A further migration adds nullable `payments.fee`/`payments.net` (minor units) for the gateway processing fee + net received. The lead-pipeline migration adds `lead_stage`/`lead_source`/`next_follow_up_at`/`lost_reason` to `consultation_requests`; a later one adds the AI lead-score columns (`lead_score`, `lead_score_summary`, `lead_score_flags` JSON, `lead_scored_at`).
