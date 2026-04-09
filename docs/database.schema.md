# Bourgo Arena — Database Schema & ERD

> **Purpose:** Canonical model definitions, relationships, and field contracts.
> Copilot must use these exact table names, column names, and types.
> **ORM:** Laravel Eloquent · Migrations · MySQL 8+

---

## Entity Relationship Summary

```
Member ──────────── NfcCard          (one-to-one)
Member ──────────── Subscription[]   (one-to-many, one active at a time)
Member ──────────── MemberOnboardingToken[] (one-to-many)
Member ──────────── MemberDeviceToken[] (one-to-many)
Member ──────────── MemberNotification[] (one-to-many)
Subscription ──────── Plan           (many-to-one)
Member ──────────── Booking[]        (one-to-many)
Booking ──────────── CourseSession   (many-to-one)
Booking ──────────── CourtSlot       (many-to-one, nullable)
Member ──────────── CheckInEvent[]   (one-to-many, via NfcCard)
NfcCard ──────────── CheckInEvent[]  (one-to-many)
HikvisionTerminal ── CheckInEvent[]  (one-to-many)
```

---

## Tables

### `members`

| Column                      | Type                                           | Notes                      |
| --------------------------- | ---------------------------------------------- | -------------------------- |
| `id`                        | bigint PK                                      |                            |
| `name`                      | string                                         |                            |
| `email`                     | string unique                                  |                            |
| `phone`                     | string unique                                  |                            |
| `date_of_birth`             | date                                           |                            |
| `gender`                    | enum(`male`,`female`)                          |                            |
| `emergency_contact`         | string nullable                                |                            |
| `avatar`                    | string nullable                                | path to stored image       |
| `status`                    | enum(`pending`,`active`,`suspended`,`expired`) | default `pending`          |
| `rgpd_consented_at`         | timestamp nullable                             | required before activation |
| `password`                  | string                                         | hashed                     |
| `remember_token`            | string nullable                                |                            |
| `deleted_at`                | timestamp nullable                             | soft-delete marker         |
| `created_at` / `updated_at` | timestamps                                     |                            |

> Member records use soft deletes (`deleted_at`) to preserve historical subscriptions and check-in history.

**Relationships:**

```php
hasOne(NfcCard::class)
hasMany(Subscription::class)
hasMany(CheckInEvent::class)
hasMany(Booking::class)
hasMany(MemberOnboardingToken::class)
hasMany(MemberDeviceToken::class)
hasMany(MemberNotification::class)
```

---

### `member_onboarding_tokens`

| Column                      | Type                   | Notes                                       |
| --------------------------- | ---------------------- | ------------------------------------------- |
| `id`                        | bigint PK              |                                             |
| `member_id`                 | bigint FK → members.id |                                             |
| `email`                     | string                 | member email snapshot at token creation     |
| `token_hash`                | string(64) unique      | SHA-256 hash of onboarding token            |
| `expires_at`                | timestamp              | default flow uses 24-hour validity          |
| `used_at`                   | timestamp nullable     | set when password setup is completed        |
| `created_at` / `updated_at` | timestamps             |                                             |

**Relationships:**

```php
belongsTo(Member::class)
```

---

### `member_device_tokens`

| Column                      | Type                   | Notes                                      |
| --------------------------- | ---------------------- | ------------------------------------------ |
| `id`                        | bigint PK              |                                            |
| `member_id`                 | bigint FK → members.id |                                            |
| `token`                     | string unique          | push device token (FCM token)              |
| `provider`                  | string                 | default `fcm`                              |
| `device_type`               | string nullable        | `android` \/ `ios` \/ `web`                |
| `is_active`                 | boolean                | default true                               |
| `last_used_at`              | timestamp nullable     | token heartbeat / deactivation audit       |
| `created_at` / `updated_at` | timestamps             |                                            |

**Relationships:**

```php
belongsTo(Member::class)
```

---

### `member_notifications`

