# Plan & Subscription Engine

This document covers the plan-subscription system: the multi-level plan hierarchy, smart enrollment validation with stacking/upgrade/merge logic, tier-based loyalty multipliers, course access control, and scheduling conflict detection.

---

## Table of Contents

1. [Domain Model Hierarchy](#domain-model-hierarchy)
2. [Tier System](#tier-system)
3. [Enrollment Validation (Smart Stacking)](#enrollment-validation-smart-stacking)
4. [Activation Logic: Renew, Upgrade, or Stack](#activation-logic-renew-upgrade-or-stack)
5. [Enrollment Flow Diagram](#enrollment-flow-diagram)
6. [Subscription State Transitions](#subscription-state-transitions)
7. [Course Access Control](#course-access-control)
8. [Scheduling Conflict Detection](#scheduling-conflict-detection)
9. [Loyalty Point Integration](#loyalty-point-integration)
10. [API Endpoints](#api-endpoints)
11. [Key Files Reference](#key-files-reference)

---

## Domain Model Hierarchy

```
                              Service
                       (e.g. Padel, Football)
                         │
            ┌────────────┼────────────┐
            ▼            ▼            ▼
          Plan         Course       Activity
      (level 1/2/3)      │
            │            │
            │     ┌──────┴──────┐
            │     ▼             ▼
            │  CourseSession  ActivitySession
            │  (day_of_week,  (day_of_week,
            │   start_time,    start_time,
            │   duration,      duration,
            │   capacity)      capacity)
            │
            ▼
      Subscription
   (links Member → Plan)
     status, dates,
     payment fields
```

### Key Relationships

- A **Service** is the top-level category (e.g. Padel, Football, Gym)
- A **Plan** belongs to exactly one Service. It has a `level` (1, 2, or 3), a `price`, a `duration_days`, and a `has_all_courses` boolean
- A **Course** also belongs to one Service. Plans and Courses have a many-to-many relationship via `course_plan` pivot
- A **Subscription** links a Member to a Plan for a date range
- An **Activity** belongs to a Service and has ActivitySessions

### Plan Global Scope

`ActivePlanScope` automatically filters all Plan queries:
```sql
WHERE is_archived = false
  AND EXISTS (SELECT 1 FROM services WHERE services.id = plans.service_id AND services.status = 'active')
```
Archived plans and those belonging to inactive services are invisible to all queries — including through relationship eager loads.

---

## Tier System

The tier system is **member-level**, calculated from the number of active subscriptions across all services. It ONLY affects the loyalty point multiplier — not course access or plan features.

### Tier Definitions (`config/tiers.php`)

**Individual Tiers:**

| Tier | Required Subscriptions | Loyalty Multiplier |
|------|----------------------|-------------------|
| **Standard** | 0 | 1.0× |
| **Plus** | 2 | 1.2× |
| **Ultra** | 3 | 1.5× |
| **Max** | 4+ | 2.0× |

**Family Tiers** (same tiers, but counts subscriptions across ALL family members):

| Tier | Required Subscriptions | Loyalty Multiplier |
|------|----------------------|-------------------|
| **Family** | 0 | 1.0× |
| **Family Plus** | 2 | 1.2× |
| **Family Ultra** | 3 | 1.5× |
| **Family Max** | 4+ | 2.0× |

### Tier Resolution Flow

```
Member
 │
 ├─ Is child (has parent_id)?
 │   ├─ YES → Use parent as effective member
 │   │         Count all subscriptions across parent + all children
 │   └─ NO  → Count member's own subscriptions
 │
 └─ TierResolutionService::calculateTierDetails(count, tiers):
     │
     ├─ Walk tiers in order (Standard → Plus → Ultra → Max)
     │
     ├─ While count >= tier.requiredSubscriptions:
     │     currentTier = tier
     │     advance to next tier
     │
     ├─ progressPercentage = count / nextTier.requiredSubscriptions × 100
     │   (0% if at Max already)
     │
     └─ Return TierResolution {
          currentTier: TierData,
          nextTier: TierData | null,
          currentSubscriptionCount: int,
          progressPercentage: float
        }
```

### How Tiers Affect the System

```
Tier Resolution
       │
       ├──▶ LoyaltyCalculatorService::creditFixedMonthlyRenewal()
       │     250 base points × tier.multiplier
       │
       ├──▶ LoyaltyCalculatorService::creditVariableForReservation()
       │     10 base points × monthly_count × tier.multiplier
       │
       └──▶ ReservationService::calculateReservationPrice()
             base_price × (1 - tier_discount)
```

> Tiers do **not** affect: course access, plan features, API permissions, or any other system behavior.

---

## Enrollment Validation (Smart Stacking)

`SubscriptionService::validateEnrollment(Member, Plan)` runs before any subscription is created. It returns `true` (allowed) or an error string (blocked).

```
validateEnrollment(member, plan):
 │
 ├─ 1. DUPLICATE PENDING CHECK
 │   Does this member already have a pending subscription for this exact plan?
 │   │
 │   ├─ YES, stale (>30 min old, no active payment)
 │   │   └─ cancelPending() → cleanup → allow
 │   │
 │   └─ YES, not stale
 │       └─ BLOCK: "You already have a pending payment for this exact plan."
 │
 ├─ 2. EXACT SAME PLAN (Active)
 │   Is there an active subscription for this exact plan_id?
 │   └─ YES → ALLOW (will be treated as Renewal at activation)
 │
 ├─ 3. EXISTING LEVEL 3 + NEW LOWER LEVEL (Same Service)
 │   Does member have an active subscription for the SAME service at level 3,
 │   and is the new plan level < 3?
 │   └─ YES → BLOCK: "Your current plan already provides full access to this
 │                    service. This new plan would be redundant."
 │
 ├─ 4. EXISTING LOWER LEVEL + NEW LEVEL 3 (Same Service)
 │   Does member have an active subscription for the same service at a lower
 │   level, and is the new plan level 3?
 │   └─ YES → ALLOW (will be treated as Upgrade at activation)
 │
 ├─ 5. SAME LEVEL, SAME SERVICE
 │   Both plans at the same level in the same service.
 │   │
 │   ├─ Same course set? (compared by sorted course IDs)
 │   │   └─ BLOCK: "These plans cover the same courses."
 │   │
 │   └─ Different course sets?
 │       └─ ALLOW (will be treated as a side-by-side Stack)
 │
 └─ 6. DIFFERENT SERVICES
     └─ ALLOW (Stack — new subscription row)
```

### Validation Summary Table

| Existing Active Subscription | New Plan | Same Service? | Result |
|---|---|---|---|
| Same plan_id | Same plan_id | Yes | **Renewal** (extend end date) |
| Level 3 | Level 1 or 2 | Yes | **Blocked** (redundant) |
| Level 1 or 2 | Level 3 | Yes | **Upgrade** (merge into existing) |
| Level N | Level N, different courses | Yes | **Stack** (two rows) |
| Level N | Level N, same courses | Yes | **Blocked** (same coverage) |
| Any level | Any level | No | **Stack** (different services always stack) |

---

## Activation Logic: Renew, Upgrade, or Stack

`SubscriptionService::activate(Subscription)` is called by `ProcessSuccessfulPayment` when payment completes. It transitions a `pending` subscription to `active`. The `enroll()` method (admin-side) uses identical logic but creates subscriptions as `active` immediately.

```
activate(pending_subscription):
 │
 ├─ Get member and plan from the pending subscription
 │
 ├─ Check for existing active subscription:
 │
 ├─ RENEWAL: Active subscription with same plan_id exists
 │   │
 │   ├─ Update existing subscription:
 │   │     ends_at = existing.ends_at + plan.duration_days
 │   │     amount_paid = plan.price
 │   │
 │   ├─ Transfer payment details to existing subscription
 │   │
 │   ├─ DELETE the pending subscription
 │   │
 │   └─ finalizeSubscription(existing)
 │       ├─ Generate PDF receipt
 │       ├─ Update member state to active (if pending)
 │       └─ Credit loyalty points (fixed monthly renewal × tier)
 │
 ├─ UPGRADE/MERGE: Active subscription for same service, lower level
 │   │
 │   ├─ Update existing subscription:
 │   │     plan_id = new plan.id
 │   │     ends_at = existing.ends_at + plan.duration_days
 │   │     amount_paid = plan.price
 │   │
 │   ├─ Transfer payment details
 │   │
 │   ├─ DELETE the pending subscription
 │   │
 │   └─ finalizeSubscription(existing)
 │
 └─ STACK: No matching active subscription (or different service)
     │
     ├─ Update pending subscription:
     │     status = 'active'
     │     starts_at = now()
     │     ends_at = now() + plan.duration_days
     │
     └─ finalizeSubscription(pending)
```

### Visualization: Plan Upgrade vs Stack

```
UPGRADE (Level 2 → Level 3, Same Service):
  Before:  [Plan A (L2): ends in 15 days]
  Enroll:  [Plan B (L3): new, pending]
  Pay:     Plan A morphs → Plan B
  After:   [Plan B (L3): ends in 15 + 30 = 45 days]
           (One subscription row, one service)

STACK (Level 2 + Level 2, Different Services):
  Before:  [Plan A (L2, Padel): ends in 15 days]
  Enroll:  [Plan B (L2, Football): new, pending]
  Pay:     Both active
  After:   [Plan A (L2, Padel): ends in 15 days]
           [Plan B (L2, Football): ends in 30 days]
           (Two rows, two services, tier count = 2 → Plus tier)

RENEWAL (Same Plan):
  Before:  [Plan A (L2): ends in 15 days]
  Enroll:  [Plan A (L2): new, pending]
  Pay:     Existing extended
  After:   [Plan A (L2): ends in 15 + 30 = 45 days]
           (No new row created)
```

---

## Enrollment Flow Diagram

```
                         POST /api/v1/subscriptions
                                   │
                                   ▼
                         ┌────────────────────┐
                         │  Validate plan_id   │
                         │  (must exist, not   │
                         │   archived)         │
                         └────────┬───────────┘
                                  │
                                  ▼
                         ┌────────────────────┐
                         │  validateEnrollment │
                         │  (member, plan)     │
                         └────────┬───────────┘
                                  │
                    ┌─────────────┼─────────────┐
                    ▼             ▼             ▼
              ┌───────────┐ ┌───────────┐ ┌───────────┐
              │  ALLOW    │ │  BLOCK    │ │  STALE    │
              │  (true)   │ │  (string) │ │  CLEANUP  │
              └─────┬─────┘ └─────┬─────┘ └─────┬─────┘
                    │             │             │
                    │             ▼             │
                    │       ┌───────────┐       │
                    │       │ 422 Error │       │
                    │       │ with      │       │
                    │       │ reason    │       │
                    │       └───────────┘       │
                    │                           │
                    └───────────┬───────────────┘
                                │
                                ▼
                    ┌────────────────────┐
                    │  Create Pending    │
                    │  Subscription      │
                    │  status = 'pending'│
                    │  starts_at = null  │
                    │  ends_at = null    │
                    └────────┬───────────┘
                             │
                             ▼
                    ┌────────────────────┐
                    │  Return to client   │
                    │  (client then pays) │
                    └────────┬───────────┘
                             │
                    Payment completes
                             │
                             ▼
                    ┌────────────────────┐
                    │  PaymentPaid event │
                    │  → ProcessSuccess..│
                    │    → activate()    │
                    └────────────────────┘
```

---

## Subscription State Transitions

```
                    ┌──────────┐
                    │  Pending  │ (created via API, awaiting payment)
                    └─────┬─────┘
                          │
              ┌───────────┼───────────┐
              │           │           │
         Payment      Stale        User
         completes    timeout      cancels
              │      (30 min)        │
              ▼           ▼           ▼
        ┌─────────┐  ┌──────────┐  ┌───────────┐
        │ Active   │  │ Cancelled│  │ Cancelled  │
        └────┬─────┘  └──────────┘  └───────────┘
             │
     ┌───────┼───────┐
     │       │       │
  Admin    ends_at  Renewal/
  suspends  passes  Upgrade
     │       │       │
     ▼       ▼       ▼
  ┌─────────┐ ┌─────────┐ ┌──────────────┐
  │Suspended│ │ Expired  │ │ Active       │
  │(can be  │ │ (terminal│ │ (extended,   │
  │ resumed)│ │  for that│ │  or morphed  │
  └─────────┘ │  sub)    │ │  into new    │
              └─────────┘ │  plan)        │
                          └──────────────┘
```

### `isActive()` Criteria

```php
$subscription->status === 'active' && $subscription->ends_at > now()
```

Both conditions must be true. Expired subscriptions return `false` even if status reads `active`.

---

## Course Access Control

### Middleware: `course.access` (`EnsureUserHasCourseAccess`)

Applied to course booking routes:
- `GET  /courses/{course}/sessions`
- `GET  /courses/{course}/sessions/{session}/booking`
- `POST /courses/{course}/sessions/{session}/book`

### Access Check (`Member::hasAccessToCourse`)

```
hasAccessToCourse(course):
 │
 ├─ Get all valid (active, non-expired) subscriptions
 │   with plan.courses eager-loaded
 │
 └─ Check if ANY subscription grants access:
     │
     ├─ subscription.plan === null → skip (no, continue)
     │
     ├─ subscription.plan.has_all_courses === true
     │   → "Full Access" plan grants access to ALL courses in the service
     │
     └─ subscription.plan.courses contains this course (by ID)
         → Plan specifically includes this course
```

### Course Access Flow Diagram

```
Request to /courses/{course}/sessions
         │
         ▼
┌────────────────────────────┐
│  course.access middleware    │
│  (EnsureUserHasCourseAccess)│
└────────────┬───────────────┘
             │
             ▼
┌────────────────────────────┐
│  Is user a Member?          │
└────┬───────────┬───────────┘
     │ NO        │ YES
     ▼           ▼
   403      ┌────────────────────────────┐
            │  member.hasAccessToCourse() │
            └────────────┬───────────────┘
                         │
              ┌──────────┴──────────┐
              ▼                     ▼
         ┌─────────┐          ┌──────────┐
         │ Access  │          │ Denied   │
         │ Granted │          │ (403)    │
         │         │          │          │
         │ → pass  │          │ "Your    │
         │   to    │          │  current │
         │   route │          │  plan    │
         │         │          │  does    │
         └─────────┘          │  not     │
                              │  include │
                              │  access  │
                              │  to this │
                              │  course" │
                              └──────────┘
```

### Important Nuances

- **Plans are globally scoped**: Archived plans and plans from inactive services are invisible. If a member has an active subscription to a plan that gets archived (but the service remains active), the subscription rows won't return the plan via the relationship, and `hasAccessToCourse()` will fail (since `plan === null`).
- **`has_all_courses` takes precedence**: If any subscription has this flag, individual course membership isn't checked.
- **Tier does NOT affect course access**: A Standard-tier member with a Level 3 plan has the same access as a Max-tier member.

---

## Scheduling Conflict Detection

Both `CourseSession` and `ActivitySession` implement the same `hasOverlap()` static method to prevent time-conflicting sessions on the same day of the week.

### Method Signature

```php
public static function hasOverlap(
    int $entityId,        // course_id or activity_id
    int $dayOfWeek,       // 0=Sunday, 1=Monday, ..., 6=Saturday
    string $startTime,    // "HH:MM" format
    int $durationMinutes, // total duration in minutes
    ?int $excludeId = null // exclude when editing existing session
): bool
```

### Overlap Logic

```
hasOverlap(entityId, dayOfWeek, startTime, duration, excludeId):
 │
 ├─ newStart = Carbon::parse(startTime)
 ├─ newEnd   = newStart + durationMinutes
 │
 ├─ Query: all non-cancelled sessions for same entity
 │         on same day_of_week
 │         excluding $excludeId (if editing)
 │
 └─ For each existing session:
     ├─ existingStart = Carbon::parse(session.start_time)
     ├─ existingEnd   = existingStart + session.duration
     │
     └─ OVERLAP IF:
         newStart < existingEnd AND newEnd > existingStart
```

### Visual Overlap Examples

```
Session A: 09:00–10:30  (90 min)
Session B: 10:00–11:00  (60 min)  → OVERLAP (10:00 < 10:30 AND 11:00 > 09:00)

Session A: 09:00–10:00  (60 min)
Session B: 10:00–11:00  (60 min)  → NO OVERLAP (10:00 is NOT < 10:00)

Session A: 09:00–10:00  (60 min)
Session B: 08:00–09:00  (60 min)  → NO OVERLAP (09:00 is NOT < 09:00)

Session A: 09:00–11:00  (120 min)
Session B: 09:30–10:00  (30 min)  → OVERLAP (fully contained)
```

### Where It's Used

Both methods are called in admin Livewire forms: `CreateActivitySessionForm`, `ActivitySessionMasterModal`, `CreateSessionForm`, `MasterScheduleModal` — preventing admins from creating scheduling conflicts.

---

## Loyalty Point Integration

The subscription engine credits loyalty points on finalization, applying the member's tier multiplier.

### On Subscription Activation (`finalizeSubscription`)

```
finalizeSubscription(subscription):
 │
 ├─ Generate PDF receipt (ReceiptGenerator)
 │
 ├─ Update member status to active (if still pending_*)
 │
 └─ LoyaltyCalculatorService::creditFixedMonthlyRenewal(subscription)
     │
     ├─ Resolve member tier via TierResolutionService
     │
     ├─ Base: config('loyalty.fixed_monthly_renewal_points') = 250
     │
     ├─ Points = 250 × tier.multiplier
     │   Standard: 250 × 1.0 = 250
     │   Plus:    250 × 1.2 = 300
     │   Ultra:   250 × 1.5 = 375
     │   Max:     250 × 2.0 = 500
     │
     └─ Idempotency key: subscription:{id}:monthly-renewal
        (UNIQUE constraint prevents double-credit on accidental re-finalize)
```

### Loyalty Points as Payment Method

Members can pay for subscriptions using loyalty points instead of Konnect. See [Payment & Reservation Lifecycle](PAYMENT_LIFECYCLE.md#loyalty-points-as-payment) for the full flow.

---

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/v1/tiers` | Guest (api.access) | List all tiers (individual + family) |
| `GET` | `/api/v1/member/tier` | Full (onboarded) | Current member's tier resolution |
| `GET` | `/api/v1/plans` | Guest (api.access) | List available plans |
| `GET` | `/api/v1/plans/{plan}` | Guest (api.access) | Plan details |
| `GET` | `/api/v1/member/subscription` | Full (onboarded) | Current active subscriptions |
| `GET` | `/api/v1/member/subscriptions/history` | Full (onboarded) | Paginated subscription history |
| `POST` | `/api/v1/subscriptions` | Full (onboarded) | Initiate subscription (creates pending) |
| `POST` | `/api/v1/subscriptions/{id}/cancel` | Full (onboarded) | Cancel pending subscription |
| `GET` | `/api/v1/courses` | Guest (api.access) | List courses |
| `GET` | `/api/v1/courses/{course}` | Guest (api.access) | Course details |
| `GET` | `/api/v1/courses/{course}/sessions` | Full + `course.access` | List sessions for a course |
| `POST` | `/api/v1/courses/{course}/sessions/{session}/book` | Full + `course.access` | Book a session |
| `POST` | `/api/v1/loyalty/pay` | Full + `tunisia_geo` | Pay for subscription with points |

---

## Key Files Reference

### Services
| File | Role |
|------|------|
| `app/Services/SubscriptionService.php` | Core engine: `validateEnrollment()`, `enroll()`, `activate()`, `finalizeSubscription()`, `cancelPending()`, `isStalePending()` |
| `app/Services/TierResolutionService.php` | Tier calculation: counts subscriptions, assigns tier, computes progress to next tier |
| `app/Services/LoyaltyCalculatorService.php` | Credits loyalty points on subscription renewal and reservation |
| `app/Services/LoyaltyPaymentService.php` | Pay for subscriptions/reservations with loyalty points |
| `app/Services/ApiSubscriptionRepository.php` | `getValidSubscriptionCount()` for tier resolution |
| `app/Services/ApiFamilyRepository.php` | Family member resolution for family tier calculation |
| `app/Services/ReceiptGenerator.php` | PDF receipt generation |

### Models
| File | Role |
|------|------|
| `app/Models/Service.php` | Top-level category (status, slug, name) |
| `app/Models/Plan.php` | Plan model (level, price, duration_days, has_all_courses, archived) |
| `app/Models/Subscription.php` | Subscription model (status, dates, payment fields, scopes) |
| `app/Models/Course.php` | Course model (belongsToMany Plans) |
| `app/Models/CourseSession.php` | Course session with `hasOverlap()` |
| `app/Models/ActivitySession.php` | Activity session with `hasOverlap()` |
| `app/Models/Member.php` | `hasAccessToCourse()`, `validSubscriptions()` |
| `app/Models/Scopes/ActivePlanScope.php` | Global scope filtering archived plans |

### DTOs
| File | Role |
|------|------|
| `app/DTOs/Tier/TierData.php` | Tier definition (label, multiplier, required subscriptions) |
| `app/DTOs/Tier/TierResolution.php` | Tier calculation result (current, next, progress) |

### Middleware
| File | Role |
|------|------|
| `app/Http/Middleware/EnsureUserHasCourseAccess.php` | `course.access` middleware |

### Controllers
| File | Role |
|------|------|
| `app/Http/Controllers/Api/V1/SubscriptionController.php` | Subscription API (active, history, store, cancel) |
| `app/Http/Controllers/Api/V1/TierController.php` | Tier API (index, show) |
| `app/Http/Controllers/Api/V1/CourseController.php` | Course listing and session booking |

### Events & Listeners
| File | Role |
|------|------|
| `app/Events/PaymentPaid.php` | Dispatched when payment completes |
| `app/Listeners/ProcessSuccessfulPayment.php` | Calls `SubscriptionService::activate()` for subscription payments |

### Config
| File | Role |
|------|------|
| `config/tiers.php` | Tier definitions (labels, multipliers, required subscription counts) |
| `config/loyalty.php` | Points-to-TND rate, renewal base points, pricing discounts by tier |

### Tests
| File | Role |
|------|------|
| `tests/Feature/Api/V1/SubscriptionTest.php` | 14 tests covering stacking, upgrades, redundancy, stale cleanup, cancellation |
