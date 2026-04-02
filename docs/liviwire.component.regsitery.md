# Bourgo Arena — Livewire Component Registry

> **Purpose:** Defines what each Livewire component owns, emits, listens to, and must NOT do.
> Copilot must not duplicate logic across components. Each concern lives in exactly one place.
> **Stack:** Livewire 3 · Flux UI · Laravel 11

---

## Component Ownership Rules

- Each component owns **one domain concern** — it never reaches into another component's domain
- State is **not shared via properties** between sibling components — use events
- Database queries happen **only in the component that owns that model**
- Flux UI is the only permitted UI component library — no raw Tailwind utility soup

---

## Admin Panel Components

---

### `Admin\Members\MemberTable`

**File:** `app/Livewire/Admin/Members/MemberTable.php`
**View:** `resources/views/livewire/admin/members/member-table.blade.php`

**Owns:** Member list, search, filter, pagination, CSV export
**Does NOT:** Modify members, handle subscriptions, assign cards

| Property        | Type      | Description                  |
| --------------- | --------- | ---------------------------- |
| `$search`       | string    | Filter by name, email, phone |
| `$statusFilter` | string    | Filter by member status      |
| `$planFilter`   | int\|null | Filter by plan ID            |
| `$perPage`      | int       | Default 50                   |

| Action        | Description                                    |
| ------------- | ---------------------------------------------- |
| `exportCsv()` | Triggers CSV download of current filtered list |

| Emits             | When                                       |
| ----------------- | ------------------------------------------ |
| `member-selected` | When a row is clicked — passes `$memberId` |

| Listens          | From                                          |
| ---------------- | --------------------------------------------- |
| `member-updated` | `MemberDetailPanel` — refreshes list          |
| `card-assigned`  | `NfcCardAssignment` — refreshes status column |

---

### `Admin\Members\MemberDetailPanel`

**File:** `app/Livewire/Admin/Members/MemberDetailPanel.php`

**Owns:** Single member profile view and inline admin actions
**Does NOT:** Show subscription payment forms, handle NFC card scan

| Property    | Type   | Description                     |
| ----------- | ------ | ------------------------------- |
| `$memberId` | int    | Set via `member-selected` event |
| `$member`   | Member | Loaded on mount                 |

| Action            | Description                            |
| ----------------- | -------------------------------------- |
| `suspend()`       | Sets member status to `suspended`      |
| `activate()`      | Sets member status to `active`         |
| `resetPassword()` | Triggers password reset email          |
| `delete()`        | Soft-deletes member after confirmation |

| Emits            | When                    |
| ---------------- | ----------------------- |
| `member-updated` | After any status change |

| Listens           | From          |
| ----------------- | ------------- |
| `member-selected` | `MemberTable` |

---

### `Admin\Members\AddMemberForm`

**File:** `app/Livewire/Admin/Members/AddMemberForm.php`
**View:** `resources/views/livewire/admin/members/add-member-form.blade.php`

**Owns:** Manual member creation workflow from dashboard and onboarding dispatch orchestration
**Does NOT:** Assign NFC cards, mutate subscriptions, or manage terminal check-ins

| Property            | Type   | Description                               |
| ------------------- | ------ | ----------------------------------------- |
| `$name`             | string | Member full name                          |
| `$email`            | string | Unique member email                       |
| `$phone`            | string | Unique member phone (SMS channel target)  |
| `$dateOfBirth`      | string | Date of birth                             |
| `$gender`           | string | `male` / `female`                         |
| `$emergencyContact` | string | Optional emergency contact                |

| Action     | Description                                                                 |
| ---------- | --------------------------------------------------------------------------- |
| `create()` | Creates pending member, creates onboarding token, queues email/sms/push jobs |

| Emits            | When                                         |
| ---------------- | -------------------------------------------- |
| `member-created` | On success — passes `memberId`               |
| `toast`          | Success and failure UX feedback to ToastManager |

---

### `Admin\Members\NfcCardAssignment`

