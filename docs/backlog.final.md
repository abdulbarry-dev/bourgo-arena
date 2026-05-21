# Bourgo Arena — Admin Dashboard Panel
## User Stories: Detailed Specification

> **Project:** Bourgo Arena — IoT Gym Management Platform
> **Version:** 2.0 — March 2026
> **Scope:** Admin Dashboard Panel only (8 User Stories)
> **Stack:** Vue.js (frontend) · Laravel (backend) · Hikvision ISAPI · WebSocket

---

## Table of Contents

1. [US-031 — Terminal Provisioning Panel](#us-031--terminal-provisioning-panel)
2. [US-019 — Real-Time Check-In Monitoring](#us-019--real-time-check-in-monitoring)
3. [US-022 — Member Management Console](#us-022--member-management-console)
4. [US-012 — Immutable Access Audit Log](#us-012--immutable-access-audit-log)
5. [US-013 — Anti-Passback Fraud Prevention](#us-013--anti-passback-fraud-prevention)
6. [US-020 — Revenue & Subscription Analytics](#us-020--revenue--subscription-analytics)
7. [US-021 — Hourly Occupancy Heatmap](#us-021--hourly-occupancy-heatmap)
8. [US-023 — Group Class Schedule Management](#us-023--group-class-schedule-management)

---

## Story Index

| ID | Title | Epic | Priority | Points | Sprint | Status |
|----|-------|------|----------|--------|--------|--------|
| US-031 | Terminal Provisioning Panel | EP-00 | Must Have | 5 | S0 | To Do |
| US-019 | Real-Time Check-In Monitoring | EP-05 | Must Have | 8 | S4 | To Do |
| US-022 | Member Management Console | EP-05 | Must Have | 5 | S4 | To Do |
| US-012 | Immutable Access Audit Log | EP-03 | Must Have | 5 | S4 | To Do |
| US-013 | Anti-Passback Fraud Prevention | EP-03 | Must Have | 5 | S4 | To Do |
| US-020 | Revenue & Subscription Analytics | EP-05 | Must Have | 8 | S5 | To Do |
| US-021 | Hourly Occupancy Heatmap | EP-05 | Should Have | 5 | S5 | To Do |
| US-023 | Group Class Schedule Management | EP-05 | Should Have | 5 | S5 | To Do |

**Total: 8 stories — 46 story points**

---

## US-031 — Terminal Provisioning Panel

| Field | Value |
|-------|-------|
| **Epic** | EP-00 — Infrastructure, CI/CD & Security Foundation |
| **Priority** | Must Have |
| **Estimate** | 5 story points |
| **Sprint** | S0 — Cadrage & Fondations |
| **Assignee** | Etudiant B (mobile/IoT) |
| **Status** | To Do |
| **Dependency** | US-030 (RBAC Matrix) |

### User Story

> *As an IT administrator, I want to register and configure each Hikvision terminal so that I can control which devices are authorized to communicate with the platform API.*

### Acceptance Criteria

1. Admin interface provides fields for: terminal name, IP address, serial number, and physical location.
2. A unique API token is automatically generated per terminal upon registration.
3. The token is revocable from the dashboard at any time with immediate effect.
4. Any unregistered terminal attempting to connect is rejected with `HTTP 401 Unauthorized`.
5. The terminal list displays a real-time connectivity indicator (online / offline) for each device.
6. The full provisioning procedure is documented and accessible to the IT team.

### Implementation Logic

**Token lifecycle:**
- Each terminal receives a device-bound, long-lived API token scoped to the `Terminal` role defined in the RBAC matrix (US-030).
- Tokens are stored hashed in the database; plaintext is only shown once at generation time.
- On revocation, the token is immediately invalidated in the auth middleware — all subsequent ISAPI calls from that terminal return `401`.

**Connectivity monitoring:**
- Each terminal sends a heartbeat ping every 30 seconds to `/api/terminals/{id}/heartbeat`.
- If no heartbeat is received within 60 seconds, the terminal status switches to `offline`.
- An offline event triggers a real-time alert in the admin dashboard.

**Security:**
- Tokens are never stored in client-side code or logs.
- All terminal-to-server communication is over HTTPS/TLS 1.2+ (enforced in US-025).

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/terminals` | List all terminals with status |
| `POST` | `/api/admin/terminals` | Register a new terminal |
| `PUT` | `/api/admin/terminals/{id}` | Update terminal details |
| `DELETE` | `/api/admin/terminals/{id}/token` | Revoke terminal token |
| `POST` | `/api/terminals/{id}/heartbeat` | Terminal heartbeat (terminal auth) |

---

## US-019 — Real-Time Check-In Monitoring

| Field | Value |
|-------|-------|
| **Epic** | EP-05 — Admin Web Dashboard (Vue.js) |
| **Priority** | Must Have |
| **Estimate** | 8 story points |
| **Sprint** | S4 — Mobile Avancé & Admin Core |
| **Assignee** | Etudiant A (frontend) |
| **Status** | To Do |
| **Dependency** | US-009 (NFC tap-to-enter flow), US-031 (Terminal provisioning) |

### User Story

> *As an administrator, I want to monitor check-ins in real time so that I can track gym occupancy and immediately react to access incidents.*

### Acceptance Criteria

1. A live counter displays the current number of active members inside the gym with a latency of less than 3 seconds.
2. A live feed shows the last 20 check-in events with: member name, timestamp, and access result (authorized / denied).
3. Each entry displays a clear visual indicator — green for authorized, red for denied.
4. An alert is triggered automatically if more than 3 denials occur within 5 minutes on the same terminal.
5. A global capacity indicator shows gym occupancy percentage with configurable thresholds (e.g., warning at 80%, critical at 100%).
6. A permanent WebSocket connection status indicator is visible at all times in the dashboard header.

### Implementation Logic

**Real-time feed:**
- A WebSocket channel (Laravel Broadcasting / Pusher) pushes each check-in event to the dashboard within 1 second of the tap.
- The frontend maintains a sliding window of the last 20 events in Vue reactive state.
- Events are color-coded: `AUTHORIZED` → success badge, `DENIED` → danger badge with denial reason.

**Occupancy counter:**
- Occupancy = cumulative `ENTRY` events − cumulative `EXIT` events for the current day.
- Computed from the audit log in real time; updated on every WebSocket event.
- Capacity thresholds are stored in an admin-configurable settings table.

**Denial alert logic:**
- A per-terminal counter resets every 5 minutes using a sliding window (Redis).
- When the counter exceeds 3, a toast notification is shown in the dashboard and a persisted alert is created in the `admin_alerts` table.
- Alerts remain visible until manually dismissed by an admin.

**WebSocket indicator:**
- Displays `CONNECTED` (green dot) or `DISCONNECTED` (red dot) with last-event timestamp.
- On disconnect, the dashboard attempts auto-reconnect every 5 seconds.

### API & Events

| Type | Name | Payload |
|------|------|---------|
| WebSocket Event | `checkin.created` | `{ member_id, name, timestamp, result, terminal_id }` |
| WebSocket Event | `occupancy.updated` | `{ count, capacity, percentage }` |
| WebSocket Event | `terminal.alert` | `{ terminal_id, alert_type, count }` |
| `GET` | `/api/admin/checkins/live` | Last 20 check-in events (REST fallback) |

---

## US-022 — Member Management Console

| Field | Value |
|-------|-------|
| **Epic** | EP-05 — Admin Web Dashboard (Vue.js) |
| **Priority** | Must Have |
| **Estimate** | 5 story points |
| **Sprint** | S4 — Mobile Avancé & Admin Core |
| **Assignee** | Etudiant A (frontend) |
| **Status** | To Do |
| **Dependency** | US-030 (RBAC), US-001 (Member registration), US-002 (NFC assignment) |

### User Story

> *As an administrator, I want to manage all member accounts from the dashboard so that I can administer the full member lifecycle without requiring direct database access or technical tools.*

### Acceptance Criteria

1. The member list is filterable by: name, phone number, account status, and subscription plan.
2. The list is paginated at 50 members per page with standard pagination controls.
3. Each member's detail view displays: profile info, subscription details, payment history, check-in history, and NFC card status.
4. Available actions per member: suspend, activate, assign/replace NFC card, reset password, and delete account.
5. A CSV export of the full member list is available from the list view.
6. All actions are gated by the RBAC matrix (US-030) — Staff cannot delete or export; only Admin can.

### Implementation Logic

**Destructive action protection:**
- All irreversible actions (delete, suspend) require a confirmation modal with a typed confirmation string.
- Accounts are soft-deleted only — records and associated audit logs are preserved for the mandatory 12-month retention period (US-012).

**NFC card assignment:**
- Triggers an ISAPI call to push the updated whitelist to all terminals (US-011).
- The previous card UID is immediately invalidated on assignment of a new card.

**Password reset:**
- Generates a time-limited OTP (valid for 15 minutes) sent to the member's verified email or phone.
- Does not expose the new password to the admin performing the reset.

**CSV export:**
- Exported fields: ID, name, email, phone, plan, status, subscription expiry, NFC UID, created date.
- Export is streamed server-side for large datasets to avoid memory issues.

**RBAC enforcement:**

| Action | Admin | Staff |
|--------|-------|-------|
| View list & profile | ✅ | ✅ |
| Suspend / Activate | ✅ | ✅ |
| Assign NFC card | ✅ | ✅ |
| Reset password | ✅ | ✅ |
| Delete account | ✅ | ❌ |
| Export CSV | ✅ | ❌ |

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/members` | Paginated & filtered member list |
| `GET` | `/api/admin/members/{id}` | Member detail view |
| `PUT` | `/api/admin/members/{id}/status` | Suspend or activate |
| `POST` | `/api/admin/members/{id}/nfc` | Assign NFC card |
| `POST` | `/api/admin/members/{id}/reset-password` | Trigger password reset |
| `DELETE` | `/api/admin/members/{id}` | Soft delete (Admin only) |
| `GET` | `/api/admin/members/export` | CSV export (Admin only) |

---

## US-012 — Immutable Access Audit Log

| Field | Value |
|-------|-------|
| **Epic** | EP-03 — Access Control & Check-in (Hikvision ISAPI) |
| **Priority** | Must Have |
| **Estimate** | 5 story points |
| **Sprint** | S4 — Mobile Avancé & Admin Core |
| **Assignee** | Etudiant A (backend/web) |
| **Status** | To Do |
| **Dependency** | US-009 (NFC tap-to-enter) |

### User Story

> *As an administrator, I want to consult an immutable audit log of all access events so that I can investigate incidents, resolve disputes, and guarantee full traceability.*

### Acceptance Criteria

1. Each log entry contains: member name, NFC card UID, event timestamp, access result (authorized / denied), and terminal ID.
2. The log is filterable by: date range, member name or ID, and result type.
3. Entries can be exported in both CSV and PDF formats.
4. All entries are retained for a minimum of 12 months.
5. Entries are strictly read-only — no edit, update, or delete is possible from any interface or role.
6. The access history for a specific member is accessible directly from their profile page in the member console (US-022).

### Implementation Logic

**Immutability enforcement:**
- Audit entries are written to an append-only database table (`access_logs`).
- The Laravel application database user has `INSERT` and `SELECT` privileges only on this table — `UPDATE` and `DELETE` are revoked at the database level.
- No soft-delete flag exists on this table.

**Retention policy:**
- A scheduled Laravel command runs nightly to check entry age.
- Entries older than 12 months + 1 day are permanently purged (the only allowed deletion, executed by a privileged DB job, not the application user).

**Export generation:**
- CSV exports are streamed directly for speed.
- PDF exports are dispatched as async queue jobs (US-027) to prevent API timeout on large datasets.
- The PDF is generated server-side and a download link is emailed to the requesting admin.

**Data model:**

```
access_logs
├── id             (BIGINT, auto-increment)
├── member_id      (FK → members, nullable for unknown cards)
├── card_uid       (VARCHAR — stored even if card is later deleted)
├── terminal_id    (FK → terminals)
├── result         (ENUM: AUTHORIZED, DENIED)
├── denial_reason  (VARCHAR, nullable)
├── created_at     (TIMESTAMP — set by DB trigger, not application)
└── [NO updated_at, NO deleted_at]
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/access-logs` | Paginated & filtered audit log |
| `GET` | `/api/admin/members/{id}/access-logs` | Member-specific log |
| `GET` | `/api/admin/access-logs/export/csv` | CSV export |
| `POST` | `/api/admin/access-logs/export/pdf` | Async PDF export (queued) |

---

## US-013 — Anti-Passback Fraud Prevention

| Field | Value |
|-------|-------|
| **Epic** | EP-03 — Access Control & Check-in (Hikvision ISAPI) |
| **Priority** | Must Have |
| **Estimate** | 5 story points |
| **Sprint** | S4 — Mobile Avancé & Admin Core |
| **Assignee** | Etudiant A (backend/web) |
| **Status** | To Do |
| **Dependency** | US-009 (NFC tap-to-enter), US-012 (Audit log) |

### User Story

> *As the system, I want to automatically detect and block card sharing between members so that I can prevent subscription fraud and enforce access integrity.*

### Acceptance Criteria

1. The system tracks the last check-in direction (entry / exit) for each NFC card.
2. A new entry event on a card that has not yet registered an exit triggers a suspicious event (passback violation).
3. A real-time alert is pushed to the admin dashboard for each passback violation.
4. The admin can review, dismiss, or escalate each individual alert from the dashboard.
5. If the same card accumulates 3 or more alerts, the member's account is automatically suspended pending admin review.

### Implementation Logic

**Card state machine:**
Each NFC card operates on a directional state machine:

```
OUTSIDE → [ENTRY tap] → INSIDE → [EXIT tap] → OUTSIDE
                 ↑                       |
                 └── passback violation ─┘
           (ENTRY when already INSIDE)
```

- Card state is stored in Redis for sub-millisecond reads on every tap.
- State keys: `card_state:{card_uid}` → `{ direction: "IN"|"OUT", last_event_at: timestamp }`.
- The state is updated atomically after each authorized entry or exit.

**Alert flow:**
1. Passback detection fires synchronously during the `/api/checkin` webhook processing (before door command).
2. A `PassbackViolationDetected` event is dispatched.
3. A listener increments the violation counter for the card in Redis and creates an `admin_alerts` record.
4. A WebSocket event pushes the alert to all connected admin dashboard sessions in real time.

**Auto-suspension logic:**
- When the violation counter for a card reaches 3, a queued job (`SuspendMemberJob`) is dispatched.
- The job sets `member.status = SUSPENDED_FRAUD` and prevents door access.
- A push notification is sent to the member explaining the suspension.
- The admin receives a separate high-priority alert and must explicitly review before lifting the suspension.

**Admin actions per alert:**

| Action | Effect |
|--------|--------|
| Dismiss | Clears the alert; does NOT decrement violation counter |
| Escalate | Creates a support ticket and notifies the gym manager |
| Lift suspension | Resets violation counter; sets member status back to `ACTIVE` |

---

## US-020 — Revenue & Subscription Analytics

| Field | Value |
|-------|-------|
| **Epic** | EP-05 — Admin Web Dashboard (Vue.js) |
| **Priority** | Must Have |
| **Estimate** | 8 story points |
| **Sprint** | S5 — Analytics, Security & Go-Live |
| **Assignee** | Etudiant A (frontend) |
| **Status** | To Do |
| **Dependency** | US-005 (Subscription management), US-007 (Renewal), US-026 (Automated backups) |

### User Story

> *As an administrator, I want to consult revenue reports and subscription trends so that I can make informed decisions about pricing, retention strategy, and business growth.*

### Acceptance Criteria

1. A bar chart displays monthly revenue with a month-over-month comparison badge (±%).
2. A donut chart shows the ratio of active vs expired subscriptions at the selected date range.
3. The churn rate KPI is calculated and prominently displayed.
4. A ranking of subscription plans is shown by both subscriber count and total revenue generated.
5. Revenue is broken down by payment method (cash, Konnect, Paymee).
6. All charts are filterable by custom date range; the full report is exportable to PDF and CSV.

### Implementation Logic

**Data aggregation strategy:**
- Raw payment and subscription data is aggregated daily by a scheduled Laravel command at 03:00 local time into a `revenue_snapshots` table.
- Dashboard queries hit the snapshot table — never the raw transactions table — to avoid full-table scans on user request.

**Churn rate formula:**

```
Churn Rate (%) = (Members who did not renew in period / Members at start of period) × 100
```

- "Did not renew" = subscription expired and no renewal payment recorded within 7 days after expiry.
- Displayed as a percentage with a color indicator: green (< 5%), amber (5–15%), red (> 15%).

**Month-over-month delta:**
- Calculated as `(current_month_revenue − previous_month_revenue) / previous_month_revenue × 100`.
- Displayed as a ±% badge alongside the current month's total on the bar chart.

**Export logic:**
- CSV export: streams raw aggregated data rows directly.
- PDF export: dispatched as an async queue job (US-027); server-side render of charts as images + tabular data; download link delivered by email to the requesting admin.

**Chart types summary:**

| Chart | Type | Data Source |
|-------|------|-------------|
| Monthly revenue | Bar (grouped) | `revenue_snapshots` |
| Active vs expired | Donut | `subscriptions` (live count) |
| Plan ranking | Horizontal bar | `subscriptions` grouped by plan |
| Payment breakdown | Pie | `payments` grouped by method |

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/analytics/revenue` | Revenue data for date range |
| `GET` | `/api/admin/analytics/subscriptions` | Subscription status breakdown |
| `GET` | `/api/admin/analytics/churn` | Churn rate for date range |
| `POST` | `/api/admin/analytics/export/pdf` | Async PDF export (queued) |
| `GET` | `/api/admin/analytics/export/csv` | CSV export |

---

## US-021 — Hourly Occupancy Heatmap

| Field | Value |
|-------|-------|
| **Epic** | EP-05 — Admin Web Dashboard (Vue.js) |
| **Priority** | Should Have |
| **Estimate** | 5 story points |
| **Sprint** | S5 — Analytics, Security & Go-Live |
| **Assignee** | Etudiant A (frontend) |
| **Status** | To Do |
| **Dependency** | US-012 (Audit log), US-020 (Analytics infrastructure) |

### User Story

> *As an administrator, I want to visualize a heatmap of hourly gym occupancy so that I can identify peak hours and optimize staff scheduling.*

### Acceptance Criteria

1. The heatmap is a 7-column × 17-row grid representing days of the week (Mon–Sun) and hourly time slots (06:00–23:00).
2. Cell color intensity is proportional to the average occupancy percentage for that day/hour combination.
3. The view is filterable by day of week and by specific time range.
4. Hovering over a cell displays a tooltip showing: average number of people and percentage of total capacity.
5. The heatmap is exportable as a printable PDF report.

### Implementation Logic

**Data source:**
- Built from the `access_logs` table (US-012) via pre-aggregated daily snapshots.
- Each cell value = average occupancy for `(day_of_week, hour)` over the selected date range.
- Formula: `avg_people = sum(entry_count - exit_count at that hour, across all days in range) / number_of_days_in_range`.

**Color scale:**
- 0% occupancy → lightest ramp stop (e.g., `#E1F5EE`).
- 100% occupancy → darkest ramp stop (e.g., `#04342C`).
- Linear interpolation between stops; values capped at 100%.

**Tooltip:**
- Computed from pre-aggregated data only — no live database query on hover.
- Displays: `"avg. X people — Y% of capacity"`.

**PDF export:**
- Headless render of the heatmap canvas element to a PNG, embedded in a server-generated PDF with metadata (date range, gym name, generated timestamp).

**Data model:**

```
occupancy_hourly_aggregates
├── id
├── day_of_week   (0=Mon … 6=Sun)
├── hour          (6–23)
├── date_range    (FK or date fields for period)
├── avg_count     (FLOAT)
├── avg_percentage (FLOAT)
└── last_computed_at
```

---

## US-023 — Group Class Schedule Management

| Field | Value |
|-------|-------|
| **Epic** | EP-05 — Admin Web Dashboard (Vue.js) |
| **Priority** | Should Have |
| **Estimate** | 5 story points |
| **Sprint** | S5 — Analytics, Security & Go-Live |
| **Assignee** | Etudiant A (frontend) |
| **Status** | To Do |
| **Dependency** | US-016 (Mobile class booking), US-018 (Push notifications) |

### User Story

> *As an administrator, I want to create and manage the weekly class schedule so that I can control registrations, prevent overbooking, and efficiently manage instructors and facilities.*

### Acceptance Criteria

1. An admin can create a class with: name, instructor, day of week, start time, duration, and maximum capacity.
2. Classes can be set as recurring weekly — a single creation generates all future occurrences.
3. A list of registered members is shown per class session, with the option to manually remove a member.
4. Registrations are automatically closed when the maximum capacity is reached.
5. When a class is cancelled, all registered members receive a push notification automatically.
6. A weekly calendar view displays all scheduled classes for the current week.

### Implementation Logic

**Recurring schedule storage:**
- Classes are stored using an RRULE-based approach — one row per class definition with a recurrence rule.
- Individual occurrences are not stored unless modified (exception-based expansion).
- The calendar view expands the RRULE client-side for the current week's display.

**Capacity enforcement:**
- Registration count is tracked in the `class_registrations` table.
- A database-level check constraint prevents `INSERT` when `count ≥ max_capacity`.
- When capacity is reached, the registration endpoint returns `HTTP 409 Conflict` with a clear message.
- The waiting list (from US-016) is managed as a separate ordered queue; a slot opening triggers automatic promotion of the first waitlisted member.

**Class cancellation flow:**
1. Admin marks a session as cancelled (affects one occurrence or all future occurrences).
2. A `ClassCancelledEvent` is fired.
3. A queue job (`NotifyClassCancellationJob`) dispatches bulk FCM/APNs push notifications to all enrolled member device tokens.
4. Members on the waitlist are also notified that the class is cancelled.
5. The session is visually marked as cancelled in the calendar but remains in the database for historical reference.

**Weekly calendar view:**
- Displays a Mon–Sun grid with class cards showing: name, instructor, time, and `enrolled/capacity` count.
- Color coding: available (green), nearly full (amber, > 80% capacity), full (red), cancelled (gray strikethrough).

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/classes` | List all class definitions |
| `POST` | `/api/admin/classes` | Create new class (with recurrence rule) |
| `PUT` | `/api/admin/classes/{id}` | Update class definition |
| `GET` | `/api/admin/classes/{id}/sessions` | Expanded occurrences for date range |
| `GET` | `/api/admin/classes/sessions/{sessionId}/registrations` | Enrolled members list |
| `DELETE` | `/api/admin/classes/sessions/{sessionId}/registrations/{memberId}` | Remove member |
| `POST` | `/api/admin/classes/sessions/{sessionId}/cancel` | Cancel a session (triggers notifications) |

---

## Non-Functional Requirements (Admin Dashboard)

| Category | Requirement | Target |
|----------|-------------|--------|
| Performance | Live check-in feed latency | < 3 seconds |
| Performance | WebSocket event to dashboard update | < 1 second |
| Performance | Analytics page load | < 2 seconds (from snapshot cache) |
| Performance | PDF export generation | < 30 seconds (async queue) |
| Security | All endpoints | Authenticated + RBAC enforced |
| Security | Audit log | Read-only at DB level |
| Security | Admin sessions | JWT with 7-day expiry + RBAC middleware |
| Availability | WebSocket auto-reconnect | Every 5 seconds on disconnect |
| Scalability | Member list | Supports 10,000+ members with pagination |
| Maintainability | API documentation | All endpoints documented in Swagger/OpenAPI |

---

*Bourgo Arena — Confidentiel © 2026 | Product Backlog v2.0*
*Plateforme IoT Hikvision ISAPI · Flutter · Vue.js · Laravel*