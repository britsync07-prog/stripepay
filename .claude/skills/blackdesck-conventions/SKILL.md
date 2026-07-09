---
name: blackdesck-conventions
description: BlackDesck coding conventions and best practices. Invoke BEFORE implementing or reviewing any feature in this repo — covers Actions/DTOs/enums, where business logic belongs, money, notifications, state machines, the activity log, frontend patterns, and the verification gate. Use it to keep new code consistent with the codebase.
---

# BlackDesck — Conventions & Best Practices

The source of truth for *what* to build is the **BlackDesck — Tasks** Notion database (project overview + roadmap + live task status); `CLAUDE.md` documents the system as built. This skill is *how* to build. Follow it for every change.

## 1. Architecture (non-negotiable)
- **Business logic → single-action classes** in `app/Actions/` using `lorisleiva/laravel-actions` (`AsAction`). Register Actions directly as route handlers — **no dedicated controllers** for new features.
- **DTOs → `app/Data/`** with `spatie/laravel-data`. Define `rules()` for validation; use `fromModel()` for model → DTO.
- **Thin Actions.** An Action validates + orchestrates; it must not hold domain rules that belong on a model/enum.
- **Money → always `brick/money`**, integer **minor units** + a currency column. Never floats. Format via the `FormatsMoney` concern / `Money::ofMinor()`.
- **Public URLs use UUIDs** (`getRouteKeyName() = 'uuid'`), never auto-increment IDs.

## 2. Where logic belongs — push it down
Keep Actions thin by putting domain logic on the **enum or model**, not the Action.

❌ Don't build view-model/transition logic inline in the Action:
```php
$statusOptions = collect([$work, ...$work->allowedTransitions()])->map(...)->values();
```
✅ Put it on the enum and call it:
```php
'statusOptions' => $work->transitionOptions(),
```
Benefits: reusable, unit-testable, and the Action stays a one-liner.

## 3. Enums (`app/Enums/`)
- Backed string enums. Always a `label(): string`. Add `options(): array<int,{value,label}>` for selects.
- Put **domain behaviour on the enum**: `allowedTransitions()`, `canTransitionTo()`, `transitionOptions()`, `isCall()`, etc. (see `ConsultationWorkStatus`, `ContactChannel`).

## 4. State machines (enum-guarded)
Ticket lifecycle = `New → InProgress → Resolved → Closed` (+ reopen). Pattern:
- `allowedTransitions()` + `canTransitionTo()` on the enum.
- The transition Action **rejects illegal moves** (`throw ValidationException::withMessages([...])`), stamps lifecycle timestamps (`resolved_at`/`closed_at`, cleared on reopen), notifies, and logs.
- The picker only offers `transitionOptions()` so the UI can't pick an illegal move.
- (We use enum guards, not `spatie/laravel-model-states`, for consistency with existing enum-based code.)

## 5. Money & payments — correctness is non-negotiable
Real money flows through `app/Payments/` (money-in gateways) and the `PayoutRail` seam (money-out). Treat every rule here as a hard gate; there is no acceptable margin of error on an amount.

- **Never float arithmetic on money.** No `(float)$x * 100`, no `round($x * 100)`. Convert major→minor with `Money::of((string) $value, Currency::CODE, roundingMode: RoundingMode::HALF_UP)->getMinorAmount()->toInt()` (this bit us in `StorePlan`: `10.005` floated to `1000` instead of `1001`). The only float-ish casts allowed are display formatting in `FormatsMoney`, which must never feed a charge.
- **Validate before you record.** Every confirmed payment is recorded *only* through `RecordConsultationPayment`, which re-checks the **charged amount + currency against the expected** (`mismatches()` — strict `!==` on minor units, `strtoupper` currency) and **fails closed** (records nothing) on any mismatch. Never write a `Payment` row outside this funnel.
- **Bind gateway callbacks to their resource — fail closed.** A return/capture/webhook must prove it belongs to *this* consultation before crediting it: Stripe checks `session.metadata.consultation_request_uuid === $request->uuid`; PayPal checks `custom_id === uuid` (and rejects when absent). A leaked/known session/order id from a same-priced consultation must never be replayable against another one.
- **Idempotency is a DB unique index, not just a runtime check.** `payments.reference`, `payouts.idempotency_key`, and `projects`/`invoices.consultation_request_id` are uniquely indexed; recording runs in a transaction and tolerates the loser's `UniqueConstraintViolationException`. Pass the idempotency key through to the provider (Stripe) too so it dedupes server-side.
- **Partial refund ≠ full refund.** `FlagPayment` takes the refunded amount + event id; a sub-total refund accumulates `refunded_total` on the payment payload (idempotent on the event id) and alerts admins **without** flipping `Payment`/`Invoice` to `Refunded`. Only a full-amount refund flips status — records must never overstate what was returned.
- **Invoices are paid receipts.** The total **always equals the amount charged**; tax is forced to 0 (`recalculateTotals()` sets `tax_rate=0, tax_amount=0, total=subtotal`). Do not reintroduce tax-on-top math.
- **Provision identity only after payment.** Never create or link a user account from unverified, client-supplied input. Guests *and* custom-plan leads stay account-less until they pay; `ProvisionClientFromConsultation` (run from `RecordConsultationPayment`) creates/links the user and marks the email verified — because the payment is what proves email ownership.
- **Money-out is admin-only and rail-agnostic.** `CreatePayout` requires a *live* `recipientReady()` check before transferring; a failed transfer surfaces the provider error and leaves no dangling `Paid` row. Add a rail via one `PayoutRail` adapter + a config key — no per-provider branching in the flow.
- **Wrap external services in the container so they're mockable.** Bind wrappers (`StripeCheckout`, `StripeConnect`) in `AppServiceProvider` and resolve via `app(...)`, so tests can `$this->mock(...)`. Always mock Stripe/PayPal in tests (`Http::fake` + `$this->mock`) — never hit a live API — and assert the **request you send**, not just the canned response.

