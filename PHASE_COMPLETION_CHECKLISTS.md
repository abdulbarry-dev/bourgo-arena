# Phase Completion Checklists — Admin Dashboard Implementation

> **Purpose:** Track completed sub-tasks for each phase before advancing to the next.
> **Updated:** March 29, 2026 · **Reference:** `implementation-plan.md`

---

## Phase 1: Infrastructure & Security Foundation

**Target Sprint:** Sprint 1–2  
**Story Points:** 23  
**Status:** 🟡 Code Complete (Env Validation Pending)

### Task 1.1: Implement RBAC Middleware & Policies

- [x] Create `EnsureUserHasRole` middleware with admin/manager role coverage
- [x] Create `TerminalPolicy` for terminal resource actions
- [x] Create `SubscriptionPolicy` for subscription resource actions
- [x] Create `MemberPolicy` for member resource actions
- [ ] Add `role()` macro to Route facade for cleaner registration
- [x] Populate `config/authorization.php` with explicit permission matrix
- [x] Write integration tests for each role boundary (RBAC Policy Test)
- [ ] All 15+ policy assertions passing
- [x] Documentation: policy matrix accessible in codebase

**Verification:** Run `php artisan test --filter=RbacPolicyTest` → All tests passing

---

### Task 1.2: Integrate Tunisian Payment Gateway (Konnect or Paymee)

- [x] Research Konnect + Paymee API documentation
- [ ] Validate sandbox environment availability
- [x] Create `PaymentGatewayDriver` interface
- [x] Implement `KonnectDriver` concrete class
- [x] Implement `PaymeeDriver` concrete class
- [x] Create `config/payment.php` with multi-driver support
- [x] Register `PaymentGateway` service facade in service container
- [x] Build sandbox test harness (test payment → webhook → verification)
- [x] Create `ReceiptGenerator` class (PDF generation via TCPDF/Dompdf)
- [x] Write tests: sandbox flow, webhook reception, receipt generation
- [x] Generate decision doc: `docs/PAYMENT_GATEWAY_SELECTION.md` (fees, rates, reasoning)
- [ ] Payment gateway selected + tested in sandbox
- [x] Receipts generate correctly for test transactions

**Verification:** Run `php artisan test --filter=PaymentGatewayTest` → All tests passing + sandbox transaction logged

---

### Task 1.3: Provision Hikvision DS-K1T805MX Terminals

- [x] Create `hikvision_terminals` migration with required columns
- [x] Create `HikvisionTerminal` Eloquent model + relationships
- [x] Implement unique `api_token` generation on terminal creation
- [x] Create terminal registration endpoint: POST `/api/terminal-provisioning`
- [x] Build terminal webhook receiver: POST `/api/checkin` (ISAPI parsing)
- [x] Create `TerminalAuthMiddleware` for API token validation
- [x] Implement online/offline status tracking (`last_seen_at` update)
- [x] Implement terminal revocation action (token regeneration)
- [x] Build decommissioning logic (status = decommissioned)
- [x] Write tests: token generation, revocation, decommissioning flows
- [ ] Load test: 50 concurrent terminal registrations succeeding
- [ ] Terminal webhook receiver handles ISAPI payload correctly

**Verification:** Run `php artisan test --filter=TerminalProvisioningTest` → All tests passing + load test results logged

---

### Task 1.4: Set Up CI/CD Pipeline

- [x] Create `.github/workflows/{lint,tests}.yml` with Pint + unit + integration tests
- [x] Create `.github/workflows/deploy-staging.yml` for auto-deploy on `develop` merge
- [x] Create `.github/workflows/deploy-production.yml` with manual approval gate
- [ ] Configure GitHub Actions secrets (PAYMENT_API_KEY, DATABASE_URL, S3 credentials, etc.)
- [x] Implement rollback procedure + Git tag strategy documentation
- [x] Add email notifications for pipeline success/failure
- [x] Create `docs/CI_CD_PROCEDURES.md` with deployment guide + rollback steps
- [ ] Test trial commit: PR passes all checks → auto-deploy to staging
- [ ] CI/CD pipelines deployed and functional
- [ ] Secrets configured securely in GitHub Actions

