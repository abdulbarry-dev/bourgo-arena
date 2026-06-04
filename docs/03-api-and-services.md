# API and Services

## API Architecture

The Bourgo Arena API is a comprehensive RESTful interface designed to power the mobile application ecosystem. It follows versioning principles (V1) and provides a secure, stateless environment for member interactions.

### Authentication and Security

- **JWT / Bearer Tokens**: All authenticated endpoints require a valid token generated via Laravel Sanctum.
- **Role-Based Access Control (RBAC)**: Middleware ensures that only users with the correct role (Admin, Manager, or Member) can access specific resources.
- **Throttling**: Critical endpoints like login, registration, and OTP verification are protected by granular rate limiters to prevent brute-force attacks.
- **OTP Verification**: A multi-step verification process (Email/SMS) is required during registration and for sensitive account changes.

---

## Service Layer Logic

The application logic is decoupled into specialized Service classes, ensuring high maintainability and testability.

### Membership and Authentication Services

- **AuthOrchestrationService**: Manages the complex state transitions during member login and registration. It handles multi-factor verification states (Pending Onboarding, Pending Verification) and ensures the user is in the correct state before granting full access.
- **OtpService**: Handles the generation, storage, and validation of One-Time Passwords. It supports multiple delivery channels (Email, SMS) and implements expiry and retry cooldown logic.
- **ApiFamilyService**: Manages family-related operations, allowing "Head of Family" members to create and manage children accounts while enforcing ownership rules and account isolation.

### Financial and Payment Services

- **PaymentService & PaymentManager**: Acts as an abstraction layer for payment gateways (Konnect, Flouci). It handles the full payment lifecycle: initiation, verification via gateway polling, and asynchronous webhook processing.
- **PaymentAuditService**: Provides a non-repudiation layer by logging every payment attempt, request payload, and gateway response into a dedicated audit trail.
- **ReceiptGenerator**: Orchestrates the automated creation of PDF receipts after successful payment verification and manages their storage and retrieval.

### Loyalty and Rewards Service

- **LoyaltyCalculatorService**: Implements the business rules for earning points.
    - **Fixed Credits**: Automatically awards points upon successful monthly subscription renewals based on the member's current tier.
    - **Variable Credits**: Calculates points for reservations based on eligible categories and the member's monthly participation frequency.
    - **Idempotency**: Uses unique keys to ensure points are never credited twice for the same transaction.

### Facility and Reservation Services

- **ReservationService**: Manages the complex logic of booking activities.
    - **Inventory Management**: Uses database-level locks (`lockForUpdate`) to prevent overbooking of time slots during high-concurrency periods.
    - **Pricing Resolution**: Dynamically calculates reservation costs based on the member's loyalty tier and applicable discounts.
    - **State Logging**: Maintains a polymorphic log of every status transition (e.g., Pending to Confirmed) for a complete audit history.

---

## Event-Driven Architecture

The system utilizes an asynchronous model to improve performance and decouple non-critical tasks.

### Background Jobs

- **Tournament Management**: `GenerateTournamentBracketJob` handles the computationally intensive task of creating match pairings for tournaments.
- **Automated Communication**: Dedicated jobs handle the dispatch of welcome emails, password reset notifications, and subscription alerts via email, SMS, and push notifications.
- **Cleanup and Maintenance**: Background tasks manage the archiving of expired subscriptions and the deletion of accounts scheduled for removal.

### Listeners

- **Event Lifecycle**: `HandleEventCancellation` reacts to tournament cancellations by notifying all registered participants and cleaning up scheduled matches.
- **Administrative Auditing**: `LogAdminAction` automatically captures every significant change made by an administrator for security and accountability.