## 6. Notifications
- Database notifications. `toArray()` **must** include `title`, `message`, and a **relative `url`** so the bell/list can navigate.
- **URLs must be relative**: `route('admin.consultation', $c, false)` → `/admin/...`. Absolute URLs break across host/port (queued notifications render with `APP_URL`, not the request host).
- The frontend (`notifications-list.tsx`) normalizes any URL to its path before `router.visit` — but still store relative.
- Client-facing notifications link to `/portal/...`; admin ones to `/admin/...`.

## 7. Activity log (audit trail)
- `spatie/laravel-activitylog`. Log every admin mutation:
  `activity('consultation')->causedBy($user)->performedOn($model)->withProperties(['type'=>..., 'message'=>...])->log('...')`.
- The consultation timeline reads `properties` (`type`, `message`, `duration`, `from_label`/`to_label`). Reuse this shape.

## 8. Frontend
- Pages in `resources/js/pages/` map 1:1 to Inertia page names. Compose **house components** (`PageHeader`, `Panel`, `DataTable`, `StatCard`, `RowActions`, `EmptyState`) — don't hand-roll surfaces or raw `<table>`s.
- shadcn primitives in `components/ui/` (add via `npx shadcn@latest add`, never edit).
- Forms: `react-hook-form` + `zod` (define the schema beside the component). `react-select` for country/timezone/type pickers.
- Design identity: warm paper + gold + serif; keep pages **rich, not plain**. Reserve saturated colours for meaning (status/priority), not big page blocks.
- **Colour policy — semantic tokens only.** Status/feedback colours use the CSS tokens: `text-success`, `text-info`, `text-destructive`; amber text uses `text-warning-foreground` (readable ink; add `dark:text-warning`) — never raw palette classes (`emerald-600`, `amber-500`, `violet-*`, `slate-*`…). Tinted chips/alerts: `bg-success/10 text-success`, `bg-info/10 text-info`, `bg-warning/15 text-warning-foreground`, `bg-destructive/10 text-destructive`; tinted surfaces get `border-{tone}/25 bg-{tone}/[0.08]`. The only sanctioned raw-palette maps are `nav-main.tsx` (wayfinding tones) and `constants/dashboard.ts` (chart segments).
- Surfaces: use the app.css utilities — `surface-card` (section cards; `surface-elevated` for document-weight cards), `surface-tile` (stat tiles / standalone tables), `text-eyebrow` + a `text-*` colour for mono kickers. Inner tiles `rounded-xl`; small controls `shadow-xs`. Never generic `shadow`/`shadow-lg` on cards.
- After route changes run `php artisan wayfinder:generate --with-form`; import route helpers from `@/routes`.

## 9. Verification gate — run before claiming done
- PHP: `vendor/bin/pint` (auto-fix) / `vendor/bin/pint --test`.
- JS/TS: `npm run types:check`, `npx eslint <files>`, `npx prettier --write <files>`.
- Build when frontend changed: `npm run build`.
- **Tests run locally** — `php artisan test` (or `--filter <Name>`); `pdo_sqlite` is installed, so actually run the suite, don't just lint. Tests use in-memory SQLite (the app DB is MySQL). Mock all external payment APIs (`Http::fake` + `$this->mock`) — never hit Stripe/PayPal — and assert the request sent.
- For money/payments changes, add tests that **lock the invariant** (amount recorded == charged, binding rejects a mismatched session/order, refund + idempotency behaviour) — turn "I reviewed it" into a passing test.

## 10. Workflow
- Pick the next task from the **BlackDesck — Tasks** Notion board → implement → verify (gate above) → mark the matching Notion task **Done** with a short delivered/verified note.
- Commit only when asked; branch off `master`.