| Column                      | Type                   | Notes                                     |
| --------------------------- | ---------------------- | ----------------------------------------- |
| `id`                        | bigint PK              |                                           |
| `member_id`                 | bigint FK → members.id |                                           |
| `type`                      | string                 | domain event key, e.g. `member_welcome`   |
| `title`                     | string                 | short notification title                  |
| `message`                   | text                   | full notification body                    |
| `channel`                   | string                 | `in_app`, `push`, `sms`, or `email`       |
| `status`                    | string                 | `queued`, `delivered`, or `failed`        |
| `is_read`                   | boolean                | read-state in member mobile account       |
| `metadata`                  | json nullable          | channel/provider specific context          |
| `delivered_at`              | timestamp nullable     | set when message is delivered/persisted    |
| `created_at` / `updated_at` | timestamps             |                                           |

**Relationships:**

```php
belongsTo(Member::class)
```

---

### `nfc_cards`

| Column                      | Type                              | Notes                  |
| --------------------------- | --------------------------------- | ---------------------- |
| `id`                        | bigint PK                         |                        |
| `member_id`                 | bigint FK → members.id            |                        |
| `uid`                       | string unique                     | hardware UID from card |
| `status`                    | enum(`active`,`suspended`,`lost`) | default `active`       |
| `assigned_by`               | bigint FK → users.id              | admin who assigned     |
| `assigned_at`               | timestamp                         |                        |
| `created_at` / `updated_at` | timestamps                        |                        |

**Relationships:**

```php
belongsTo(Member::class)
belongsTo(User::class, 'assigned_by')
hasMany(CheckInEvent::class, 'card_uid', 'uid')
```

---

### `plans`

| Column                      | Type          | Notes                                                   |
| --------------------------- | ------------- | ------------------------------------------------------- |
| `id`                        | bigint PK     |                                                         |
| `name`                      | string        |                                                         |
| `has_all_courses`           | boolean       | default false — allows member to book any class         |
| `price`                     | decimal(10,3) | Tunisian Dinar                                          |
| `duration_days`             | integer       |                                                         |
| `included_services`         | json          | array of custom service names (e.g. `gym`, `tennis`)    |
| `image_url`                 | string        | nullable, points to LFS url                             |
| `is_archived`               | boolean       | default false — never delete plans                      |
| `created_at` / `updated_at` | timestamps    |                                                         |

**Relationships:**

```php
hasMany(Subscription::class)
belongsToMany(Course::class)
```

---

### `course_plan` (Pivot Table)

| Column                      | Type          | Notes                                                   |
| --------------------------- | ------------- | ------------------------------------------------------- |
| `id`                        | bigint PK     |                                                         |
| `plan_id`                   | bigint FK     | references `plans.id`                                   |
| `course_id`                 | bigint FK     | references `courses.id`                                 |
| `created_at` / `updated_at` | timestamps    |                                                         |

---

### `subscriptions`

| Column                      | Type                                               | Notes                                   |
| --------------------------- | -------------------------------------------------- | --------------------------------------- |
| `id`                        | bigint PK                                          |                                         |
| `member_id`                 | bigint FK → members.id                             |                                         |
| `plan_id`                   | bigint FK → plans.id                               |                                         |
| `status`                    | enum(`active`,`suspended`,`expired`,`transferred`) |                                         |
| `starts_at`                 | date                                               |                                         |
| `ends_at`                   | date                                               | always = starts_at + plan.duration_days |
| `suspended_at`              | timestamp nullable                                 | set on suspension                       |
| `days_remaining`            | integer nullable                                   | frozen on suspension                    |
| `resumed_at`                | timestamp nullable                                 | set on resume                           |
| `payment_method`            | enum(`cash`,`konnect`,`paymee`)                    |                                         |
| `payment_reference`         | string nullable                                    | gateway transaction ID                  |
| `amount_paid`               | decimal(10,3)                                      |                                         |
| `receipt_path`              | string nullable                                    | path to generated PDF                   |
| `enrolled_by`               | bigint FK → users.id                               | admin who enrolled                      |
| `created_at` / `updated_at` | timestamps                                         |                                         |

**Relationships:**

