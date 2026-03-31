# Bourgo Arena — Admin Dashboard Implementation Plan

> **Version:** 2.0 · **Stack:** Laravel 11 · Livewire 3 · Flux UI · MySQL 8+
> **Objective:** Deliver admin web dashboard in 6 logically independent phases.
> **Total Scope:** 16 User Stories · 105 Story Points · ~6–8 weeks (2-week sprints)

---

## Overview: Phased Architecture

This plan divides implementation into **6 sequential phases**, each representing a logically independent unit of development that can be **tested separately** before moving forward. Phases respect technical dependencies while maximizing parallelizability within a phase.

```
┌─────────────────────────────────────────────────────────────┐
│ Phase 1: Infrastructure & Security Foundation               │
│ RBAC · Payment Gateways · Hikvision Terminal Provisioning  │
│ (foundations for all downstream features)                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 2: Member Management Core                             │
│ MemberTable · MemberDetailPanel · NfcCardAssignment          │
│ (independent admin workflows; can build in parallel)         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 3: Subscription Management                            │
│ SubscriptionEnrollment · SubscriptionSuspension ·           │
│ ExpiringSubscriptionsView                                   │
│ (transaction workflows; depends on Phase 2)                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 4: Access Control & Real-Time Monitoring             │
│ CheckInMonitor · AuditLog · AntiPassbackAlerts             │
│ (real-time infrastructure; depends on Phase 1 + Phase 2)   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 5: Analytics & Reporting                              │
│ RevenueAnalytics · OccupancyHeatmap · CourseSchedule        │
│ (data aggregation; depends on Phase 2–4)                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Phase 6: Advanced Infrastructure & Optimization            │
│ Queue Processing · Backups · CI/CD · Performance            │
│ (non-blocking; can be parallelized with Phase 5)           │
└─────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Infrastructure & Security Foundation

### Phase Name & Objective

**Establish role-based access control, integrate payment gateways, and provision Hikvision terminals.**

This phase lays the security and infrastructure on which all downstream admin features depend. Must be completed before any feature-level development.

### Scope

- **User Stories:** US-028 (Payment Gateway), US-030 (RBAC), US-031 (Hikvision Provisioning), US-032 (CI/CD)
- **Story Points:** 23 pts
- **Components:** None new Livewire components (backend middleware + config panels only)
- **Key Deliverables:**
  - RBAC middleware + Laravel policies
  - Payment gateway decision + Sanctum token scopes
  - Terminal provisioning backend + API token generation
  - CI/CD pipeline configuration (GitHub Actions / equivalent)

### Key Tasks

#### 1.1 Implement RBAC Middleware & Policies

- [x] Create `EnsureUserHasRole` middleware (already exists — verify coverage of admin + manager)
- [x] Create `TerminalPolicy`, `SubscriptionPolicy`, `MemberPolicy` to gate resource actions per role
- [x] Add `role()` macro to Route facade for cleaner route registration: `Route::post('/...')->role('admin', 'manager')`
- [x] Populate `config/authorization.php` with explicit permission matrix (Resource × Role × Action)
- [x] Write integration tests: each role attempts forbidden actions → HTTP 403
- [x] Document matrix in UI (for future admin dashboards): admin_resources × roles table

**Deliverables:**
- `/app/Http/Middleware/EnsureUserHasRole.php` (updated)
- `/app/Policies/*.php` (TerminalPolicy, SubscriptionPolicy, MemberPolicy, etc.)
- Test suite: `tests/Feature/Auth/RbacPolicyTest.php` (15–20 assertions on policy boundaries)

#### 1.2 Integrate Tunisian Payment Gateway (Konnect or Paymee)

- [x] Research Konnect + Paymee API docs; validate sandbox availability
- [x] Select primary gateway based on fees, settlement terms, sandbox stability
- [x] Create `PaymentGatewayDriver` interface + concrete implementations (`KonnectDriver`, `PaymeeDriver`)
- [x] Add `config/payment.php` with multi-driver support (staging/production separation)
- [x] Implement `PaymentGateway` service facade (Laravel service container registration)
- [x] Build sandbox test harness: initiate test payment → receive webhook → verify result
- [x] Store decision artifact: `PAYMENT_GATEWAY_SELECTED.md` with reasoning, fees, rate limits documented
- [x] Implement payment method validation (cash + gateway methods always accepted)
- [x] Create `ReceiptGenerator` class: convert payment records → branded PDF via TCPDF or Dompdf

**Deliverables:**
- `/app/Services/PaymentGateway/PaymentGatewayDriver.php` (interface)
- `/app/Services/PaymentGateway/{KonnectDriver, PaymeeDriver}.php`
- `/config/payment.php` (gateway selection + API keys)
- `/app/Services/ReceiptGenerator.php`
- `/tests/Feature/PaymentGatewayTest.php` (sandbox flow, webhook reception, receipt generation)
- Decision doc: `/docs/PAYMENT_GATEWAY_SELECTION.md`

#### 1.3 Provision Hikvision DS-K1T805MX Terminals

- [x] Create `HikvisionTerminal` model + migration (tables: `hikvision_terminals`)
- [x] Generate unique `api_token` on terminal creation (255-char random string, stored hashed in DB)
- [x] Implement terminal registration endpoint: POST `/api/terminal-provisioning` (accepts IP, serial, location)
- [x] Build terminal webhook receiver: POST `/api/checkin` (ISAPI payload parsing)
- [x] Create `TerminalAuthMiddleware`: API tokens validated against `hikvision_terminals.api_token`
- [x] Implement online/offline status tracking: `last_seen_at` updated on each webhook, status computed if > 5 min stale
- [x] Create terminal revocation action: regenerate `api_token`, invalidate old token immediately
- [x] Build decommissioning logic: mark terminal `status = decommissioned`, reject future attempts to register same serial

**Deliverables:**
- `/database/migrations/*_create_hikvision_terminals_table.php`
- `/app/Models/HikvisionTerminal.php`
- `/app/Http/Controllers/TerminalProvisioningController.php`
- `/app/Http/Middleware/TerminalAuthMiddleware.php`
- `/app/Jobs/UpdateTerminalStatus.php` (async last_seen_at update)
- `/tests/Feature/TerminalProvisioningTest.php` (token generation, revocation, decommissioning)

#### 1.4 Set Up CI/CD Pipeline

- [x] Create `.github/workflows/test.yml`: lint (Pint) + unit tests + integration tests on every PR
- [x] Create `.github/workflows/deploy-staging.yml`: auto-deploy to staging on `develop` merge
- [x] Create `.github/workflows/deploy-production.yml`: manual approval gate, deploy to production
- [x] Configure GitHub Actions secrets: PAYMENT_API_KEY, DATABASE_URL, AWS_S3_CREDENTIALS, etc.
- [x] Implement rollback procedure: documented steps + Git tag strategy per deployment
- [x] Add email notifications on pipeline success/failure (via GitHub Actions or equivalent)
- [x] Store CI/CD reference docs: `/docs/CI_CD_PROCEDURES.md` (deployment checklist, secrets rotation, rollback)

**Deliverables:**
- `/.github/workflows/{test, deploy-staging, deploy-production}.yml`
- `/docs/CI_CD_PROCEDURES.md` (deployment guide, secrets management, rollback)
- GitHub Actions secrets configured in Settings

### Dependencies

- **Technical:** None — this phase is foundational
- **Functional:** All downstream features require RBAC middleware + payment gateway selection

### Deliverables

- [x] RBAC policies applied to all routes (tests passing)
- [x] Payment gateway selected, sandbox tested, receipts generated
- [x] Hikvision terminal registration + webhook receiver working under load
- [x] CI/CD pipelines deployed and passing on trial commit

### Testing Strategy

**Unit Tests:**
- RBAC policy assertions: each role + resource combination
- Payment gateway driver mock tests (success/failure scenarios)
- Terminal token generation uniqueness

**Integration Tests:**
- RBAC: role-based route access, policy denials
- Payment: sandbox flow end-to-end (initiate → webhook → verify)
- Terminal: provision → token generation → webhook reception → status tracking

**Manual Load Tests:**
- Terminal provisioning under 50 concurrent registrations
- Payment gateway sandbox under 100 concurrent transactions
- CI/CD: trial deployment to staging environment

**Success Criteria:**
- All RBAC policies tested; 0 unauthorized access
- Payment sandbox transactions succeed consistently; receipts generated correctly
- Terminals register, tokens revoke, status reflects online/offline state
- CI/CD pipelines run on every PR and deploy without human intervention

### Risks & Considerations

| Risk                                  | Severity | Mitigation                                                                              |
| ------------------------------------- | -------- | --------------------------------------------------------------------------------------- |
| Payment gateway API contract changes | Medium   | Abstract behind driver interface; monitor Tunisian provider release notes monthly        |
| Terminal network latency / drops      | Medium   | Implement exponential backoff on webhook retries; buffer events locally for 5 min       |
| RBAC over-authorization              | High     | Generate policy audit report monthly; verify denied access logs < 1% of requests        |
| Secrets exposure in CI/CD logs        | High     | Use GitHub Actions secrets exclusively; never log API keys; audit logs monthly          |
| Terminal API token collision          | Low      | Use Laravel's `Str::random(255)` + database unique constraint; verify uniqueness in test |

---

## Phase 2: Member Management Core

### Phase Name & Objective

**Build the essential member lifecycle admin interface: discovery, detail view, and NFC card assignment.**

This phase delivers the primary member admin workflow, enabling managers to search members, view full profiles, and assign NFC cards for access.

### Scope

- **User Stories:** US-002 (NFC Card Assignment), US-022 (Member Management Console)
- **Story Points:** 10 pts
- **Components:**
  - `Admin\Members\MemberTable` (search, filter, pagination)
  - `Admin\Members\MemberDetailPanel` (member profile, inline actions)
  - `Admin\Members\NfcCardAssignment` (card UID entry/scan, validation)
- **Key Deliverables:**
  - Member model scopes (searchable, filterable)
  - Three Livewire components fully tested
  - CSV export functionality

### Key Tasks

#### 2.1 Create Member Model Scopes & Queries

- [x] Add `searchable()` scope: search by name (full-text), email, phone number
- [x] Add `byStatus()` scope: filter by member status (pending, active, suspended, expired)
- [x] Add `byPlan()` scope: filter by subscription plan ID
- [x] Add eager-loading scope: `with('activeSubscription', 'nfcCard')` to prevent N+1
- [x] Create member resource class: `MemberDetailResource` (profile + subscription + recent check-ins)
- [x] Write scope tests: verify each scope produces expected SQL, returns correct records

**Deliverables:**
- `/app/Models/Member.php` (scopes + relationships updated)
- `/app/Http/Resources/MemberDetailResource.php`
- `/tests/Unit/MemberScopeTest.php` (searchable, byStatus, byPlan, eager-loading)

#### 2.2 Build `MemberTable` Livewire Component

**Purpose:** Paginated, searchable, filterable table of all members.

**Component Properties:**
```php
public string $search = '';
public string $statusFilter = '';
public ?int $planFilter = null;
public int $perPage = 50;
```

**Component Actions:**
- `updatedSearch()` → debounce search (300ms), reset pagination
- `updatedStatusFilter()` → apply filter, reset pagination
- `updatedPlanFilter()` → apply filter, reset pagination
- `exportCsv()` → generate CSV of entire filtered list

**Events:**
- Emits: `member-selected` (passes `$memberId`) when row clicked
- Listens: `member-updated` (refreshes list when detail panel changes), `card-assigned` (updates card status column)

**Implementation:**
- [x] Create component class with properties, scoped queries, pagination
- [x] Build Blade template: Flux UI table with sortable columns (name, email, phone, status, plan, nfc_status)
- [x] Add search input + filter dropdowns (Flux UI field + select components)
- [x] Implement CSV export: collect filtered records, stream response
- [x] Add row click handler: wire:click dispatch `member-selected` event
- [x] Add skeleton loaders for initial load, re-renders on filter
- [x] Test: verify search/filter SQL, pagination, CSV output, event dispatches

**Deliverables:**
- `/app/Livewire/Admin/Members/MemberTable.php`
- `/resources/views/livewire/admin/members/member-table.blade.php`
- `/tests/Feature/Livewire/MemberTableTest.php` (search, filter, pagination, CSV, event dispatch)

#### 2.3 Build `MemberDetailPanel` Livewire Component

**Purpose:** Display full member profile + subscription + recent check-ins + inline admin actions.

**Component Properties:**
```php
public ?int $memberId = null;
public ?Member $member = null;
```

**Component Actions:**
- `suspend()` → set member status = suspended, disable access
- `activate()` → set member status = active, re-enable access
- `resetPassword()` → dispatch password reset email job
- `delete()` → soft-delete member (confirmation required)

**Implementation:**
- [x] Create component; load member on mount via `member-selected` event listener
- [x] Build Blade template: member card (name, email, phone, status, avatar) + subscription card (plan, dates, status) + recent check-ins (last 10 events)
- [x] Add action buttons: Suspend, Activate, Reset Password, Delete (with confirmation modal via Flux UI)
- [x] Add audit trail: show who enrolled member, when card assigned, etc.
- [x] Implement optimistic UI: show loading state during action, update on response
- [x] Test: verify each action updates DB, emits `member-updated` event, disables button on load

**Deliverables:**
- `/app/Livewire/Admin/Members/MemberDetailPanel.php`
- `/resources/views/livewire/admin/members/member-detail-panel.blade.php`
- `/tests/Feature/Livewire/MemberDetailPanelTest.php` (suspend, activate, password reset, delete, event emit)

#### 2.4 Build `NfcCardAssignment` Livewire Component

**Purpose:** Assign Mifare NFC card UID to a member; detect duplicates.

**Component Properties:**
```php
public int $memberId;
public string $uid = '';
public string $cardStatus = 'active'; // active | suspended | lost
```

**Component Actions:**
- `assign()` → validate UID uniqueness, create NfcCard, log assignment, notify member

**Validation Rules:**
- UID format: alphanumeric, 8–32 characters (Hardware UID from Mifare card)
- UID uniqueness: reject if `nfc_cards.uid` already exists (inline error)
- Card status: one of active, suspended, lost
- Member must exist and be in <= active state

**Implementation:**
- [x] Create component with UID input field (text or NFC scan compatible)
- [x] Add real-time UID validation: check uniqueness on blur, show inline error if duplicate
- [x] Build Blade template: UID input field, card status selector (Flux UI), assign button
- [x] Implement assignment action: create NfcCard record, log event (assigned_by, assigned_at), update member status if pending → active, dispatch notification job
- [x] Add success feedback: "Card assigned successfully — member notified"
- [x] Test: verify UID validation, duplicate detection, event logging, member notification job dispatched

**Deliverables:**
- `/app/Livewire/Admin/Members/NfcCardAssignment.php`
- `/resources/views/livewire/admin/members/nfc-card-assignment.blade.php`
- `/app/Jobs/NotifyMemberCardAssigned.php` (push + email)
- `/tests/Feature/Livewire/NfcCardAssignmentTest.php` (UID validation, duplicate detection, assignment logic, notification job)

#### 2.5 Layout & Integration

- [x] Create dashboard page: `resources/views/livewire/admin/members/dashboard.blade.php` (two-column: MemberTable + MemberDetailPanel)
- [x] Add route: `Route::get('/admin/members', MemberTable::class)->role('admin', 'manager')->name('admin.members')`
- [x] Implement responsive layout: full-width on desktop, stacked modal on mobile (MemberDetailPanel as modal)
- [x] Test page load performance: < 2s initial load with 1000+ members

**Deliverables:**
- `/resources/views/livewire/admin/members/dashboard.blade.php`
- `/routes/admin.php` (members routes)
- Performance test: EXPLAIN on member queries, verify index usage

### Dependencies

- **Technical:** Phase 1 (RBAC middleware) — routes must be protected
- **Functional:** `Member` model must exist with relationships to `NfcCard` and `Subscription`

### Deliverables

- [x] `MemberTable` component: searchable, filterable, paginated, exports CSV
- [x] `MemberDetailPanel` component: member profile, actions (suspend, activate, reset password), integrates with MemberTable
- [x] `NfcCardAssignment` component: UID entry, duplicate validation, assignment logging
- [x] Dashboard page: responsive two-column layout (desktop) / modal (mobile)
- [x] Full test coverage: unit + integration + E2E on member workflows

### Testing Strategy

**Unit Tests:**
- Member model scopes (searchable, byStatus, byPlan)
- UID validation rules

**Integration Tests:**
- MemberTable: search, filter, pagination, CSV export
- MemberDetailPanel: suspend, activate, password reset, delete, event dispatch
- NfcCardAssignment: UID validation, duplicate detection, card creation, notification job dispatch

**Feature Tests:**
- Full member workflow: view list → click member → assign card → receive notification

**Performance Tests:**
- Member search with 1000+ records → < 500ms query time
- CSV export of 500 members → < 2s generation

**Success Criteria:**
- All component tests passing (30+ test cases)
- No N+1 queries in MemberTable
- CSV export generates valid CSV, imports correctly in Excel
- Member notification job fires on card assignment

### Risks & Considerations

| Risk                                  | Severity | Mitigation                                                                  |
| ------------------------------------- | -------- | --------------------------------------------------------------------------- |
| Duplicate NFC UIDs (hardware defect)  | Medium   | Database unique constraint + real-time validation; log duplicates for audit |
| N+1 queries on large member lists     | Medium   | Implement eager-loading scopes; verify with EXPLAIN; use query logs in dev  |
| Member deletion cascade               | High     | Soft-delete only; preserve check-in history; document cascading relationships |
| Password reset email failure          | Low      | Queue password reset jobs; retry failed jobs; email logs                    |

---

## Phase 3: Subscription Management

### Phase Name & Objective

**Enable managers to enroll members in plans, suspend/resume subscriptions, and track expiring memberships.**

This phase delivers the transaction workflows: subscription creation, status transitions, and renewal reminders.

### Scope

- **User Stories:** US-005 (Subscription Enrollment), US-006 (Expiring Subscriptions), US-008 (Suspension & Transfer)
- **Story Points:** 18 pts
- **Components:**
  - `Admin\Subscriptions\SubscriptionEnrollment` (plan selection, payment recording, enrollment)
  - `Admin\Subscriptions\SubscriptionSuspension` (suspend, resume, transfer flows)
  - `Admin\Subscriptions\ExpiringSubscriptionsView` (alert list for 7-day expiry)
- **Key Deliverables:**
  - Subscription model business logic (renewal calculations, expiry scopes)
  - Three Livewire components fully tested
  - Receipt PDF generation + email dispatch

### Key Tasks

#### 3.1 Create Subscription Model Scopes & Business Logic

- [x] Add `active()` scope: where `status = active` AND `ends_at > now()`
- [x] Add `expiring()` scope: `active()` AND `ends_at <= now() + 7 days`
- [x] Create `calculateEndDate($startsAt, $durationDays)` method: returns exact end date (no timezone issues)
- [x] Add `isActive()` getter: true if `status = active` AND `ends_at > now()`
- [x] Add `daysRemaining()` getter: integer count of days until `ends_at`
- [x] Create `suspend($reason)` method: freeze `days_remaining`, set `suspended_at`, update status
- [x] Create `resume()` method: recalculate `ends_at = resumed_at + days_remaining`, clear suspension fields
- [x] Create `transfer($newMemberId)` method: change `member_id`, set status = transferred, create audit log
- [x] Write scope tests: verify SQL correctness, edge cases (today = expiry date, timezone handling)

**Deliverables:**
- `/app/Models/Subscription.php` (scopes, methods, business logic)
- `/tests/Unit/SubscriptionLogicTest.php` (active, expiring, suspend, resume, transfer, date calculations)

#### 3.2 Build `SubscriptionEnrollment` Livewire Component

**Purpose:** Enroll a member in a plan, record payment, generate receipt, update access immediately.

**Component Properties:**
```php
public int $memberId;
public ?int $planId = null;
public string $startsAt; // date
public string $paymentMethod = ''; // cash | konnect | paymee
public ?string $paymentReference = null; // gateway transaction ID
public bool $isProcessing = false;
```

**Component Actions:**
- `enroll()` → validate form, create subscription, process payment, generate receipt, dispatch notification, update member status

**Implementation:**
- [x] Create component; load member on mount
- [x] Build Blade template:
  - Plan selector (Flux UI select, populated from `Plan::where('is_archived', false)->orderBy('price')`)
  - Start date input (Flux UI date picker, default today)
  - Payment method selector (radio buttons: cash, konnect, paymee)
  - Conditional payment fields: if gateway method, show amount pre-filled, payment status
  - Enroll button (disabled during processing)
- [x] Implement `enroll()` logic:
  - Validate member exists, plan valid, dates correct
  - Create `Subscription` record: `starts_at`, auto-calculate `ends_at = starts_at + plan.duration_days`, status = active
  - Record payment: `payment_method`, amount, reference (if gateway)
  - Generate PDF receipt (via ReceiptGenerator from Phase 1)
  - Store receipt path in subscription
  - Dispatch `SendSubscriptionReceiptEmail` job + `SendSubscriptionNotification` job
  - Update member status: if pending → active
  - Update terminal whitelist: force sync card UIDs to all terminals
  - Show success toast + emit `subscription-created` event
- [x] Add error handling: show inline errors for validation failures, payment failures, notification job failures
- [x] Test: form validation, payment recording, receipt generation, job dispatch, member status update

**Deliverables:**
- `/app/Livewire/Admin/Subscriptions/SubscriptionEnrollment.php`
- `/resources/views/livewire/admin/subscriptions/subscription-enrollment.blade.php`
- `/app/Jobs/SendSubscriptionReceiptEmail.php`
- `/app/Jobs/SendSubscriptionNotification.php`
- `/tests/Feature/Livewire/SubscriptionEnrollmentTest.php` (form validation, payment recording, receipt generation, notification dispatch)

#### 3.3 Build `SubscriptionSuspension` Livewire Component

**Purpose:** Suspend, resume, and transfer subscriptions for special member cases.

**Component Properties:**
```php
public int $subscriptionId;
public string $action = ''; // suspend | resume | transfer
public string $suspensionReason = ''; // medical | travel | other
public ?int $transferToMemberId = null;
public bool $requiresApproval = false; // for transfer
```

**Component Actions:**
- `suspend()` → freeze days_remaining, set suspended_at, update status, notify member
- `resume()` → recalculate ends_at, clear suspension fields, update status, notify member
- `transfer()` → Admin approval + identity verification, reassign subscription, audit log

**Implementation:**
- [x] Create component; load subscription on mount
- [x] Build Blade template:
  - Action selector (radio: Suspend | Resume | Transfer)
  - Conditional fields:
    - **Suspend:** reason selector (medical | travel | other), confirmation checkbox, suspend button
    - **Resume:** show frozen days_remaining, resumed date (defaults now), resume button
    - **Transfer:** member search/picker (Flux UI), identity verification checklist, approve checkbox, transfer button
  - Alert: show current subscription state (active/suspended, dates, remaining days)
- [x] Implement action methods:
  - `suspend()`: update `suspended_at = now()`, `days_remaining = $subscription->daysRemaining()`, status = suspended; dispatch notification job
  - `resume()`: update `ends_at = now() + days_remaining`, `resumed_at = now()`, clear suspension fields, status = active; dispatch notification job
  - `transfer()`: validate new member exists, set `member_id = $newMemberId`, status = transferred, create audit log entry
- [x] Add toast feedback: success confirmations + error messages
- [x] Test: suspension/resumption calculations, transfer validation, notification dispatch, audit log creation

**Deliverables:**
- `/app/Livewire/Admin/Subscriptions/SubscriptionSuspension.php`
- `/resources/views/livewire/admin/subscriptions/subscription-suspension.blade.php`
- `/app/Listeners/SubscriptionStatusChanged.php` (audit logging)
- `/tests/Feature/Livewire/SubscriptionSuspensionTest.php` (suspend, resume, transfer, date calculations, event dispatch)

#### 3.4 Build `ExpiringSubscriptionsView` Livewire Component

**Purpose:** Show alert list of subscriptions expiring within 7 days; quick action to renew.

**Component Properties:**
```php
public Collection $expiringSubscriptions;
public int $touchedCount = 0; // tracks sent reminders
```

**Component Actions:**
- `loadExpiringSubscriptions()` → fetch subscriptions where `ends_at <= now() + 7 days` and status = active
- `sendReminder($subscriptionId)` → dispatch reminder email/push job, log action

**Implementation:**
- [x] Create component; initial load in mount()
- [x] Build Blade template:
  - Flux UI alert box: "X subscriptions expiring in ≤ 7 days"
  - Table: member name, plan name, days remaining, renewal link
  - Bulk action: send reminder to all (button), tracks number of notifications sent
  - Empty state: "No expiring subscriptions in next 7 days"
- [x] Implement `sendReminder()`: dispatch notification job (email + push)
- [x] Test: query returns correct subscriptions, reminder jobs dispatched

**Deliverables:**
- `/app/Livewire/Admin/Subscriptions/ExpiringSubscriptionsView.php`
- `/resources/views/livewire/admin/subscriptions/expiring-subscriptions-view.blade.php`
- `/tests/Feature/Livewire/ExpiringSubscriptionsViewTest.php`

#### 3.5 Dashboard Integration

- [x] Add subscription routes
- [x] Create admin subscriptions dashboard page
- [x] Add breadcrumb navigation: Members → detail → Subscription Management

**Deliverables:**
- `/routes/admin.php` (subscription routes)
- `/resources/views/livewire/admin/subscriptions/dashboard.blade.php`

### Dependencies

- **Technical:** Phase 1 (RBAC, payment gateway, receipt generator), Phase 2 (MemberTable, MemberDetailPanel)
- **Functional:** Subscription model exists; Plan model exists; Payment gateway integration complete

### Deliverables

- [x] `SubscriptionEnrollment` component: plan selection, payment recording, receipt generation, instant member notification
- [x] `SubscriptionSuspension` component: suspend/resume calculations, transfer with approval
- [x] `ExpiringSubscriptionsView` component: 7-day expiry alerts, bulk reminder dispatch
- [x] Full test coverage: unit + integration on subscription logic, component workflows
- [x] Admin subscriptions dashboard: integrated pages with navigation

### Testing Strategy

**Unit Tests:**
- Subscription scopes (active, expiring)
- Date calculations (enrollment, suspension, resumption, timezone handling)
- Business rule validation

**Integration Tests:**
- SubscriptionEnrollment: form validation, payment recording, receipt generation, notification dispatch, member status update
- SubscriptionSuspension: suspend/resume logic, transfer validation, audit logging
- ExpiringSubscriptionsView: expiring subscription query, reminder dispatch

**Feature Tests:**
- Full workflow: enroll member → receive receipt → near expiry → send reminder → renew

**Performance Tests:**
- Expiring subscriptions query with 10,000 subscriptions → < 100ms

**Success Criteria:**
- All component tests passing (25+ test cases)
- No business rule violations in suspend/resume (dates always calculated correctly)
- Receipt generated and emailed within 1s of enrollment
- Expiring subscriptions query fast and accurate

### Risks & Considerations

| Risk                           | Severity | Mitigation                                                                    |
| ------------------------------ | -------- | ----------------------------------------------------------------------------- |
| Subscription date edge cases   | High     | Comprehensive test suite; test leap years, timezone transitions, DST changes  |
| Payment refunds / reversals    | High     | Log all payment events immutably; implement reversal detection + reconciliation |
| Notification delivery lag      | Medium   | Use queue jobs; add retry logic; monitor email delivery logs                  |
| Transfer audit trail loss      | Medium   | Immutable audit log table; log all transfers with admin ID + reason           |
| Plan deletion breaking history | Low      | Never delete plans; archive instead (already in schema)                       |

---

## Phase 4: Access Control & Real-Time Monitoring

### Phase Name & Objective

**Implement real-time check-in visuals, immutable audit logs, and anti-fraud detection.**

This phase delivers operational dashboards: live occupancy counters, check-in event feeds, suspicious activity alerts, and read-only audit trails.

### Scope

- **User Stories:** US-019 (Real-Time Check-in Monitoring), US-012 (Immutable Audit Log), US-013 (Anti-Passback)
- **Story Points:** 18 pts
- **Components:**
  - `Admin\AccessControl\CheckInMonitor` (real-time feed, occupancy counter, alerts, WebSocket)
  - `Admin\AccessControl\AuditLog` (immutable event log, filters, export)
  - `Admin\AccessControl\AntiPassbackAlerts` (suspicious event queue, admin actions)
- **Key Deliverables:**
  - Real-time WebSocket infrastructure (Laravel Echo)
  - Immutable check-in event logging (no updates after creation)
  - Anti-passback fraud detection logic

### Key Tasks

#### 4.1 Implement Real-Time WebSocket Infrastructure

- [x] Configure Laravel Echo + Pusher or equivalent (Reverb for local development)
- [x] Set up WebSocket server (Redis backed message queue)
- [x] Create `CheckInProcessed` broadcast event: fired when check-in is authorized or denied
- [x] Implement terminal webhook receiver (from Phase 1); parse ISAPI payload → create CheckInEvent + broadcast
- [x] Store broadcast credentials in `.env` (BROADCAST_DRIVER, BROADCAST_HOST, etc.)
- [x] Test WebSocket under load: 50+ concurrent connections, < 1s message latency

**Deliverables:**
- `/config/broadcasting.php` (WebSocket configuration)
- `/app/Events/CheckInProcessed.php` (broadcast event)
- `/app/Http/Controllers/TerminalCheckInController.php` (webhook receiver)
- WebSocket load test: 50 connections, 10 check-ins/sec, verify latency < 1s

#### 4.2 Build `CheckInMonitor` Livewire Component

**Purpose:** Real-time dashboard view of gym occupancy and check-in events.

**Component Properties:**
```php
public Collection $recentEvents; // last 20 CheckInEvent records
public int $occupancyCount = 0; // members currently inside
public array $terminalStatuses = []; // online | offline per terminal
public int $alertCount = 0; // denial events in last 5 min
public bool $isWebSocketConnected = false;
```

**Component Actions:**
- `loadOccupancy()` → count members with active subscriptions inside (last entry > last exit)
- `acknowledgeAlert($terminalId)` → clear alert state

**Real-Time Updates:**
Uses `#[On('echo:checkins,CheckInProcessed')]` listener — updates recentEvents + occupancyCount + alerts without full page reload

**Implementation:**
- [x] Create component with WebSocket listener
- [x] Build Blade template:
  - **Header:** occupancy counter (large number + icon), WebSocket status badge (green = connected, red = disconnected)
  - **Recent Events List:** 20 most recent check-ins (member name, result icon green/red, time, terminal, denial reason if denied)
  - **Terminal Status Grid:** card per terminal (name, online/offline, last seen timestamp)
  - **Alerts Section:** list of terminals with > 3 denials in last 5 min, each with acknowledge button
  - **Empty State:** "No check-ins yet today"
- [x] Implement WebSocket listener: update recentEvents + occupancyCount on CheckInProcessed event
- [x] Add polling fallback: if WebSocket disconnects, poll `/api/check-in/recent` every 5 sec to maintain fresh data
- [x] Test: occupancy count accuracy, WebSocket message delivery, fallback polling, alert triggering

**Deliverables:**
- `/app/Livewire/Admin/AccessControl/CheckInMonitor.php`
- `/resources/views/livewire/admin/access-control/check-in-monitor.blade.php`
- `/tests/Feature/Livewire/CheckInMonitorTest.php` (occupancy calculation, WebSocket updates, alert logic)

#### 4.3 Build `AuditLog` Livewire Component

**Purpose:** Immutable, filterable, read-only log of all check-in events (authorized + denied).

**Component Properties:**
```php
public string $dateFrom = ''; // date picker
public string $dateTo = ''; // date picker (defaults today)
public string $memberSearch = ''; // search by member name/email
public string $resultFilter = ''; // authorized | denied | all
public int $perPage = 50;
```

**Component Actions:**
- `updatedDateFrom()`, `updatedDateTo()`, `updatedMemberSearch()`, `updatedResultFilter()` → requery with filters
- `exportCsv()` → download filtered events as CSV
- `exportPdf()` → generate PDF report of filtered events

**Implementation:**
- [x] Create component with date range pickers (Flux UI), member search, result filter
- [x] Build Blade template:
  - Filter controls (Flux UI field, input, select)
  - Table: member name, card UID, timestamp, result (Authorized/Denied), terminal name, denial reason (if denied)
  - Pagination: 50 records per page
  - Export buttons (CSV + PDF)
  - Empty state: "No check-in events for selected filters"
- [x] Implement queries: CheckInEvent scoped by date, member, result with eager-loaded relations
- [x] Implement exports:
  - CSV: tab-separated, includes all columns
  - PDF: branded report template, date range header, total count summary
- [x] Add audit note: "This log is immutable — records cannot be modified or deleted"
- [x] Test: filter accuracy, export generation, no N+1 queries

**Deliverables:**
- `/app/Livewire/Admin/AccessControl/AuditLog.php`
- `/resources/views/livewire/admin/access-control/audit-log.blade.php`
- `/app/Exports/CheckInEventExport.php` (CSV via Maatwebsite/Excel)
- `/app/Reports/CheckInEventPdfReport.php` (PDF generation)
- `/tests/Feature/Livewire/AuditLogTest.php` (filtering, pagination, exports)

#### 4.4 Implement Anti-Passback Fraud Detection

- [x] Create `AntiPassbackRule` service: detects consecutive entries (no exit between)
- [x] Implement check-in event processing:
  - [x] Track last check-in event per card UID (entry or exit status)
  - [x] On new event: if terminal_type = entry AND last event of same card = entry (no exit), flag as suspicious
  - [x] Create alert: `anti_passback_triggered` event broadcast to admin dashboard
  - [x] Auto-suspend card after 3 consecutive suspicious events (pending review status)
- [x] Add review workflow: admin can Dismiss (clear flag) or Escalate (permanent suspension)
- [x] Write anti-passback tests: consecutive entry detection, alert triggering, auto-suspension

**Deliverables:**
- `/app/Services/AntiPassbackRule.php` (detection logic)
- `/app/Jobs/ProcessAntiPassbackEvent.php` (queued job)
- `/tests/Feature/AntiPassbackRuleTest.php` (detection accuracy, auto-suspension)

#### 4.5 Build `AntiPassbackAlerts` Livewire Component

**Purpose:** Queue of suspicious events pending admin review; actions to dismiss or escalate.

**Component Properties:**
```php
public Collection $alerts; // suspicious events not yet reviewed
```

**Component Actions:**
- `dismiss($cardUid)` → clear suspicious flag, reset counter, unblock card
- `escalate($cardUid)` → permanently suspend card, notify member

**Implementation:**
- [x] Create component; listen to `anti-passback-triggered` event for real-time updates
- [x] Build Blade template:
  - Alert list: member name, card UID, event count, most recent event time, action buttons (Dismiss | Escalate)
  - Empty state: "No suspicious activities detected"
  - Batch action: "Dismiss all" button
- [x] Implement dismiss/escalate: update card status, log admin action, dispatch notification job
- [x] Test: alert creation, dismiss logic, escalation logic, card suspension

**Deliverables:**
- `/app/Livewire/Admin/AccessControl/AntiPassbackAlerts.php`
- `/resources/views/livewire/admin/access-control/anti-passback-alerts.blade.php`
- `/tests/Feature/Livewire/AntiPassbackAlertsTest.php`

#### 4.6 Dashboard Integration

- [x] Create access control dashboard: CheckInMonitor (top) + AuditLog tab + AntiPassbackAlerts tab
- [x] Add real-time refresh for monitoring area (WebSocket-driven)
- [x] Add breadcrumb navigation

**Deliverables:**
- `/resources/views/livewire/admin/access-control/dashboard.blade.php`
- `/routes/admin.php` (access control routes)

### Dependencies

- **Technical:** Phase 1 (terminal provisioning, RBAC), Phase 2 (member data)
- **Functional:** CheckInEvent model exists; HikvisionTerminal model exists; WebSocket infrastructure available

### Deliverables

- [x] `CheckInMonitor` component: real-time feed, occupancy counter, terminal status, alerts
- [x] `AuditLog` component: immutable event log with filters, CSV + PDF export
- [x] `AntiPassbackAlerts` component: suspicious event queue with dismiss/escalate actions
- [x] Anti-passback fraud detection service + queued jobs
- [x] Full test coverage: unit + integration + real-time WebSocket tests
- [x] Admin access control dashboard: integrated pages with navigation

### Testing Strategy

**Unit Tests:**
- Anti-passback detection logic (consecutive entries, alert triggering)
- Occupancy count calculation (entry/exit logic)

**Integration Tests:**
- CheckInMonitor: WebSocket message reception, occupancy update, alert display
- AuditLog: filtering (date, member, result), pagination, CSV/PDF exports
- AntiPassbackAlerts: alert creation, dismiss, escalation, card suspension

**Feature Tests:**
- Full check-in workflow: member taps card → authorized event created → CheckInMonitor updates → occupancy increases
- Fraud detection: consecutive entries → alert created → admin dismisses/escalates

**Load Tests:**
- WebSocket: 50+ concurrent connections, 10 check-ins/sec, latency < 1s
- AuditLog query: 100,000 events filtered by date range → < 500ms

**Success Criteria:**
- All component tests passing (20+ test cases)
- WebSocket messages delivered < 1s latency consistently
- Audit log immutability enforced (no update/delete capability in UI or DB)
- Anti-passback detection catches 100% of consecutive entries
- Real-time occupancy counter accurate within 2 events

### Risks & Considerations

| Risk                            | Severity | Mitigation                                                         |
| ------------------------------- | -------- | ------------------------------------------------------------------ |
| WebSocket disconnection         | Medium   | Implement fallback polling; display connection status to user      |
| Anti-passback false positives   | High     | Manual review queue before auto-suspension; log all decisions      |
| Occupancy count drift           | Medium   | Periodic reconciliation job (hourly); alert if counts diverge > 2% |
| Audit log size / storage        | Medium   | Archive events > 12 months; implement s3 cold storage              |
| Real-time feed lag under load   | Medium   | Queue check-in jobs; broadcast only recent events (last 20)        |

---

## Phase 5: Analytics & Reporting

### Phase Name & Objective

**Deliver business intelligence dashboards: revenue trends, occupancy heatmaps, and class scheduling.**

This phase builds data aggregation and visualization: financial reports, occupancy patterns, and membership forecasting.

### Scope

- **User Stories:** US-020 (Revenue & Subscription Analytics), US-021 (Occupancy Heatmap), US-023 (Course Scheduling)
- **Story Points:** 18 pts
- **Components:**
  - `Admin\Analytics\RevenueAnalytics` (charts, KPIs, exports)
  - `Admin\Analytics\OccupancyHeatmap` (7-day × 24-hour grid)
  - `Admin\Scheduling\CourseSchedule` (weekly class CRUD, enrollment management)
- **Key Deliverables:**
  - Revenue + subscription KPI calculations
  - Heatmap data aggregation from check-in events
  - Course scheduling CRUD + enrollment tracking

### Key Tasks

#### 5.1 Build `RevenueAnalytics` Livewire Component

**Purpose:** Monthly revenue trends, subscription status breakdown, churn rate, top plans, payment method breakdown.

**Component Properties:**
```php
public string $dateFrom = ''; // month start
public string $dateTo = ''; // month end (default today)
public array $chartData = []; // prepared for Chart.js
public float $monthlyRevenue = 0;
public float $churnRate = 0;
public int $activeSubscriptions = 0;
public int $expiredSubscriptions = 0;
```

**Component Actions:**
- `updatedDateFrom()`, `updatedDateTo()` → recalculate all metrics
- `exportPdf()` → generate branded PDF report
- `exportCsv()` → export raw data as CSV

**Calculations:**
- **Monthly Revenue:** sum all payments in date range grouped by month (bar chart)
- **Month-over-Month Growth:** compare current month to previous month
- **Churn Rate:** (subscriptions expired in period) / (active subscriptions start of period)
- **Subscription Status Breakdown:** active vs expired (donut chart)
- **Top Plans:** rank plans by subscriber count + revenue
- **Payment Method Breakdown:** cash vs konnect vs paymee (pie chart)

**Implementation:**
- [x] Create component with date range pickers
- [x] Build Blade template:
  - KPI cards: Monthly Revenue, Churn Rate, Active Subscriptions, Expired Subscriptions
  - Charts: Revenue trend (bar), Status breakdown (donut), Top plans (bar), Payment methods (pie)
  - Filter controls: date range
  - Export buttons: PDF + CSV
- [x] Implement data loading: Subscription query with aggregations; support large datasets (10,000+ records) efficiently
- [x] Add Chart.js integration via Alpine.js or Livewire component prop
- [x] Test: KPI accuracy, chart data correctness, export generation, performance

**Deliverables:**
- `/app/Livewire/Admin/Analytics/RevenueAnalytics.php`
- `/resources/views/livewire/admin/analytics/revenue-analytics.blade.php`
- `/app/DTO/RevenueAnalyticsData.php` (data transfer object for chart preparation)
- `/tests/Feature/Livewire/RevenueAnalyticsTest.php` (KPI calculations, chart data, exports)

#### 5.2 Build `OccupancyHeatmap` Livewire Component

**Purpose:** 7-day × hourly (06:00–23:00) grid showing occupancy % intensity for gym planning.

**Component Properties:**
```php
public array $heatmapData = []; // [day][hour] => occupancy_pct
public ?int $dayFilter = null; // 0=Mon … 6=Sun
public ?int $hourFilter = null; // 0–17 (06:00–23:00)
```

**Component Actions:**
- `loadHeatmapData()` → aggregate check-in events for last 7 days, calculate occupancy % per hour per day
- `updatedDayFilter()`, `updatedHourFilter()` → recompute filtered view

**Occupancy Calculation:**
- For each hour slot: count members currently inside during that hour (entry before or during, exit after or no documented exit yet)
- Occupancy % = (concurrent members / assumed capacity 100) × 100, capped at 100%

**Implementation:**
- [x] Create component; load heatmap data on mount
- [x] Build Blade template:
  - Heatmap grid: 7 rows (Mon–Sun) × 18 columns (06:00–23:00)
  - Color intensity: 0% white → 50% light → 100% dark
  - Tooltip on hover: "Monday 09:00 – 47 members (47%)"
  - Filter controls: day picker, hour picker (optional)
  - Export button: PDF (print-friendly grid)
- [x] Implement data loading: CheckInEvent aggregation query; cache results (valid for 1 hour)
- [x] Test: occupancy calculation accuracy, heatmap rendering, export quality

**Deliverables:**
- `/app/Livewire/Admin/Analytics/OccupancyHeatmap.php`
- `/resources/views/livewire/admin/analytics/occupancy-heatmap.blade.php`
- `/app/Services/OccupancyCalculator.php` (occupancy % logic)
- `/tests/Feature/Livewire/OccupancyHeatmapTest.php` (occupancy calculation, caching, rendering)

#### 5.3 Build `CourseSchedule` Livewire Component

**Purpose:** Admin interface to create, edit, cancel, and view recurring weekly group fitness classes.

**Component Properties:**
```php
public Collection $sessions; // all active CourseSession records
public ?CourseSession $selectedSession = null; // for detail/edit panel
public string $viewMode = 'calendar'; // calendar | list
```

**Component Actions:**
- `createSession($data)` → create CourseSession, set recurring weekly
- `editSession($id, $data)` → update session details
- `cancelSession($id)` → mark cancelled, dispatch notification job to all enrolled members
- `removeEnrollment($bookingId)` → cancel specific member's booking, notify waitlisted next member if applicable

**Implementation:**
- [x] Create component; load sessions on mount
- [x] Build Blade template:
  - **Calendar View:** 7-column grid (Mon–Sun), time rows (06:00–23:00), session blocks showing class name + enrolled count / capacity
  - **List View:** table with Name, Instructor, Day, Time, Duration, Capacity, Enrolled, Actions
  - **Detail Panel:** session form (name, instructor, day, time, duration, capacity), enrolled members list, action buttons (edit, cancel, export)
  - **Modals:** create session modal, edit session modal, cancel confirmation, removal confirmation
- [x] Implement CRUD actions:
  - Create: validate capacity > 0, time slot not overlapping; create CourseSession record
  - Edit: update session (cannot change time retroactively if past sessions exist; update future only)
  - Cancel: set `is_cancelled = true`, `cancelled_at = now()`, dispatch notification job to all enrolled members
  - Remove enrollment: cancel booking, check waitlist for next member, notify if promoted from waitlist
- [x] Test: session creation, edit validation, cancellation notifications, enrollment removal

**Deliverables:**
- `/app/Livewire/Admin/Scheduling/CourseSchedule.php`
- `/resources/views/livewire/admin/scheduling/course-schedule.blade.php`
- `/app/Jobs/NotifySessionCancellation.php`
- `/app/Jobs/NotifyWaitlistPromotion.php`
- `/tests/Feature/Livewire/CourseScheduleTest.php` (CRUD, notifications, waitlist logic)

#### 5.4 Dashboard Integration

- [x] Create analytics dashboard: RevenueAnalytics + OccupancyHeatmap tabs
- [x] Create scheduling dashboard: CourseSchedule page
- [x] Add breadcrumb navigation

**Deliverables:**
- `/resources/views/livewire/admin/analytics/dashboard.blade.php`
- `/resources/views/livewire/admin/scheduling/dashboard.blade.php`
- `/routes/admin.php` (analytics + scheduling routes)

### Dependencies

- **Technical:** Phase 1 (RBAC), Phase 2–4 (data models, check-in events)
- **Functional:** Subscription, CheckInEvent, CourseSession, Booking models exist; check-in data spanning weeks available

### Deliverables

- [x] `RevenueAnalytics` component: KPI cards, trend charts, export (PDF + CSV)
- [x] `OccupancyHeatmap` component: 7-day × hourly grid, tooltips, export
- [x] `CourseSchedule` component: calendar view, CRUD operations, enrollment management
- [x] Full test coverage: unit + integration on all calculations and workflows
- [x] Admin analytics + scheduling dashboards: integrated pages with navigation

### Testing Strategy

**Unit Tests:**
- Revenue KPI calculations (monthly revenue, churn rate, top plans)
- Occupancy % calculation per hour
- Course session overlapping validation

**Integration Tests:**
- RevenueAnalytics: data loading, chart preparation, exports
- OccupancyHeatmap: occupancy query, caching, rendering
- CourseSchedule: CRUD operations, notification dispatch, waitlist logic

**Feature Tests:**
- Full workflow: create class → enroll members → reach capacity → cancel → notify all enrolled

**Performance Tests:**
- RevenueAnalytics with 50,000 subscriptions → < 1s KPI calculation
- OccupancyHeatmap with 100,000 check-in events → < 500ms query, caching working

**Success Criteria:**
- All component tests passing (20+ test cases)
- KPI calculations verified against manual SQL
- Course schedule notifications sent to all enrolled members
- Analytics dashboards load < 2s even with large datasets

### Risks & Considerations

| Risk                         | Severity | Mitigation                                                          |
| ---------------------------- | -------- | ------------------------------------------------------------------- |
| Analytics query performance  | High     | Create database indexes on date/status columns; implement caching    |
| Occupancy calculation drift  | Medium   | Daily reconciliation job; compare calculated vs actual counts        |
| Class cancellation cascades  | Medium   | Test all enrolled + waitlisted notifications; verify all sent        |
| Large dataset export delays  | Medium   | Implement export queueing; stream CSV generation for large datasets  |
| Heatmap data staleness       | Low      | Cache valid for 1 hour; manual refresh button available              |

---

## Phase 6: Advanced Infrastructure & Optimization

### Phase Name & Objective

**Implement queue processing, automated backups, CI/CD final touches, and performance optimization.**

This phase is non-blocking and can run in parallel with Phase 5. Focuses on operational reliability and scalability.

### Scope

- **User Stories:** US-027 (Queue Processing), US-026 (Automated Backups), US-032 (CI/CD), Performance optimizations
- **Story Points:** 18 pts (distributed across infrastructure tasks)
- **Key Components:**
  - Laravel Horizon (queue monitoring dashboard)
  - Backup automation + restore procedure
  - CI/CD pipeline tuning
  - Query optimization + caching layer

### Key Tasks

#### 6.1 Implement Async Queue Processing (Laravel Horizon)

**Objective:** Offload heavy tasks (emails, notifications, report generation) to queues; keep API response times under 500ms.

**Tasks:**
- [x] Configure Redis for queue backend (already in project? verify `config/queue.php`)
- [x] Set up separate job queues: `default` (high priority), `notifications` (medium), `reports` (low priority)
- [x] Install Laravel Horizon: `composer require laravel/horizon`
- [x] Configure Horizon dashboard: `/admin/horizon` (only admin access via middleware)
- [x] Implement failed job handler: retry logic (up to 3 attempts), dead-letter queue for persistent failures
- [x] Create job list: all notification, email, report generation, terminal sync jobs with retry counts
- [x] Add health monitoring: Horizon dashboard alert if queue depth > 1000 or failed jobs accumulating
- [x] Document queue architecture: job priorities, estimated processing times, monitoring procedures
- [x] Test: dispatch 1000 jobs across queues, verify processing within SLA, check Horizon dashboard

**Deliverables:**
- `/config/queue.php` (queue configuration + retry policies)
- `/config/horizon.php` (Horizon configuration)
- All existing jobs refactored to use queues (NotifyMemberCardAssigned, SendSubscriptionReceiptEmail, etc.)
- `/docs/QUEUE_ARCHITECTURE.md` (job priority, SLAs, monitoring)
- Horizon health checks + alerting configuration

#### 6.2 Implement Automated Daily Backups

**Objective:** Nightly database backups with disaster recovery capability; 30/12/3 retention policy.

**Tasks:**
- [x] Create `DatabaseBackup` scheduled command (Laravel artisan command)
- [x] Implement backup logic:
  - [x] Nightly dump at 02:00 UTC (MySQL dump or Laravel backup package)
  - [x] Encrypt backup (GPG or similar)
  - [x] Upload to S3 or equivalent remote storage
  - [x] Tag backup with timestamp + environment (staging/production)
  - [x] Cleanup old backups per policy (30 daily / 12 weekly / 3 monthly)
- [x] Schedule via `app/Console/Kernel.php`: `$schedule->command('backup:database')->dailyAt('02:00');`
- [x] Implement restore procedure: documented steps + test restore quarterly
- [x] Email backup success/failure to ops team
- [x] Add monitoring: alert if backup > 24 hrs overdue
- [x] Test: create backup, verify size/integrity, restore to separate DB, verify data completeness

**Deliverables:**
- `/app/Console/Commands/BackupDatabaseCommand.php`
- `/app/Services/BackupService.php` (backup/restore logic)
- S3 bucket configuration (with versioning, encryption)
- `/docs/BACKUP_AND_RESTORE_PROCEDURES.md` (detailed restore steps, RTO/RPO targets)
- Backup health monitoring + alerting

#### 6.3 Query Optimization & Caching Layer

**Objective:** Ensure admin dashboard queries stay under SLA (< 500ms for check-in, < 2s for analytics).

**Tasks:**
- [x] Audit all Livewire component queries: run with `DB::enableQueryLog()`, identify N+1 issues
- [x] Add eager-loading scopes to models (Member, Subscription, CheckInEvent, HikvisionTerminal)
- [x] Create database indexes on frequently queried columns:
  - [x] `members.status`, `members.email`
  - [x] `subscriptions.status`, `subscriptions.ends_at`
  - [x] `check_in_events.created_at`, `check_in_events.terminal_id`
  - [x] `nfc_cards.member_id`, `nfc_cards.status`
  - [x] Run EXPLAIN on each major query; verify index usage
- [x] Implement caching layer:
  - [x] Cache member list queries (valid 5 min)
  - [x] Cache occupancy heatmap (valid 1 hour)
  - [x] Cache KPI calculations (valid 1 hour)
  - [x] Add manual cache invalidation on data changes
- [x] Profile memory usage: verify Livewire component memory < 5MB per instance
- [x] Stress test: 100 concurrent users on dashboard, verify response times < 2s
- [x] Document cache invalidation strategy: when to bust cache on data mutations

**Deliverables:**
- Database indexes created (migrations)
- `/app/Services/CacheService.php` (centralized cache operations)
- Query optimization documentation: before/after query times
- Load test results: 100 concurrent users, response time graphs

#### 6.4 CI/CD Pipeline Tuning & Deployment Safety

**Objective:** Ensure safe, automated deployments with automatic rollback on test failure.

**Tasks:**
- [x] Add code coverage reporting: Pest + coverage report to PR (target > 80%)
- [x] Add performance regression testing: compare request times vs baseline; fail if > 10% slower
- [x] Add dependency vulnerability scanning: `composer audit` in CI, fail if vulnerabilities found
- [x] Implement database migration safety: pre-production backup before migration, rollback on failure
- [x] Add secrets rotation: monthly reminder to rotate API keys; automated key rotation for non-critical keys
- [x] Create deployment checklist: pre-deploy validation (migrations dry-run, config validation, health checks)
- [x] Implement blue-green deployment (if infrastructure allows) or rolling deployment
- [x] Add post-deployment smoke tests: verify key admin pages load, health checks pass
- [x] Document rollback procedure: git tag strategy, database rollback steps

**Deliverables:**
- Updated `.github/workflows/{test, deploy}.yml` with coverage, performance, vulnerability checks
- `/scripts/pre-deploy-checks.sh` (validation before deployment)
- `/docs/DEPLOYMENT_PROCEDURES.md` (detailed deployment steps, rollback procedures)
- GitHub Actions secrets audit trail

#### 6.5 Optimization Audit & Documentation

- [x] Run full application audit: performance profiling, memory leaks, database efficiency
- [x] Document performance baselines: key metrics (check-in latency, dashboard load time, bulk operation time)
- [x] Create monitoring dashboard: Horizon queue depth, backup job status, database size trend, API response times
- [x] Write optimization runbook: how to identify and fix common performance bottlenecks

**Deliverables:**
- `/docs/PERFORMANCE_BASELINES.md` (target metrics, measurement procedures)
- `/docs/OPTIMIZATION_RUNBOOK.md` (troubleshooting guide)

### Dependencies

- **Technical:** All prior phases (data models, queued jobs must exist)
- **Functional:** Production environment available for backup testing; Redis available for queue backend

### Deliverables

- [x] Laravel Horizon deployed; queue monitoring dashboard accessible
- [x] Automated backup system running nightly; restore procedure tested quarterly
- [x] Database indexes created; queries optimized (no N+1, eager-loading applied)
- [x] Caching layer implemented; cache invalidation strategy documented
- [x] CI/CD pipelines enhanced with coverage, performance, security checks
- [x] Performance baselines documented; monitoring dashboard set up
- [x] Deployment procedures automated; blue-green or rolling deployment implemented

### Testing Strategy

**Unit Tests:**
- Backup service (encrypt, upload, cleanup logic)
- Cache service (set, get, invalidate logic)

**Integration Tests:**
- Queue job processing under load (1000+ jobs)
- Backup creation + restore verification
- CI/CD pipeline on trial commit

**Performance Tests:**
- Query optimization: before/after times
- Dashboard load under 100 concurrent users
- Check-in API latency < 500ms under 10 requests/sec
- Backup job completion within 30 min SLA

**Infrastructure Tests:**
- Backup restore to separate DB environment
- Secrets rotation without service interruption
- Blue-green/rolling deployment safety

**Success Criteria:**
- Queue depth stays < 100 under normal load
- Backup completes nightly within 30 min; restore verified quarterly
- No N+1 queries in admin dashboard
- Dashboard loads < 2s for typical user action
- CI/CD pipeline catches 90%+ of integration issues before production

### Risks & Considerations

| Risk                              | Severity | Mitigation                                                         |
| --------------------------------- | -------- | ------------------------------------------------------------------ |
| Queue processing bottleneck       | Medium   | Monitor queue depth; scale horizontally if > 10,000 jobs backlog    |
| Backup size explosive growth      | Medium   | Monitor backup size weekly; adjust retention policy if exceeding SLA |
| Cache invalidation race condition | High     | Use event listeners to trigger cache busting; test concurrent edits |
| CI/CD false negatives             | Medium   | Review rejected deployments; tune coverage/performance thresholds   |
| Secrets exposure in CI logs       | High     | Use GitHub Actions secrets exclusively; audit logs monthly          |
| Database lock during backup       | Low      | Use non-locking MySQL dump options (--single-transaction)           |

---

## Phasing Summary & Timeline

| Phase | Name                                   | Story Points | Sprints | Key Deliverables                                       |
| ----- | -------------------------------------- | ------------ | ------- | ------------------------------------------------------ |
| 1     | Infrastructure & Security Foundation  | 23           | 1.5–2   | RBAC, Payment Gateway, Terminals, CI/CD              |
| 2     | Member Management Core                | 10           | 1.5     | MemberTable, DetailPanel, NfcCardAssignment           |
| 3     | Subscription Management               | 18           | 2       | Enrollment, Suspension, ExpiringSubscriptions         |
| 4     | Access Control & Real-Time Monitoring | 18           | 2       | CheckInMonitor, AuditLog, AntiPassback                |
| 5     | Analytics & Reporting                 | 18           | 2       | RevenueAnalytics, OccupancyHeatmap, CourseSchedule    |
| 6     | Advanced Infrastructure & Optimization| 18           | 2–3     | Horizon, Backups, Query Optimization, CI/CD Tuning   |
| **TOTAL** | **Admin Dashboard Completion**       | **105**      | **~11–13 sprints (5–7 weeks)**  | **Fully functional, tested, deployed** |

**Note:** Phases 1–5 are strictly sequential. Phase 6 can begin in parallel with the latter part of Phase 5. Two-week sprint cycles recommended for maximum velocity.

---

## Cross-Phase Concerns

### Testing & Quality Assurance

- **Every phase includes:** unit tests (40%), integration tests (40%), feature tests (15%), load tests (5%)
- **Test-driven development:** write tests first for business logic, then implementation
- **Continuous integration:** all tests pass before PR merge
- **Code review:** peer review required for all PRs; focus on architecture + maintainability

### Documentation & Knowledge Transfer

- **Each phase generates:** task documentation, code comments, architecture decisions (ADRs)
- **Admin guides:** how to use each feature (separate UX documentation not included in this plan)
- **Developer guides:** how to extend features, add new roles, implement new analytics
- **Operations guides:** monitoring, backups, disaster recovery, performance troubleshooting

### Security & Compliance

- **RBAC enforced** on every resource-level action (Phase 1 prerequisite)
- **GDPR/RGPD**: member consent timestamps stored; deletion = soft-delete only
- **Payment PCI compliance**: never log card data; all gateway data encrypted
- **Audit trails**: immutable logs of all admin actions, member status changes, payment transactions
- **Secrets management**: API keys in `.env` only; rotate quarterly

### Performance Standards

- **Check-in API**: < 500ms latency under 10 requests/sec (Phase 1 target)
- **Dashboard searches**: < 500ms for 10,000 records (Phase 2)
- **Analytics queries**: < 2s for 50,000 subscriptions (Phase 5)
- **Real-time updates**: < 1s WebSocket latency (Phase 4)
- **Bulk operations**: CSV export of 500 members < 2s (Phase 2)

---

## Getting Started: Immediate Next Steps

1. **Review this plan** with the team; confirm phase sequencing with project stakeholders
2. **Create GitHub issues** for each phase (epic) and user story (19 total issues)
3. **Assign Phase 1 tasks** to infrastructure/backend team; start RBAC + payment gateway + terminal provisioning
4. **Parallel setup:** provision databases, configure Redis, set up S3 buckets, obtain payment gateway sandbox credentials
5. **Launch Phase 1** target: complete by end of Sprint 1 (2 weeks); deploy to staging
6. **Phase 2 kickoff:** once Phase 1 complete; begin member management components simultaneously with Phase 1's remaining CI/CD work

---

## Appendix: Glossary of Components & Models

| Component / Model         | Ownership                          | File Location                                                |
| ------------------------- | ---------------------------------- | ------------------------------------------------------------ |
| `Member`                  | Member lifecycle + subscriptions   | `/app/Models/Member.php`                                     |
| `Subscription`            | Enrollment, status transitions     | `/app/Models/Subscription.php`                               |
| `Plan`                    | Membership offerings               | `/app/Models/Plan.php`                                       |
| `NfcCard`                 | Card assignment + status tracking  | `/app/Models/NfcCard.php`                                    |
| `HikvisionTerminal`       | Terminal provisioning + webhooks   | `/app/Models/HikvisionTerminal.php`                          |
| `CheckInEvent`            | Immutable access log               | `/app/Models/CheckInEvent.php`                               |
| `CourseSession`           | Class scheduling                   | `/app/Models/CourseSession.php`                              |
| `Booking`                 | Class/court reservations           | `/app/Models/Booking.php`                                    |
| `User` (admin/manager)    | Admin authentication + RBAC        | `/app/Models/User.php`                                       |
| `MemberTable`             | Member list UI                     | `/app/Livewire/Admin/Members/MemberTable.php`                |
| `MemberDetailPanel`       | Member profile UI                  | `/app/Livewire/Admin/Members/MemberDetailPanel.php`          |
| `NfcCardAssignment`       | Card assignment UI                 | `/app/Livewire/Admin/Members/NfcCardAssignment.php`          |
| `SubscriptionEnrollment`  | Enrollment UI                      | `/app/Livewire/Admin/Subscriptions/SubscriptionEnrollment.php` |
| `SubscriptionSuspension`  | Suspension UI                      | `/app/Livewire/Admin/Subscriptions/SubscriptionSuspension.php` |
| `CheckInMonitor`          | Real-time check-in UI              | `/app/Livewire/Admin/AccessControl/CheckInMonitor.php`       |
| `AuditLog`                | Immutable event log UI             | `/app/Livewire/Admin/AccessControl/AuditLog.php`             |
| `AntiPassbackAlerts`      | Fraud alert UI                     | `/app/Livewire/Admin/AccessControl/AntiPassbackAlerts.php`   |
| `RevenueAnalytics`        | Revenue charts UI                  | `/app/Livewire/Admin/Analytics/RevenueAnalytics.php`         |
| `OccupancyHeatmap`        | Occupancy chart UI                 | `/app/Livewire/Admin/Analytics/OccupancyHeatmap.php`         |
| `CourseSchedule`          | Class scheduling UI                | `/app/Livewire/Admin/Scheduling/CourseSchedule.php`          |

---

**Last Updated:** March 29, 2026  
**Status:** Ready for Implementation  
**Next Review:** After Phase 1 completion
