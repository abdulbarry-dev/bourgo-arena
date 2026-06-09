# Plans API Documentation

This document details the Plans API endpoints for mobile application integration.

## Overview

Plans represent subscription tiers within a service. Each plan belongs to a single service, has a price in TND, a duration in days, and can optionally grant access to specific courses or all courses in its parent service.

**Plans are read-only via the public API** — they are created and managed through the admin dashboard. The API automatically filters to only show available plans (non-archived, belonging to active services).

---

## Base URL

All endpoints are relative to `/api/v1/`.

## Authentication

**None required.** Plans endpoints are public.

---

## Data Model

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique plan ID |
| `name` | string | Plan display name (e.g., "Basic", "Standard", "Premium") |
| `price` | float | Price in TND (e.g., `99.999`) |
| `duration_days` | int | Subscription duration in days (e.g., 30, 60, 90, 365) |
| `has_all_courses` | bool | If `true`, grants access to all courses in the parent service |
| `service` | object | Nested service object (see Service structure below) |

**Internal fields (not exposed):** `level`, `is_archived`, `description`, `image_url`, `created_at`, `updated_at`

---

## 1. List All Plans

Returns a paginated list of all available plans, ordered alphabetically by name.

- **URL:** `GET /plans`
- **Authentication:** None
- **Query Parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `per_page` | int | 15 | Number of plans per page |

### Request

```bash
curl -X GET https://yourdomain.com/api/v1/plans
```

### Success Response — Paginated (200)

```json
{
  "data": [
    {
      "id": "3",
      "name": "Basic",
      "price": 49.999,
      "duration_days": 30,
      "has_all_courses": false,
      "service": {
        "id": "1",
        "name": "Fitness",
        "slug": "fitness",
        "description": "Access to gym equipment and group fitness classes",
        "image_url": "https://yourdomain.com/storage/services/fitness.jpg",
        "images": [
          "https://yourdomain.com/storage/services/fitness-1.jpg",
          "https://yourdomain.com/storage/services/fitness-2.jpg"
        ],
        "status": "active"
      }
    },
    {
      "id": "5",
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
        "images": [
          "https://yourdomain.com/storage/services/fitness-1.jpg",
          "https://yourdomain.com/storage/services/fitness-2.jpg"
        ],
        "status": "active"
      }
    }
  ],
  "success": true,
  "message": null,
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

### Pagination Fields

| Field | Type | Description |
|-------|------|-------------|
| `meta.current_page` | int | Current page number |
| `meta.last_page` | int | Total number of pages |
| `meta.per_page` | int | Items per page |
| `meta.total` | int | Total number of plans |

---

## 2. Get Single Plan

Returns detailed information for a specific plan.

- **URL:** `GET /plans/{id}`
- **Authentication:** None
- **Route Model Binding:** The `{id}` must be a valid plan ID. Returns 404 if not found.

### Request

```bash
curl -X GET https://yourdomain.com/api/v1/plans/3
```

### Success Response (200)

```json
{
  "data": {
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
      "images": [
        "https://yourdomain.com/storage/services/fitness-1.jpg",
        "https://yourdomain.com/storage/services/fitness-2.jpg"
      ],
      "status": "active"
    }
  }
}
```

### Error Response — Plan Not Found (404)

```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Plan] 999",
  "errors": {}
}
```

---

## Service Object Structure

Each plan includes a nested `service` object with these fields:

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique service ID |
| `name` | string | Service display name (e.g., "Fitness", "Padel") |
| `slug` | string | URL-friendly identifier |
| `description` | string | Full service description text |
| `image_url` | string\|null | Primary image URL (absolute URL) |
| `images` | array | Array of image URLs (falls back to `[image_url]` if no images array) |
| `status` | string | Service status — always `"active"` for plans visible in API |

---

## Filtering Rules (Automatic)

The API applies these filters automatically via the `ActivePlanScope` global scope:

1. **Archived plans excluded** — Plans with `is_archived = true` are never returned
2. **Inactive service plans excluded** — Plans whose parent service has `status != 'active'` are never returned

You do not need to pass any query parameters to enable these filters — they are always active.

---

## Plan → Course Relationship

Plans can link to specific courses via a many-to-many pivot table (`course_plan`). This is not exposed in the Plans API directly — courses are loaded separately through the Courses API.

- If `has_all_courses = true`: the plan grants blanket access to all courses in its service
- If `has_all_courses = false`: the plan grants access only to explicitly linked courses (queryable via `GET /courses?plan_id={id}`)

---

## Plan → Subscription Flow

When a user subscribes to a plan:

1. Client calls `GET /plans` to browse available plans
2. User selects a plan
3. Client calls `POST /subscriptions` with the selected `plan_id` to create a subscription
4. Payment is processed via Konnect or Loyalty Points (see `docs/KONNECT_API.md` or `docs/LOYALTY_API.md`)

---

## Flutter Integration Notes

### Dart Model

```dart
class Plan {
  final String id;
  final String name;
  final double price;
  final int durationDays;
  final bool hasAllCourses;
  final Service service;

  Plan({
    required this.id,
    required this.name,
    required this.price,
    required this.durationDays,
    required this.hasAllCourses,
    required this.service,
  });

  factory Plan.fromJson(Map<String, dynamic> json) {
    return Plan(
      id: json['id'],
      name: json['name'],
      price: (json['price'] as num).toDouble(),
      durationDays: json['duration_days'],
      hasAllCourses: json['has_all_courses'],
      service: Service.fromJson(json['service']),
    );
  }
}
```

### Paginated Response Handler

```dart
class PaginatedResponse<T> {
  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  PaginatedResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  bool get hasMore => currentPage < lastPage;
}
```

### API Call Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<PaginatedResponse<Plan>> fetchPlans({int page = 1, int perPage = 15}) async {
  final uri = Uri.parse('https://yourdomain.com/api/v1/plans')
      .replace(queryParameters: {
    'page': page.toString(),
    'per_page': perPage.toString(),
  });

  final response = await http.get(uri);

  if (response.statusCode == 200) {
    final json = jsonDecode(response.body);
    final List<Plan> plans = (json['data'] as List)
        .map((item) => Plan.fromJson(item))
        .toList();

    return PaginatedResponse<Plan>(
      data: plans,
      currentPage: json['meta']['current_page'],
      lastPage: json['meta']['last_page'],
      perPage: json['meta']['per_page'],
      total: json['meta']['total'],
    );
  }

  throw Exception('Failed to load plans: ${response.statusCode}');
}
```

---

## Error Codes Reference

| HTTP Status | Condition | Resolution |
|-------------|-----------|------------|
| 200 | Success | — |
| 404 | Plan not found | Verify plan ID exists and is not archived |
| 500 | Server error | Retry, check server logs |

---

## Related Endpoints

| Endpoint | Doc | Description |
|----------|-----|-------------|
| `GET /services` | `docs/SUBSCRIPTION_API.md` | List parent services |
| `GET /courses` | — | List courses (filterable by `plan_id`) |
| `POST /subscriptions` | `docs/SUBSCRIPTION_API.md` | Subscribe to a plan |
| `POST /loyalty/pay` | `docs/LOYALTY_API.md` | Pay for subscription with loyalty points |
| `POST /payments/initiate` | `docs/KONNECT_API.md` | Pay for subscription via Konnect |
