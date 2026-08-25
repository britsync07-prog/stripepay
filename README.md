# BlackDesck (stripepay)
> A consultation-commerce platform with gateway-agnostic Stripe and PayPal checkout, risk-based 3DS, and managed expert payouts.

BlackDesck (repository name `stripepay`) is a Laravel + React platform that sells expert consultations end to end: guests buy fixed plans or request custom ones, admins price and work each engagement through a built-in CRM pipeline, clients get a magic-login portal, and experts are paid out over Stripe Connect - all without ever exposing floats, auto-increment IDs, or unverified identities.

## Overview

The platform is the intermediary: it owns the client relationship, the money flow, and all communication. Experts have no login; admins manage them and pay them manually through a payout rail. The codebase follows a strict single-action architecture (`lorisleiva/laravel-actions`), typed DTOs (`spatie/laravel-data`), backed enums for every status transition, and `brick/money` minor-unit arithmetic throughout.

Status: production-oriented and actively developed. The repo ships a DigitalOcean deploy runbook (`docs/DIGITALOCEAN_DEPLOY.md`), an AI feature walkthrough (`docs/AI_FEATURES.md`), a large PHPUnit suite (60+ test files), and an external Notion roadmap that tracks the remaining phases.

## Features

- Guest-first purchase flows: buy a plan, submit a consultation request, or ask for a custom-priced plan with no account required.
- Gateway-agnostic checkout: one abstraction serves embedded Stripe Checkout and PayPal buttons; adding a rail means adding one driver class plus a config key.
- Risk-based Strong Customer Authentication: Stripe sessions are fingerprinted for metadata binding, client relationship, customer creation, and 3DS policy before reuse.
- Admin-issued payment links with per-link checkout tracking (`payment_link_type`, `checkout_source` metadata).
- Quotation workflow: quotation templates, builder, guest-facing quote page with PDF download and throttled acceptance.
- Magic-login client portal: signed, expiring access URLs that verify the email on first use; portal covers consultations, payments, invoices, notifications, profile.
- Lead pipeline CRM: kanban board with drag-and-drop stages (New, Contacted, Quoted, Won, Lost), follow-up scheduling, overdue agenda, and a daily digest notification; "Won" is reachable only by a recorded payment.
- Contact resolution: find-or-create person records by unique email so leads reuse existing people without creating logins.
- Projects and milestones generated automatically from paid consultations, with completion percentage and status auto-flips.
- Auto-issued, already-paid invoices as PDF receipts (on-demand DOMPDF render, admin or owner gated).
- Expert payouts via Stripe Connect Express: hosted onboarding links, live `payouts_enabled` checks, idempotent transfers with client-supplied idempotency keys.
- AI assistance behind one provider seam (Groq / Gemini / OpenAI): proposal/email drafts, next-step coaching, lead scoring, project health assessments, pre-sales concierge, and an in-portal assistant - all grounded in database facts.
- Real-time notification bell via Laravel Reverb broadcasting on private user channels.
- Audit trail viewer, CSV/XLSX list exports, platform settings for currency/tax/company identity, and rich media uploads with extension allowlists.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel (Fortify auth, Horizon queues, Reverb WebSockets, Scout search, Wayfinder routes) |
| Frontend | React 19, TypeScript, Inertia.js 3, Tailwind CSS, shadcn/ui + Radix, Vite |
| Database | MySQL 8 primary store; in-memory SQLite for tests |
| Payments | Stripe PHP SDK (Checkout, Connect Express), PayPal REST (orders, webhook signature verification); Adyen library installed but not wired |
| Money | brick/money (integer minor units), settings-driven currency |
| AI | Groq / Google Gemini / OpenAI behind a single `AiProvider` interface |
| Documents | barryvdh/laravel-dompdf (invoices, quotations), phpoffice/phpspreadsheet (exports) |
| Media & storage | spatie/laravel-medialibrary; local disk in dev, S3/Cloudflare R2 via Flysystem in prod |
| Realtime | laravel/reverb + laravel-echo/pusher-js |
| Auth & roles | Laravel Fortify (2FA TOTP, passkeys, email verification), spatie/laravel-permission |
| Ops | Redis cache/queues, Supervisor (Horizon), cron scheduler, PM2-free classic LEMP deployment |
| Quality | PHPUnit 12, Laravel Pint, ESLint, Prettier, TypeScript type check |

## Architecture

