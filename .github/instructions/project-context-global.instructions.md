---
description: "Use when planning, implementing, or reviewing any work in this repository. Load project context docs first so domain rules, backlog priorities, component ownership, and UX constraints are respected."
name: "Project Context Global"
applyTo: "**"
---

# Project Context Global Rules

- Treat this as an always-on context rule for this repository.
- Before proposing architecture, data models, routes, or UI behavior, align with the referenced docs below.
- If any referenced docs conflict with current code, do not guess: surface the conflict and ask for a decision.

## Required Reference Order

1. Implementation phases and sequencing:
    - `implementation-plan.md` (phased delivery schedule, dependencies, testing strategy)
2. Product scope and delivery priority:
    - `docs/product.backlog.md`
3. Domain language and business rules:
    - `docs/domain.glossory.md`
4. Livewire ownership boundaries and event contracts:
    - `docs/liviwire.component.regsitery.md`
5. UX and interaction standards:
    - `docs/ux.rules.md`
6. Database structure constraints:
    - `docs/database.schema.md`

## Enforcement Rules

- **Phase sequencing:** Follow `implementation-plan.md` strictly for phase ordering, dependencies, and deliverables.
- **Completion verification:** Update checklists directly in `implementation-plan.md` to track completed sub-tasks per phase before moving to the next.
- Use glossary terms exactly as defined in `docs/domain.glossory.md` for entities, statuses, and access terms.
- Follow component ownership in `docs/liviwire.component.regsitery.md`; do not duplicate business logic across components.
- Apply UX decisions from `docs/ux.rules.md` for all admin dashboard UI changes.
- For validated mutating actions in admin workflows (create, update, suspend, activate, transfer, delete, assign), dispatch professional user-facing toast feedback for success and relevant no-op/info outcomes.
- Respect backlog sequencing in `docs/product.backlog.md`, including admin-first prioritization.
- Use `docs/database.schema.md` as schema authority for tables, columns, types, and relationships.
- If code conflicts with `docs/database.schema.md`, surface the conflict and ask for a decision before migrations or model changes.
- Role mapping rule for current implementation: treat glossary `manager` as the primary admin role (formerly `staff`).

## Architecture & Code Organization Rules

- **Thin Controllers:** Controllers must exclusively handle HTTP request routing and extracting the input. They should not contain business logic, database queries/repository logic, or complex data transformations.
- **Separation of Concerns:**
    - **Validation:** Always use dedicated Form Request classes (`app/Http/Requests`) for validation and authorization logic, never inline in the controller.
    - **Business Logic:** Delegate core business logic and database interactions to dedicated Service classes or Actions (`app/Services` or `app/Actions`).
    - **Response Formatting:** Always use API Resource classes (`app/Http/Resources`) to format outgoing JSON responses.

## Existing Priority Rule Alignment

- Keep alignment with the admin-first rule in `.github/instructions/admin-dashboard-first.instructions.md`.
- For mixed admin and API requests, dashboard prerequisites remain first unless user explicitly overrides.
