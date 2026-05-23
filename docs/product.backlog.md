# Bourgo Arena — Admin Features Backlog

> **Stack:** Laravel · Livewire · Flux UI
> **Scope:** Admin-facing features only · Extracted from Product Backlog v2.0

---

## 1. Infrastructure & Security Foundation

### 1.1 Payment Gateway Integration

- **US-028** · Must Have · 8 pts · Sprint 0
- **Description:** Select and integrate Konnect as the platform payment gateway
- **Key Actions / Components:**
    - Livewire comparison form (gateway options, fees, sandbox status)
    - Config panel for API key management (staging vs. production)
    - Sandbox test runner with end-to-end payment result display
    - Gateway decision recorded and visible to PO

### 1.2 RBAC Permission Matrix

- **US-030** · Must Have · 5 pts · Sprint 0
- **Description:** Define and enforce role-based access control across all admin resources
- **Key Actions / Components:**
    - Flux UI matrix table: Roles (Admin, Manager, Member, Terminal) × Resources × Actions
    - Laravel middleware enforcement per role
    - Swagger/OpenAPI role annotations per endpoint
    - Integration test suite for each role boundary

### 1.3 Hikvision Terminal Provisioning

- **US-031** · Must Have · 5 pts · Sprint 0
- **Description:** Register and configure Hikvision DS-K1T805MX terminals in the admin panel
- **Key Actions / Components:**
    - Livewire form: terminal name, IP, serial number, location
    - Auto-generated unique API token per terminal on registration
    - Token revocation action in dashboard
    - Terminal list with online/offline connectivity indicator
    - HTTP 401 rejection for unregistered terminals

### 1.4 CI/CD Pipeline

- **US-032** · Must Have · 5 pts · Sprint 0
- **Description:** Automated CI/CD pipeline for staging and production deployments
- **Key Actions / Components:**
    - CI: lint + unit + integration tests on every PR
    - CD: auto-deploy to staging on `develop` merge
    - Manual production deploy with explicit approval gate
    - Email notifications on pipeline success/failure
    - Secrets managed outside source code; rollback procedure documented

---

## 2. Member Management

### 2.1 NFC Card Assignment

- **US-002** · Must Have · 5 pts · Sprint 1
- **Description:** Assign Mifare NFC card UIDs to member accounts
- **Key Actions / Components:**
    - Livewire searchable member picker (name, email, phone)
    - Manual UID entry or NFC scan input
    - Duplicate UID validation with inline error
    - Card status selector: Active / Suspended / Lost
    - Timestamped audit log entry (admin ID + action)
    - Push/email notification to member on activation
    - RBAC: Admin and Manager only

### 2.2 Member Management Console

- **US-022** · Must Have · 5 pts · Sprint 4
- **Description:** Full lifecycle member administration from a single dashboard view
- **Key Actions / Components:**
    - Filterable Flux UI data table: name, phone, status, plan
    - Pagination: 50 members per page
    - Member detail view: profile, subscription, payments, check-ins, NFC card
    - Inline actions: Suspend, Activate, Assign Card, Reset Password, Delete
    - CSV export of full member list
    - RBAC enforcement per action (US-030)

---

## 3. Subscription & Payment Management

### 3.1 Subscription Enrollment

- **US-005** · Must Have · 8 pts · Sprint 2
- **Description:** Enroll a member in a subscription plan and activate gym access
- **Key Actions / Components:**
    - Livewire plan selector with available gym services
    - Editable start date (defaults to today); auto-calculated end date
    - Payment recording: cash or gateway (US-028)
    - Real-time status update to `Active` with permission sync
    - PDF receipt generation and email dispatch

### 3.2 Subscription Suspension & Transfer

- **US-008** · Should Have · 5 pts · Sprint 3
- **Description:** Suspend or transfer a subscription to handle special member cases
- **Key Actions / Components:**
    - Suspension modal with mandatory reason selector: Medical / Travel / Other
    - Days-remaining freeze and exact restoration on resume
    - Transfer flow: admin approval + identity verification of new holder
    - Timestamped audit log per event (admin ID)
    - Push/email notification to member on each status change

### 3.3 Expiring Subscriptions Dashboard View

- **US-006** · Must Have · 5 pts · Sprint 2
- **Description:** Surface members whose subscriptions expire within 7 days
- **Key Actions / Components:**
    - Flux UI alert list: members expiring in ≤ 7 days
    - Direct link to member detail / renewal action

---

## 4. Access Control & Check-in

### 4.1 Real-Time Check-in Monitoring

- **US-019** · Must Have · 8 pts · Sprint 4
- **Description:** Live dashboard view of gym occupancy and check-in activity
- **Key Actions / Components:**
    - Live counter: members currently inside (latency < 3s)
    - Livewire WebSocket feed: last 20 check-ins (name, time, result)
    - Visual indicators: Authorized (green) / Denied (red)
    - Alert trigger: > 3 denials in 5 min on same terminal
    - Configurable capacity thresholds with occupancy indicator
    - Persistent WebSocket connection status badge

### 4.2 Immutable Access Audit Log

