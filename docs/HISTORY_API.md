# Payment & Subscription History API

This document describes the endpoints for retrieving transaction and subscription history, including access to invoices/receipts.

## 1. Full Payment History

Retrieve a comprehensive record of all financial transactions (Subscriptions, Reservations, etc.).

- **URL:** `/api/v1/user/payments`
- **Method:** `GET`
- **Authentication:** Required (Sanctum)
- **Controller:** `App\Http\Controllers\Api\V1\UserPaymentController@index`

### Response Structure
Provides a paginated list of all payments.

```json
{
    "data": [
        {
            "id": "25",
            "type": "subscription",
            "description": "Subscription: Yoga Elite",
            "amount": 120.000,
            "status": "paid",
            "gateway": "konnect",
            "payment_reference": "KNT_abc123",
            "reservation_id": null,
            "subscription_id": 10,
            "receipt_url": "https://arena.com/storage/receipts/receipt_10_uuid.pdf",
            "created_at": "2024-06-07T15:00:00Z"
        },
        {
            "id": "24",
            "type": "reservation",
            "description": "Reservation: Padel Match",
            "amount": 35.000,
            "status": "paid",
            "gateway": "konnect",
            "payment_reference": "KNT_xyz789",
            "reservation_id": 42,
            "subscription_id": null,
            "receipt_url": null,
            "created_at": "2024-06-06T10:00:00Z"
        }
    ],
    "links": { ... },
    "meta": { ... }
}
```

---

## 2. Subscription History

Retrieve a specific record of all subscription plans (Active, Expired, and Pending).

- **URL:** `/api/v1/member/subscriptions/history`
- **Method:** `GET`
- **Authentication:** Required (Sanctum)
- **Controller:** `App\Http\Controllers\Api\V1\SubscriptionController@history`

### Response Structure
Provides a detailed list of subscription objects.

```json
{
    "data": [
        {
            "id": 10,
            "plan": {
                "id": 5,
                "name": "Yoga Elite",
                "level": 3,
                "price": 120.000
            },
            "service": {
                "id": 2,
                "name": "Wellness",
                "slug": "wellness"
            },
            "status": "active",
            "starts_at": "2024-06-07",
            "ends_at": "2024-09-07",
            "is_active": true,
            "receipt_url": "https://arena.com/storage/receipts/receipt_10_uuid.pdf"
        }
    ],
    "meta": { ... }
}
```

## Implementation Highlights

- **Dynamic Descriptions**: The `description` field in payments is automatically generated based on the related plan or activity name.
- **Eager Loading**: All relationships (`subscription.plan`, `reservation.activity`) are eager-loaded to ensure high performance.
- **Direct Invoice Access**: The `receipt_url` field provides a pre-resolved public URL to the PDF invoice for any subscription-related transaction.
