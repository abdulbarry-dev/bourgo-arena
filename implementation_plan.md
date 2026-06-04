# Card-View Redesign — Plans, Courses, Activities & Courts, Services

Replace all four `<table>` layouts with responsive card grids while keeping every action, modal, flyout, filter, and pagination intact.

---

## Scope

| Page | Current file(s) changed | Component class touched? |
|---|---|---|
| Plans | `plans/partials/table.blade.php` | ❌ No |
| Courses | `courses/partials/courses-table.blade.php` | ❌ No |
| Activities & Courts | `activities/activity-manager.blade.php` (inline table) | ❌ No |
| Services | `services/service-manager.blade.php` (inline table) | ❌ No |

No PHP class files will be modified. The Livewire component data (paginator, counts, models) is already correct — only view templates change.

---

## Card Design System

Each card follows the same anatomy across all four pages:

```
┌──────────────────────────────────────┐
│  [Icon/Image]   Name          [···]  │  ← header row with actions dropdown
│                 sub-label            │
├──────────────────────────────────────┤
│  stat 1 │ stat 2 │ stat 3            │  ← stat chips row
├──────────────────────────────────────┤
│  [Status badge]         [CTA button] │  ← footer
└──────────────────────────────────────┘
```

- **Grid**: `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4` inside the existing page wrapper — no wrapper component changes
- **Card shell**: white background, `rounded-2xl border border-zinc-200 shadow-sm hover:shadow-md transition` with dark-mode variants
- **Skeleton loaders**: pulsing card-shaped placeholders during Livewire loading (replaces row skeletons)
- **Empty state**: existing `<x-ui.dashboard.empty-state>` reused unchanged
- **Pagination**: rendered below the card grid using the existing `$paginator->links()` call — same as today
- **Confirmation modals**: kept in place, emitted per-card (same `Flux.modal('confirm-*-id')` pattern)

---

## Per-Entity Card Specification

### 1 · Plans (`plans/partials/table.blade.php`)

**Available data per card:** `name`, `service->name`, `price`, `duration_days`, `has_all_courses`, `courses_count`, `subscriptions_count`, `is_archived`

| Zone | Content |
|---|---|
| Icon | `clipboard-document-list` in sky-blue circle |
| Title | `$plan->name` |
| Sub-label | Service badge (blue) or _N/A_ |
| Stat chips | 💰 `price` TND · ⏱ `duration_days` days · 👥 `subscriptions_count` subs |
| Courses chip | "All-Inclusive" badge **or** "`N` Courses" |
| Status badge | Active (green) / Archived (zinc) |
| Actions | **⋯ dropdown** → View Details · Edit · Archive/Reactivate · Delete |
| CTA footer button | → "View Details" triggers `openDetailFlyout(id)` |

---

### 2 · Courses (`courses/partials/courses-table.blade.php`)

**Available data per card:** `name`, `image_url`, `service->name`, `status`, `sessions_count`, `category`

| Zone | Content |
|---|---|
| Header image | Course image (160 px tall `object-cover rounded-t-2xl`) **or** gradient placeholder with `book-open` icon |
| Title | `$course->name` |
| Sub-label | Category tag (if present) · Service badge |
| Stat chip | 📅 `sessions_count` sessions |
| Status badge | Active / Inactive / Archived |
| Actions | **⋯ dropdown** → View Details · Edit · Archive/Restore · Delete |
| CTA footer button | → "View Details" triggers `openViewFlyout(id)` |

> Courses with `image_url` get a real image header; others get an emerald-to-zinc gradient placeholder. This leverages the image column that was already in the table.

---

### 3 · Activities & Courts (`activities/activity-manager.blade.php`)

**Available data per card:** `title`, `service->name`, `category`, `base_price`, `currency`, `slots_count`, `is_active`, `images[]`

