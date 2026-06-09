# Loyalty Points Payment Gateway API Documentation

This document details all loyalty points payment endpoints for mobile application integration.

## Overview

Members can use their accumulated Bourgo loyalty points to pay for **reservations** and **subscriptions** at a configurable conversion rate. All payments are full-amount only — no partial payments.

### Conversion Rate (Admin-Configurable)

The points-to-TND conversion is controlled by `config/loyalty.php`:

| Setting | Default | Description |
|---------|---------|-------------|
| `points_to_tnd.rate` | 100 | Points needed per 1 TND (100 points = 1 TND) |
| `points_to_tnd.minimum_payment_points` | 100 | Minimum points required to make any payment |
| `points_to_tnd.maximum_per_transaction` | 10000 | Maximum points per single transaction |

**Formula:** `points_needed = ceil(amount_tnd × rate)`

**Example:** A 15 TND reservation requires `ceil(15 × 100) = 1500` points.

---

## Base URL

All endpoints are relative to `/api/v1/`.

## Authentication

All loyalty endpoints require **Sanctum token authentication** (`Bearer <token>`) plus `verified.account` and `onboarding.completed` middleware.

---

## Geo-IP Enforcement

All loyalty endpoints are protected by the `tunisia_geo` middleware. Requests from non-Tunisian IP addresses receive:

```json
{
  "success": false,
  "error": "geo_restricted",
  "country": "FR",
  "message": "Payments are only available from Tunisia."
}
```

**HTTP Status:** `403 Forbidden`

---

## 1. Pay with Loyalty Points

Pay for a reservation or subscription using loyalty points. The payment is **atomic** — points are only deducted if the payment fully succeeds.

- **URL:** `POST /loyalty/pay`
- **Authentication:** Required (Sanctum + verified.account + onboarding.completed)
- **Headers:**
  ```
  Authorization: Bearer <sanctum_token>
  Accept: application/json
  Content-Type: application/json
  ```

### Request Body

```json
{
  "type": "reservation",
  "id": 42
}
```

