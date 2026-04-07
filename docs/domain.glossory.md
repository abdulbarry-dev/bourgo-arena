# Bourgo Arena — Domain Glossary

> **Purpose:** Canonical definitions for all domain terms used in prompts, code, and documentation.
> GitHub Copilot and AI agents must treat these as the single source of truth.
> **Stack:** Laravel 11 · Livewire 3 · Flux UI · Hikvision ISAPI

---

## Core Domain Entities

### Member

A registered gym customer with a physical NFC card and a mobile app account.

- Has one active `Subscription` at a time
- Has one assigned `NfcCard`
- Generates `CheckInEvent` records on every access attempt
- Statuses: `pending` → `active` → `suspended` → `expired`
- `pending`: registered but NFC card not yet assigned
- RGPD consent timestamp is stored at registration
- Member records are soft-deleted (`deleted_at`) to preserve operational and audit history

### Subscription

A time-bound gym membership plan purchased by or for a Member.

- Belongs to one `Member` and one `Plan`
- Has `starts_at` and `ends_at` dates
- `ends_at` is always calculated from `starts_at + plan.duration_days` — never from today's date on renewal
- Statuses: `active` | `suspended` | `expired` | `transferred`
- When suspended: `suspended_at` and `days_remaining` are frozen — not recalculated
- When resumed: `ends_at` is recomputed from `resumed_at + days_remaining`
- A Member with no active Subscription cannot enter the gym

### Plan

A gym membership product with a fixed duration and price.

- Fields: `name`, `price`, `duration_days`, `included_services[]`
- `included_services` governs what a Member can book (e.g. courts, classes)
- Plans are not deleted — they are `archived` to preserve historical subscription records

### NfcCard

A physical Mifare NFC card linked to exactly one Member.

- Identified by `uid` (hardware UID read from card)
- Statuses: `active` | `suspended` | `lost`
- A `uid` must be globally unique — duplicates are rejected at assignment
- Only Admin and Manager can assign, suspend, or mark a card as lost
- Card assignment is logged with `assigned_by` (admin ID) and `assigned_at`

### HikvisionTerminal

A physical Hikvision DS-K1T805MX access control device installed at gym entry/exit points.

- Each terminal has a unique `api_token` generated at provisioning
- Communicates with the backend via ISAPI (Hikvision's proprietary HTTP API)
- Statuses: `online` | `offline`
- An unregistered terminal is rejected with HTTP 401
- Terminals are never deleted — they are `decommissioned`

### CheckInEvent

An immutable record of every access attempt at a terminal, regardless of outcome.

- Created on every NFC tap — authorized or denied
- Fields: `member_id`, `card_uid`, `terminal_id`, `checked_in_at`, `result`, `denial_reason`
- `result`: `authorized` | `denied`
- `denial_reason`: `expired_subscription` | `suspended_card` | `invalid_card` | `anti_passback`
- Records are **read-only after creation** — never updated or deleted
- Retained for minimum 12 months

### Course

A predefined type of group fitness class, acting as a template for `CourseSession`s.

- Fields: `name`, `instructor`, `description`, `color`
- Provides the baseline details when an admin schedules a class
- Can have multiple scheduled `CourseSession`s

### CourseSession

A scheduled instance of a `Course` running at a specific time.

- Fields: `course_id`, `starts_at`, `duration_minutes`, `capacity`, `day_of_week`
- Has many `Booking` records
- Auto-closes enrollment when `bookings.count >= capacity`
- Recurring weekly by default
- Cancellation triggers push notification to all enrolled Members

### CourtSlot

A bookable time slot for a tennis or squash court.

- Fields: `court_type` (`tennis` | `squash`), `starts_at`, `ends_at`, `member_id`
- Only bookable if Member's Plan includes court access

### Booking

A Member's reservation for a `CourseSession` or `CourtSlot`.

- Cancellable up to 2 hours before session start
- If capacity is full, Member is added to `waitlist`
- Waitlist position is integer-ordered; next in line is notified on cancellation

---

## Business Rules

### Access Control Rules

- A Member may enter ONLY if: NfcCard status = `active` AND Subscription status = `active` AND `ends_at` > now
- Door unlock command is sent via ISAPI only after backend validation succeeds
- Total flow (tap → validation → door open) must complete in < 2 seconds
- The backend `/api/checkin` endpoint must respond in < 500ms under normal load

### Anti-Passback Rule

- Every NFC tap is classified as `entry` or `exit` based on terminal location type
- A second `entry` event without a preceding `exit` is flagged as `suspicious`
- 3 suspicious events on the same card → card is auto-suspended, status = `suspended`, pending admin review
- Admin can Dismiss (clear flag) or Escalate (permanent suspension)

### Offline / Degraded Mode

- Each terminal maintains a local whitelist of authorized card UIDs
- Whitelist is synced from the backend every 15 minutes
- Whitelist is force-refreshed immediately on any subscription or card status change
- If network is lost, terminal uses local whitelist — access continues
- Offline events are buffered locally and synced within 5 minutes of reconnection

### RBAC Roles

| Role       | Description                                                              |
| ---------- | ------------------------------------------------------------------------ |
| `admin`    | Full access to all resources and actions                                 |
| `manager`  | Member management, card assignment, check-in view — no financial reports |
| `member`   | Own profile, subscription, bookings, check-in history only               |
| `terminal` | Single-purpose: POST to `/api/checkin` only — no other endpoints         |

### Payment Rules

- Accepted methods: `cash` | `konnect` | `paymee`
- All online payments go through the configured Tunisian gateway (Konnect or Paymee)
- Staging and production API keys are always separate
- A PDF receipt is always generated and emailed after successful payment

### Subscription Renewal Rule

- Renewal extends `ends_at` from the **current `ends_at`** date — not from today
- Prevents gaps for early renewals
- Permissions update within 1 minute of payment confirmation

---

## ISAPI Terms

| Term                  | Definition                                                                             |
| --------------------- | -------------------------------------------------------------------------------------- |
| ISAPI                 | Hikvision's proprietary HTTP-based API for communicating with access control terminals |
| DS-K1T805MX           | The specific Hikvision terminal model deployed at Bourgo Arena                         |
| Whitelist (ISAPI)     | A list of authorized card UIDs stored locally on the terminal for offline use          |
| Door Relay            | The hardware signal sent by the terminal to physically unlock the door                 |
| Anti-Passback (ISAPI) | Hardware-level or software-level detection of card reuse without exit                  |
| Webhook (check-in)    | POST request sent by the terminal to `/api/checkin` on every NFC tap                   |

---

## Notification Channels

| Channel         | Used For                                                              |
| --------------- | --------------------------------------------------------------------- |
| Push (FCM/APNs) | Subscription expiry reminders, booking confirmations, waitlist alerts |
| SMS             | Fallback for critical alerts when push is not received                |
| Email           | Welcome, receipts, password reset, backup status alerts               |

### Expiry Reminder Schedule

- J-7 (7 days before expiry)
- J-3 (3 days before expiry)
- J-1 (1 day before expiry)

---

## Tunisian Business Context

| Term       | Definition                                                                   |
| ---------- | ---------------------------------------------------------------------------- |
| Konnect    | Tunisian online payment gateway — primary option                             |
| Paymee     | Tunisian online payment gateway — secondary option                           |
| RGPD       | French acronym for GDPR — data protection regulation applied in this project |
| Espèces    | Cash payment — always a valid payment method alongside gateway               |
| Front Desk | Physical reception manager; use the admin web panel on desktop               |
