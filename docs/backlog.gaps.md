# Bourgo Arena — Gap Analysis (backlog.gaps.md)

## Overview
This document outlines the gaps between the current application implementation and the specified requirements in `backlog.final.md` (Version 2.0). While the foundational domain logic (Members, Subscriptions, Courses) and Livewire Admin structural components are in place, several advanced operational, real-time, and reporting features remain unimplemented or partially aligned.

---

### Core Architecture & Stack Alignment Gap
*   **Tech Stack Pivot:** The backlog specifies pure REST API consumption via a **Vue.js** frontend dashboard (EP-05). The current implementation is built using **Laravel Livewire and Flux UI** in a monolithic architecture. While this achieves the same end goal faster, the REST APIs defined in the specifications (`/api/admin/...`) do not exist. Any export and chart logic will need to be adapted for Livewire.

---

### US-031 — Terminal Provisioning Panel
**Implemented:**
*   Terminal records via `HikvisionTerminal` model and `Terminals` Livewire components.
*   Token generation via `TerminalProvisioningController`.

**Gaps:**
1.  **Heartbeat API:** Missing the `POST /api/terminals/{id}/heartbeat` endpoint for terminals to report their active status.
2.  **Status Monitoring:** Missing the scheduled background command to mark terminals as `offline` if no heartbeat is received within 60 seconds.
3.  **Real-Time Alerts:** Missing WebSocket broadcasts for terminal offline events.

---

### US-019 — Real-Time Check-In Monitoring
**Implemented:**
*   Check-in event model (`CheckInEvent`).
*   Basic endpoint configuration (`TerminalCheckInController`).

**Gaps:**
1.  **WebSocket Integration:** The real-time feed pushing `checkin.created` and `occupancy.updated` events over Reverb/Pusher is missing or not hooked into the frontend.
2.  **Occupancy Counter:** No real-time occupancy math (entries minus exits) is being calculated or broadcast.
3.  **Denial Alert Logic:** Missing the Redis-backed sliding window to detect >3 denied entries within 5 minutes on the same terminal.
4.  **Admin Alerts Table:** The `admin_alerts` table to persist these alerts is completely missing from the database schema.

---

### US-022 — Member Management Console
**Implemented:**
*   Comprehensive Livewire management via `MemberTable` and `MemberDetailPanel`.
*   Soft-deletes on the `Member` model.
*   NFC assignment and suspend/activate toggles.

**Gaps:**
1.  **CSV Export:** Missing the streaming CSV export functionality for the member list.
2.  **Password Reset OTP:** Missing the time-limited OTP/token generation flow for member password resets directly from the admin dashboard (currently relying on standard Fortify resets).
3.  **Strict RBAC Validation:** Needs auditing to ensure that Staff roles cannot trigger soft-deletes or exports.

---

### US-012 — Immutable Access Audit Log
**Implemented:**
*   Storage of check-ins via `CheckInEvent` (acting as the access log).

**Gaps:**
1.  **Immutability at DB Level:** The database table permissions are not restricted to `INSERT`/`SELECT` only for the Laravel user.
2.  **Data Retention Policy:** Missing the scheduled console command (CRON) to automatically hard-purge records older than 12 months.
3.  **Exports:** Missing CSV and queued asynchronous PDF export functionalities for access logs.

---

### US-013 — Anti-Passback Fraud Prevention
**Implemented:**
*   None of the specific passback logic is currently operational.

**Gaps:**
1.  **Card State Machine (Redis):** Missing the atomic sub-millisecond tracking of card directional state (IN/OUT).
2.  **Alert Dispatching:** Missing the synchronous detection pre-door command that fires `PassbackViolationDetected` events.
3.  **Auto-Suspension Job:** Missing the logic to count 3 passes and queue the suspension. (`NotifyMemberCardSuspended` exists, but the trigger aggregator `SuspendMemberJob` is missing).

---

### US-020 — Revenue & Subscription Analytics
**Implemented:**
*   Base tables for `Subscriptions` and standard Eloquent tracking.

**Gaps:**
1.  **Aggregation Infrastructure:** Missing the `revenue_snapshots` table and the nightly 03:00 aggregation console command.
2.  **Analytics UI:** Missing the Livewire views/components for Bar charts (monthly revenue), Donut charts (active vs expired), and churn rate KPIs.
3.  **Exports:** Missing the queued PDF generation and CSV exports for these reports.

---

### US-021 — Hourly Occupancy Heatmap
**Implemented:**
*   None.

**Gaps:**
1.  **Database:** Missing the `occupancy_hourly_aggregates` table.
2.  **Aggregation Job:** Missing the translation of `CheckInEvents` to hourly throughput averages.
3.  **UI visualization:** Missing the 7x17 heatmap grid view and its associated hover tooltips.
4.  **PDF Export:** Missing the headless rendering of the heatmap canvas for export.

---

### US-023 — Group Class Schedule Management
**Implemented:**
*   Courses, CourseSessions, and Bookings exist to model the schedule.
*   `SendCourseCancelledPush` job is available.

**Gaps:**
1.  **RRULE Recurrence:** The schedule appears to be manually expanded via `CourseSession` rather than using a recurring rule (RRULE) architecture that expands dynamically in the client/server memory.
2.  **Waiting List Auto-Promotion:** Missing the strict queue queueing and auto-promotion upon cancellation.
3.  **Weekly Calendar Grid:** Missing the visual calendar layout displaying the week's overlapping schedules with "nearly full" / "full" visual indicators.