```json
{
  "type": "subscription",
  "id": 15
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | Yes | `reservation` or `subscription` |
| `id` | int | Yes | The ID of the reservation or subscription to pay for |

**Note:** The points needed are calculated server-side from the activity/plan price. The client does **not** send or control the points amount (price shield).

### Success Response (201)

```json
{
  "data": {
    "id": 55,
    "points_used": 1500,
    "amount_tnd": "15.000",
    "type": "reservation",
    "item_title": "Padel Court 1",
    "item_id": 42,
    "status": "paid",
    "created_at": "2026-06-08T14:30:00+01:00"
  }
}
```

| Field | Description |
|-------|-------------|
| `id` | Payment record ID |
| `points_used` | Loyalty points deducted from the member's balance |
| `amount_tnd` | TND value of the payment (from activity/plan price) |
| `type` | `reservation` or `subscription` |
| `item_title` | Readable name of the paid item |
| `item_id` | ID of the reservation or subscription |
| `status` | Always `paid` for successful loyalty payments |
| `created_at` | ISO 8601 timestamp |

### Side Effects

On successful payment:
1. Points are deducted from the member's `loyalty_points` balance
2. A `LoyaltyPoint` record is created with `transaction_type = "payment"` and negative points
3. A `LoyaltyAuditLog` is created with full balance snapshot
4. A `Payment` record is created with `driver = "loyalty"` and `gateway = "loyalty_points"`
5. For reservations: `payment_status` is updated to `paid`
6. For subscriptions: `payment_method` is updated to `loyalty_points`
7. The `PaymentPaid` event is dispatched (triggers subscription activation for subscription payments)

---

## 2. Loyalty Payment History

Returns a paginated list of the member's loyalty point payments.

- **URL:** `GET /loyalty/payments`
- **Authentication:** Required (Sanctum + verified.account + onboarding.completed)
- **Pagination:** 10 items per page

### Success Response (200)

```json
{
  "data": [
    {
      "id": 55,
      "points_used": 1500,
      "amount_tnd": "15.000",
      "type": "reservation",
      "item_title": "Padel Court 1",
      "item_id": 42,
      "status": "paid",
      "created_at": "2026-06-08T14:30:00+01:00"
    },
    {
      "id": 48,
      "points_used": 5000,
      "amount_tnd": "50.000",
      "type": "subscription",
      "item_title": "Monthly Standard",
      "item_id": 15,
      "status": "paid",
      "created_at": "2026-06-01T10:00:00+01:00"
    }
  ],
  "links": {
    "first": "/api/v1/loyalty/payments?page=1",
    "last": "/api/v1/loyalty/payments?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 10,
    "to": 2,
    "total": 2
  }
}
```

### Empty Response (200)

```json
{
  "data": [],
  "links": { "...": "..." },
  "meta": {
    "current_page": 1,
    "total": 0
  }
}
```

---

## 3. Loyalty Balance

Returns the member's current loyalty points balance with TND equivalent and recent transaction history.

- **URL:** `GET /loyalty/balance`
- **Authentication:** Required (Sanctum + verified.account + onboarding.completed)

### Success Response (200)

```json
{
  "success": true,
  "data": {
    "points": 3500,
    "tnd_equivalent": 35.0,
    "conversion_rate": 100,
    "transactions": [
      {
        "id": 234,
        "points": 250,
        "transaction_type": "fixed",
        "source_type": "App\\Models\\Subscription",
        "source_id": 12,
        "idempotency_key": "subscription:12:monthly-renewal",
        "created_at": "2026-06-01T00:00:00+01:00"
      },
      {
        "id": 220,
        "points": -1500,
        "transaction_type": "payment",
        "source_type": "App\\Models\\ApiReservation",
        "source_id": 42,
        "idempotency_key": "loyalty_payment:reservation:42:5_1717842600",
        "created_at": "2026-06-08T14:30:00+01:00"
      }
    ]
  }
}
```

| Field | Description |
|-------|-------------|
| `points` | Current loyalty points balance |
| `tnd_equivalent` | TND equivalent at current conversion rate |
| `conversion_rate` | Points per 1 TND |
| `transactions[].transaction_type` | `fixed` (monthly renewal), `variable` (reservation activity), `gift` (admin gift), `refund` (admin refund), `payment` (loyalty payment) |
| `transactions[].points` | Positive for credits, negative for debits/payments |

---

## Error Codes Reference

| HTTP Status | Error Key | Meaning | Resolution |
|-------------|-----------|---------|------------|
| 401 | — | Missing or invalid Sanctum token | Re-authenticate |
| 403 | `geo_restricted` | IP not from Tunisia | Must be physically in Tunisia |
| 403 | `geo_lookup_failed` | Geo-IP service unavailable | Try again later |
| 422 | `insufficient_points` | Not enough loyalty points | Display: "You need X more points" |
| 422 | `below_minimum_points` | Payment below minimum points threshold | Display: "Minimum Y points required" |
| 422 | `above_maximum_points` | Payment exceeds per-transaction cap | Display: "Maximum Z points per transaction" |
| 422 | `already_paid` | Item already paid | Display: "This reservation has already been paid" |
| 422 | (validation) | Invalid `type` or `id` field | Check request body |
| 422 | (ownership) | Item belongs to another member | Cannot happen with correct session |
| 500 | (various) | Server error | Try again |

### Insufficient Points Response (422)

```json
{
  "message": "Insufficient loyalty points. You need 1300 more points.",
  "errors": {
    "points": [
      "Insufficient loyalty points. You need 1300 more points."
    ]
  }
}
```

### Already Paid Response (422)

```json
{
  "message": "This reservation has already been paid.",
  "errors": {
    "reservation_id": [
      "This reservation has already been paid."
    ]
  }
}
```

---

## Security & Idempotency

1. **Atomic transactions** — Points are deducted in a database transaction with `LOCK FOR UPDATE` on the member row. If any step fails, the entire payment rolls back.
2. **Race condition prevention** — Concurrent payment attempts for the same member are serialized by the row-level lock. Only one succeeds.
3. **Double-deduction protection** — Each payment gets a unique `idempotency_key`, preventing duplicate point deductions if a request is retried.
4. **Price shield** — The server calculates points from the activity/plan price; client-side values are ignored.
5. **IP tracking** — Every loyalty payment records `ip_address`, `country_code`, and `city`.
6. **IP rotation detection** — If a member's IP changes within 5 minutes of their last payment, a security warning is logged.
7. **Geo-restriction** — All loyalty payments require a Tunisian IP address.
8. **Full audit trail** — Balances before/after every payment are recorded in `LoyaltyAuditLog`.

---

## Payment vs Points Transaction Types

| Transaction Type | Points | Meaning |
|-----------------|--------|---------|
| `fixed` | +250+ | Monthly subscription renewal bonus (×tier multiplier) |
| `variable` | +10+ | Per-reservation activity bonus (Padel/Football only, ×monthly count ×tier) |
| `gift` | +varies | Admin-gifted points |
| `refund` | −varies | Admin-refunded points |
| `payment` | −varies | Payment using loyalty points (this API) |

**Note:** `payment` type transactions with negative points are created by the loyalty payment endpoint. These are distinct from the non-loyalty `fixed`, `variable`, `gift`, and `refund` types which are system-generated.
