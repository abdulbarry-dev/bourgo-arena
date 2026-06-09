# Bourgo Arena — Mobile API Integration Guide

Complete Flutter developer reference for all mobile-facing API endpoints, request formats, response structures, authentication requirements, and payment workflows.

---

## Table of Contents

1. [Authentication](#authentication)
2. [Activities](#activities)
3. [Plans](#plans)
4. [Subscriptions](#subscriptions)
5. [Reservations](#reservations)
6. [Payment Gateways](#payment-gateways)
7. [Loyalty Points](#loyalty-points)
8. [User Payments](#user-payments)
9. [Flutter Reference Models](#flutter-reference-models)
10. [Route Map Summary](#route-map-summary)

---

## Base URL

```
https://yourdomain.com/api/v1/
```

## Authentication

All protected endpoints require a **Sanctum bearer token** in the `Authorization` header:

```
Authorization: Bearer <your_sanctum_token>
```

Obtain a token via:
```
POST /auth/login         → { phone, password }
POST /auth/verify-otp    → { phone, otp_code }
POST /auth/register      → { name, phone, password, password_confirmation }
```

**Middleware requirements** for protected endpoints:

| Middleware | Required For |
|-----------|-------------|
| `auth:sanctum` | All protected endpoints |
| `verified.account` | Email or phone verified |
| `onboarding.completed` | Registration fully completed |
| `tunisia_geo` | All payment + loyalty endpoints |

---

## Activities

Activities represent bookable courts/facilities (Padel, Football, etc.) with configurable capacity, base pricing, and recurring session schedules.

### Data Shape

```json
{
  "id": "5",
  "title": "Padel Court 1",
  "name": "Padel Court 1",
  "base_price": 15.0,
  "capacity": 4,
  "image_url": "https://yourdomain.com/storage/activities/padel.jpg",
  "images": [
    "https://yourdomain.com/storage/activities/padel-1.jpg",
    "https://yourdomain.com/storage/activities/padel-2.jpg"
  ],
  "description": "Professional-grade padel court with glass walls",
  "features": ["Floodlights", "Locker Room", "Showers"]
}
```

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique activity ID |
| `title` / `name` | string | Activity display name (both fields contain the same value) |
| `base_price` | float | Base price in TND |
| `capacity` | int | Display-only capacity info (e.g., court accommodates 4 people) |
| `image_url` | string\|null | Primary image (absolute URL) |
| `images` | array | All image URLs (falls back to `[image_url]` if no images) |
| `description` | string\|null | Full text description |
| `features` | array | List of amenity strings |

### Endpoints

#### `GET /activities` — List All Activities

- **Authentication:** None (public)
- **Pagination:** 15 per page (configurable via `?per_page=`)

**Request:**
```bash
curl -X GET "https://yourdomain.com/api/v1/activities?per_page=20"
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "5",
      "title": "Padel Court 1",
      "name": "Padel Court 1",
      "base_price": 15.0,
      "capacity": 4,
      "image_url": "https://yourdomain.com/storage/activities/padel.jpg",
      "images": ["https://yourdomain.com/storage/activities/padel.jpg"],
      "description": "Professional-grade padel court",
      "features": ["Floodlights", "Locker Room"]
    }
  ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 12
  }
}
```

#### `GET /activities/{id}` — Get Single Activity

- **Authentication:** None

**Response (200):**
```json
{
  "data": {
    "id": "5",
    "title": "Padel Court 1",
    "name": "Padel Court 1",
    "base_price": 15.0,
    "capacity": 4,
    "image_url": "https://yourdomain.com/storage/activities/padel.jpg",
    "images": ["https://yourdomain.com/storage/activities/padel.jpg"],
    "description": "Professional-grade padel court with glass walls",
    "features": ["Floodlights", "Locker Room", "Showers"]
  },
  "success": true,
  "message": null
}
```

#### `GET /activities/{id}/slots` — Get Available Sessions

Returns the recurring session schedule for an activity, filtered to sessions active within the next 7 days.

- **Authentication:** None
- **Query Parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `date` | string | none | Optional: filter sessions for a specific date (YYYY-MM-DD) |

**Response (200):**
```json
{
  "data": [
    {
      "id": "12",
      "day_of_week": 1,
      "start_time": "10:00:00",
      "end_time": "11:00:00",
      "duration_minutes": 60,
      "capacity": 4
    },
    {
      "id": "13",
      "day_of_week": 1,
      "start_time": "14:00:00",
      "end_time": "15:00:00",
      "duration_minutes": 60,
      "capacity": 4
    }
  ],
  "success": true,
  "message": null
}
```

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Session ID — use this as `activity_session_id` when creating a reservation |
| `day_of_week` | int | 0=Sunday → 6=Saturday (Carbon dayOfWeek) |
| `start_time` | string | Start time in HH:MM:SS format |
| `end_time` | string | Calculated end time (start + duration) |
| `duration_minutes` | int | Session duration in minutes |
| `capacity` | int | Display-only capacity |

#### `GET /reservations/slots` — Get Available Sessions for All Activities

- **Authentication:** None
- **Query Parameters:** `?date=YYYY-MM-DD` (optional)
- **Note:** This is a convenience alias that calls the same controller method as `/activities/{id}/slots` but without an activity filter when used without `{id}`.

---

## Plans

Plans are subscription tiers that members subscribe to. Each plan belongs to a service and has a TND price, duration, and optional course access.

### Full documentation: `docs/route/PLANS_API.md`

### Quick Reference

#### `GET /plans` — List All Plans
Public, paginated (15/page), sorted by name.

**Response (200):**
```json
{
  "data": [
    {
      "id": "3",
      "name": "Premium",
      "price": 149.999,
      "duration_days": 90,
      "has_all_courses": true,
      "service": {
        "id": "1",
        "name": "Fitness",
        "slug": "fitness",
        "description": "Access to gym equipment and group fitness classes",
        "image_url": "https://yourdomain.com/storage/services/fitness.jpg",
        "images": ["https://yourdomain.com/storage/services/fitness.jpg"],
        "status": "active"
      }
    }
  ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

#### `GET /plans/{id}` — Get Single Plan
Public.

---

## Subscriptions

Subscriptions link a member to a plan. They have a status (`pending`, `active`, `cancelled`), start/end dates, and payment tracking.

### Data Shape

```json
{
  "id": 42,
  "plan": {
    "id": 3,
    "name": "Premium",
    "description": "Full access to all facilities",
    "price": 149.999,
    "has_all_courses": true
  },
  "service": {
    "id": 1,
    "name": "Fitness",
    "slug": "fitness",
    "image_url": "https://yourdomain.com/storage/services/fitness.jpg"
  },
  "status": "active",
  "starts_at": "2026-06-01",
  "ends_at": "2026-08-30",
  "days_remaining": 82,
  "payment_method": "konnect",
  "amount_paid": 149.999,
  "is_active": true,
  "receipt_url": "https://yourdomain.com/storage/receipts/abc.pdf"
}
```

### Endpoints

All subscription endpoints require **authentication** (`auth:sanctum` + `verified.account` + `onboarding.completed`).

#### `GET /member/subscription` — Get Active Subscriptions

Returns the authenticated member's currently active subscriptions.

**Response — Has subscriptions (200):**
```json
{
  "success": true,
  "message": "Your active subscription details have been retrieved successfully.",
  "data": [
    {
      "id": 42,
      "plan": {
        "id": 3,
        "name": "Premium",
        "description": "Full access to all facilities",
        "price": 149.999,
        "has_all_courses": true
      },
      "service": {
        "id": 1,
        "name": "Fitness",
        "slug": "fitness",
        "image_url": "https://yourdomain.com/storage/services/fitness.jpg"
      },
      "status": "active",
      "starts_at": "2026-06-01",
      "ends_at": "2026-08-30",
      "days_remaining": 82,
      "payment_method": "konnect",
      "amount_paid": 149.999,
      "is_active": true,
      "receipt_url": "https://yourdomain.com/storage/receipts/abc.pdf"
    }
  ]
}
```

**Response — No subscriptions (200):**
```json
{
  "success": true,
  "message": "No active subscriptions were found for your account.",
  "data": []
}
```

#### `GET /member/subscriptions/history` — Subscription History

Paginated history of all subscriptions (active + past + cancelled).

**Response (200):**
```json
{
  "data": [ /* subscription objects */ ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

#### `POST /subscriptions` — Create Subscription (API self-service)

Creates a **pending** subscription. The member must then pay via Konnect to activate it.

**Request:**
```json
{
  "plan_id": 3,
  "starts_at": "2026-06-15"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `plan_id` | int | Yes | Must exist and not be archived |
| `starts_at` | string | No | Start date (YYYY-MM-DD, defaults to today, must be ≥ today) |

**Response — Created (201):**
```json
{
  "success": true,
  "message": "Subscription initiated successfully. Please proceed to payment.",
  "data": {
    "id": 43,
    "plan": {
      "id": 3,
      "name": "Premium",
      "description": "Full access to all facilities",
      "price": 149.999,
      "has_all_courses": true
    },
    "service": {
      "id": 1,
      "name": "Fitness",
      "slug": "fitness",
      "image_url": "https://yourdomain.com/storage/services/fitness.jpg"
    },
    "status": "pending",
    "starts_at": "2026-06-15",
    "ends_at": null,
    "days_remaining": 0,
    "payment_method": "konnect",
    "amount_paid": 0.0,
    "is_active": false,
    "receipt_url": null
  }
}
```

**Error — Validation (422):**
```json
{
  "success": false,
  "message": "Your current plan already provides full access to this service. This new plan would be redundant.",
  "errors": {}
}
```

### Subscription Purchase Flow

```
1. Browse plans:   GET /plans
2. Select plan:     User chooses a plan
3. Subscribe:       POST /subscriptions { plan_id }
   → Creates pending subscription
4. Pay via Konnect: POST /payments/initiate { amount, type: "subscription", subscription_id }
   → Returns payment_url → user completes in browser
5. Verify payment:  POST /payments/verify { payment_reference }
   → Subscription activates automatically
   
OR pay with loyalty points:
4. Loyalty pay:     POST /loyalty/pay { type: "subscription", id: subscription_id }
   → Subscription activates immediately (no pending state)
```

---

## Reservations

Reservations book a specific activity session on a specific date. Each `(session, date)` combination allows exactly one reservation.

### Data Shape

```json
{
  "id": "128",
  "member_id": "42",
  "activity_id": "5",
  "activity_session_id": "12",
  "activity_title": "Padel Court 1",
  "date": "2026-06-15",
  "session": {
    "id": 12,
    "day_of_week": 1,
    "start_time": "10:00:00",
    "end_time": "11:00:00",
    "duration_minutes": 60,
    "capacity": 4
  },
  "price": 15.0,
  "status": "confirmed",
  "payment_status": "pending",
  "qr_code": "a1b2c3...",
  "cancelled_at": null,
  "created_at": "2026-06-08T14:30:00+01:00",
  "activity": {
    "id": 5,
    "title": "Padel Court 1",
    "base_price": 15.0,
    "capacity": 4,
    "image_url": "https://yourdomain.com/storage/activities/padel.jpg",
    "description": "Professional-grade padel court",
    "features": ["Floodlights"],
    "is_active": true
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Reservation ID |
| `member_id` | string | The member who made the reservation |
| `activity_id` | string | The activity being booked |
| `activity_session_id` | string | The specific time slot |
| `activity_title` | string | Readable activity name |
| `date` | string | Reservation date (YYYY-MM-DD) |
| `session` | object | Session time details (start_time, end_time, duration) |
| `price` | float | Price in TND (recalculated server-side) |
| `status` | string | `confirmed` or `cancelled` |
| `payment_status` | string | `pending`, `paid`, or `refunded` |
| `qr_code` | string | SHA-256 hash for check-in verification |
| `cancelled_at` | string\|null | When cancelled (null if active) |
| `created_at` | string | ISO 8601 timestamp |

### Endpoints

All reservation endpoints require **authentication** (`auth:sanctum` + `verified.account` + `onboarding.completed`).

#### `GET /reservations/ongoing` — Upcoming Reservations

Current and future confirmed reservations (ordered by date ascending).

- **Pagination:** 10 per page

**Response (200):**
```json
{
  "data": [
    {
      "id": "128",
      "member_id": "42",
      "activity_id": "5",
      "activity_session_id": "12",
      "activity_title": "Padel Court 1",
      "date": "2026-06-15",
      "session": {
        "id": 12,
        "day_of_week": 1,
        "start_time": "10:00:00",
        "end_time": "11:00:00",
        "duration_minutes": 60,
        "capacity": 4
      },
      "price": 15.0,
      "status": "confirmed",
      "payment_status": "pending",
      "qr_code": "a1b2c3...",
      "cancelled_at": null,
      "created_at": "2026-06-08T14:30:00+01:00",
      "activity": { /* ... */ }
    }
  ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 3
  }
}
```

#### `GET /reservations/history` — Reservation History

Past, completed, or cancelled reservations (ordered by date descending).

- **Pagination:** 10 per page

#### `POST /reservations` — Create Reservation + Initiate Payment

Creates a confirmed reservation and automatically initiates a **10% deposit payment** via Konnect.

**Request:**
```json
{
  "activity_id": 5,
  "activity_session_id": 12,
  "date": "2026-06-15"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `activity_id` | int | Yes | Must exist and be active |
| `activity_session_id` | int | Yes | Must belong to the activity |
| `date` | string | Yes | Date in YYYY-MM-DD format, must be ≥ today |

**Response — Created with payment (201):**
```json
{
  "data": {
    "id": "128",
    "member_id": "42",
    "activity_id": "5",
    "activity_session_id": "12",
    "activity_title": "Padel Court 1",
    "date": "2026-06-15",
    "session": { /* ... */ },
    "price": 15.0,
    "status": "confirmed",
    "payment_status": "pending",
    "qr_code": "a1b2c3d4e5f6...",
    "cancelled_at": null,
    "created_at": "2026-06-08T14:30:00+01:00",
    "activity": { /* ... */ }
  },
  "success": true,
  "message": "Reservation created successfully",
  "payment": {
    "id": 256,
    "payment_url": "https://api.sandbox.konnect.network/payment/abc123",
    "payment_reference": "konnect_a1b2c3"
  }
}
```

**Payment breakdown:**
- `payment.payment_url` → Redirect the user to this URL to complete payment
- `payment.payment_reference` → Save for verification later
- The deposit amount is 10% of the reservation price

**Error — Session already reserved (422):**
```json
{
  "success": false,
  "message": "This activity session is already reserved for this date.",
  "errors": {}
}
```

**Error — Double booking (422):**
```json
{
  "success": false,
  "message": "You already have an active reservation for this session.",
  "errors": {}
}
```

#### `DELETE /reservations/{id}` — Cancel Reservation

Members can cancel unpaid reservations. **Paid reservations cannot be self-cancelled** — member must contact admin.

**Response — Cancelled (200):**
```json
{
  "success": true,
  "message": null,
  "data": null
}
```

**Response — Paid reservation (403):**
```json
{
  "success": false,
  "message": "Paid reservations cannot be cancelled. Please contact an administrator.",
  "errors": {}
}
```

#### `POST /reservations/{id}/payment/initiate` — Pay Remaining Balance

Pay the remaining 90% of a reservation price (after the initial 10% deposit). The `amount` parameter is optional — if omitted, pays the full remaining price.

**Request:**
```json
{
  "amount": 13.5
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | float | No | Amount to pay (defaults to full reservation price) |

**Response — Initiated (200):**
```json
{
  "success": true,
  "message": "Payment initiated",
  "data": {
    "payment": {
      "id": 257,
      "payment_url": "https://api.sandbox.konnect.network/payment/xyz789",
      "payment_reference": "konnect_x7y8z9"
    }
  }
}
```

#### `GET /reservations/{id}/payment/verify` — Verify Payment Status

Check if a payment linked to a reservation has been completed.

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `payment_id` | int | Yes | The payment ID to verify |

**Response — Paid (200):**
```json
{
  "success": true,
  "message": "Payment verification completed",
  "data": {
    "success": true,
    "status": "paid",
    "data": {
      "status": "paid",
      "amount": 13.5,
      "transaction_id": "TXN_123456",
      "paid_at": "2026-06-08T14:30:00+01:00"
    }
  }
}
```

**Response — Still pending (400):**
```json
{
  "success": false,
  "message": "Payment verification completed",
  "data": {
    "success": false,
    "status": "unknown",
    "data": { "status": "unknown" }
  }
}
```

### Reservation Flow

```
1. Browse activities:  GET /activities
2. View sessions:      GET /activities/{id}/slots
3. Select session:     User picks a date + time slot
4. Create reservation: POST /reservations { activity_id, activity_session_id, date }
   → Returns reservation + deposit payment_url
5. Pay deposit:        Redirect user to payment_url (Konnect browser flow)
6. Verify deposit:     GET /reservations/{id}/payment/verify?payment_id={id}
7. Pay remainder:      POST /reservations/{id}/payment/initiate { amount }
                       → Returns payment_url for remaining balance
8. Verify remainder:   GET /reservations/{id}/payment/verify?payment_id={id}

OR pay entirely with loyalty points:
4. after step 3, pay:  POST /loyalty/pay { type: "reservation", id: reservation.id }
   → Reservation.marked as paid immediately
```

---

## Payment Gateways

### Geo-IP Restriction

All payment endpoints are protected by the `tunisia_geo` middleware. Requests from non-Tunisian IPs receive:

```json
{
  "success": false,
  "error": "geo_restricted",
  "country": "FR",
  "message": "Payments are only available from Tunisia."
}
```
**HTTP 403**

---

### Konnect Gateway

#### `POST /payments/initiate` — Start a Konnect Payment

- **Auth:** Sanctum + verified + onboarding + tunisia_geo
- **Rate Limit:** 10 requests/minute

**Request:**
```json
{
  "amount": 15.0,
  "type": "reservation_deposit",
  "description": "Padel Court booking",
  "reservation_id": 128,
  "provider": "konnect"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | float | Yes | Amount in TND |
| `type` | string | No | `reservation`, `reservation_deposit`, or `subscription` |
| `description` | string | No | Payment description |
| `reservation_id` | int | No | Link to reservation |
| `subscription_id` | int | No | Link to subscription |
| `provider` | string | No | `konnect` (default) |

**Response (200):**
```json
{
  "success": true,
  "payment_url": "https://api.sandbox.konnect.network/payment/abc123",
  "payment_reference": "konnect_a1b2c3d4e5",
  "payment_id": 256
}
```

**After success:** Redirect the user to `payment_url`. The Konnect payment page handles the transaction.

#### `POST /payments/verify` — Verify Konnect Payment

Check if a payment was completed.

**Request:**
```json
{
  "payment_reference": "konnect_a1b2c3d4e5"
}
```

**Response — Paid (200):**
```json
{
  "success": true,
  "status": "paid",
  "data": {
    "status": "paid",
    "amount": 15.0,
    "transaction_id": "TXN_123456",
    "paid_at": "2026-06-08T14:30:00+01:00",
    "raw": {}
  }
}
```

**Response — Not found (404):**
```json
{
  "success": false,
  "error": "payment_not_found"
}
```

**Response — Failed (400):**
```json
{
  "success": false,
  "status": "failed",
  "data": {}
}
```

#### `POST /payments/webhook/konnect` — Konnect Server Callback

**Server-to-server only.** Konnect sends this after payment processing.

**Headers:**
```
X-Konnect-Signature: <HMAC-SHA256 of raw body with webhook secret>
```

**Body (from Konnect):**
```json
{
  "payment_reference": "konnect_a1b2c3d4e5",
  "status": "paid",
  "payment_id": "TXN_123456",
  "order_id": "ORD_789"
}
```

Full details: `docs/KONNECT_API.md`

---

## Loyalty Points

### `POST /loyalty/pay` — Pay with Loyalty Points

Full-amount-only payment for a reservation or subscription using accumulated loyalty points.

- **Auth:** Sanctum + verified + onboarding + tunisia_geo

**Points Conversion:** `points = ceil(amount_tnd × rate)` where `rate = 100` (100 points = 1 TND). Admin-configurable in `config/loyalty.php`.

**Request:**
```json
{
  "type": "reservation",
  "id": 128
}
```

**Response — Success (201):**
```json
{
  "data": {
    "id": 55,
    "points_used": 1500,
    "amount_tnd": "15.000",
    "type": "reservation",
    "item_title": "Padel Court 1",
    "item_id": 128,
    "status": "paid",
    "created_at": "2026-06-08T14:30:00+01:00"
  }
}
```

**Error — Insufficient points (422):**
```json
{
  "message": "Insufficient loyalty points. You need 1300 more points.",
  "errors": {
    "points": ["Insufficient loyalty points. You need 1300 more points."]
  }
}
```

**Error — Already paid (422):**
```json
{
  "message": "This reservation has already been paid.",
  "errors": {
    "reservation_id": ["This reservation has already been paid."]
  }
}
```

**Error — Below minimum (422):**
Minimum 100 points required for any loyalty payment.

### `GET /loyalty/payments` — Loyalty Payment History

Paginated, 10 per page.

**Response (200):**
```json
{
  "data": [
    {
      "id": 55,
      "points_used": 1500,
      "amount_tnd": "15.000",
      "type": "reservation",
      "item_title": "Padel Court 1",
      "item_id": 128,
      "status": "paid",
      "created_at": "2026-06-08T14:30:00+01:00"
    }
  ],
  "links": {},
  "meta": {
    "current_page": 1,
    "total": 5,
    "per_page": 10
  }
}
```

### `GET /loyalty/balance` — Points Balance + History

**Response (200):**
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
        "source_id": 128,
        "idempotency_key": "loyalty_payment:reservation:128:42_1717842600",
        "created_at": "2026-06-08T14:30:00+01:00"
      }
    ]
  }
}
```

**Transaction Types:**

| Type | Points | Meaning |
|------|--------|---------|
| `fixed` | +250+ | Monthly subscription renewal (×tier multiplier) |
| `variable` | +10+ | Per-reservation activity bonus (Padel/Football, ×monthly count ×tier) |
| `gift` | +varies | Admin-gifted points |
| `refund` | −varies | Admin-refunded points |
| `payment` | −varies | Paid with loyalty points |

Full details: `docs/LOYALTY_API.md`

---

## User Payments

#### `GET /user/payments` — All User Payments

Paginated listing of all payments for the authenticated member (Konnect + loyalty).

- **Auth:** Sanctum + verified + onboarding
- **Pagination:** 15 per page

**Response (200):**
```json
{
  "data": [
    {
      "id": "256",
      "type": "reservation_deposit",
      "description": "Reservation: Padel Court 1",
      "amount": 1.5,
      "status": "paid",
      "gateway": "konnect",
      "payment_reference": "konnect_a1b2c3",
      "reservation_id": 128,
      "subscription_id": null,
      "receipt_url": null,
      "created_at": "2026-06-08T14:30:00+01:00"
    },
    {
      "id": "55",
      "type": "reservation",
      "description": null,
      "amount": 15.0,
      "status": "paid",
      "gateway": "loyalty_points",
      "payment_reference": "loyalty_42_128_1717842600",
      "reservation_id": 128,
      "subscription_id": null,
      "receipt_url": null,
      "created_at": "2026-06-08T14:35:00+01:00"
    }
  ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  }
}
```

---

## Route Map Summary

```
PUBLIC (no auth):
GET    /activities
GET    /activities/{id}
GET    /activities/{id}/slots
GET    /reservations/slots
GET    /plans
GET    /plans/{id}

AUTH REQUIRED (sanctum):
POST   /auth/logout
GET    /user/verification-status
POST   /user/verify-email
POST   /user/verify-phone
GET    /notifications
POST   /notifications/mark-all-read

AUTH + VERIFIED + ONBOARDING:
GET    /member/profile
PUT    /member/profile
POST   /member/profile/avatar
DELETE /member/profile/avatar
PUT    /user/password
GET    /user/payments
GET    /member/tier
GET    /member/subscription
GET    /member/subscriptions/history
POST   /subscriptions
POST   /subscriptions/{id}/cancel
GET    /reservations/ongoing
GET    /reservations/history
POST   /reservations
DELETE /reservations/{id}
POST   /reservations/{id}/payment/initiate
GET    /reservations/{id}/payment/verify

AUTH + VERIFIED + ONBOARDING + TUNISIA_GEO:
POST   /payments/initiate
POST   /payments/verify
POST   /loyalty/pay
GET    /loyalty/payments
GET    /loyalty/balance

PUBLIC + TUNISIA_GEO:
POST   /payments/webhook/{provider}   (HMAC-signed, no auth token)
```

---

## Error Codes Quick Reference

| HTTP | Condition |
|------|-----------|
| 200 | Success |
| 201 | Created (reservation, subscription, loyalty payment) |
| 400 | Payment initiation failed, verification not ready |
| 401 | Missing or invalid Sanctum token |
| 403 | Geo-restricted (non-Tunisian IP), staff-only, paid reservation cancel |
| 404 | Resource not found (plan, activity, reservation, payment) |
| 422 | Validation error (see `errors` or `message` field) |
| 429 | Rate limit exceeded |
| 500 | Server error (payment gateway failure, retry) |

### Paginated Response Wrapper

```json
{
  "data": [ /* array of resources */ ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

### Single Resource Wrapper

```json
{
  "data": { /* single resource */ },
  "success": true,
  "message": "Optional message"
}
```

### Success Wrapper (generic)

```json
{
  "success": true,
  "message": "Optional message",
  "data": { /* any payload */ }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Validation error for this field"]
  }
}
```
