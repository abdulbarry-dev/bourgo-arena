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
- Respect backlog sequencing in `docs/product.backlog.md`, including admin-first prioritization.
- Use `docs/database.schema.md` as schema authority for tables, columns, types, and relationships.
- If code conflicts with `docs/database.schema.md`, surface the conflict and ask for a decision before migrations or model changes.
- Role mapping rule for current implementation: treat glossary `manager` as the primary admin role (formerly `staff`).

## Existing Priority Rule Alignment

- Keep alignment with the admin-first rule in `.github/instructions/admin-dashboard-first.instructions.md`.
- For mixed admin and API requests, dashboard prerequisites remain first unless user explicitly overrides.