```php
belongsTo(Member::class)
belongsTo(Plan::class)
belongsTo(User::class, 'enrolled_by')
```

**Scopes:**

```php
scopeActive($q)    → where status = active AND ends_at > now()
scopeExpiring($q)  → active subscriptions where ends_at <= now() + 7 days
```

---

### `hikvision_terminals`

| Column                      | Type                                      | Notes                             |
| --------------------------- | ----------------------------------------- | --------------------------------- |
| `id`                        | bigint PK                                 |                                   |
| `name`                      | string                                    | human-readable label              |
| `ip_address`                | string                                    |                                   |
| `serial_number`             | string unique                             |                                   |
| `location`                  | string                                    | e.g. "Main Entrance", "Exit Gate" |
| `terminal_type`             | enum(`entry`,`exit`)                      | drives anti-passback logic        |
| `api_token`                 | string unique                             | generated at provisioning         |
| `status`                    | enum(`online`,`offline`,`decommissioned`) |                                   |
| `last_seen_at`              | timestamp nullable                        | updated on each webhook           |
| `created_at` / `updated_at` | timestamps                                |                                   |

**Relationships:**

```php
hasMany(CheckInEvent::class)
```

---

### `check_in_events`

| Column          | Type                                                                                  | Notes                                      |
| --------------- | ------------------------------------------------------------------------------------- | ------------------------------------------ |
| `id`            | bigint PK                                                                             |                                            |
| `member_id`     | bigint FK → members.id nullable                                                       | null if card unknown                       |
| `card_uid`      | string                                                                                | raw UID from terminal tap                  |
| `terminal_id`   | bigint FK → hikvision_terminals.id                                                    |                                            |
| `result`        | enum(`authorized`,`denied`)                                                           |                                            |
| `denial_reason` | enum(`expired_subscription`,`suspended_card`,`invalid_card`,`anti_passback`) nullable |                                            |
| `is_suspicious` | boolean                                                                               | default false — set by anti-passback logic |
| `checked_in_at` | timestamp                                                                             | immutable — set at creation                |
| `created_at`    | timestamp                                                                             | no `updated_at` — records are immutable    |

> ⚠️ No `updated_at` column. Records must never be modified after creation.

**Relationships:**

```php
belongsTo(Member::class)
belongsTo(HikvisionTerminal::class, 'terminal_id')
```

---

### `courses`

| Column                      | Type               | Notes               |
| --------------------------- | ------------------ | ------------------- |
| `id`                        | bigint PK          |                     |
| `name`                      | string             |                     |
| `instructor`                | string             |                     |
| `description`               | text nullable      |                     |
| `color`                     | string nullable    | hex color code      |
| `image_url`                 | string nullable    | points to LFS url   |
| `created_at` / `updated_at` | timestamps         |                     |

**Relationships:**

```php
hasMany(CourseSession::class)
belongsToMany(Plan::class)
```

---

### `course_sessions`

| Column                      | Type               | Notes               |
| --------------------------- | ------------------ | ------------------- |
| `id`                        | bigint PK          |                     |
| `course_id`                 | bigint FK          | → courses.id        |
| `day_of_week`               | tinyint            | 0=Monday … 6=Sunday |
| `starts_at`                 | time               | e.g. 09:00:00       |
| `duration_minutes`          | integer            |                     |
| `capacity`                  | integer            |                     |
| `is_cancelled`              | boolean            | default false       |
| `cancelled_at`              | timestamp nullable |                     |
| `created_at` / `updated_at` | timestamps         |                     |

**Relationships:**

```php
hasMany(Booking::class)
```

---

### `court_slots`

| Column                      | Type                            | Notes            |
| --------------------------- | ------------------------------- | ---------------- |
| `id`                        | bigint PK                       |                  |
| `court_type`                | enum(`tennis`,`squash`)         |                  |
| `date`                      | date                            |                  |
| `starts_at`                 | time                            |                  |
| `ends_at`                   | time                            |                  |
| `member_id`                 | bigint FK → members.id nullable | null = available |
| `created_at` / `updated_at` | timestamps                      |                  |

---

### `bookings`

