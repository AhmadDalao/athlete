# Throughline Billing

Date: 2026-06-20

## What is live

Stripe is now the real billing provider for the MVP.

The app now supports:

- authenticated Stripe Checkout launch from the memberships page
- Stripe Billing Portal launch for existing Stripe customers
- signed Stripe webhook intake
- membership sync from Stripe subscription events
- invoice event logging into `payment_events`
- idempotent webhook tracking through `billing_webhook_events`
- role-based post-login routing so admins land in control center and coaches land in roster

This is the right MVP cut. It is enough to sell, collect money, and keep subscription state sane without dragging in Redis, queues, or a fake real-time stack.

## Environment variables

These must be set in production:

- `STRIPE_SECRET`
- `STRIPE_PUBLISHABLE_KEY`
- `STRIPE_WEBHOOK_SECRET`

These are optional but useful when you want seeded plans pre-linked:

- `STRIPE_PRICE_COACH_PRO_MONTHLY`
- `STRIPE_PRICE_ATHLETE_PERFORMANCE_MONTHLY`

## Routes

### Authenticated web billing routes

- `POST /billing/checkout`
    - Starts a Stripe Checkout session for a selected `subscription_plans` record.
- `POST /billing/portal`
    - Starts a Stripe Billing Portal session for the authenticated user.

### Public webhook route

- `POST /webhooks/stripe`
    - Accepts signed Stripe webhooks.
    - CSRF is disabled for this route on purpose.
    - Signature verification still happens through `STRIPE_WEBHOOK_SECRET`.

## Data model changes

### `users`

- `stripe_customer_id`
    - Stores the Stripe customer attached to the app user.

### `subscription_plans`

- `stripe_price_id`
    - Links the local plan to the Stripe recurring price used in Checkout.

### `memberships`

- `billing_provider`
- `provider_customer_id`
- `provider_subscription_id`
- `provider_price_id`
- `provider_payload`

These fields let the app reconcile local membership state against Stripe instead of guessing from manual notes.

### `billing_webhook_events`

Purpose:

- stores Stripe event ids
- prevents duplicate processing
- keeps payload snapshots for debugging and reconciliation

## Key backend files and why they exist

| File | Function / method | Why it exists |
| --- | --- | --- |
| `app/Services/Billing/StripeBillingService.php` | `createCheckoutUrl()` | Builds the real Stripe Checkout session for a chosen plan. |
| `app/Services/Billing/StripeBillingService.php` | `createPortalUrl()` | Sends existing Stripe customers into self-serve billing management. |
| `app/Services/Billing/StripeBillingService.php` | `handleWebhook()` | Verifies the Stripe signature, stores the webhook event, and blocks duplicate replays. |
| `app/Services/Billing/StripeBillingService.php` | `syncMembershipFromSubscriptionPayload()` | Maps Stripe subscription state back into local `memberships`. |
| `app/Services/Billing/StripeBillingService.php` | `recordCheckoutCompletion()` | Persists customer and subscription references as soon as checkout completes. |
| `app/Services/Billing/StripeBillingService.php` | `recordInvoiceEvent()` | Converts Stripe invoice outcomes into local `payment_events` rows. |
| `app/Services/Billing/StripeBillingService.php` | `ensureCustomer()` | Creates the Stripe customer once and reuses it after that. |
| `app/Http/Controllers/Billing/CheckoutSessionStoreController.php` | `__invoke()` | Validates the chosen plan and redirects the browser into Checkout. |
| `app/Http/Controllers/Billing/PortalSessionStoreController.php` | `__invoke()` | Redirects the browser into the Stripe customer portal. |
| `app/Http/Controllers/Billing/StripeWebhookController.php` | `__invoke()` | Accepts Stripe webhook traffic and returns machine-safe JSON responses. |
| `app/Http/Controllers/MembershipIndexController.php` | `__invoke()` | Exposes billing readiness, active plans, and provider-managed membership metadata to React. |
| `app/Models/User.php` | `landingPath()` | Stops admins and coaches from being dumped into the wrong starting page after login. |

## Webhook events handled

### `checkout.session.completed`

Used for:

- persisting the Stripe customer id on the user
- persisting the Stripe subscription id on an existing local membership when possible

### `customer.subscription.created`
### `customer.subscription.updated`
### `customer.subscription.deleted`

Used for:

- syncing `status`
- syncing `auto_renew`
- syncing period dates
- syncing provider ids and price ids

### `invoice.payment_succeeded`
### `invoice.payment_failed`

Used for:

- writing auditable `payment_events`
- reflecting real money movement in the membership surface and admin reporting

## Membership page behavior

The memberships page now does three jobs:

1. It still shows the operator queue for admins and coaches.
2. It exposes self-serve checkout and billing portal controls.
3. It shows whether a membership is provider-managed and which Stripe subscription is attached.

That split matters. Operators need queue visibility. End users need clean billing actions. Mixing both into one flat blob would be dumb.

## Launch checklist

1. Create recurring prices in Stripe for each sellable plan.
2. Save those price ids into `subscription_plans.stripe_price_id`.
3. Set `STRIPE_SECRET`, `STRIPE_PUBLISHABLE_KEY`, and `STRIPE_WEBHOOK_SECRET` in production.
4. Register the Stripe webhook endpoint:
   `https://athlete.ahmaddalao.com/webhooks/stripe`
5. Subscribe the webhook to:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
6. Run:
   `php artisan migrate --force`
7. Smoke test one full checkout, one portal launch, and one webhook delivery from the Stripe dashboard.

## Current limits

- Stripe is the only live billing provider right now.
- Plan management is still DB-driven; there is no admin UI for editing Stripe price ids yet.
- Subscription sync is webhook-first. If you disable Stripe webhooks, your membership data will drift.
- Stripe billing is live. Apple auth, phone OTP, and signed WHOOP webhooks are now implemented in the main app and documented separately.