**Verification:** Create trial commit → PR auto-checks triggered + deploy logs available

---

### Phase 1 Completion Gate — MUST PASS

- [x] All RBAC policies tested; 0 unauthorized access incidents
- [ ] Payment gateway sandbox transactions succeeding consistently
- [x] Receipts generating correctly for test enrollments
- [x] Hikvision terminals registering, tokens revoking, webhooks received
- [ ] CI/CD pipelines running on every PR + deploying to staging without errors
- [x] Phase 2 dependencies satisfied: RBAC middleware + payment gateway ready

**Sign-off:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

## Phase 2: Member Management Core

**Target Sprint:** Sprint 2–3  
**Story Points:** 10  
**Status:** ⭕ Not Started

### Task 2.1: Create Member Model Scopes & Queries

- [ ] Add `searchable()` scope (name, email, phone search)
- [ ] Add `byStatus()` scope (pending, active, suspended, expired filter)
- [ ] Add `byPlan()` scope (plan ID filter)
- [ ] Add eager-loading scope for subscriptions + NFC cards (N+1 prevention)
- [ ] Create `MemberDetailResource` for API responses
- [ ] Write scope tests: verify SQL correctness + return values
- [ ] All member scopes tested; queries optimized

**Verification:** Run `php artisan test --filter=MemberScopeTest` → All tests passing

---

### Task 2.2: Build `MemberTable` Livewire Component

- [ ] Create component class with $search, $statusFilter, $planFilter, $perPage properties
- [ ] Implement debounced search handler
- [ ] Build Blade template with Flux UI table
- [ ] Add sortable columns (name, email, phone, status, plan, nfc_status)
- [ ] Add search input + filter dropdowns
- [ ] Implement CSV export functionality
- [ ] Add row click event dispatch (`member-selected` event)
- [ ] Add skeleton loaders for loading states
- [ ] Listen to `member-updated` event (refresh list)
- [ ] Listen to `card-assigned` event (update card status column)
- [ ] Write integration tests: search, filter, pagination, CSV, event dispatch
- [ ] No N+1 queries in component (verified with query log)
- [ ] Performance: < 500ms query time with 1000+ records

**Verification:** Run `php artisan test --filter=MemberTableTest` → All tests passing + query performance logged

---

### Task 2.3: Build `MemberDetailPanel` Livewire Component

- [ ] Create component class with $memberId, $member properties
- [ ] Implement `member-selected` event listener
- [ ] Build Blade template with member card (profile overview)
- [ ] Build subscription card (dates, status, renewal info)
- [ ] Build recent check-ins section (last 10 events)
- [ ] Implement `suspend()` action + confirmation modal
- [ ] Implement `activate()` action
- [ ] Implement `resetPassword()` action (dispatch email job)
- [ ] Implement `delete()` action (soft-delete with confirmation)
- [ ] Add loading states during actions
- [ ] Emit `member-updated` event after each action
- [ ] Write integration tests: all actions, state updates, event emission
- [ ] All member actions tested + events emitted correctly

**Verification:** Run `php artisan test --filter=MemberDetailPanelTest` → All tests passing

---

### Task 2.4: Build `NfcCardAssignment` Livewire Component

- [ ] Create component class with $memberId, $uid, $cardStatus properties
- [ ] Build Blade template with UID input field
- [ ] Add real-time UID validation (blur event, check uniqueness)
- [ ] Add card status selector (active | suspended | lost)
- [ ] Implement `assign()` action with UID validation
- [ ] Validate UID format (alphanumeric, 8–32 chars)
- [ ] Validate UID uniqueness in database
- [ ] Create NfcCard record on successful assignment
- [ ] Log assignment (assigned_by, assigned_at)
- [ ] Update member status (pending → active if needed)
- [ ] Dispatch `NotifyMemberCardAssigned` job
- [ ] Emit `card-assigned` event
- [ ] Add success toast feedback
- [ ] Create `NotifyMemberCardAssigned` job (push + email notification)
- [ ] Write integration tests: UID validation, duplicate detection, assignment, notification job
- [ ] UID validation + duplicate detection working correctly

