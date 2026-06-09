# Konnect Payment Gateway API Documentation

This document details all Konnect payment gateway endpoints for mobile application integration.

## Base URL

All endpoints are relative to `/api/v1/`.

## Authentication

All payment endpoints require **Sanctum token authentication** (`Bearer <token>`) except the webhook endpoint which uses **HMAC-SHA256 signature verification**.

---

## Geo-IP Enforcement

All payment endpoints are protected by the `tunisia_geo` middleware. Requests from non-Tunisian IP addresses receive:

```json
{
  "success": false,
  "error": "geo_restricted",
  "country": "FR",
  "message": "Payments are only available from Tunisia."
}
```

**HTTP Status:** `403 Forbidden`

Staff accounts are exempt from geo-restriction via `config('geo.exempt_staff')`.

---

## 1. Initiate Payment

Initiate a Konnect gateway payment for a reservation or subscription.

- **URL:** `POST /payments/initiate`
- **Authentication:** Required (Sanctum)
- **Headers:**
  ```
  Authorization: Bearer <sanctum_token>
  Accept: application/json
  Content-Type: application/json
  ```
- **Rate Limit:** 10 requests/minute (`throttle:payments`)

### Request Body

```json
{
  "amount": 15.000,
  "type": "reservation",
  "description": "Padel Court booking",
  "reservation_id": 42,
  "provider": "konnect"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | float | Yes | Payment amount in TND |
| `type` | string | No | `reservation` or `subscription` |
| `description` | string | No | Payment description |
| `reservation_id` | int | No | Linked reservation ID |
| `subscription_id` | int | No | Linked subscription ID |
| `provider` | string | No | `konnect` (default) or `test` (test env only) |

### Success Response (200)

```json
{
  "success": true,
  "payment_url": "https://api.sandbox.konnect.network/payment/abc123",
  "payment_reference": "konnect_a1b2c3d4e5",
  "payment_id": 128
}
```

The user should be redirected to `payment_url` to complete the Konnect payment.

### Error Response — Gateway failure (400)

```json
{
  "success": false,
  "error": "Konnect API credentials not configured"
}
```

### Error Response — Server error (500)

```json
{
  "success": false,
  "error": "Connection refused"
}
```

---

## 2. Verify Payment

Verify a Konnect payment status after the user returns from the gateway.

- **URL:** `POST /payments/verify`
- **Authentication:** Required (Sanctum)

### Request Body

```json
{
  "payment_reference": "konnect_a1b2c3d4e5"
}
```

Or by gateway transaction ID:

```json
{
  "gateway_transaction_id": "TXN_123456"
}
```

### Success Response — Payment confirmed (200)

```json
{
  "success": true,
  "status": "paid",
  "data": {
    "status": "paid",
    "amount": 15.000,
    "transaction_id": "TXN_123456",
    "paid_at": "2026-06-08T14:30:00+01:00",
    "raw": { "...": "..." }
  }
}
```

The reservation's `payment_status` is automatically updated to `paid`. For subscriptions, the subscription is activated.

### Error Response — Payment not found (404)

```json
{
  "success": false,
  "error": "payment_not_found"
}
```

### Error Response — Verification failed (400)

```json
{
  "success": false,
  "status": "failed",
  "data": { "...": "..." }
}
```

---

## 3. Webhook (Konnect Callback)

Konnect sends a webhook POST to this endpoint after payment processing. This is a **server-to-server** call.

- **URL:** `POST /payments/webhook/konnect`
- **Authentication:** HMAC-SHA256 signature in header (no Sanctum token)
- **Headers:**
  ```
  X-Konnect-Signature: <HMAC-SHA256 of raw POST body with webhook secret>
  Content-Type: application/json
  ```

### Webhook Payload (from Konnect)

```json
{
  "payment_reference": "konnect_a1b2c3d4e5",
  "status": "paid",
  "payment_id": "TXN_123456",
  "order_id": "ORD_789"
}
```

| Field | Description |
|-------|-------------|
| `payment_reference` | The reference returned in the initiate response |
| `status` | `paid`, `completed`, `refunded`, etc. |
| `payment_id` | Konnect transaction ID |
| `order_id` | Konnect order ID |

### Webhook Response — Success (200)

```json
{
  "success": true,
  "status": "paid"
}
```

### Webhook Response — Already processed (200)

```json
{
  "success": true,
  "message": "already_processed"
}
```

### Webhook Response — Invalid signature (403)

```json
{
  "success": false,
  "error": "invalid_signature"
}
```

### Webhook Response — Payment not found (404)

```json
{
  "success": false,
  "error": "payment_not_found"
}
```

### Webhook Response — Missing signature (403)

```json
{
  "success": false,
  "error": "missing_signature"
}
```

---

## Security Notes

1. **Signature verification** is HMAC-SHA256. The raw request body is hashed with the webhook secret configured in `.env` as `KONNECT_WEBHOOK_SECRET`.
2. **IP tracking** is recorded on every payment (`ip_address`, `country_code` fields on the `payments` table).
3. **IP rotation detection** — if a member's IP changes within 5 minutes of their last payment, a security warning is logged.
4. **Rate limiting** — 10 payment initiations per minute per IP.
5. **Geo-restriction** — payments only available from Tunisian IPs (non-staff).
6. **Price shield** — the server always recalculates the payment amount from the activity/plan price, ignoring client-provided values.

---

## Error Codes Reference

| HTTP Status | Error Key | Meaning |
|-------------|-----------|---------|
| 400 | `initiation_failed` | Payment gateway returned an error |
| 401 | — | Missing or invalid Sanctum token |
| 403 | `geo_restricted` | IP address is not from Tunisia |
| 403 | `geo_lookup_failed` | Geo-IP service is unavailable |
| 403 | `invalid_signature` | Webhook HMAC-SHA256 signature mismatch |
| 403 | `missing_signature` | Webhook signature header not present |
| 404 | `payment_not_found` | Payment reference or transaction ID not found |
| 422 | (validation) | Invalid request body — see `errors` field for details |
| 429 | — | Rate limit exceeded (10/minute) |
| 500 | (various) | Server error — payment initiation failed |

---

## Stale Payment Recovery

When a payment flow is abandoned (user closes browser, gateway session expires), subsequent attempts to subscribe or reserve the same resource may be blocked. The system now includes **stale payment recovery** that automatically cleans up abandoned state.

### How It Works

Both subscription and reservation flows check for stale pending state when a new attempt is made:

| Flow | Blocking Check | Stale Threshold |
|------|---------------|-----------------|
| Subscription | `POST /subscriptions` — existing `pending` subscription for same plan | 30 minutes |
| Reservation | `POST /reservations` — existing `confirmed` reservation for same session+date | 30 minutes |

**Stale = resource older than timeout AND no active payment (`pending`/`initiated`) updated within the timeout window.**

### Recovery Paths

| Scenario | Behavior |
|----------|----------|
| Pending subscription >30 min old, no payment | Auto-cancelled → new subscription created |
| Pending subscription >30 min old, payment initiated <30 min ago | **Blocked** — payment still in progress |
| Pending subscription <30 min old | **Blocked** — too recent |
| Reservation >30 min old, no active payment | Auto-cancelled → new reservation created |
| Reservation >30 min old, payment initiated <30 min ago | **Blocked** — payment still in progress |

### Explicit Cancel

In addition to auto-stale detection, users can explicitly cancel a pending subscription:

```
POST /api/v1/subscriptions/{id}/cancel
```

Reservations can be cancelled with the existing endpoint:

```
DELETE /api/v1/reservations/{id}
```

Both cancel endpoints mark associated `pending`/`initiated` payments as `failed`.

### Configuration

```env
PENDING_PAYMENT_TIMEOUT_MINUTES=30  # Default: 30 minutes
```

Configured in `config/payment.php` under `subscription.pending_timeout_minutes`.
