# Fix Confirm Modal on Dashboard Export Actions

## Root Cause Analysis

The confirm modal (`x-ui.confirm-modal` → `<flux:modal>`) uses `wire:model.self="showExportConfirmModal"` to sync open/close state with the Livewire property. Although this pattern works in the subscription table (same Flux version, same component), it fails on the dashboard page. The suspected cause is **Alpine initialization order**: the modal is nested inside `@can` + `x-ui.dashboard.page-wrapper` + `<div wire:key>` — deeper than the subscription table's root-level modal. Flux's Alpine `fluxModal()` component may not properly bind to the Livewire property when the modal is nested in multiple wrapper layers.

## Fix: Dual-Trigger Approach (Client + Server)

### 1. `app/Livewire/Admin/Analytics/Dashboard.php`

Replace the modal methods with Livewire `$dispatch()` events that fire browser custom events. This lets Alpine set the property from the client side instantly (no server roundtrip needed):

**`openExportConfirmModal()`** — dispatch `show-export-modal` instead of setting the property:
```php
public function openExportConfirmModal(string $format): void
{
    if (! in_array($format, ['csv', 'pdf'], true)) {
        return;
    }

    $this->exportFormat = $format;
    $this->dispatch('show-export-modal');
}
```

**`closeExportConfirmModal()`** — add dispatch alongside property set:
```php
public function closeExportConfirmModal(): void
{
    $this->showExportConfirmModal = false;
    $this->dispatch('hide-export-modal');
}
```

**`confirmExport()`** — add dispatch alongside property set:
```php
public function confirmExport()
{
    $this->showExportConfirmModal = false;
    $this->dispatch('hide-export-modal');

    if ($this->exportFormat === 'pdf') {
        return $this->exportPdf();
    }

    return $this->exportCsv();
}
```

Note: `openExportConfirmModal` does NOT set `$showExportConfirmModal = true` anymore — the Alpine event handler handles that client-side. The property is set to `false` by default and only changes when Alpine touches it.

### 2. `resources/views/livewire/admin/analytics/dashboard.blade.php`

Add Alpine event listeners on the `<x-ui.confirm-modal>` element:

```blade
<x-ui.confirm-modal
    wire:model.self="showExportConfirmModal"
    x-on:show-export-modal.window="$wire.showExportConfirmModal = true"
    x-on:hide-export-modal.window="$wire.showExportConfirmModal = false"
    ...
/>
```

Flow:
1. User clicks "Export CSV" → `wire:click="openExportConfirmModal('csv')"` → server sets `$exportFormat = 'csv'` and dispatches `show-export-modal` browser event
2. Alpine catches `show-export-modal` on `window` → calls `$wire.showExportConfirmModal = true`
3. Flux modal detects the property via `wire:model.self` → opens the modal dialog via native `<dialog>.showModal()`
4. For close: same flow but with `hide-export-modal` → `$wire.showExportConfirmModal = false`

### Files to Change

| File | Change |
|------|--------|
| `app/Livewire/Admin/Analytics/Dashboard.php` | Replace 3 methods: `openExportConfirmModal`, `closeExportConfirmModal`, `confirmExport` |
| `resources/views/livewire/admin/analytics/dashboard.blade.php` | Add Alpine `x-on:show-export-modal.window` and `x-on:hide-export-modal.window` on confirm-modal |

No other files affected. Run `php artisan test --compact --filter=Dashboard` + full suite after applying.
