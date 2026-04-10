# View Architecture and Structural Patterns

This document defines the standard design patterns for organizing Blade and Livewire views within the application. Adhering to these patterns ensures maintainability, predictable file discovery, and a consistent developer experience as the platform grows.

## Core Directory Roles

### Layouts (`resources/views/layouts/`)
Defines the high-level application shells.
- `app.blade.php`: The primary authenticated application wrapper.
- `sidebar.blade.php`: Layout specifically containing the sidebar navigation and main content area.
- `auth.blade.php`: Specialized layout for login, registration, and password recovery pages.

### Livewire Components (`resources/views/livewire/`)
Houses all reactive component views, organized by administrative or functional domains.
- Example: `livewire/admin/courses/` or `livewire/settings/`.

### Shared Utilities (`resources/views/livewire/shared/`)
Contains Livewire components used globally across different domains, such as notification managers, search bars, or global filters.

### Anonymous Components (`resources/views/components/`)
Reusable UI fragments that do not require server-side state (e.g., logos, buttons, badges, icons).

---

## Domain-Driven View Nesting

To prevent directory bloat, views associated with a specific feature are nested within a domain folder.

### The Main-Partial-Modal Pattern

Each major admin view follows a three-tier hierarchy:

1.  **The Entry Component (`[feature]-manager.blade.php`)**:
    The top-level file for the Livewire component. It handles the layout structure and high-level logic, including partials and modals.

2.  **Partials Folder (`partials/`)**:
    Contains significant UI sections that are too large to maintain within the main component file.
    - Path: `livewire/admin/[domain]/partials/`
    - Examples: `[domain]-table.blade.php`, `[domain]-stats-grid.blade.php`.

3.  **Modals Folder (`partials/modals/`)**:
    A specialized subdirectory for all dialogs, flyouts, and confirmation panels related to the feature.
    - Path: `livewire/admin/[domain]/partials/modals/`

---

## Naming and Inclusion Conventions

### File Naming
- **Format**: All files must use lowercase kebab-case.
- **Suffixes**: Append the component type to the filename for instant identification:
    - `-table` for data listings.
    - `-modal` for flyouts or dialogs.
    - `-form` for isolated input sections.

### Inclusion Rules
Modals and large UI blocks should be included using the `@include` directive. To maintain a clean DOM structure and prevent z-index issues, modals should be placed at the bottom of the main component file.

```blade
<!-- Typical Main Component Structure -->
<div class="space-y-6">
    <!-- Header/Stats -->
    @include('livewire.admin.courses.partials.stats')

    <!-- Main Data Listing -->
    @include('livewire.admin.courses.partials.courses-table')

    <!-- Modals (Placed at bottom) -->
    @include('livewire.admin.courses.partials.modals.form-modal')
    @include('livewire.admin.courses.partials.modals.view-modal')
</div>
```

---

## Shared and Global Partials

Files that are shared across layouts but are not dedicated components reside in `resources/views/partials/`.
- `head.blade.php`: Global meta tags, scripts, and fonts.
- `footer.blade.php`: Global footer content.
