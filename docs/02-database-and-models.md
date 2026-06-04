# Database and Models

## Data Architecture

The Bourgo Arena database is designed to handle complex sports management requirements, including membership tiers, family accounts, recurring subscriptions, facility reservations, and tournament logistics. The schema follows high normalization standards while utilizing modern database features like JSON columns for flexible metadata and polymorphic relationships for multi-purpose logging systems.

---

## Core Models and Schema

### Identity and Access Management

#### User
The central identity model for internal staff and administrators.
- **Attributes**: Stores standard identity information (name, email, phone) alongside application-specific roles (Admin, Manager, Member).
- **Security**: Implements two-factor authentication (Fortify), session management, and account banning capabilities.
- **Logic**: Provides helper methods to determine staff status and generate user initials for the UI.

#### Member
Represents the actual sports club members and consumers of the facility's services.
- **Attributes**: Contains comprehensive profile data, including date of birth, gender, and emergency contact.
- **States**: Managed via a state column (Active, Pending Verification, Pending Onboarding).
- **Family Logic**: Supports a recursive self-referencing relationship where a 'Parent' member can have multiple 'Children' members.
- **Relationships**: Connects to subscriptions, notifications, device tokens, and reservations.
- **Custom Logic**: Includes accessors for avatar URLs, verification status checks, and complex searchable scopes.

---

### Subscription and Billing

#### Service
The high-level category of offerings (e.g., Padel, Football).
- **Attributes**: Metadata like name, slug, and status.
- **Logic**: Automatically generates slugs on creation and provides status-based scopes (Active, Archived).

#### Plan
Defines the pricing and duration for memberships within a Service.
- **Relationships**: Belongs to a Service and can encompass multiple specific Courses.
- **Global Scopes**: Implements an 'ActivePlanScope' to ensure only non-archived plans are retrieved by default.

#### Subscription
The link between a Member and a Plan.
- **Attributes**: Tracks start and end dates, status (Active, Suspended, Expired), and payment details.
- **Logic**: Calculates remaining days and provides automated end-date calculation based on plan duration.
- **Auditing**: Maintains a complete history via audit logs for every status change or enrollment action.

---

### Facility Management and Reservations

#### Activity
Specific bookable items or events within a service (e.g., individual court sessions).
- **Metadata**: Stores flexible data like features and image galleries using JSON columns.
- **Availability**: Managed through time slots and capacity tracking.

#### Reservation
Tracks the intent of a User or Member to participate in an Activity.
- **Attributes**: Stores payment status, deposit amounts, and full pricing.
- **State Management**: Uses state logs to track transitions between reservation statuses (Confirmed, Canceled, Pending).

#### Booking
Handles specific time-sensitive facility usage, such as court slots or course sessions.
- **Logic**: Manages waitlist positions and cancellation timestamps.

---

### Tournament and Event Logistics

#### Event
High-level sports events or tournaments.
- **Attributes**: Defines registration deadlines, participant limits, and tournament formats.
- **Logic**: Provides state-based logic to determine if an event is in Draft, Open, In Progress, or Completed status. Includes built-in cancellation logic that triggers system-wide events.

#### Event Participant & Event Match
- **Participants**: Tracks registrations, check-in status, and team assignments.
- **Matches**: Models the tournament bracket system, tracking rounds, match numbers, scores, and winners. Uses a self-referencing relationship to link a match to the next one in the bracket.

---

### Loyalty and Auditing

#### Loyalty Point & Loyalty Audit Log
- **Points**: Records individual point transactions.
- **Logic**: Uses polymorphic relationships ('source') to link points to the action that earned them (e.g., a specific Reservation or Subscription).
- **Audit Logs**: Records a detailed trail of every balance change, including balance before/after and IP address tracking for security.

#### Admin Audit Log
A generic auditing system for administrative actions, tracking which admin performed what action on which entity.

---

## Global Traits and Conventions

### Soft Deletes
Critical models such as Members, Events, and Subscriptions utilize Soft Deletes to ensure data is never permanently removed without an intentional administrative action, preserving historical records for reporting.

### Custom Casts
The system uses custom Eloquent casts for:
- **Enums**: Roles and statuses are cast to PHP Enums for type-safe comparisons.
- **JSON**: Complex configurations (like activity features or payment metadata) are cast to arrays for easy manipulation.
- **Decimals**: All financial amounts are cast to 3-decimal precision to prevent floating-point errors.

### Searchable Scopes
Most primary models implement a `Searchable` scope that allows for consistent multi-column searching (e.g., name, email, phone) across the entire administrative dashboard.
