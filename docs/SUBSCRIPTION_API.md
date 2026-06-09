# Subscription API Documentation

This document describes the implementation and behavior of the member subscription endpoints, specifically designed to support multiple concurrent active plans.

## Active Subscriptions Endpoint

Retrieve a detailed list of all active subscriptions for the authenticated member.

- **URL:** `/api/v1/member/subscription`
- **Method:** `GET`
- **Authentication:** Required (Sanctum)
- **Controller:** `App\Http\Controllers\Api\V1\SubscriptionController@active`

### Business Logic (Smart Stacking)

The system supports **Smart Stacking**, allowing members to hold multiple active plans simultaneously.

1.  **Multiple Services:** A member can hold active subscriptions for different services (e.g., Fitness + Wellness).
2.  **Parallel Access (Stacking):** A member can hold multiple plans in the **same service** if they provide unique course access (e.g., Yoga Level 2 + Crossfit Level 2).
3.  **Automatic Upgrades:** If a member buys a **higher-level** plan (e.g., Full Access Level 3) while having a lower-level one (e.g., Single Course Level 2), the system **merges** them. The duration is extended, and the member gets the higher tier features for the entire combined period.
4.  **Renewals:** Buying the exact same plan again **extends** the current subscription duration.

Subscriptions are returned ordered by their expiration date in descending order (`ends_at` DESC).

### Response Structure

The response follows the standard application API format, providing a professional message based on the number of subscriptions found.

#### Success Response (Multiple Subscriptions)
**Status Code:** `200 OK`

```json
{
    "success": true,
    "message": "Successfully retrieved 2 active subscriptions detailing your current planning access.",
    "data": [
        {
            "id": 1,
            "plan": {
                "id": 10,
                "name": "Yoga Plus",
                "description": "Access to all premium yoga sessions",
                "price": 49.99,
                "has_all_courses": true
            },
            "service": {
                "id": 5,
                "name": "Wellness",
                "slug": "wellness",
                "image_url": "https://example.com/images/wellness.png"
            },
            "status": "active",
            "starts_at": "2024-01-01",
            "ends_at": "2024-12-31",
            "days_remaining": 300,
            "payment_method": "stripe",
            "amount_paid": 49.99,
            "is_active": true
        },
        {
            "id": 2,
            "plan": {
                "id": 12,
                "name": "Gym Standard",
                "description": "General gym access",
                "price": 29.99,
                "has_all_courses": false
            },
            "service": {
                "id": 6,
                "name": "Fitness",
                "slug": "fitness",
                "image_url": "https://example.com/images/fitness.png"
            },
            "status": "active",
            "starts_at": "2024-02-15",
            "ends_at": "2024-08-15",
            "days_remaining": 160,
            "payment_method": "paypal",
            "amount_paid": 29.99,
            "is_active": true
        }
    ]
}
```

#### Success Response (No Subscriptions)
**Status Code:** `200 OK`

```json
{
    "success": true,
    "message": "No active subscriptions were found for your account.",
    "data": []
}
```

### Data Fields Specification

| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | `int` | Unique ID of the subscription record. |
| `plan` | `object` | Details of the subscribed plan (id, name, description, price, has_all_courses). |
| `service` | `object` | Details of the service the plan belongs to (id, name, slug, image_url). |
| `status` | `string` | Current status (e.g., "active"). |
| `starts_at` | `string` | Start date (YYYY-MM-DD). |
| `ends_at` | `string` | Expiration date (YYYY-MM-DD). |
| `days_remaining` | `int` | Number of days until expiration. |
| `payment_method` | `string` | Method used for payment. |
| `amount_paid` | `float` | Total amount paid for this subscription. |
| `is_active` | `bool` | Convenience flag for UI to determine if subscription is currently valid. |

## Implementation Details

- **Service Layer**: `App\Services\SubscriptionService` handles the query logic, utilizing `validSubscriptions()` relation on the `Member` model.
- **Resource Layer**: `App\Http\Resources\Api\V1\SubscriptionResource` handles the transformation, ensuring consistent nested objects for `plan` and `service`.
- **Concurrency Support**: The logic explicitly allows a member to hold multiple active subscriptions across different services or plans simultaneously.

---

## Cancel Pending Subscription

Abandon a stuck pending subscription before re-attempting. Only the owning member can cancel their own pending subscriptions.

- **URL:** `POST /api/v1/subscriptions/{subscription}/cancel`
- **Method:** `POST`
- **Authentication:** Required (Sanctum)
- **Controller:** `App\Http\Controllers\Api\V1\SubscriptionController@cancel`

### Success Response (200)

```json
{
    "success": true,
    "message": "Pending subscription cancelled successfully.",
    "data": {
        "id": 42,
        "plan": {
            "id": 10,
            "name": "Yoga Plus",
            "description": "Access to all premium yoga sessions",
            "price": 49.99,
            "has_all_courses": true
        },
        "service": {
            "id": 5,
            "name": "Wellness",
            "slug": "wellness",
            "image_url": "https://example.com/images/wellness.png"
        },
        "status": "cancelled",
        "starts_at": null,
        "ends_at": null,
        "days_remaining": 0,
        "payment_method": "konnect",
        "amount_paid": 49.99,
        "is_active": false
    }
}
```

All associated `pending` and `initiated` payments are marked as `failed` with metadata `cancelled_reason: "stale_pending_subscription"`.

### Error — Not pending (422)

```json
{
    "success": false,
    "message": "Only pending subscriptions can be cancelled."
}
```

### Error — Not owned (404)

```json
{
    "success": false,
    "message": "Subscription not found."
}
```

---

## Stale Pending Detection

When a member attempts to create a subscription (`POST /subscriptions`) and an existing `pending` subscription exists for the same plan, the system automatically checks for staleness:

1. If the pending subscription was created **less than 30 minutes ago** → blocks with `422` (still active)
2. If the pending subscription is **older than 30 minutes** AND has **no active payment** (`pending`/`initiated` updated within the timeout) → **auto-cancels** the stale subscription and allows the new attempt
3. If the pending subscription is **older than 30 minutes** but has an **active initiated payment** within the timeout → blocks with `422` (payment still in progress)

This prevents users from being permanently locked out when they abandon a payment flow midway.

### Configuration

The timeout is configurable in `config/payment.php`:

```php
'subscription' => [
    'pending_timeout_minutes' => (int) env('PENDING_PAYMENT_TIMEOUT_MINUTES', 30),
],
```

Set `PENDING_PAYMENT_TIMEOUT_MINUTES` in `.env` to customize (default: 30 minutes).
