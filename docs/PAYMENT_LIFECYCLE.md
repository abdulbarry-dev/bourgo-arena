# Payment & Reservation Lifecycle

This document traces every path through the reservation → payment → confirmation pipeline: slot booking, Konnect payment gateway integration, webhook processing, loyalty point redemption, and the geo-IP security layer.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Reservation Lifecycle Diagram](#reservation-lifecycle-diagram)
3. [Reservation Creation & Slot Booking](#reservation-creation--slot-booking)
4. [Payment Initiation (Konnect)](#payment-initiation-konnect)
5. [Payment Verification](#payment-verification)
6. [Webhook Processing (Server-to-Server)](#webhook-processing-server-to-server)
7. [PaymentPaid Event & Post-Payment Actions](#paymentpaid-event--post-payment-actions)
8. [Loyalty Points as Payment](#loyalty-points-as-payment)
9. [Geo-IP Enforcement](#geo-ip-enforcement)
10. [Stale Reservation & Subscription Cleanup](#stale-reservation--subscription-cleanup)
11. [Reservation Cancellation](#reservation-cancellation)
12. [Loyalty Points Accrual](#loyalty-points-accrual)
13. [Rate Limiting](#rate-limiting)
14. [Key Files Reference](#key-files-reference)

---

## Architecture Overview

```
                                    ┌──────────────────────┐
                                    │     Mobile App        │
                                    └──────┬───────┬───────┘
                                           │       │
                              ┌────────────┘       └────────────┐
                              ▼                                  ▼
                    ┌──────────────────┐              ┌──────────────────┐
                    │   Reservation    │              │     Payment      │
                    │   Controller     │              │   Controller     │
                    └────────┬─────────┘              └────────┬─────────┘
                             │                                 │
                             ▼                                 ▼
                    ┌──────────────────┐              ┌──────────────────┐
                    │  Reservation     │              │   Payment        │
                    │  Service         │              │   Service        │
                    └────────┬─────────┘              └────────┬─────────┘
                             │                                 │
                             │                      ┌──────────┼──────────┐
                             │                      ▼          ▼          ▼
                             │             ┌────────────┐ ┌────────┐ ┌──────────┐
                             │             │  Konnect   │ │ Loyalty│ │  Test    │
                             │             │  Provider  │ │ Pay..  │ │ Provider │
                             │             └─────┬──────┘ └────────┘ └──────────┘
                             │                   │
                             ▼                   ▼
                    ┌──────────────────┐  ┌──────────────────┐
                    │    Database      │  │  Konnect Server   │
                    │  (Reservations,  │  │  (Initiate /      │
                    │   Payments)      │  │   Verify /        │
                    └──────────────────┘  │   Webhook)        │
                                          └──────────────────┘
                                                   │
                                                   │ Webhook callback
                                                   ▼
                                          ┌──────────────────┐
                                          │  Webhook Handler │
                                          │  (HMAC verified) │
                                          └────────┬─────────┘
                                                   │
                                                   ▼
                                          ┌──────────────────┐
                                          │  PaymentPaid     │
                                          │  Event           │
                                          └────────┬─────────┘
                                                   │
                                                   ▼
                                          ┌──────────────────┐
                                          │  Process         │
                                          │  Successful      │
                                          │  Payment Listener│
                                          └────────┬─────────┘
                                                   │
                                     ┌─────────────┼─────────────┐
                                     ▼             ▼             ▼
                              ┌────────────┐ ┌──────────┐ ┌──────────────┐
                              │ Activate   │ │ Mark     │ │ Credit       │
                              │ Subscription│ │ Reserv.. │ │ Loyalty Pts  │
                              │            │ │ as Paid  │ │ (subscription)│
                              └────────────┘ └──────────┘ └──────────────┘
```

---

## Reservation Lifecycle Diagram

```
User creates reservation
         │
         ▼
┌────────────────────┐
│  Validate Request   │
│  - activity_id       │
│  - session_id        │
│  - date (≥ today)    │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│ Check Existing      │
│ Active Reservation  │
│ (same session+date) │
└────┬───────────┬───┘
     │           │
  Stale?      Active?
     │           │
     ▼           ▼
┌─────────┐  ┌──────────────┐
│ Cancel  │  │ 422 Error    │
│ stale   │  │ "already have│
│ + cleanup│  │  active      │
│         │  │  reservation"│
└────┬────┘  └──────────────┘
     │
     ▼
┌────────────────────┐
│ DB::transaction()   │
│                     │
│ 1. Lock Session     │
│   (pessimistic lock)│
│                     │
│ 2. Check Slot       │
│    Conflict         │
│    (any reservation │
│     for this        │
│     session+date?)  │
└────┬───────────┬───┘
     │           │
  No conflict  Conflict
     │           │
     ▼           ▼
┌─────────┐  ┌──────────────┐
│ 3. Calc │  │ 422 Error    │
│   Price │  │ "already     │
│  (with  │  │  reserved for│
│   tier  │  │  this date"  │
│   discount)│              │
│         │  └──────────────┘
│ 4. Create│
│   Reservation│
│   (status=│
│    confirmed,│
│    payment_│
│    status=│
│    pending)│
│         │
│ 5. Gen QR│
│   Code   │
└────┬────┘
     │
     ▼
┌────────────────────┐
│ Initiate Payment    │
│ (10% deposit via    │
│  Konnect)           │
└────┬───────────┬───┘
     │           │
  Success     Failure
     │           │
     ▼           ▼
┌─────────┐  ┌──────────────┐
│ Payment │  │ Payment      │
│ status= │  │ status=      │
│ initiated│ │ failed       │
│ Return  │  │ (reservation │
│ payUrl  │  │  remains     │
│ to client│  │  confirmed)  │
└────┬────┘  └──────────────┘
     │
     ▼
┌────────────────────┐
│ User Pays at Konnect│
│ (redirect/external) │
└────────┬───────────┘
         │
    ┌────┴─────┐
    ▼          ▼
┌────────┐ ┌───────────────┐
│ Client │ │ Konnect       │
│ Verify │ │ Webhook       │
│ (poll) │ │ (server push) │
└───┬────┘ └───────┬───────┘
    │              │
    └──────┬───────┘
           ▼
┌────────────────────┐
│ Payment status=paid│
│ verified_at=now()  │
│                    │
│ ────── PaymentPaid │
│        Event       │
└────────┬───────────┘
         │
         ▼
┌────────────────────┐
│ ProcessSuccessful  │
│ Payment Listener   │
│                    │
│ reservation.       │
│ payment_status=    │
│ 'paid'             │
└────────────────────┘
```

### Reservation States

| Field | Value | Description |
|-------|-------|-------------|
| `status` | `confirmed` | Reservation is active and held |
| `status` | `cancelled` | Reservation has been cancelled |
| `payment_status` | `pending` | Payment not yet completed |
| `payment_status` | `paid` | Payment completed successfully |

---

## Reservation Creation & Slot Booking

### Endpoint

`POST /api/v1/reservations` — Middleware: `api.access`, `auth:sanctum`, `verified.account`, `onboarding.completed`

### Flow (`ReservationController::store` → `ReservationService::makeActivityReservation`)

1. **Request validation** (`StoreReservationRequest`):
   - `activity_id` → must exist in `activities` table
   - `activity_session_id` → must exist in `activity_sessions` and belong to the `activity_id`
   - `date` → must be today or in the future

2. **Active reservation check** (`assertNoActiveReservationForSession`):
   - Checks if member already has a non-cancelled reservation for the same session + date
   - If a stale reservation exists (older than `pending_timeout_minutes`, no active payments):
     - Marks associated pending payments as `failed`
     - Cancels the stale reservation
     - Allows the new reservation
   - If an active (non-stale) reservation exists: **422** "You already have an active reservation for this session"

3. **Slot conflict detection** (inside `DB::transaction`):
   - Locks the `ActivitySession` row with `lockForUpdate()` (pessimistic write lock)
   - Checks if ANY non-cancelled reservation exists for this session + date (regardless of member)
   - **1:1 slot-per-date constraint** — only one reservation per session per date
   - If conflict: **422** "This activity session is already reserved for this date"

4. **Price calculation** (`calculateReservationPrice`):
   - Resolves member's tier via `TierResolutionService`
   - Applies tier-based discount from `config('loyalty.pricing_discounts.{tier_label}')`
   - Formula: `max(0.0, base_price * (1 - discount))`

5. **Reservation creation**:
   - `status = 'confirmed'`, `payment_status = 'pending'`
   - Generates QR code: `hash('sha256', reservation.id . member.id . now())`

6. **Payment initiation** — 10% deposit via Konnect (see next section)

---

## Payment Initiation (Konnect)

Two entry paths converge on the same Konnect HTTP layer:

### Path A: Via ReservationController (deposit flow)

1. Creates `PaymentInitiateDTO` with `type = 'reservation_deposit'`, amount = 10% of reservation price
2. `PaymentService::createPayment()`:
   - Detects geo-location via `GeoLocationService::detect()` (ip-api.com)
   - Records `ip_address`, `country_code`, `city` on the Payment
   - Creates Payment with `status = 'pending'`, `driver = 'konnect'`
3. `PaymentService::initiate($payment, $options)`:
   - Resolves provider via `PaymentManager::driver()`
   - Builds payload and delegates to `KonnectProvider::initiatePayment()`
   - On success: `status = 'initiated'`, stores `gateway_transaction_id`
   - On failure: `status = 'failed'`

### Path B: Via PaymentController (general-purpose)

Same flow but through `PaymentController::initiate()` → `PaymentService::createPayment()` → `PaymentService::initiate()`.

### Konnect HTTP Layer

**Initiation** (`KonnectPaymentService::initiatePayment`):

```
POST {base_url}/payments/init-payment
Headers: x-api-key, Content-Type: application/json

Body:
{
  "amount": 10000,              // TND × 1000 (millimes)
  "receiverWalletId": "...",    // API secret
  "orderId": "...",             // payment_reference
  "successUrl": "{APP_URL}/payments/success",
  "failureUrl": "{APP_URL}/payments/failure",
  "webhookUrl": "{APP_URL}/api/v1/payments/webhook/konnect",
  "customer": {
    "firstname": "...",
    "lastname": "...",
    "email": "...",
    "phoneNumber": "..."
  },
  "type": "immediate"
}

Response:
{
  "paymentRef": "{transaction_id}",
  "payUrl": "{redirect_url}",
  "expires_in_minutes": 20,
  "requires3DS": false
}
```

**HTTP client config**: `Http::acceptJson()->asJson()->timeout(30)->retry([200, 500, 1000])`

### Driver Fallback

In non-production environments, if `konnect` driver is configured but `api_key` or `api_secret` is empty, `PaymentManager` automatically falls back to `test` driver. In production, the configured driver is always used.

---

## Payment Verification

### Path A: Client-Initiated Verification

```
GET /api/v1/reservations/{id}/payment/verify
GET /api/v1/payments/verify?paymentReference=...&gatewayTransactionId=...
```

1. Resolves the payment (by ID, `paymentReference`, or `gatewayTransactionId`)
2. Resolves provider based on payment's `driver` field
3. `KonnectPaymentService::verifyPayment($transactionId)`:
   ```
   GET {base_url}/payments/{transactionId}
   Response: { status, amount, transaction_id, paid_at }
   ```
4. If `status = 'paid' || 'completed'`:
   - Updates payment: `status = 'paid'`, `verified_at = now()`
   - **Dispatches `PaymentPaid` event**
5. If not paid: `status = 'failed'`

### Path B: Webhook (Server-to-Server)

See next section.

### Audit Logging

Every Konnect interaction is logged via `PaymentAuditService` which uses `PaymentTransaction::updateOrCreate(['transaction_id' => $id], [...])` for idempotent audit records, capturing `transaction_id`, `user_id`, `reservation_id`, `amount`, `request_payload`, `response_payload`, `ip_address`, and `user_agent`.

---

## Webhook Processing (Server-to-Server)

### Endpoint

`POST /api/v1/payments/webhook/{provider}` — **No API auth required** (called by Konnect servers)

Middleware: `tunisia_geo` (geo-check is bypassed for webhooks)

### HMAC Signature Validation

```
                    ┌─────────────────────────────┐
                    │  Webhook arrives             │
                    └────────────┬────────────────┘
                                 │
                                 ▼
                    ┌─────────────────────────────┐
                    │  Read webhook secret from    │
                    │  config('payment.providers.  │
                    │  konnect.webhook_secret')    │
                    └────────┬───────┬────────────┘
                             │       │
                        Secret set  Secret empty
                             │       │
                             ▼       ▼
                    ┌────────────┐  ┌────────────────┐
                    │ Look for   │  │ Log warning     │
                    │ signature  │  │ "webhook secret │
                    │ header:    │  │ not configured" │
                    │            │  │                 │
                    │ X-Konnect- │  │ Fall back to    │
                    │ Signature  │  │ reference-based │
                    │ X-Signature│  │ lookup only     │
                    │ X-Sig-256  │  └───────┬────────┘
                    └──┬──┬──────┘          │
                       │  │                 │
                 Found │  │ Not found       │
                       │  ▼                 │
                       │ ┌──────────┐       │
                       │ │ 403      │       │
                       │ │ missing_ │       │
                       │ │ signature│       │
                       │ └──────────┘       │
                       ▼                    │
                    ┌────────────┐          │
                    │ HMAC       │          │
                    │ Validation │          │
                    │            │          │
                    │ sha256(    │          │
                    │  raw_body, │          │
                    │  secret)   │          │
                    │            │          │
                    │ vs         │          │
                    │ signature  │          │
                    └──┬──┬──────┘          │
                       │  │                │
                 Match │  │ Mismatch       │
                       │  ▼                │
                       │ ┌──────────┐      │
                       │ │ 403      │      │
                       │ │ invalid_ │      │
                       │ │ signature│      │
                       │ └──────────┘      │
                       ▼                   ▼
                    ┌────────────────────────────┐
                    │  Process webhook payload    │
                    └────────────────────────────┘
```

**HMAC computation**: `hash_hmac('sha256', $request->getContent(), $secret)` — uses raw request body, not JSON-decoded data.

**Constant-time comparison**: `hash_equals($expected, $signature)` prevents timing attacks.

### Payload Processing

1. **Normalize identifiers** from multiple possible payload keys:
   - `paymentRef` / `payment_id` / `transaction_id` → `transactionId`
   - `order_id` / `token` / `orderId` / `payment_reference` → `orderId`

2. **Find payment** by `gateway_transaction_id` matching normalized `transactionId`, or `payment_reference` matching `orderId`. If not found → **404** `payment_not_found`.

3. **Idempotency check**: If payment is already `paid` and webhook status is also `paid/completed/success` → **200** `already_processed`.

4. **Process status**:
   | Webhook Status | Action |
   |---|---|
   | `paid` / `completed` / `success` | Payment → `paid`, `verified_at = now()`, dispatch `PaymentPaid` |
   | `refunded` / `reversed` | Payment → `failed`, attach refund metadata |
   | Any other | Payment → `failed` |

---

## PaymentPaid Event & Post-Payment Actions

### Wiring

```
AppServiceProvider::boot():
    Event::listen(PaymentPaid::class, ProcessSuccessfulPayment::class);
```

### Where PaymentPaid Is Dispatched

1. `PaymentService::verify()` — client-initiated verification
2. `PaymentService::handleWebhook()` — webhook processing
3. `PaymentController::verify()` — direct verify endpoint
4. `LoyaltyPaymentService::pay()` — loyalty point redemption

### ProcessSuccessfulPayment Listener

```
ProcessSuccessfulPayment::handle($event):
  │
  ├─ payment.type === 'subscription' && payment.subscription_id exists
  │   └─ SubscriptionService::activate($subscription)
  │       (handles Renewal / Upgrade / Stacking)
  │
  ├─ payment.type === 'reservation' || 'reservation_deposit'
  │   └─ ApiReservation::where('id', $payment->reservation_id)
  │       ->update(['payment_status' => 'paid'])
  │
  └─ (Silently ignores other types)
```

---

## Loyalty Points as Payment

### Endpoint

`POST /api/v1/loyalty/pay` — Middleware: `api.access`, `auth:sanctum`, `verified.account`, `onboarding.completed`, `tunisia_geo`

### Flow (LoyaltyPaymentService::pay)

```
DB::transaction {
  │
  ├─ 1. Lock item (ApiReservation or Subscription) with lockForUpdate()
  │
  ├─ 2. Validate ownership (item must belong to member)
  │
  ├─ 3. Validate state
  │     ├─ Reservation: not cancelled, payment_status != 'paid'
  │     └─ Subscription: active or pending
  │
  ├─ 4. Calculate points needed
  │     points = ceil(amount_tnd × rate)
  │     (rate from config: loyalty.points_to_tnd.rate, default 100)
  │     (1 TND = 100 points)
  │
  ├─ 5. Min/max checks
  │     ├─ < minimum_payment_points (100) → 422 "below_minimum_points"
  │     └─ > maximum_per_transaction (10000) → 422 "above_maximum_points"
  │
  ├─ 6. Lock member row with lockForUpdate()
  │
  ├─ 7. Balance check: member.loyalty_points >= points_needed
  │     └─ Insufficient → 422 "insufficient_points"
  │
  ├─ 8. Deduct points: member->decrement('loyalty_points', $needed)
  │
  ├─ 9. Create LoyaltyPoint record (negative, type=payment)
  │     idempotency_key: loyalty_payment:{type}:{id}:{member_id}_{timestamp}
  │
  ├─ 10. Create LoyaltyAuditLog (balance_before, balance_after, ip, geo)
  │
  ├─ 11. Create Payment record
  │      driver = 'loyalty', status = 'paid'
  │      payment_reference = loyalty_{member_id}_{id}_{time}
  │
  ├─ 12. Mark item as paid
  │      └─ reservation.payment_status = 'paid' OR subscription paid
  │
  └─ 13. Dispatch PaymentPaid event
}
```

---

## Geo-IP Enforcement

### Middleware: `RestrictToTunisia` (alias `tunisia_geo`)

Applied to all payment and loyalty routes:
- `/api/v1/payments/*`
- `/api/v1/loyalty/*`

### Decision Flow

```
                    ┌──────────────────────┐
                    │  Payment request      │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │  geo.enabled?         │
                    └─────┬──────────┬─────┘
                          │ NO       │ YES
                          ▼          ▼
                    ┌──────────┐  ┌──────────────────────┐
                    │ Pass     │  │  Is webhook?          │
                    │ through  │  └─────┬──────────┬─────┘
                    └──────────┘        │ YES      │ NO
                                        ▼          ▼
                                  ┌──────────┐  ┌──────────────────────┐
                                  │ Pass     │  │  User is staff AND   │
                                  │ through  │  │  exempt_staff=true?  │
                                  └──────────┘  └─────┬──────────┬─────┘
                                                      │ YES      │ NO
                                                      ▼          ▼
                                                ┌──────────┐  ┌──────────────────────┐
                                                │ Pass     │  │  IP is local/private  │
                                                │ through  │  │  AND block_local_ips  │
                                                └──────────┘  │  = false?             │
                                                              └─────┬──────────┬─────┘
                                                                    │ YES      │ NO
                                                                    ▼          ▼
                                                              ┌──────────┐  ┌──────────────────────┐
                                                              │ Pass     │  │  GeoLocationService  │
                                                              │ through  │  │  ::detect($ip)       │
                                                              └──────────┘  └─────┬──────────┬─────┘
                                                                                  │
                                                                                  ▼
                                                                           ┌──────────────────────┐
                                                                           │  Country === 'TN'?    │
                                                                           └─────┬──────────┬─────┘
                                                                                 │ YES      │ NO
                                                                                 ▼          ▼
                                                                           ┌──────────┐  ┌──────────────────────┐
                                                                           │ Pass     │  │ 403                  │
                                                                           │ through  │  │ "Payments available  │
                                                                           └──────────┘  │  only from Tunisia"   │
                                                                                         └──────────────────────┘
```

### Geo-Location Service

- Uses `ip-api.com` with 5-second timeout
- Results cached for **1440 minutes** (24 hours)
- IP rotation detection: if a member's IP changes within `rotation_detection_minutes` (5 min) vs their previous payment IP, logs suspicious activity

### Config (`config/payment.php`)

```php
'geo_restriction' => [
    'enabled' => true,
    'allowed_countries' => ['TN'],
    'exempt_staff' => true,
    'block_local_ips' => false,
],
```

---

## Stale Reservation & Subscription Cleanup

### Reservation Staleness

In `ReservationService::assertNoActiveReservationForSession()`:
- A reservation is **stale** if `created_at` exceeds `pending_timeout_minutes` (default 30) AND no active payment exists
- Stale cleanup:
  - All pending/initiated payments → `status = 'failed'`, `cancelled_reason = 'stale_reservation'`
  - Reservation → `status = 'cancelled'`, `cancelled_at = now()`
- After cleanup, a new reservation is allowed

### Subscription Staleness

In `SubscriptionService::isStalePending()`:
- Same logic as reservations — pending subscriptions with no active payment after timeout
- `cancelPending()` marks payments as `failed` and subscription as `cancelled`

---

## Reservation Cancellation

### Endpoint

`DELETE /api/v1/reservations/{id}` — Middleware: `api.access`, `auth:sanctum`, `verified.account`, `onboarding.completed`

### Flow

1. **Policy check**: Member must own the reservation
2. **Payment check**: If `payment_status === 'paid'` → **403** "Cannot cancel paid reservations"
3. **Cancel**: `status = 'cancelled'`, `cancelled_at = now()`

---

## Loyalty Points Accrual

Loyalty points are credited **after** successful payments, not at reservation creation.

### On Subscription Finalization (`LoyaltyCalculatorService::creditFixedMonthlyRenewal`)

```
Base: config('loyalty.fixed_monthly_renewal_points') = 250
× tier.currentTier.multiplier:
  Standard: 250 × 1.0 = 250
  Plus:    250 × 1.2 = 300
  Ultra:   250 × 1.5 = 375
  Max:     250 × 2.0 = 500

Idempotency: subscription:{id}:monthly-renewal
```

### On Reservation Payment (`LoyaltyCalculatorService::creditVariableForReservation`)

- Only for eligible activity categories (`Padel`, `Football`)
- Base: 10 points × monthly_paid_reservation_count × tier multiplier
- First reservation of month: 10 points; second: 20, etc.

### Idempotency Protection

The `loyalty_points` table has a **UNIQUE** constraint on `idempotency_key`. On duplicate key violation, `creditPoints()` catches `UniqueConstraintViolationException` and silently returns `false` — preventing double-crediting.

---

## Rate Limiting

| Endpoint | Limiter | Limit | Key |
|----------|---------|-------|-----|
| `POST /payments/initiate` | `throttle:payments` | `config('payment.initiate_per_minute')` (default 10/min) | User ID or IP |
| `POST /loyalty/pay` | None explicitly (behind `tunisia_geo`) | — | — |
| `POST /device/register` | `throttle:3,1` | 3/min per IP | IP |
| Webhook endpoints | None (server-to-server) | — | — |

Custom 429 handler: `ThrottleRequestsExceptionHandler` returns JSON with `retry-after` header and endpoint-specific messaging.

---

## Key Files Reference

### Controllers
| File | Role |
|------|------|
| `app/Http/Controllers/Api/V1/ReservationController.php` | Reservation CRUD + payment initiation/verification |
| `app/Http/Controllers/Api/V1/PaymentController.php` | General payment initiation, verification, webhook |
| `app/Http/Controllers/Api/V1/LoyaltyPaymentController.php` | Loyalty points as payment |

### Services
| File | Role |
|------|------|
| `app/Services/ReservationService.php` | Reservation business logic, slot conflict, stale cleanup, tier pricing |
| `app/Services/PaymentService.php` | Payment orchestration (create, initiate, verify, markFailed, handleWebhook) |
| `app/Services/PaymentGateway/KonnectPaymentService.php` | Low-level Konnect HTTP (initiate, verify, payload building) |
| `app/Services/Payment/Providers/KonnectProvider.php` | Konnect provider (implements PaymentProviderInterface) |
| `app/Services/Payment/PaymentManager.php` | Driver resolution with auto-fallback to test in non-production |
| `app/Services/PaymentAuditService.php` | Audit trail (PaymentTransaction model) |
| `app/Services/LoyaltyPaymentService.php` | Pay with loyalty points |
| `app/Services/LoyaltyCalculatorService.php` | Credit loyalty points (monthly renewal, variable reservation) |
| `app/Services/LoyaltyService.php` | Loyalty balance and transaction retrieval |
| `app/Services/TierResolutionService.php` | Member tier calculation for pricing discounts |
| `app/Services/GeoLocationService.php` | Geo-IP detection |
| `app/Services/SubscriptionService.php` | Subscription activation post-payment |

### Models
| File | Role |
|------|------|
| `app/Models/ApiReservation.php` | Primary reservation model (status, payment_status, QR) |
| `app/Models/Payment.php` | Payment model (driver, type, status, gateway_transaction_id, metadata) |

### Middleware
| File | Role |
|------|------|
| `app/Http/Middleware/RestrictToTunisia.php` | Geo-IP enforcement |

### Events & Listeners
| File | Role |
|------|------|
| `app/Events/PaymentPaid.php` | Dispatched when payment completes |
| `app/Listeners/ProcessSuccessfulPayment.php` | Activates subscription or marks reservation paid |

### Contracts
| File | Role |
|------|------|
| `app/Contracts/PaymentGatewayInterface.php` | `initiatePayment()`, `verifyPayment()` |
| `app/Contracts/PaymentProviderInterface.php` | Extends GatewayInterface + webhook methods |

### Config
| File | Role |
|------|------|
| `config/payment.php` | Payment providers, geo restriction, rate limiting, webhook settings |
| `config/loyalty.php` | Points-to-TND rate, renewal points, pricing discounts by tier |