**File:** `app/Livewire/Admin/Members/NfcCardAssignment.php`

**Owns:** NFC card UID assignment to a member
**Does NOT:** Read from terminal hardware directly — UID is entered manually or via scan input

| Property      | Type   | Description                     |
| ------------- | ------ | ------------------------------- |
| `$memberId`   | int    |                                 |
| `$uid`        | string | Card UID input                  |
| `$cardStatus` | string | `active` / `suspended` / `lost` |

| Action     | Description                                                         |
| ---------- | ------------------------------------------------------------------- |
| `assign()` | Validates UID uniqueness, assigns card, logs event, notifies member |

| Emits           | When                                          |
| --------------- | --------------------------------------------- |
| `card-assigned` | On successful assignment — passes `$memberId` |

---

### `Admin\Subscriptions\SubscriptionTable`

**File:** `app/Livewire/Admin/Subscriptions/SubscriptionTable.php`
**View:** `resources/views/livewire/admin/subscriptions/subscription-table.blade.php`

**Owns:** Subscription index browsing (search/filter/sort/pagination) and navigation entry points to dedicated subscription detail/actions pages
**Does NOT:** Perform enrollment, suspend/resume/transfer actions, or send reminders

| Property         | Type      | Description                                                   |
| ---------------- | --------- | ------------------------------------------------------------- |
| `$search`        | string    | Member/plan search term                                       |
| `$statusFilter`  | string    | `active` / `suspended` / `expired` / `transferred`           |
| `$planFilter`    | int\|null | Optional plan filter                                          |
| `$sortBy`        | string    | Sort column (`member`, `plan`, `status`, `starts_at`, `ends_at`) |
| `$sortDirection` | string    | `asc` / `desc`                                                |

| Action           | Description                                             |
| ---------------- | ------------------------------------------------------- |
| `sort($column)`  | Toggles sort direction on supported columns             |
| `refreshTable()` | Refreshes the table when subscription mutation events fire |

| Listens                    | Source Components                            |
| -------------------------- | -------------------------------------------- |
| `subscription-created`     | `Admin\Subscriptions\SubscriptionEnrollment` |
| `subscription-updated`     | `Admin\Subscriptions\SubscriptionSuspension` |

---

### `Admin\Subscriptions\SubscriptionEnrollment`

**File:** `app/Livewire/Admin/Subscriptions/SubscriptionEnrollment.php`

