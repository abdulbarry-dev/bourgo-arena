# Subscription, Reservation & Payment Gateway Review

Date: 2026-05-23

## Overview

This document summarizes the code paths for reservations, subscription enrollment, and the payment gateway configuration found in the repository. It lists the endpoints, where the core logic lives, identified gaps/risks, and recommended next steps.

## API Endpoints (where to look)

- Reservations
  - `GET /v1/reservations` — [routes/api.php](routes/api.php)
  - `POST /v1/reservations` — handled by `App\Http\Controllers\Api\V1\ReservationController` ([app/Http/Controllers/Api/V1/ReservationController.php](app/Http/Controllers/Api/V1/ReservationController.php))
  - `DELETE /v1/reservations/{reservation}` — same controller

- Subscription (read)
  - `GET /v1/member/subscription` — `App\Http\Controllers\Api\V1\SubscriptionController` ([app/Http/Controllers/Api/V1/SubscriptionController.php](app/Http/Controllers/Api/V1/SubscriptionController.php))

- Activities / Slots (planning/scheduling)
  - `GET /v1/activities` and `GET /v1/activities/{activity}/slots` — [routes/api.php](routes/api.php) → `ActivityController`

## Reservation flow (implementation)

- Controller: [app/Http/Controllers/Api/V1/ReservationController.php](app/Http/Controllers/Api/V1/ReservationController.php)
- Core service: [app/Services/ReservationService.php](app/Services/ReservationService.php)
  - Locks the `ActivitySlot` with `lockForUpdate()`.
  - Checks capacity (`isFullyBooked()`), resolves tier discounts via `TierResolutionService`, computes price from `activity->base_price` and `config('loyalty.pricing_discounts')`.
  - Creates `ApiReservation` and increments `booked_count` on the slot.
  - IMPORTANT: sets `payment_status` => `'paid'` unconditionally (comment: "Assuming paid for now"). No gateway call or verification occurs here.

## Subscription flow (implementation)

- Read endpoint: [app/Http/Controllers/Api/V1/SubscriptionController.php](app/Http/Controllers/Api/V1/SubscriptionController.php) (returns active subscription resource)
- Enrollment (admin UI): `SubscriptionEnrollmentFlyout` — [app/Livewire/Admin/Subscriptions/SubscriptionEnrollmentFlyout.php](app/Livewire/Admin/Subscriptions/SubscriptionEnrollmentFlyout.php)
  - Enrollment is performed in the admin Livewire component (not via a public API).
  - When enrolling, the component creates a `Subscription` record and records `payment_method`, `payment_reference`, `amount_paid`, and generates a receipt via `ReceiptGenerator`.
  - For gateway payments (`konnect`) the UI requires a `paymentReference` to be provided. The enrollment process accepts the provided reference and does not call the gateway service to verify it.

## Payment gateway implementation & wiring

- Gateway service: [app/Services/PaymentGateway/KonnectGateway.php](app/Services/PaymentGateway/KonnectGateway.php)
- Provider: [app/Providers/PaymentServiceProvider.php](app/Providers/PaymentServiceProvider.php)
- Config: [config/payment.php](config/payment.php)
- Tests: [tests/Feature/PaymentGatewayTest.php](tests/Feature/PaymentGatewayTest.php)

The gateway service provides `initiate()`, `verify()`, and `refund()` implementations and tests mock HTTP responses to validate behavior.

## Configuration & env variables

- Default driver key (env): `PAYMENT_GATEWAY` (e.g. `konnect`)
- Konnect env vars: `KONNECT_API_KEY`, `KONNECT_API_SECRET`, `KONNECT_SANDBOX`
- Toggle payment methods in `config/payment.php` (`methods` array)

## Gaps, risks and important notes

1. Reservation payments are assumed paid: `ReservationService` sets `payment_status` to `'paid'` without contacting or verifying a gateway. This risks marking unpaid reservations as paid.
2. Admin enrollment trusts payment references: `SubscriptionEnrollmentFlyout` accepts a `paymentReference` from the admin UI without server-side verification.
3. No server-side verification before marking subscriptions/reservations as paid: verification and reconciliation are still partially manual for admin-entered references.

## Recommendations (prioritized)

1. Keep hardening the server-side payment initiation and verification flows:
  - Add an API endpoint to initiate payments that calls `app(KonnectGateway::class)->initiate($payload)` and returns the `payment_url` and `payment_reference` to the client.
  - Add webhook route(s) to receive Konnect notifications and verify them server-side. Update `payment_status`, `payment_reference`, `amount_paid`, and dispatch receipts/notifications.

2. Change `ReservationService` to require a verified payment reference (or accept an explicit paid token) before marking `payment_status` as `'paid'`. Example approaches:
  - Accept a `payment_reference` validated via `KonnectGateway::verify()` before creating the reservation; or
   - Create reservation as `pending` and update to `paid` when webhook verification arrives.

3. Harden admin enrollment flow:
   - If admin supplies a gateway `paymentReference`, verify it server-side before persisting `amount_paid` and `payment_reference`.
   - Alternatively, create an admin-initiated `initiate` flow that opens the provider checkout and waits for webhook or verification.

4. Add webhook verification and security (signature check) and ensure `config/payment.php`'s `webhooks.verify_signature` is used.

5. Document expected env vars and add a deployment checklist to ensure keys are set for staging/production.

## Suggested next steps (small increments)

1. Extend the payment API to expose only Konnect initiation and verification endpoints.
2. Add or refine the Konnect webhook controller so signatures and payment reconciliation stay centralized.
3. Update `ReservationService` to accept payment verification or create `payment_status` as `pending` and reconcile when webhook verifies.
4. Add integration tests that simulate initiate → webhook → reconciliation (tests/Feature).

## Quick checklist (for implementer)

- [ ] Add `PaymentController::initiate` API route and implementation
- [ ] Add webhook routes + controllers for Konnect
- [ ] Verify admin enrollment references server-side
- [ ] Make reservations require verified payments or adopt pending→paid reconciliation
- [ ] Ensure env vars are present in staging/prod

---
If you want, I can scaffold the `PaymentController` + webhook handlers and a test case now. Tell me whether to implement the admin verification first or the public initiate/webhook flow.