| Column                      | Type                                       | Notes                         |
| --------------------------- | ------------------------------------------ | ----------------------------- |
| `id`                        | bigint PK                                  |                               |
| `member_id`                 | bigint FK → members.id                     |                               |
| `course_session_id`         | bigint FK → course_sessions.id nullable    |                               |
| `court_slot_id`             | bigint FK → court_slots.id nullable        |                               |
| `status`                    | enum(`confirmed`,`waitlisted`,`cancelled`) |                               |
| `waitlist_position`         | integer nullable                           | integer order when waitlisted |
| `cancelled_at`              | timestamp nullable                         |                               |
| `created_at` / `updated_at` | timestamps                                 |                               |

> ⚠️ Exactly one of `course_session_id` or `court_slot_id` must be set — never both.

**Relationships:**

```php
belongsTo(Member::class)
belongsTo(CourseSession::class)
belongsTo(CourtSlot::class)
```

---

### `users` (Admin & Manager accounts — separate from Members)

| Column                      | Type                    | Notes |
| --------------------------- | ----------------------- | ----- |
| `id`                        | bigint PK               |       |
| `name`                      | string                  |       |
| `email`                     | string unique           |       |
| `password`                  | string                  |       |
| `role`                      | enum(`admin`,`manager`) |       |
| `created_at` / `updated_at` | timestamps              |       |

> Members authenticate separately via the `members` table. Admin/Manager use the `users` table.
> Never merge these two auth guards.

---

### `terminal_whitelists` (Offline mode cache)

| Column        | Type                               | Notes          |
| ------------- | ---------------------------------- | -------------- |
| `id`          | bigint PK                          |                |
| `terminal_id` | bigint FK → hikvision_terminals.id |                |
| `card_uid`    | string                             | authorized UID |
| `synced_at`   | timestamp                          | last sync time |

---

## Key Laravel Conventions for This Project

```php
// Always use these guard names
Auth::guard('web')      // Admin/Manager (users table)
Auth::guard('member')   // Members (members table)
Auth::guard('terminal') // Hikvision terminals (api_token)

// Subscription active check — always use the scope, never raw query
$member->subscriptions()->active()->exists()

// CheckInEvent — always use create(), never update()
CheckInEvent::create([...]);

// Money — always store as decimal(10,3) for Tunisian Dinar (TND)
// Never use float for financial values

// Dates — always use Carbon, always store in UTC
$subscription->ends_at->gt(now())
```

## Analytics & Aggregations (Phase 2)

### `revenue_snapshots`
Stores nightly aggregations of revenue and subscription metrics for fast dashboard reporting (US-020).

*   `id` (BigInt, PK)
*   `date` (Date, Unique) - The date this snapshot represents.
*   `total_revenue` (Decimal 10,2, Default 0) - Total revenue generated on this date.
*   `active_subscriptions` (Int, Default 0) - Count of active subscriptions at the time of snapshot.
*   `expired_subscriptions` (Int, Default 0) - Count of expired subscriptions on that date.
*   `churn_rate` (Decimal 5,2, Default 0.00) - Calculated churn rate percentage.
*   `revenue_by_method` (JSON, nullable) - Key-value pair of payment methods and their revenue totals (e.g. `{"cash": 100, "konnect": 250}`).
*   `plan_metrics` (JSON, nullable) - Details of active subscribers and revenue split per plan.
*   `created_at` / `updated_at` (Timestamps)

### `occupancy_hourly_aggregates`
Stores nightly aggregations of check-in events broken down by hour to render the occupancy heatmap (US-021).

*   `id` (BigInt, PK)
*   `date` (Date) - The date of the aggregations.
*   `hour` (Int) - The hour block (0–23).
*   `entries_count` (Int, Default 0) - Total `entry` checks during this hour.
*   `exits_count` (Int, Default 0) - Total `exit` checks during this hour.
*   `avg_occupancy` (Int, Default 0) - The average gym capacity observed over this hour block.
*   `created_at` / `updated_at` (Timestamps)

*Indexes:*
*   Unique composite index on `(date, hour)`
