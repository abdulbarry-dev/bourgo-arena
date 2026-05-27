
# Removal Summary — NFC / Hikvision / Terminal Access

This file documents the changes made to remove NFC card management, Terminal Access member features, and Hikvision integrations from the codebase. It is a concise, production-ready summary for reviewers and deployers.

## What was removed (high level)
- NFC card assignment and management features (models, controllers, views, Livewire UI bits).
- Terminal access / check-in endpoints and controllers tied to hardware integrations.
- Hikvision integration code and related event listeners/jobs.

## Files and places touched (representative)
- Livewire admin views: resources/views/livewire/admin/members/* — removed NFC card UI blocks and assign-card actions.
- Livewire components: app/Livewire/Admin/Members/MemberDetailPanel.php, app/Livewire/Admin/Members/MemberTable.php — removed references to `nfcCard` and related eager loads where applicable.
- Tests: updated CSV export expectation in tests/Feature/Performance/MemberTablePerformanceTest.php; removed/adjusted NFC-related assertions in tests/Feature/Livewire/MemberDetailPanelTest.php.
- Routes: routes/admin.php and routes/api.php were inspected and cleaned of member/assign-card or member/nfc API groups where appropriate.
- Seeders / factories: removed references to NfcCard where they appeared in member seeders.

## Dependencies
- No composer or npm packages were removed by automation in this pass. If you previously added a vendor package solely for Hikvision or NFC handling, remove it manually and run `composer update`.

## Database / Migrations
- The physical `nfc_cards` table and migrations should already have been removed earlier in the branch. If you still have an `nfc_cards` migration in `database/migrations`, run the usual rollback or create a new migration to drop the table:

  - Recommended safe procedure before deploy:

```bash
# Backup DB
mysqldump -u user -p database_name > backup-before-nfc-removal.sql

# If the original migration still exists and is in a branch, rollback safely in staging
php artisan migrate:rollback --path=/database/migrations/xxxx_drop_nfc_cards.php

# Or create explicit drop migration
php artisan make:migration drop_nfc_cards_table --table=nfc_cards
# then implement Schema::dropIfExists('nfc_cards'); and migrate
php artisan migrate
```

## Tests & Fixes applied
- Ran the full test suite: all tests currently pass locally (277 passed at time of run).
- Fixed failing tests by:
  - Removing the `"NFC Status"` CSV column expectation in `tests/Feature/Performance/MemberTablePerformanceTest.php`.
  - Removing UI expectations for assign-card routes and card details from Livewire snapshot/assertions and restoring member info partials.

## Breaking changes & notes for deployers
- Any external integrations or devices relying on NFC/Hikvision endpoints will stop working; ensure those integrations are decommissioned first.
- API clients that called `member/nfc` or `members/{member}/assign-card` routes must be updated; they will return 404 after deploy if not present.
- Admin UI: the Assign Card flow, NFC status, and card UID fields are no longer available.

## Rollback plan
- If you need to revert, revert the branch or re-add the removed migrations/models/views from git history. Ensure you restore the `nfc_cards` table migration and run `php artisan migrate`.

## Next steps (recommended)
- Review repository for any remaining vendor packages specific to Hikvision/NFC and remove them manually.
- Update architecture/docs to note the removal (e.g., docs/product.backlog.md).
- Run staging deploy with DB backup and confirm no external systems require the removed endpoints.

If you want, I can now: create a commit with these changes, open a PR, or generate a detailed changelog listing every file touched. Which would you like next?
