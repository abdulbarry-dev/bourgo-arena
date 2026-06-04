# Mobile API V1 Updates (Bourgo Arena)

This document outlines the latest API updates and endpoints added to align the mobile application with the backend dashboard logic. 

**Important Note for the Mobile Agent:** 
Many of the endpoints below now explicitly include an `image_url` property natively in their JSON response. This was specifically requested so that the mobile screens (e.g., Service categories, Course listings, Plan options) can be designed with rich, professional imagery without requiring extra lookups.

---

## 1. Services API

Previously, services were not fully exposed. Now, they represent the main categories (e.g., Football, Padel).

### `GET /api/v1/services`
Returns a paginated list of all active services.
- **Response Format**: `AnonymousResourceCollection<ServiceResource>`
- **Key Fields Exposed**:
  - `id`: The unique identifier
  - `name`: The service name (e.g., "Football")
  - `image_url`: **[NEW]** The absolute URL of the service image. Use this for the home screen category cards.

### `GET /api/v1/services/{service}`
Returns detailed information for a specific service.

---

## 2. Courses API

The previous `GET /api/v1/courses` endpoint incorrectly returned `CourseSession` models directly. It has been restructured to properly distinguish between the Course Catalog and Scheduled Sessions.

### `GET /api/v1/courses`
Returns the Course Catalog (paginated).
- **Response Format**: `AnonymousResourceCollection<CourseResource>`
- **Key Fields Exposed**:
  - `id`: The unique identifier
  - `name`: The course name
  - `image_url`: **[NEW]** The absolute URL for the course's cover image.

### `GET /api/v1/courses/{course}`
Returns the details of a specific course from the catalog.

### `GET /api/v1/courses/{course}/sessions`
Returns upcoming, non-cancelled scheduled sessions for a specific course.
- **Response Format**: `AnonymousResourceCollection<CourseSessionResource>`
- **Key Fields Exposed**:
  - `id`: Session ID
  - `start_time` / `end_time`: Formatted time strings
  - `title`: The title of the session (inherited from the parent Course).
  - `course.image_url`: The parent course's image URL is available here as well.

---

## 3. Plans & Subscriptions API

Memberships and subscription plans are now exposed to allow users to subscribe directly from the app.

### `GET /api/v1/plans`
Returns a list of all active plans.
- **Response Format**: `AnonymousResourceCollection<PlanResource>`
- **Key Fields Exposed**:
  - `id`, `name`, `description`, `price`, `billing_cycle`
  - `service`: **[NEW]** A nested `ServiceResource` object. Plans don't have images natively; they inherit their visual identity from the associated Service. Use `service.image_url` to design the plan selection cards beautifully.

### `GET /api/v1/plans/{plan}`
Returns details for a specific plan.

### `POST /api/v1/subscriptions`
Initiates a new subscription to a plan. (Requires `auth:sanctum` and verified account middleware).
- **Payload**:
  - `plan_id` (integer, required)

### `POST /api/v1/subscriptions/{subscription}/cancel`
Cancels an active subscription owned by the authenticated member. (Requires `auth:sanctum`).

---

## 4. User Payment History API

Users can now view their transaction and payment history within the app.

### `GET /api/v1/user/payments`
Returns a paginated list of all payments made by the currently authenticated member. (Requires `auth:sanctum`).
- **Response Format**: `AnonymousResourceCollection<PaymentResource>`
- **Key Fields Exposed**:
  - `id`, `type`, `amount`, `currency`, `status`, `gateway`, `payment_reference`, `created_at`
  - *Use these fields to build a professional transaction history screen.*

---

## Authentication & Middleware Context

For the Mobile Agent handling these calls:
- Endpoints under `/user/*` or `/subscriptions/*` are protected by `auth:sanctum`.
- Ensure that the Bearer token acquired during login is appended to the `Authorization` header.
- Subscriptions typically require the user to have passed onboarding (`onboarding.completed` middleware) and have verified their email or phone (`verified.account` middleware). Handle 403 Forbidden responses appropriately by routing the user to the verification/onboarding screens if needed.