- **US-012** · Must Have · 5 pts · Sprint 4
- **Description:** Read-only tamper-proof log of all access events for incident investigation
- **Key Actions / Components:**
    - Log table: member name, card UID, timestamp, result, terminal ID
    - Filters: date range, member, result (Authorized / Denied)
    - Export: CSV and PDF
    - Minimum 12-month retention; entries are read-only
    - Accessible from individual member profile view

### 4.3 Anti-Passback (Card Sharing Prevention)

- **US-013** · Must Have · 5 pts · Sprint 4
- **Description:** Detect and block card sharing fraud automatically
- **Key Actions / Components:**
    - System tracks last check-in and check-out per card
    - Suspicious entry detection: entry without prior exit
    - Real-time alert in admin dashboard with event detail
    - Admin review actions: Dismiss / Escalate
    - Auto-suspension after 3 unresolved alerts (pending review status)

---

## 5. Analytics & Reporting

### 5.1 Revenue & Subscription Analytics

- **US-020** · Must Have · 8 pts · Sprint 5
- **Description:** Financial and subscription trend reporting for business decisions
- **Key Actions / Components:**
    - Bar chart: monthly revenue with month-over-month comparison
    - Donut chart: active vs. expired subscriptions
    - KPI: churn rate (calculated and displayed)
    - Top plans by subscriber count and revenue
    - Revenue breakdown by payment method
    - Date range filter; PDF and CSV export

### 5.2 Hourly Occupancy Heatmap

- **US-021** · Should Have · 5 pts · Sprint 5
- **Description:** Visual heatmap of gym occupancy by day and hour for manager planning
- **Key Actions / Components:**
    - 7-day × hourly (06:00–23:00) grid with intensity proportional to occupancy %
    - Flux UI tooltip on hover: headcount + percentage
    - Filters: day and time slot
    - Printable PDF export

---

## 6. Class & Facility Scheduling

### 6.1 Group Class Schedule Management

- **US-023** · Should Have · 5 pts · Sprint 5
- **Description:** Create and manage recurring class schedules with enrollment control
- **Key Actions / Components:**
    - Livewire class creation form: name, instructor, day/time, duration, capacity
    - Recurring weekly schedule builder
    - Enrolled member list with individual removal action
    - Auto-close enrollment at max capacity
    - Cancellation action → push notification to all enrolled members
    - Weekly calendar view (Flux UI)

---

## 7. Backend Infrastructure (Admin-Impacting)

### 7.1 API Authentication & RBAC (Sanctum)

- **US-024** · Must Have · 8 pts · Sprint 1
- **Description:** Secure all admin API endpoints with scoped tokens and role enforcement
- **Key Actions / Components:**
    - Sanctum tokens scoped per role: Admin, Manager, Member, Terminal
    - Long-lived terminal tokens tied to device, renewably managed
    - RBAC middleware on all endpoints (resource-level)
    - Token revocation endpoint
    - Full Swagger authentication schema

### 7.2 Automated Daily Backups

- **US-026** · Must Have · 5 pts · Sprint 2
- **Description:** Scheduled database backups with disaster recovery capability
- **Key Actions / Components:**
    - Nightly backup at 02:00 local time
    - Encrypted remote storage (S3 or equivalent)
    - Retention policy: 30 daily / 12 weekly / 3 monthly
    - Documented and quarterly-tested restore procedure
    - Email alert on backup success or failure
    - RTO target: < 4 hours

### 7.3 Async Queue Processing (Laravel Horizon)

- **US-027** · Should Have · 5 pts · Sprint 4
- **Description:** Offload heavy tasks to queues to keep check-in API under 500ms
- **Key Actions / Components:**
    - Notifications dispatched asynchronously
    - Check-in logs via dedicated queue
    - Reports dispatched via queue
    - Failed job retry: up to 3 attempts
    - Horizon dashboard accessible to Admin
    - `/api/checkin` ≤ 500ms verified under load

---

## Summary

| Epic                 | Admin Stories         | Total Points |
| -------------------- | --------------------- | ------------ |
| EP-00 Infrastructure | US-028, 030, 031, 032 | 23           |
| EP-01 Members        | US-002, 022           | 10           |
| EP-02 Subscriptions  | US-005, 006, 008      | 18           |
| EP-03 Access Control | US-012, 013, 019      | 18           |
| EP-05 Analytics      | US-020, 021, 023      | 18           |
| EP-06 Backend        | US-024, 026, 027      | 18           |
| **TOTAL**            | **16 User Stories**   | **105 pts**  |

- Check-in logs via dedicated queue
- Reports dispatched via queue
- Failed job retry: up to 3 attempts
- Horizon dashboard accessible to Admin
- `/api/checkin` ≤ 500ms verified under load

---

## Summary

| Epic                 | Admin Stories         | Total Points |
| -------------------- | --------------------- | ------------ |
| EP-00 Infrastructure | US-028, 030, 031, 032 | 23           |
| EP-01 Members        | US-002, 022           | 10           |
| EP-02 Subscriptions  | US-005, 006, 008      | 18           |
| EP-03 Access Control | US-012, 013, 019      | 18           |
| EP-05 Analytics      | US-020, 021, 023      | 18           |
| EP-06 Backend        | US-024, 026, 027      | 18           |
| **TOTAL**            | **16 User Stories**   | **105 pts**  |
