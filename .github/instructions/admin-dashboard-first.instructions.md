---
description: "Use when planning, sequencing, or implementing features for this project. Prioritize admin web dashboard work before mobile API work unless the user explicitly overrides the order."
name: "Admin Dashboard First"
---

# Admin Dashboard First Rule

- Treat the admin web dashboard as the primary delivery surface for new features.
- For mixed requests (dashboard + API), implement dashboard foundations first.
- Defer member mobile API endpoints until the related admin workflows, permissions, and operational controls are implemented.
- Keep all role and authorization decisions aligned with dashboard-first rollout.
- This is a hard rule and must be enforced unless the user explicitly overrides it.
- Even for API-only requests, first propose and validate dashboard prerequisites before implementing API work.

## Current Project Intent

- Build and stabilize gym operations in the admin dashboard first.
- Member access through API routes comes after admin flows are ready.
- Enforce strict role separation:
    - `member`: mobile/member API only
    - `admin`, `manager`: dashboard routes