| Zone | Content |
|---|---|
| Header image | First image from `images[]` **or** orange gradient + `building-storefront` icon |
| Title | `$activity->title` |
| Sub-label | Category tag · Service badge |
| Stat chips | 💰 `base_price` TND · 🗓 `slots_count` slots |
| Status badge | Active (green) / Inactive (red) |
| Actions | **⋯ dropdown** → View Court · Manage Slots · Edit Activity |
| CTA footer button | → "Manage Slots" navigates to `admin.activities.slots` |

---

### 4 · Services (`services/service-manager.blade.php`)

**Available data per card:** `name`, `image_url`, `status`, `slug`, `description`, `plans_count`, `courses_count`, `events_count`, `activities_count`

| Zone | Content |
|---|---|
| Header image | Service image (140 px tall `object-cover rounded-t-2xl`) **or** rose gradient placeholder |
| Title | `$service->name` |
| Sub-label | `slug` in monospace |
| Stat chips | Plans · Courses · Events · Activities counts (only shown if > 0) |
| Status badge | Active / Inactive / Archived |
| Actions | **⋯ dropdown** → View Details · Edit · Archive/Restore · Delete |
| CTA footer button | → "View Details" triggers `openViewFlyout(id)` |

---

## Loading Skeletons

Replace `<flux:skeleton class="h-12 w-full" />` × 3 with a 6-card pulse grid:

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ ░░░░░░░░░░░ │ │ ░░░░░░░░░░░ │ │ ░░░░░░░░░░░ │
│ ░░░░░ ░░░  │ │ ░░░░░ ░░░  │ │ ░░░░░ ░░░  │
│             │ │             │ │             │
│ ░░ ░░░ ░░  │ │ ░░ ░░░ ░░  │ │ ░░ ░░░ ░░  │
└──────────────┘ └──────────────┘ └──────────────┘
```

---

## Pagination Placement

```blade
{{-- Card grid --}}
<div class="grid ...">
  @foreach (...) ... @endforeach
</div>

{{-- Pagination below cards --}}
@if ($paginator->hasPages())
  <div class="mt-6 flex justify-center">
    {{ $paginator->links() }}
  </div>
@endif
```

---

## What Does NOT Change

- All PHP Livewire component classes (no changes)
- All flyout / modal Blade partials (reused unchanged via `@include` or inline)
- All filter bars (`x-ui.filter-row`)
- All page headers (`x-ui.dashboard.page-header`)
- All route / policy logic
- Existing confirmation modal templates (rendered after each card, same `name="confirm-*-id"` pattern)

---

## Files Modified

| File | Change |
|---|---|
| [`plans/partials/table.blade.php`](file:///home/vortex/Desktop/Projects/bourgo-arena/resources/views/livewire/admin/plans/partials/table.blade.php) | Replace `<table>` with card grid; keep modals |
| [`courses/partials/courses-table.blade.php`](file:///home/vortex/Desktop/Projects/bourgo-arena/resources/views/livewire/admin/courses/partials/courses-table.blade.php) | Replace `<table>` with image-headed card grid; keep modals |
| [`activities/activity-manager.blade.php`](file:///home/vortex/Desktop/Projects/bourgo-arena/resources/views/livewire/admin/activities/activity-manager.blade.php) | Replace inline `<table>` section with card grid; flyouts stay |
| [`services/service-manager.blade.php`](file:///home/vortex/Desktop/Projects/bourgo-arena/resources/views/livewire/admin/services/service-manager.blade.php) | Replace inline `<table>` section with card grid; flyouts stay |

**4 view files. No PHP changes. No new files.**

---

## Verification Plan

1. Run `php artisan test --compact` to confirm no regressions
2. Manually verify:
   - Cards render on each page with real data
   - Filters, search, and sorting still work  
   - Pagination appears and navigates correctly
   - Every action dropdown item still works (view/edit/archive/delete)
   - Dark mode renders correctly
   - Mobile (1-column) and desktop (3-column) grid respond correctly