**Owns:** New subscription creation and payment recording (standalone enroll page or preselected member context)
**Does NOT:** Handle renewals (that's mobile), handle suspension logic

| Property            | Type         | Description                   |
| ------------------- | ------------ | ----------------------------- |
| `$memberId`         | int          |                               |
| `$planId`           | int\|null    | Selected plan                 |
| `$startsAt`         | date         | Default today                 |
| `$paymentMethod`    | string       | `cash` / `konnect` / `paymee` |
| `$paymentReference` | string\|null | Gateway transaction ID        |

| Action     | Description                                                                         |
| ---------- | ----------------------------------------------------------------------------------- |
| `enroll()` | Creates subscription, records payment, generates PDF receipt, updates member status |

| Emits                  | When                            |
| ---------------------- | ------------------------------- |
| `subscription-created` | On success — passes `$memberId` |

---

### `Admin\Subscriptions\SubscriptionSuspension`

**File:** `app/Livewire/Admin/Subscriptions/SubscriptionSuspension.php`

**Owns:** Suspend, resume, and transfer subscription flows
**Does NOT:** Modify payment records, create new subscriptions

| Property              | Type      | Description                    |
| --------------------- | --------- | ------------------------------ |
| `$subscriptionId`     | int       |                                |
| `$reason`             | string    | `medical` / `travel` / `other` |
| `$transferToMemberId` | int\|null | For transfer flow              |

| Action       | Description                                             |
| ------------ | ------------------------------------------------------- |
| `suspend()`  | Freezes `days_remaining`, sets `suspended_at`           |
| `resume()`   | Recomputes `ends_at`, clears suspension fields          |
| `transfer()` | Admin approval + identity check, reassigns subscription |

| Emits                  | When             |
| ---------------------- | ---------------- |
| `subscription-updated` | After any action |

---

### `Admin\AccessControl\CheckInMonitor`

**File:** `app/Livewire/Admin/AccessControl/CheckInMonitor.php`

**Owns:** Real-time check-in feed, occupancy counter, terminal health
**Does NOT:** Modify check-in records, trigger door commands

| Property            | Type       | Description                  |
| ------------------- | ---------- | ---------------------------- |
| `$recentEvents`     | Collection | Last 20 CheckInEvent records |
| `$occupancyCount`   | int        | Members currently inside     |
| `$terminalStatuses` | array      | Online/offline per terminal  |
| `$alertCount`       | int        | Denials in last 5 min        |

**Real-time:** Uses `#[On('echo:checkins,CheckInProcessed')]` — Laravel Echo + WebSocket
Latency target: dashboard updates within 1 second of tap

| Action               | Description                              |
| -------------------- | ---------------------------------------- |
| `acknowledgeAlert()` | Clears active alert state for a terminal |

---

### `Admin\AccessControl\AuditLog`

**File:** `app/Livewire/Admin/AccessControl/AuditLog.php`

**Owns:** Immutable check-in event log with filters and export
**Does NOT:** Allow any modification of records

| Property        | Type   | Description                   |
| --------------- | ------ | ----------------------------- |
| `$dateFrom`     | date   |                               |
| `$dateTo`       | date   |                               |
| `$memberSearch` | string |                               |
| `$resultFilter` | string | `authorized` / `denied` / all |

| Action        | Description                   |
| ------------- | ----------------------------- |
| `exportCsv()` | Downloads filtered log as CSV |
| `exportPdf()` | Generates PDF of filtered log |

---

### `Admin\AccessControl\AntiPassbackAlerts`

**File:** `app/Livewire/Admin/AccessControl/AntiPassbackAlerts.php`

**Owns:** Suspicious event alert queue and admin review actions
**Does NOT:** Modify CheckInEvent records directly

| Property  | Type       | Description                      |
| --------- | ---------- | -------------------------------- |
| `$alerts` | Collection | Suspicious events pending review |

| Action               | Description                            |
| -------------------- | -------------------------------------- |
| `dismiss($cardUid)`  | Clears suspicious flag, resets counter |
| `escalate($cardUid)` | Permanently suspends card              |

| Listens                   | From                             |
| ------------------------- | -------------------------------- |
| `anti-passback-triggered` | Broadcast event from CheckIn job |

---

### `Admin\Analytics\RevenueAnalytics`

**File:** `app/Livewire/Admin/Analytics/RevenueAnalytics.php`

**Owns:** Revenue charts, subscription KPIs, export
**Does NOT:** Show individual member payment records

| Property     | Type  | Description                            |
| ------------ | ----- | -------------------------------------- |
| `$dateFrom`  | date  |                                        |
| `$dateTo`    | date  |                                        |
| `$chartData` | array | Prepared for Flux/Chart.js consumption |

| Action        | Description |
| ------------- | ----------- |
| `exportPdf()` |             |
| `exportCsv()` |             |

---

### `Admin\Analytics\OccupancyHeatmap`

**File:** `app/Livewire/Admin/Analytics/OccupancyHeatmap.php`

**Owns:** Hourly occupancy grid data (7 days × 06:00–23:00)
**Does NOT:** Show individual member data

| Property       | Type      | Description                    |
| -------------- | --------- | ------------------------------ |
| `$heatmapData` | array     | `[day][hour] => occupancy_pct` |
| `$dayFilter`   | int\|null | 0=Mon … 6=Sun                  |

---

### `Admin\Terminals\TerminalList`

**File:** `app/Livewire/Admin/Terminals/TerminalList.php`

**Owns:** Terminal provisioning list, token management, connectivity status
**Does NOT:** Send ISAPI commands directly

| Property     | Type       | Description                      |
| ------------ | ---------- | -------------------------------- |
| `$terminals` | Collection | All non-decommissioned terminals |

| Action              | Description                                                |
| ------------------- | ---------------------------------------------------------- |
| `provision()`       | Creates terminal record, generates unique `api_token`      |
| `revokeToken($id)`  | Regenerates `api_token`, invalidates old token immediately |
| `decommission($id)` | Sets status to `decommissioned`                            |

---

### `Admin\Scheduling\CourseSchedule`

**File:** `app/Livewire/Admin/Scheduling/CourseSchedule.php`

**Owns:** Weekly class schedule CRUD and enrollment management
**Does NOT:** Handle member bookings directly — reads booking counts only

| Property           | Type                | Description                |
| ------------------ | ------------------- | -------------------------- |
| `$sessions`        | Collection          | All active course sessions |
| `$selectedSession` | CourseSession\|null | For detail/edit panel      |

| Action                         | Description                                       |
| ------------------------------ | ------------------------------------------------- |
| `createSession()`              | Creates new recurring CourseSession               |
| `cancelSession($id)`           | Marks cancelled, dispatches push notification job |
| `removeEnrollment($bookingId)` | Cancels a specific booking                        |

---

## Shared / Utility Components

### `Shared\Notifications\ToastManager`

**File:** `app/Livewire/Shared/Notifications/ToastManager.php`
**Owns:** Application-wide toast notifications
**Usage:** All components dispatch `$this->dispatch('toast', message: '...', type: 'success')`
**Does NOT:** Own business logic — display only

---

### `Shared\Modals\ConfirmDialog`

**File:** `app/Livewire/Shared/Modals/ConfirmDialog.php`
**Owns:** Reusable confirmation modal (destructive actions)
**Usage:** `$this->dispatch('confirm', action: 'delete', targetId: $id)`

---

## Event Bus Reference

| Event Name                       | Payload              | Dispatched By          | Consumed By        |
| -------------------------------- | -------------------- | ---------------------- | ------------------ |
| `member-selected`                | `memberId`           | MemberTable            | MemberDetailPanel  |
| `member-updated`                 | `memberId`           | MemberDetailPanel      | MemberTable        |
| `card-assigned`                  | `memberId`           | NfcCardAssignment      | MemberTable        |
| `subscription-created`           | `memberId`           | SubscriptionEnrollment | MemberDetailPanel  |
| `subscription-updated`           | `subscriptionId`     | SubscriptionSuspension | MemberDetailPanel  |
| `anti-passback-triggered`        | `cardUid`, `count`   | Broadcast (Laravel)    | AntiPassbackAlerts |
| `echo:checkins,CheckInProcessed` | full event payload   | Broadcast (Laravel)    | CheckInMonitor     |
| `toast`                          | `message`, `type`    | Any component          | ToastManager       |
| `confirm`                        | `action`, `targetId` | Any component          | ConfirmDialog     
---

## Event Bus Reference

| Event Name | Payload | Dispatched By | Consumed By |
|---|---|---|---|
| `member-selected` | `memberId` | MemberTable | MemberDetailPanel |
| `member-updated` | `memberId` | MemberDetailPanel | MemberTable |
| `card-assigned` | `memberId` | NfcCardAssignment | MemberTable |
| `subscription-created` | `memberId` | SubscriptionEnrollment | MemberDetailPanel |
| `subscription-updated` | `subscriptionId` | SubscriptionSuspension | MemberDetailPanel |
| `anti-passback-triggered` | `cardUid`, `count` | Broadcast (Laravel) | AntiPassbackAlerts |
| `echo:checkins,CheckInProcessed` | full event payload | Broadcast (Laravel) | CheckInMonitor |
| `toast` | `message`, `type` | Any component | ToastManager |
| `confirm` | `action`, `targetId` | Any component | ConfirmDialog |