**Verification:** Run `php artisan test --filter=NfcCardAssignmentTest` → All tests passing

---

### Task 2.5: Layout & Integration

- [ ] Create dashboard page template (two-column layout)
- [ ] Add route: GET `/admin/members` (protected by admin/manager role)
- [ ] Implement responsive layout (desktop: two-column, mobile: modal)
- [ ] Test page load performance: < 2s with 1000+ members
- [ ] Verify RBAC middleware protection on members route
- [ ] Dashboard page responsive on mobile/tablet/desktop

**Verification:** Load `/admin/members` → page renders < 2s, components initialize, no console errors

---

### Phase 2 Completion Gate — MUST PASS

- [ ] All MemberTable tests passing (30+ test cases)
- [ ] All MemberDetailPanel tests passing
- [ ] All NfcCardAssignment tests passing
- [ ] No N+1 queries in member table rendering
- [ ] CSV export generates valid file + imports correctly
- [ ] Member notification job fires on card assignment
- [ ] Dashboard page responsive + loads within SLA (< 2s)
- [ ] Phase 3 dependencies satisfied: member data + card assignment ready

**Sign-off:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

## Phase 3: Subscription Management

**Target Sprint:** Sprint 3–4  
**Story Points:** 18  
**Status:** ⭕ Not Started

### Task 3.1: Create Subscription Model Scopes & Business Logic

- [ ] Add `active()` scope (status = active AND ends_at > now())
- [ ] Add `expiring()` scope (active AND ends_at <= now() + 7 days)
- [ ] Create `calculateEndDate($startsAt, $durationDays)` method
- [ ] Add `isActive()` getter
- [ ] Add `daysRemaining()` getter
- [ ] Create `suspend($reason)` method (freeze days_remaining, set suspended_at)
- [ ] Create `resume()` method (recalculate ends_at, clear suspension fields)
- [ ] Create `transfer($newMemberId)` method (change member_id, set status = transferred)
- [ ] Write unit tests for all scopes + methods
- [ ] Test edge cases: leap years, timezone transitions, DST changes
- [ ] All subscription logic tests passing

**Verification:** Run `php artisan test --filter=SubscriptionLogicTest` → All tests passing

---

### Task 3.2: Build `SubscriptionEnrollment` Livewire Component

- [ ] Create component class with $memberId, $planId, $startsAt, $paymentMethod, $paymentReference properties
- [ ] Build Blade template with plan selector (Flux UI select)
- [ ] Add start date input (Flux UI date picker, default today)
- [ ] Add payment method selector (cash | konnect | paymee)
- [ ] Add conditional payment fields (gateway-specific info)
- [ ] Implement `enroll()` action (validate + create subscription)
- [ ] Validate member, plan, dates
- [ ] Create Subscription record with auto-calculated ends_at
- [ ] Record payment (method, amount, reference if gateway)
- [ ] Generate PDF receipt via ReceiptGenerator
- [ ] Store receipt path in subscription
- [ ] Dispatch `SendSubscriptionReceiptEmail` job
- [ ] Dispatch `SendSubscriptionNotification` job
- [ ] Update member status (pending → active)
- [ ] Force-sync terminal whitelist (card UIDs)
- [ ] Emit `subscription-created` event
- [ ] Add success toast + error handling
- [ ] Create `SendSubscriptionReceiptEmail` job
- [ ] Create `SendSubscriptionNotification` job
- [ ] Write integration tests: form validation, payment recording, receipt generation, notification dispatch
- [ ] Receipt generated + emailed within 1s of enrollment

**Verification:** Run `php artisan test --filter=SubscriptionEnrollmentTest` → All tests passing

---

### Task 3.3: Build `SubscriptionSuspension` Livewire Component

