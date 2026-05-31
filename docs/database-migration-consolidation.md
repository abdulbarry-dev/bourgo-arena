# Database Migration Consolidation

This repository was cleaned so the final schema is represented directly in the canonical table-creation migrations instead of through a chain of temporary alter/rename/drop migrations.

## Consolidated Tables

### `members`

The final `members` schema was folded into [2026_03_29_145639_create_members_table.php](../database/migrations/2026_03_29_145639_create_members_table.php).

Consolidated into the base create migration:

- account verification fields
- onboarding completion fields
- OTP tracking fields
- loyalty points
- archived/deletion state
- `status`/`state` compatibility columns

Removed history-only member migrations:

- [2026_05_10_173935_add_loyalty_points_to_members_table.php](../database/migrations/2026_05_10_173935_add_loyalty_points_to_members_table.php)
- [2026_05_13_232351_add_account_verification_fields_to_members_table.php](../database/migrations/2026_05_13_232351_add_account_verification_fields_to_members_table.php)
- [2026_05_14_121518_add_pin_to_members_table.php](../database/migrations/2026_05_14_121518_add_pin_to_members_table.php)
- [2026_05_14_230837_rename_status_to_state_on_members_table.php](../database/migrations/2026_05_14_230837_rename_status_to_state_on_members_table.php)
- [2026_05_16_210936_add_is_archived_to_members_table.php](../database/migrations/2026_05_16_210936_add_is_archived_to_members_table.php)
- [2026_05_17_164529_add_scheduled_for_deletion_at_to_members_table.php](../database/migrations/2026_05_17_164529_add_scheduled_for_deletion_at_to_members_table.php)
- [2026_05_29_162443_drop_pin_from_members_table.php](../database/migrations/2026_05_29_162443_drop_pin_from_members_table.php)

### `courses`

The final `courses` schema was folded into [2026_04_05_132500_create_courses_table.php](../database/migrations/2026_04_05_132500_create_courses_table.php).

Consolidated into the base create migration:

- `category`
- `icon`

Removed redundant course migrations:

- [2026_05_12_125232_add_category_and_icon_to_courses_table.php](../database/migrations/2026_05_12_125232_add_category_and_icon_to_courses_table.php)
- [2026_05_28_000000_drop_color_from_courses_table.php](../database/migrations/2026_05_28_000000_drop_color_from_courses_table.php)

### `event_matches`

The final `event_matches` schema was folded into [2026_05_28_154251_create_event_matches_table.php](../database/migrations/2026_05_28_154251_create_event_matches_table.php).

Consolidated into the base create migration:

- `scheduled_at`

Removed redundant event migration:

- [2026_05_28_200000_add_scheduled_at_to_event_matches_table.php](../database/migrations/2026_05_28_200000_add_scheduled_at_to_event_matches_table.php)

## Final Schema Notes

- Foreign keys were preserved in the consolidated create migrations.
- Existing nullable/default behavior was carried into the final schema where it is required by the current application code.
- Temporary history files for pin setup and later cleanup were removed because the mobile onboarding flow no longer uses terminal access.

## Risk Notes

- Existing databases should continue to work because the application code now matches the cleaned schema.
- Fresh installs now build the final schema directly without replaying the old patch migrations.
- If any external environment still depends on the deleted migration history, it should be rebased onto the new base create files before the next fresh deployment.