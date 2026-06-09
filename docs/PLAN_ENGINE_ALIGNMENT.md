# Plan & Subscription Engine Technical Specification

This document provides a comprehensive overview of the subscription logic, data models, and integration points for the Bourgo Arena plan engine.

## 1. Subscription Business Logic (Smart Stacking Model)

### Smart Stacking Logic
Subscriptions are no longer strictly limited to one per service. The system now uses a **Tiered Level Model** to determine if a new subscription should merge, extend, or stack in parallel.

- **Level 1 (Basic/Facility):** General access (e.g., gym access only).
- **Level 2 (Specific):** Access to specific courses or activities.
- **Level 3 (Full/Elite):** Access to everything within that service.

### Enrollment Validation
Handled by `App\Services\SubscriptionService::validateEnrollment`.
- **Renewal (Same Plan):** Allowed. Buying the exact same plan will extend the duration of the existing subscription record.
- **Redundancy (Subset):** Blocked. If a user has a Level 3 (Full Access) plan, they cannot buy a Level 1 or 2 plan in the same service.
- **Overlap (Same Level):** Blocked if identical course coverage. If a user buys a Level 2 plan that covers the **exact same courses** they already have, it is blocked as redundant.
- **Unique Access (Stacking):** Allowed. If a user has "Yoga (Level 2)" and buys "Crossfit (Level 2)", these will **stack** as two separate active subscriptions.

### Concurrency & Lifecycle Handling
- **Pending Block:** A member cannot initiate a new `pending` purchase for a **Plan ID** they already have a `pending` payment for. However, they can initiate a different plan (stacking) immediately.

| Scenario | Condition | Result |
| :--- | :--- | :--- |
| **Renewal** | Same Plan ID | **Extend Existing:** Current `ends_at` + New `duration_days`. |
| **Upgrade** | New Plan Level > Existing | **Merge & Extend:** Existing subscription is updated to the new `plan_id` and its duration is extended. User gets higher-tier features immediately. |
| **Stacking** | New Plan is different & non-redundant | **Create New:** A separate subscription record is created. Member has cumulative access. |

### Upgrade Flow
1. Member initiates purchase -> `pending` subscription created.
2. Member pays -> `PaymentPaid` event dispatched.
3. System checks for merge candidates (same plan or lower tier).
4. If found: existing record is updated/extended and `pending` is deleted.
5. If not: `pending` is activated as a new standalone subscription.

---

## 2. Data Models & Schema

### Plan Model (`plans`)
| Column | Type | Description |
| :--- | :--- | :--- |
| `level` | `integer` | Hierarchy level (1=Facility, 2=Courses, 3=Full). |
| `duration_days` | `integer` | Days added upon purchase/renewal. |
| `has_all_courses`| `boolean` | If true, typically Level 3. |

### Subscription Model (`subscriptions`)
*No change to schema, but `ends_at` can be extended multiple times via renewals/upgrades.*

### Loyalty Points (`loyalty_points`)
| Column | Type | Description |
| :--- | :--- | :--- |
| `member_id` | `bigint` | FK to members. |
| `points` | `integer` | Points awarded (or deducted). |
| `transaction_type` | `string` | `fixed`, `variable`, `gift`, `refund`. |
| `source_type` | `string` | Morphable source (e.g., `App\Models\Subscription`). |
| `source_id` | `bigint` | ID of the source record. |

---

## 3. Payment Gateway Integration

### Integration Points
1.  **Initiation:** `PaymentController::initiate` creates a `Payment` record linked to the `subscription_id`.
2.  **Verification:** `PaymentController::verify` or `webhook` calls `PaymentService::verify` / `handleWebhook`.
3.  **Event Dispatch:** Upon successful payment (`status = 'paid'`), the `App\Events\PaymentPaid` event is dispatched.

### Lifecycle Mapping
- **Transaction Success:** `PaymentPaid` event is caught by `App\Listeners\ProcessSuccessfulPayment`.
- **Activation:** The listener calls `SubscriptionService::activate($subscription)`.
- **State Transition:**
    - `pending` subscription → `active`.
    - If upgrade → existing `active` extended, `pending` discarded.
- **Finalization:** Receipts generated and loyalty points credited only after the state becomes `active`.

---

## 4. Admin Dashboard (TALL Stack)

### Management Components
- **`PlanTable.php`**: Full CRUD for plans. Implements validation logic for archiving/deleting based on active references.
- **`SubscriptionTable.php`**: Searchable/filterable list of all subscriptions.
- **`SubscriptionEnrollmentFlyout.php`**: Flyout form for manual (admin-side) enrollment. Uses `SubscriptionService::enroll` for consistent rule enforcement.

---

## 5. Implementation Summary (FAQ)

- **Are subscriptions service-scoped?** Yes, one active per service per user.
- **Does an upgrade cancel or extend?** It **extends** the existing active subscription.
- **Are loyalty points accrued per transaction?** Yes, credited upon activation of each subscription/upgrade transaction.
- **How is state transition tracked?** Primarily via the `status` column and the `PaymentPaid` event system for automated flows.