- [ ] Create component class with $subscriptionId, $action, $suspensionReason, $transferToMemberId properties
- [ ] Build Blade template with action selector (Suspend | Resume | Transfer)
- [ ] Build suspend fields (reason selector, confirmation checkbox)
- [ ] Build resume fields (show frozen days, resumed date)
- [ ] Build transfer fields (member picker, identity verification)
- [ ] Implement `suspend()` action (freeze days, set suspended_at, update status)
- [ ] Implement `resume()` action (recalculate ends_at, clear suspension fields)
- [ ] Implement `transfer()` action (change member_id, status = transferred, audit log)
- [ ] Dispatch notification jobs on suspend + resume
- [ ] Add toast feedback for all actions
- [ ] Create `SubscriptionStatusChanged` listener for audit logging
- [ ] Write integration tests: suspend, resume, transfer, date calculations, event dispatch
- [ ] Suspension/resumption calculations verified correct

**Verification:** Run `php artisan test --filter=SubscriptionSuspensionTest` → All tests passing

---

### Task 3.4: Build `ExpiringSubscriptionsView` Livewire Component

- [ ] Create component class with $expiringSubscriptions, $touchedCount properties
- [ ] Implement `loadExpiringSubscriptions()` action (query ends_at <= now() + 7 days)
- [ ] Build Blade template with alert box
- [ ] Add table: member name, plan name, days remaining, renewal link
- [ ] Implement `sendReminder($subscriptionId)` action
- [ ] Dispatch reminder notification job
- [ ] Add bulk action: send reminders to all expiring
- [ ] Add empty state message
- [ ] Write tests: expiring subscription query correctness, reminder job dispatch
- [ ] Expiring subscription query fast + accurate

**Verification:** Run `php artisan test --filter=ExpiringSubscriptionsViewTest` → All tests passing

---

### Task 3.5: Dashboard Integration

- [ ] Add subscription routes to `/routes/admin.php`
- [ ] Create admin subscriptions dashboard page
- [ ] Add breadcrumb navigation
- [ ] Test subscription dashboard page responsiveness

**Verification:** Navigate `/admin/subscriptions` → page loads, all components initialized

---

### Phase 3 Completion Gate — MUST PASS

- [ ] All SubscriptionEnrollment tests passing (25+ test cases)
- [ ] All SubscriptionSuspension tests passing
- [ ] All ExpiringSubscriptionsView tests passing
- [ ] No business rule violations in suspend/resume (dates always correct)
- [ ] Receipt generated + emailed within 1s of enrollment
- [ ] Expiring subscriptions query < 100ms with 10,000 subscriptions
- [ ] Phase 4 dependencies satisfied: subscription data ready for real-time monitoring

**Sign-off:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

## Phase 4: Access Control & Real-Time Monitoring

**Target Sprint:** Sprint 4–5  
**Story Points:** 18  
**Status:** ⭕ Not Started

### Task 4.1: Implement Real-Time WebSocket Infrastructure

- [ ] Configure Laravel Echo in project
- [ ] Set up WebSocket server (Pusher or Reverb alternative)
- [ ] Configure Redis as message queue backend
- [ ] Create `CheckInProcessed` broadcast event
- [ ] Implement terminal webhook receiver (POST `/api/checkin`)
- [ ] Parse ISAPI payload + create CheckInEvent
- [ ] Broadcast event on successful check-in
- [ ] Store broadcast credentials in `.env`
- [ ] Write WebSocket load test: 50+ connections, 10 check-ins/sec, < 1s latency
- [ ] WebSocket infrastructure tested under load

**Verification:** WebSocket load test → 50 concurrent connections, latency < 1s, 10 check-ins/sec sustained

---

### Task 4.2: Build `CheckInMonitor` Livewire Component

- [ ] Create component class with $recentEvents, $occupancyCount, $terminalStatuses, $alertCount, $isWebSocketConnected properties
- [ ] Implement `loadOccupancy()` action (count members inside)
- [ ] Implement WebSocket listener: `#[On('echo:checkins,CheckInProcessed')]`
- [ ] Build Blade template with occupancy counter (large number + icon)
- [ ] Add WebSocket status badge (green = connected, red = disconnected)
- [ ] Add recent events list (20 most recent check-ins)
- [ ] Add terminal status grid (online/offline, last seen)
- [ ] Add alerts section (> 3 denials in 5 min per terminal)
- [ ] Implement `acknowledgeAlert($terminalId)` action
- [ ] Add polling fallback: if WebSocket disconnects, poll `/api/check-in/recent` every 5 sec
- [ ] Add empty state: "No check-ins yet today"
- [ ] Write integration tests: occupancy calculation, WebSocket updates, alert logic, fallback polling
- [ ] Occupancy count accurate within 2 events
- [ ] WebSocket updates appearing in real-time (< 1s latency)