- **Request lifecycle.** Everything routes through `routes/web.php`, where Action classes are registered directly as route handlers (thin controllers). `HandleInertiaRequests` injects shared props (`auth.user`, notifications, currency) into every page; `app.tsx` resolves layouts per page namespace and intercepts expired-CSRF responses.
- **Payment seam.** `app/Payments/Contracts/PaymentGateway` defines the driver contract (`begin`, `verify`, `mode`, `publicConfig`); `PaymentManager` resolves drivers from config. Concrete wrappers live in `app/Services/Payment/` (`StripeCheckout`, `PayPalCheckout`, `StripeConnect`). Every confirmed payment funnels through the single idempotent action `RecordConsultationPayment`; every refund/dispute/denial funnels through `FlagPayment`.
- **Payout seam.** Mirroring money-in, `PayoutRail` contracts + `PayoutRailManager` abstract money-out; `StripeRail` wraps `StripeConnect` (Express accounts, hosted onboarding, idempotent transfers).
- **Domain events.** Actions dispatch typed events (`ConsultationPaid`, `PaymentFlagged`, ...); auto-discovered listeners notify admins, keeping side-effects out of business logic while staying synchronous for tests.
- **State machines without a package.** Backed enums own their legal transitions (`allowedTransitions()` / `canTransitionTo()`) and badge tones; Actions perform guarded assignments and write activity-log entries.
- **AI seam.** `App\Services\AI\Ai` selects a provider from config; all six AI endpoints validate manually, return JSON (never Inertia redirects), map upstream failures to 429/502, and are tested by faking provider hosts and asserting the outbound prompt payload.

## Project Structure

```text
stripepay/
+-- app/
¦   +-- Actions/          # Business logic as single actions (Admin/, Clients/,
¦   ¦                     #   Consultations/, Payments/, Paypal/, Plans/, Stripe/, ...)
¦   +-- Payments/         # Contracts/, Gateways/ (money-in drivers), Rails/ (payouts)
¦   +-- Services/         # StripeCheckout, PayPalCheckout, StripeConnect,
¦   ¦                     #   AI provider drivers, Media uploader
¦   +-- Data/             # spatie DTOs (Lead, Payment, Payout, Invoice, ...)
¦   +-- Enums/            # Backed status enums with transition guards
¦   +-- Events/ Listeners/# Domain events -> admin notifications
¦   +-- Models/ Concerns/ # Eloquent models, HasUuidKey / FormatsMoney traits
¦   +-- Settings/ Exports/# Platform settings, spreadsheet exporters
¦   +-- Support/          # Upload validation profiles, dashboard metrics
+-- resources/js/
¦   +-- pages/            # admin/, portal/, consultation/, auth/, settings/
¦   +-- components/       # shadcn primitives + composed feature components
+-- routes/web.php        # Actions wired directly as endpoints
+-- tests/                # Feature + Unit suites (in-memory SQLite)
+-- docs/                 # AI_FEATURES.md, DIGITALOCEAN_DEPLOY.md
+-- composer.json         # dev/test/ci scripts
```

## Getting Started

### Prerequisites

- PHP 8.4+ with extensions: intl, bcmath, mbstring, xml, curl, zip, gd, exif, opcache, pdo_mysql (and pdo_sqlite for tests)
- Composer 2
- Node.js 22 and npm
- MySQL 8 and Redis (queues/cache/Horizon)
- Stripe and PayPal developer accounts; optional Gemini/Groq/OpenAI key for AI features

