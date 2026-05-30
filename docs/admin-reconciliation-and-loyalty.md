## Admin: Reconciliation & Loyalty Features

This document summarizes the admin-facing reconciliation and loyalty features recently added, how to use them in the dashboard, and where the implementation lives in the codebase.

## Overview

- Payment reconciliation audit trail: a new `payment_reconciliations` table stores verification/refund events (who, when, amount, metadata).
- Admin UI additions:
  - Reservation detail: payments list → `History` opens a modal showing paginated reconciliation events and a `Toggle Payload` button to view provider payload, plus `Copy Payload` to copy JSON to clipboard.
  - Reconciliation Manager: `/admin/reconciliations` — search, filter by type (Verified/Refunded), pagination, `Queue Export` and `Export CSV` actions.
  - Member detail: supports `tab=loyalty` query param so you can open member detail directly on the Loyalty tab (used by the reservation flyout "View Loyalty History" button).
- Export pipeline: a `ReconciliationExport` task is created when you queue an export; `GenerateReconciliationExportJob` generates the CSV and updates the export record; admins can download finished exports from the Recent Exports list.

## Key pages & actions

- Reservation detail (admin): `Admin → Reservations` → open a reservation → Payment History panel
  - Verify / Refund actions (admin only).
  - `History` button: opens reconciliation modal for that payment (paginated). Toggle payload and Copy Payload to copy JSON.
  - `View Loyalty History` button: navigates to the Members dashboard with the member selected and `tab=loyalty` to show loyalty info.

- Reconciliation Manager: `GET /admin/reconciliations` (admin)
  - Search by admin name or metadata, filter by `Verified` or `Refunded`.
  - `Queue Export`: creates a background export task (stored in `reconciliation_exports`) and dispatches `GenerateReconciliationExportJob`.
  - `Export CSV`: immediate ad-hoc CSV export of the current filters (server-streamed download).
  - Recent Exports list shows last 10 export tasks with download links once finished.

## Implementation notes & file map

- Database
  - `database/migrations/2026_05_30_000002_create_payment_reconciliations_table.php` — reconciliation table
  - `database/migrations/2026_05_30_000005_create_reconciliation_exports_table.php` — export tasks table

- Models
  - `app/Models/PaymentReconciliation.php` — reconciliation record model
  - `app/Models/ReconciliationExport.php` — export task model

- Jobs & Services
  - `app/Jobs/ReconcilePaymentJob.php` — webhook reconciliation; now writes `PaymentReconciliation` rows for paid/refunded webhooks
  - `app/Jobs/GenerateReconciliationExportJob.php` — generates CSV and updates `ReconciliationExport`

- Livewire & Views
  - `app/Livewire/Admin/Reservations/ReservationManager.php` — reservation detail UI; opens reconciliation modal for payments; creates reconciliation rows on admin verify/refund
  - `resources/views/livewire/admin/reservations/reservation-manager.blade.php` — reservation detail modal, reconciliation modal with payload toggle & copy
  - `app/Livewire/Admin/Payments/ReconciliationManager.php` — admin reconciliation listing with search/type/pagination and export queueing
  - `resources/views/livewire/admin/payments/reconciliation-manager.blade.php` — reconciliation manager UI and recent exports list

- Controller & Routes
  - `app/Http/Controllers/Admin/ReconciliationController.php` — CSV export stream and download endpoint
  - `routes/admin.php` — new routes: `admin.reconciliations.index`, `admin.reconciliations.export`, `admin.reconciliations.download`

- Tests
  - `tests/Feature/Jobs/ReconcilePaymentJobTest.php` — job reconciliation tests
  - `tests/Feature/Livewire/Admin/ReservationHistoryModalTest.php` — modal displays reconciliation entries
  - `tests/Feature/Livewire/Admin/ReconciliationManagerTest.php` — reconciliation manager list/renders

## How export works

1. Admin clicks `Queue Export` in the Reconciliation Manager.
2. Livewire creates a `ReconciliationExport` record with `status=queued` and the current filters.
3. `GenerateReconciliationExportJob` runs (dispatched) and generates CSV in `storage/app/exports/...` and updates the export record to `status=finished` and sets `path`.
4. The Reconciliation Manager shows the recent exports list where admin can click `Download` to retrieve the generated CSV.

Notes:
- For very large datasets, the job uses chunking to avoid memory pressure. If you expect extremely large exports (multi-GB), consider offloading to an external storage service (S3) and using async workers with more resources.

## How to test manually

1. Run migrations: `php artisan migrate`
2. Seed or create test payment_reconciliations entries.
3. Visit the admin UI: `/admin/reconciliations` and try search, filter, queue export, then download once finished.
4. Reservation flow: open a reservation and click `History` to view reconciliation events; click `Toggle Payload` and `Copy Payload` to inspect provider data.
5. From reservation panel, click `View Loyalty History` to open the member detail with the loyalty tab active.

## Development & troubleshooting

- Job logs: check `storage/logs/laravel.log` for job errors when exports fail.
- Export files are stored under `storage/app/exports/` (path recorded on `ReconciliationExport.path`). Ensure `storage` is writable by the queue worker.
- The websocket/livewire UI relies on the `wire:navigate` behavior; if navigating from the reservation flyout to members behaves differently in your environment, try reloading or using the direct `/admin/members?member=<id>&tab=loyalty` URL.

## Next improvements (optional)

- Add syntax highlighting for JSON payload view (Prism.js / highlight.js) with copy & expand/collapse UI.
- Provide job status notifications (toasts) when export finishes (via events/broadcasts) so admin doesn't have to poll.
- Add scheduled cleanup for old exports and retention policy.

---

If you want, I can implement any of the optional improvements above next (syntax highlighting + copy feedback, job notifications, or S3-backed exports).