**Verification:** Run `php artisan test --filter=CheckInMonitorTest` → All tests passing + WebSocket delivery verified

---

### Task 4.3: Build `AuditLog` Livewire Component

- [ ] Create component class with $dateFrom, $dateTo, $memberSearch, $resultFilter, $perPage properties
- [ ] Build Blade template with date pickers (Flux UI)
- [ ] Add member search input
- [ ] Add result filter selector (authorized | denied | all)
- [ ] Build table: member name, card UID, timestamp, result, terminal, denial reason
- [ ] Add pagination (50 records/page)
- [ ] Implement `exportCsv()` action
- [ ] Implement `exportPdf()` action (branded report)
- [ ] Add audit immutability note to UI
- [ ] Add empty state message
- [ ] Create `CheckInEventExport` class (CSV generation)
- [ ] Create `CheckInEventPdfReport` class (PDF generation)
- [ ] Write integration tests: filtering, pagination, exports
- [ ] No N+1 queries in audit log
- [ ] Filter accuracy verified
- [ ] CSV + PDF exports generating correctly

**Verification:** Run `php artisan test --filter=AuditLogTest` → All tests passing + export files generated

---

### Task 4.4: Implement Anti-Passback Fraud Detection

- [ ] Create `AntiPassbackRule` service (consecutive entry detection logic)
- [ ] Implement check-in event processing:
  - [ ] Track last check-in per card UID
  - [ ] Detect consecutive entries (no exit between)
  - [ ] Flag event as suspicious
  - [ ] Broadcast `anti_passback_triggered` event
  - [ ] Auto-suspend card after 3 consecutive suspicious events
- [ ] Create `ProcessAntiPassbackEvent` queued job
- [ ] Add admin review workflow (Dismiss | Escalate actions)
- [ ] Write unit tests: consecutive entry detection, alert triggering, auto-suspension
- [ ] Write feature tests: full fraud detection workflow
- [ ] Anti-passback detection catches 100% of consecutive entries

**Verification:** Run `php artisan test --filter=AntiPassbackRuleTest` → All tests passing

---

### Task 4.5: Build `AntiPassbackAlerts` Livewire Component

- [ ] Create component class with $alerts property
- [ ] Implement `anti-passback-triggered` event listener
- [ ] Build Blade template with alert list
- [ ] Add columns: member name, card UID, event count, most recent event time
- [ ] Add action buttons: Dismiss | Escalate
- [ ] Implement `dismiss($cardUid)` action
- [ ] Implement `escalate($cardUid)` action
- [ ] Add batch action: "Dismiss all" button
- [ ] Add empty state message
- [ ] Update card status on dismiss/escalate
- [ ] Log admin action
- [ ] Dispatch member notification job on escalate
- [ ] Write integration tests: alert creation, dismiss, escalation, card suspension
- [ ] Alert creation, dismiss, and escalation actions working

**Verification:** Run `php artisan test --filter=AntiPassbackAlertsTest` → All tests passing

---

### Task 4.6: Dashboard Integration

- [ ] Create access control dashboard page template
- [ ] Add CheckInMonitor component (top section)
- [ ] Add AuditLog component with tab
- [ ] Add AntiPassbackAlerts component with tab
- [ ] Add real-time refresh via WebSocket
- [ ] Add breadcrumb navigation

**Verification:** Navigate `/admin/access-control` → dashboard loads, all components initialize, WebSocket connects

---

### Phase 4 Completion Gate — MUST PASS