### Installation

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```
2. Configure the environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Migrate the schema:
   ```bash
   php artisan migrate
   ```
4. Regenerate typed routes after any route change:
   ```bash
   php artisan wayfinder:generate --with-form
   ```

(One-shot alternative: `composer setup` runs install, `.env` copy, key generation, migration, npm install, and build.)

### Environment Variables

Names only - never commit real values:

| Variable | Purpose | Example placeholder |
|---|---|---|
| `APP_KEY` | Laravel encryption key | `base64:...` (generated) |
| `APP_URL` / `APP_ENV` / `APP_DEBUG` | Base URL, environment, debug flag | `https://example.test`, `local`, `true` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `STRIPE_KEY` / `STRIPE_SECRET` | Stripe publishable + secret keys | `pk_test_...` / `sk_test_...` |
| `STRIPE_WEBHOOK_SECRET` | Signature secret for `/stripe/webhook` | `whsec_...` |
| `PAYPAL_CLIENT_ID` / `PAYPAL_CLIENT_SECRET` | PayPal REST credentials | `change-me` |
| `PAYPAL_MODE` / `PAYPAL_WEBHOOK_ID` | Sandbox/live mode + webhook verification id | `sandbox`, `change-me` |
| `REVERB_*` + `VITE_REVERB_*` | WebSocket server credentials for the live bell | `change-me` |
| `QUEUE_CONNECTION` / `CACHE_STORE` / `REDIS_HOST` / `REDIS_PASSWORD` | Queue/cache backends | `redis`, `redis`, `127.0.0.1`, `change-me` |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_FROM_ADDRESS` | Outbound mail | `smtp`, `change-me` |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_BUCKET` | S3/R2 media storage in prod | `change-me` |
| `AI_PROVIDER` | Active AI driver | `groq` (or `gemini`, `openai`) |
| `GROQ_API_KEY` / `GEMINI_API_KEY` / `OPENAI_API_KEY` (+ `*_MODEL`) | Provider keys/models | `change-me` |
| `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Initial admin seeding | `Admin`, `admin@example.test`, `change-me` |

### Running

- Full dev stack (Laravel server, queue worker, log tail, Vite, Reverb concurrently):
  ```bash
  composer dev
  ```
- Additional services: `php artisan horizon` (queue dashboard), `php artisan reverb:start` (WebSockets), `php artisan schedule:work` (dev scheduler).
- Tests: `composer test` (lint + PHPUnit on SQLite), or `php artisan test --filter TestName`.
- Full CI gate: `composer ci:check` (PHP lint, ESLint, Prettier, tsc, tests).
- Production: follow `docs/DIGITALOCEAN_DEPLOY.md` (Ubuntu 24.04, nginx, PHP-FPM 8.4, MySQL, Redis, Supervisor for Horizon, cron for `schedule:run`). Note that payments and Reverb require HTTPS with a real domain.

## Challenges Faced & Solutions

- **3DS policy: fraud protection vs checkout friction** - commit `Harden Stripe checkout risk controls` initially forced `request_three_d_secure: 'any'` for payment links and high-value consultations, embedding the decision in session metadata and locking it down with `StripeCheckoutSessionPayloadTest`. Follow-up commit `Use risk-based Stripe 3DS flow` relaxed the rule to `'automatic'` after evaluating conversion cost, letting Stripe's risk engine step up authentication only when needed - while keeping the metadata fingerprint assertions so session state cannot drift silently.
- **Checkout Session replay across consultations** - a leaked or guessed session ID from a same-priced consultation could be replayed against another request. Solution: `StripeGateway::reuseOpenSession` refuses any open session whose metadata does not bind the exact `consultation_request_uuid`, `client_relationship`, SCA mode, and `customer_creation: always` values expected for that request; mismatches are logged and a fresh session is created instead.
- **Double settlement recording (browser return + webhook)** - both the redirect-return path and the signature-verified `stripe/webhook` backstop can observe the same completed payment. Solution: all paths delegate to `RecordConsultationPayment`, which writes the `Payment` row idempotently against a unique gateway `reference`, validates charged amount/currency, and no-ops if the reference already exists.
- **Partial refunds overstating returns** - naively mapping refund events would flip invoices to "Refunded" even when only part of the charge came back. Solution: `FlagPayment` accumulates per-event refunded amounts into a `refunded_total` payload (idempotent on the refund event ID), alerts admins, and flips the `Payment`/`Invoice` status only when the full amount has been refunded.
- **Response-only mocks hid a broken PayPal capture** - a capture call shipped with an empty body and failed with `MALFORMED_REQUEST_JSON`, invisible because tests only canned responses. Solution: the testing convention now also asserts outbound requests with `Http::assertSent(...)` (body, headers, endpoint) for Stripe, PayPal, and Gemini integrations.
- **Identity provisioning vs account takeover** - binding a lead to an existing account based on an unverified, client-supplied email would let anyone attach requests to a stranger's portal. Solution: no account is created or linked at request time; `ProvisionClientFromConsultation` provisions and verifies the client only after a verified payment, then issues a signed magic-login URL.
- **Money precision across currencies and fees** - floating-point amounts would corrupt totals, fees, and payouts. Solution: all monetary values are stored as integer minor units with a currency column, parsed/formatted exclusively via brick/money (`FormatsMoney` concern), with gateway fee/net captured per payment and surfaced in dashboard margin metrics.

## Known Limitations & Roadmap

- A standalone MIT [LICENSE](./LICENSE) file is included, matching the license declared in composer.json.
- The root `README.md` still contains the stock Laravel starter-kit text rather than product documentation.
- Stripe-side refund/dispute webhooks are deliberately deferred (Stripe sends cumulative refund counts, unlike PayPal's per-event deltas); post-payment automation is PayPal-only today.
- Contractor tax-form automation (W-8/W-9) for payouts was consciously deferred; revisit a Trolley/Tipalti-style provider if required.
- `adyen/php-api-library` and `spatie/laravel-query-builder` are installed ahead of the features that will use them - not yet integrated.
- Dedicated user-management actions beyond the initial admin seed remain unbuilt.

## Security Notes

Observed practices: Fortify-backed registration, email verification, TOTP two-factor, passkeys, and password confirmation; `role:admin` middleware guarding all `/admin/*` routes; UUID route binding everywhere (auto-increment IDs never appear in URLs); CSRF-exempt but signature-verified Stripe and PayPal webhooks; signed, expiring magic-login URLs; idempotent payment recording with amount/currency validation; activity logging on every admin mutation; upload allowlists that block executable/SVG payloads; secrets accessed via `config()` (never `env()`) at runtime; and a smoke-test command that hard-stops unless the configured Stripe key is a `sk_test_` key, refusing to move real money accidentally.

Hygiene: clean. No `.env`, SQLite dumps, key files, or credential-bearing artifacts are tracked in git; the secret scan found only placeholder `sk_test_x` values inside test configuration.

## License

MIT License — Copyright (c) 2026 Musfiqur Rahman Saimon. See [LICENSE](./LICENSE).
