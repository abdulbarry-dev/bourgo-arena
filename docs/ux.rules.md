# UX Rules: Admin Dashboard (Laravel + Livewire + Flux UI)

## Usage Contract (For AI Development)

- Apply these rules by default for all admin dashboard UI work.
- Favor consistency over novelty.
- Keep interactions fast, clear, and reversible.
- Use Flux UI components first; only create custom UI when Flux cannot solve the use case.

## Layout & Navigation

- Keep a persistent sidebar and top header for all authenticated admin pages.
- Keep primary actions in predictable locations: page header right side or table toolbar.
- Use one primary page title and one short supporting subtitle.
- Keep page content within a centered max-width container; avoid full-width dense layouts.
- Preserve navigation state across pages (active nav item, filters, tabs) where possible.
- Do not hide critical navigation behind hover-only interactions.

Example (page shell):

```blade
<x-layouts::app :title="__('Members')">
 <div class="flex flex-col gap-4">
  <div class="flex items-center justify-between">
   <flux:heading size="lg">{{ __('Members') }}</flux:heading>
   <flux:button variant="primary">{{ __('Add Member') }}</flux:button>
  </div>
 </div>
</x-layouts::app>
```

## Animations & Transitions

- Use subtle transitions only (150-250ms).
- Animate state changes, not decorative elements.
- Use transitions for modals, dropdowns, and row expansion only.
- Do not animate large layout shifts on data refresh.
- Respect reduced motion preferences.

Livewire-specific:

- Use `wire:transition` sparingly for conditionally rendered blocks.
- Avoid stacking multiple transition systems on the same element.

## Loading States

- Every async interaction must have a visible loading state.
- Use local loading indicators near the triggering element.
- Keep previous data visible during refresh when safe.
- Use skeleton loaders for first-load content blocks.
- Disable submit buttons during in-flight requests.

Livewire/Flux examples:

```blade
<flux:button wire:click="save" wire:loading.attr="disabled">
 <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
 <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
</flux:button>
```

```blade
<div wire:loading wire:target="loadMembers">
 <flux:skeleton class="h-16 w-full" />
</div>
```

## Empty States

- Replace blank areas with clear empty-state blocks.
- Include: what happened, why, and the next best action.
- Provide one primary CTA and one optional secondary action.
- Keep copy short and operational.

Example empty-state copy:

- Title: "No active memberships yet"
- Message: "Create a membership plan, then assign it to members."
- CTA: "Create Plan"

## Error Handling

- Show user-safe error messages; never expose raw exceptions.
- Display inline errors near the related UI control.
- Use page-level error banners for non-field failures.
- For destructive actions, show confirmation before execution and result feedback after completion.
- Keep error pages branded and consistent with dashboard layout.

Livewire rules:

- Use validation errors for form fields.
- Use event/flash feedback for action-level failures.
- Ensure failed actions restore actionable UI state.

## Forms & Feedback

- Use one column by default; switch to two columns only on wide screens and only when fields are logically paired.
- Always show labels; placeholders are supplementary only.
- Mark required fields clearly and consistently.
- Validate on submit; use live validation only for high-value inputs.
- Keep submit/cancel actions sticky at form bottom for long forms.
- After successful submit, show clear success feedback and next step.

Flux field pattern:

```blade
<flux:field>
 <flux:label>{{ __('Email') }}</flux:label>
 <flux:input type="email" wire:model="email" />
 <flux:error name="email" />
</flux:field>
```

## Data Display (Tables, Charts, Lists)

- Default to tables for operational data, cards for summaries, charts for trends.
- Keep table row height compact but readable.
- Keep key columns visible; move secondary metadata into expandable details.
- Use sortable columns only where sorting impacts decisions.
- Provide search and filter controls above the table.
- Preserve filter/sort state during pagination and refresh.
- Show row-level actions in a consistent position (usually right-aligned).
- Use color as a secondary cue; always pair with text labels.

Livewire table rules:

- Add stable `wire:key` for dynamic rows.
- Update partial regions instead of rerendering full pages when possible.
- Debounce text filters to reduce request noise.

## Accessibility & Responsiveness

- Meet WCAG AA contrast for text and controls.
- Ensure full keyboard navigation for sidebar, menus, forms, and modals.
- Keep visible focus states on all interactive elements.
- Add meaningful `aria-label` values to icon-only buttons.
- Do not rely on color-only status meaning.
- Support dashboard usage from 320px width upward.
- On small screens, prioritize task completion over feature density.

## Performance-Aware Livewire Rules

- Prefer localized component updates over whole-page rerenders.
- Avoid unnecessary polling; use polling only for real-time operational areas.
- Use lazy/deferred loading for below-the-fold sections.
- Minimize concurrent requests from multiple controls.
- Keep payloads small: paginate large datasets and request only needed fields.

## Visual Style Rules (Clean Minimal Dashboard)

- Use neutral surfaces, restrained accent usage, and clear typographic hierarchy.
- Keep spacing rhythm consistent (`gap-2`, `gap-4`, `gap-6`, `gap-8`).
- Prefer rounded corners and subtle borders over heavy shadows.
- Limit each screen to one primary action style.
- Keep icon usage purposeful; avoid decorative icon clutter.

## Rule Priority (Conflict Resolution)

- Priority 1: Clarity and task completion
- Priority 2: Accessibility and responsiveness
- Priority 3: Performance and interaction smoothness
- Priority 4: Visual polish

When rules conflict, follow higher priority first.