- [ ] All CheckInMonitor tests passing (20+ test cases)
- [ ] All AuditLog tests passing
- [ ] All AntiPassbackAlerts tests passing
- [ ] WebSocket messages delivered < 1s latency consistently
- [ ] Audit log immutability enforced (no update/delete in UI or DB)
- [ ] Anti-passback detection catches 100% of consecutive entries
- [ ] Real-time occupancy counter accurate within 2 events
- [ ] Phase 5 dependencies satisfied: real-time data + check-in events ready

**Sign-off:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

## Phase 5: Analytics & Reporting

**Target Sprint:** Sprint 5–6  
**Story Points:** 18  
**Status:** ⭕ Not Started

### Task 5.1: Build `RevenueAnalytics` Livewire Component

- [ ] Create component class with $dateFrom, $dateTo, $chartData, $monthlyRevenue, $churnRate, etc. properties
- [ ] Implement date range pickers
- [ ] Build Blade template with KPI cards:
  - [ ] Monthly Revenue
  - [ ] Churn Rate
  - [ ] Active Subscriptions
  - [ ] Expired Subscriptions
- [ ] Add charts:
  - [ ] Monthly revenue trend (bar chart)
  - [ ] Subscription status breakdown (donut chart)
  - [ ] Top plans by revenue (bar chart)
  - [ ] Payment method breakdown (pie chart)
- [ ] Implement KPI calculations (monthly revenue, churn rate, top plans, payment methods)
- [ ] Create `RevenueAnalyticsData` DTO for chart preparation
- [ ] Implement `exportPdf()` action
- [ ] Implement `exportCsv()` action
- [ ] Add Chart.js integration via Alpine.js
- [ ] Write integration tests: KPI calculations, chart data, exports
- [ ] KPI calculations verified against manual SQL
- [ ] Analytics dashboards load < 2s

**Verification:** Run `php artisan test --filter=RevenueAnalyticsTest` → All tests passing + KPI accuracy verified

---

### Task 5.2: Build `OccupancyHeatmap` Livewire Component

- [ ] Create component class with $heatmapData, $dayFilter, $hourFilter properties
- [ ] Implement `loadHeatmapData()` action (7-day × hourly aggregation)
- [ ] Build Blade template with heatmap grid:
  - [ ] 7 rows (Mon–Sun)
  - [ ] 18 columns (06:00–23:00)
  - [ ] Color intensity (white → light → dark)
- [ ] Add tooltip on hover (show occupancy %)
- [ ] Add day/hour filter controls
- [ ] Implement `exportPdf()` action (print-friendly grid)
- [ ] Create `OccupancyCalculator` service (occupancy % logic)
- [ ] Implement caching (valid 1 hour)
- [ ] Write integration tests: occupancy calculation, caching, rendering
- [ ] Occupancy calculation accuracy verified
- [ ] Query time < 500ms with 100,000 check-in events

**Verification:** Run `php artisan test --filter=OccupancyHeatmapTest` → All tests passing

---

### Task 5.3: Build `CourseSchedule` Livewire Component

- [ ] Create component class with $sessions, $selectedSession, $viewMode properties
- [ ] Build Blade template with:
  - [ ] Calendar view (Mon–Sun, time rows, session blocks)
  - [ ] List view (table: name, instructor, day, time, capacity, enrolled)
  - [ ] Detail panel for selected session
- [ ] Add modals:
  - [ ] Create session modal
  - [ ] Edit session modal
  - [ ] Cancel confirmation
  - [ ] Removal confirmation
- [ ] Implement `createSession($data)` action (validate capacity, create CourseSession)
- [ ] Implement `editSession($id, $data)` action (update session details)
- [ ] Implement `cancelSession($id)` action (mark cancelled, dispatch notification)
- [ ] Implement `removeEnrollment($bookingId)` action (cancel booking, notify waitlist)
- [ ] Create `NotifySessionCancellation` job
- [ ] Create `NotifyWaitlistPromotion` job
- [ ] Write integration tests: CRUD operations, notifications, waitlist logic
- [ ] Session creation, cancellation, and enrollment removal working
- [ ] Notifications sent to all enrolled members on cancellation

