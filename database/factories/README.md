# Factory Grouping Strategy

Factory implementations live in domain-specific namespaces so the factory layer is easier to navigate and reason about:

- `Database\Factories\Shared\Identity` for user identity factories.
- `Database\Factories\Shared\Members` for member records.
- `Database\Factories\Shared\Billing` for plans and subscriptions.
- `Database\Factories\Shared\Auth` for OTP and verification codes.
- `Database\Factories\Shared\Activities` for activity booking primitives.
- `Database\Factories\Shared\Notifications` for member notifications.
- `Database\Factories\Dashboard\Catalog` for admin-facing catalog data.
- `Database\Factories\Dashboard\Events` for tournament and bracket data.
- `Database\Factories\Api\Reservations` for API reservation payloads.

Conventions used in the grouped factories:

- Prefer `fake()` for new scalar defaults.
- Prefer named state methods for lifecycle variants.
- Keep relationship helpers explicit, such as `forEvent()`, `forSlot()`, and `forUser()`.
- Avoid eager `create()` calls inside `definition()` unless a dependent record is truly required.

Models point directly at these grouped classes through `newFactory()` hooks, so there is no compatibility shim layer in the root namespace.
