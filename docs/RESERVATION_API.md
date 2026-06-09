# Reservation API Documentation

This document details the new reservation API endpoints for use in the mobile application UI design.

## Base URL
All endpoints are relative to `/api/v1/`.

---

## 1. Get Ongoing Reservations

Returns a paginated list of the member's current and future confirmed reservations.

- **URL:** `GET /reservations/ongoing`
- **Authentication:** Required (Sanctum)
- **Logic:** Status `confirmed` AND (Date > Today OR (Date == Today AND Start Time >= Now))

### Response Structure (Paginated)

The response uses the standard `ApiReservationResource` structure:

```json
{
  "data": [
    {
      "id": "string",
      "member_id": "string",
      "activity_id": "string",
      "activity_slot_id": "string",
      "activity_title": "string",
      "date": "YYYY-MM-DD",
      "time": "HH:MM",
      "duration": "X min",
      "starts_at": "HH:MM:SS",
      "ends_at": "HH:MM:SS",
      "price": 0.00,
      "status": "confirmed",
      "payment_status": "string",
      "qr_code": "string|null",
      "cancelled_at": "string|null",
      "created_at": "YYYY-MM-DD HH:MM:SS",
      "activity": { /* ActivityResource details */ },
      "slot": { /* ActivitySlotResource details */ }
    }
  ],
  "links": { /* Pagination links */ },
  "meta": { /* Pagination meta */ }
}
```

---

## 2. Get Reservation History

Returns a paginated list of the member's past, completed, or cancelled reservations.

- **URL:** `GET /reservations/history`
- **Authentication:** Required (Sanctum)
- **Logic:** Status NOT `confirmed` OR (Date < Today) OR (Date == Today AND Start Time < Now)

### Response Structure

Same paginated structure as Ongoing Reservations.