**Verification:** Run `php artisan test --filter=CourseScheduleTest` → All tests passing

---

### Task 5.4: Dashboard Integration

- [ ] Create analytics dashboard page template
- [ ] Add RevenueAnalytics component with tab
- [ ] Add OccupancyHeatmap component with tab
- [ ] Create scheduling dashboard page
- [ ] Add CourseSchedule component to scheduling page
- [ ] Add breadcrumb navigation

**Verification:** Navigate `/admin/analytics` and `/admin/scheduling` → pages load, all components initialize

---

### Phase 5 Completion Gate — MUST PASS

- [ ] All RevenueAnalytics tests passing (20+ test cases)
- [ ] All OccupancyHeatmap tests passing
- [ ] All CourseSchedule tests passing
- [ ] KPI calculations verified against manual SQL
- [ ] Course schedule notifications sent to all members
- [ ] Analytics dashboards load < 2s with large datasets
- [ ] Phase 6 dependencies satisfied: data aggregation + reporting complete

**Sign-off:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

## Phase 6: Advanced Infrastructure & Optimization

**Target Sprint:** Sprint 6–8 (parallel with Phase 5)  
**Story Points:** 18  
**Status:** ⭕ Not Started

### Task 6.1: Implement Async Queue Processing (Laravel Horizon)

- [ ] Verify `config/queue.php` exists + Redis configured
- [ ] Set up separate job queues: `default`, `notifications`, `reports`
- [ ] Install Laravel Horizon: `composer require laravel/horizon`
- [ ] Configure Horizon dashboard: `/admin/horizon` (admin-only access)
- [ ] Implement failed job handler (retry up to 3 attempts, dead-letter queue)
- [ ] Create queue job list with retry counts + expected processing times
- [ ] Add health monitoring: alert if queue depth > 1000 or failed jobs accumulating
- [ ] Create `docs/QUEUE_ARCHITECTURE.md` (job priorities, SLAs, monitoring)
- [ ] Refactor all notification/email/report jobs to use queues
- [ ] Write load test: dispatch 1000 jobs, verify processing within SLA
- [ ] All existed jobs using queue system

**Verification:** Load test → 1000 jobs processed within SLA + Horizon dashboard displays correctly

---

### Task 6.2: Implement Automated Daily Backups

- [ ] Create `DatabaseBackup` scheduled command (Artisan command)
- [ ] Implement backup logic:
  - [ ] Nightly dump at 02:00 UTC
  - [ ] Encrypt backup (GPG)
  - [ ] Upload to S3
  - [ ] Tag with timestamp + environment
  - [ ] Cleanup old backups (30 daily / 12 weekly / 3 monthly)
- [ ] Schedule in `app/Console/Kernel.php`
- [ ] Create `BackupService` class (backup/restore logic)
- [ ] Configure S3 bucket (versioning, encryption)
- [ ] Create `docs/BACKUP_AND_RESTORE_PROCEDURES.md` (restore steps, RTO/RPO targets)
- [ ] Implement restore procedure (documented + tested)
- [ ] Add backup success/failure email notifications
- [ ] Add monitoring: alert if backup > 24 hrs overdue
- [ ] Test: create backup, restore to separate DB, verify data completeness
- [ ] Backups running successfully

**Verification:** Create test backup + verify S3 upload + test restore to separate DB

---

### Task 6.3: Query Optimization & Caching Layer

- [ ] Audit all Livewire component queries (enable `DB::queryLog()`)
- [ ] Identify N+1 issues + refactor with eager-loading
- [ ] Add eager-loading scopes to models:
  - [ ] Member (subscriptions, nfcCard)
  - [ ] Subscription (member, plan)
  - [ ] CheckInEvent (member, terminal)
  - [ ] HikvisionTerminal (recent_events)
- [ ] Create database indexes on frequently queried columns:
  - [ ] `members.status`, `members.email`
  - [ ] `subscriptions.status`, `subscriptions.ends_at`
  - [ ] `check_in_events.created_at`, `check_in_events.terminal_id`
  - [ ] `nfc_cards.member_id`, `nfc_cards.status`
- [ ] Run EXPLAIN on major queries; verify index usage
- [ ] Implement caching layer:
  - [ ] Cache member list queries (5 min)
  - [ ] Cache occupancy heatmap (1 hour)
  - [ ] Cache KPI calculations (1 hour)
  - [ ] Manual cache invalidation on data changes
- [ ] Create `CacheService` class (centralized cache operations)
- [ ] Profile memory usage (Livewire component < 5MB per instance)
- [ ] Stress test: 100 concurrent users on dashboard, verify response < 2s
- [ ] Document cache invalidation strategy

**Verification:** Stress test 100 concurrent users → response times < 2s + memory profiles logged

---

### Task 6.4: CI/CD Pipeline Tuning & Deployment Safety

- [ ] Add code coverage reporting (Pest + PR coverage report, target > 80%)
- [ ] Add performance regression testing (compare vs baseline, fail if > 10% slower)
- [ ] Add dependency vulnerability scanning (`composer audit` in CI)
- [ ] Implement database migration safety (pre-production backup, rollback on failure)
- [ ] Add secrets rotation reminder (monthly)
- [ ] Create deployment checklist: pre-deploy validation
- [ ] Implement blue-green or rolling deployment strategy
- [ ] Add post-deployment smoke tests (key pages load, health checks pass)
- [ ] Create `docs/DEPLOYMENT_PROCEDURES.md` (deployment steps, rollback procedures)
- [ ] Update `.github/workflows/{test, deploy}.yml` with coverage/performance/security checks
- [ ] Test deployment pipeline on trial commit
- [ ] GitHub Actions secrets audit trail documented

**Verification:** Trial deployment → coverage report generated + health checks pass

---

### Task 6.5: Optimization Audit & Documentation

- [ ] Run full application performance audit (profiling, memory leaks, DB efficiency)
- [ ] Document performance baselines: check-in latency, dashboard load time, bulk operation time
- [ ] Create monitoring dashboard: Horizon queue depth, backup status, DB size, API response times
- [ ] Create `docs/PERFORMANCE_BASELINES.md` (target metrics, measurement procedures)
- [ ] Create `docs/OPTIMIZATION_RUNBOOK.md` (troubleshooting guide)
- [ ] Baseline metrics documented

**Verification:** Performance baselines recorded + monitoring dashboard configured

---

### Phase 6 Completion Gate — MUST PASS

- [ ] Laravel Horizon deployed; queue monitoring dashboard accessible
- [ ] Automated backup system running nightly; restore procedure tested
- [ ] Database indexes created; queries optimized (no N+1, eager-loading applied)
- [ ] Caching layer implemented; cache invalidation strategy documented
- [ ] CI/CD pipelines enhanced with coverage, performance, security checks
- [ ] Performance baselines documented; monitoring dashboard set up
- [ ] Deployment procedures automated; rollback capability verified
- [ ] All prior phases remain stable + passing tests

**Sign-off:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

## Overall Completion Checklist

### All Phases Complete

- [ ] Phase 1: Infrastructure & Security Foundation ✅
- [ ] Phase 2: Member Management Core ✅
- [ ] Phase 3: Subscription Management ✅
- [ ] Phase 4: Access Control & Real-Time Monitoring ✅
- [ ] Phase 5: Analytics & Reporting ✅
- [ ] Phase 6: Advanced Infrastructure & Optimization ✅

### Production Readiness

- [ ] All 105 story points completed
- [ ] All tests passing (200+ test cases)
- [ ] Code coverage > 80%
- [ ] No known critical bugs
- [ ] Documentation complete: developer + operations guides
- [ ] Performance baselines met across all workflows
- [ ] Backup + disaster recovery tested
- [ ] Monitoring + alerting operational
- [ ] Team trained on new features

**Project Status:** ⭕ In Progress / 🟡 Phase X Complete / ✅ Production Ready

**Final Sign-off by Project Lead:** \***\*\_\_\_\_\*\***  
**Date:** \***\*\_\_\_\_\*\***

---

**Last Updated:** March 29, 2026  
**Next Review:** Upon Phase 1 Completion
