# Technical Realization Documentation

This document provides a detailed overview of the application's technical implementation, organized by the four main development phases (sprints).

---

## Sprint 1 — Authentication & Identity

This sprint establishes the foundational security and identity layer, focusing on device traceability and a progressive member onboarding flow.

### **1. Device Registration & API Access**
*   **Device Binding:** Every client must register via `POST /api/v1/device/register` before accessing any other endpoint. 
*   **Security Enforcement:** The `EnsureApiAccess` middleware acts as the gatekeeper. It mandates a valid `DeviceAccessToken` and an `X-Device-ID` header that must match the token's bound device UUID.
*   **Web Platform Support:** In local/testing environments, the system allows the `web` platform and bypasses mobile-specific integrity tokens (Google Play Integrity/Apple App Attest).
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/DeviceRegistrationController.php`
    *   `app/Http/Requests/Api/V1/RegisterDeviceRequest.php`
    *   `app/Models/DeviceAccessToken.php`
    *   `app/Http/Middleware/EnsureApiAccess.php`

### **2. Progressive Member Authentication**
*   **State-Driven Flow:** Members transition through four states: `pending_verification`, `pending_additional_verification`, `pending_onboarding`, and `active`.
*   **Sanctum Integration:** Tokens are issued with specific **abilities** (`verification`, `onboarding`, or `*`) based on the member's current state, preventing access to features before they are earned.
*   **OTP Orchestration:** The `OtpService` handles 6-digit codes sent via Email or SMS. Verification triggers state transitions (e.g., verifying a phone moves the user to the onboarding phase).
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/AuthController.php`
    *   `app/Services/Auth/AuthOrchestrationService.php`
    *   `app/Services/Auth/OtpService.php`
    *   `app/Models/Member.php`
    *   `app/Http/Middleware/EnsureAccountIsVerified.php`

---

## Sprint 2 — Family, Activities & Search

Focuses on relational data management for families and the complex scheduling engine for recurring sessions.

### **1. Family Management**
*   **Self-Referencing Relationships:** Implemented via a `parent_id` column in the `Member` table. Children are full `Member` records linked to a primary parent account.
*   **Service Layer:** `ApiFamilyService` encapsulates the creation and management of child accounts, ensuring they inherit relevant permissions or access.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/FamilyController.php`
    *   `app/Services/ApiFamilyService.php`
    *   `app/DTOs/FamilyChildDTO.php`

### **2. Session-Based Scheduling**
*   **Recurring Logic:** Activities and Courses use a session-based architecture. `ActivitySession` and `CourseSession` define weekly recurrences using `day_of_week` and `starts_at` (time) fields.
*   **Conflict Detection:** Static `hasOverlap` methods on session models prevent overlapping schedules during creation or updates.
*   **Schedule Exceptions:** `ActivitySessionException` and `CourseSessionException` models allow the Admin to cancel or override specific dates within a recurring series.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/ActivityController.php`
    *   `app/Http/Controllers/Api/V1/CourseController.php`
    *   `app/Models/ActivitySession.php`
    *   `app/Models/CourseSession.php`

### **3. Unified Search Engine**
*   **Unified API:** `SearchService` provides a single endpoint for mobile clients, merging results from Activities and Courses into a consistent schema.
*   **Eloquent Scopes:** Heavily uses scopes like `scopeSearchable`, `scopeActive`, and `scopeByPlan` to ensure filtering logic is dry and reusable.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/SearchController.php`
    *   `app/Services/SearchService.php`

---

## Sprint 3 — Reservations, Subscriptions & Loyalty

Implements the core business transactions, ensuring data integrity through atomic operations and strict validation.

### **1. Reservation Lifecycle**
*   **Atomic Booking:** `ReservationService` uses `DB::transaction` and `lockForUpdate` on sessions to prevent race conditions (overbooking) during high traffic.
*   **Payment States:** Reservations require a 10% deposit. The system tracks `confirm_status` and `payment_status` independently.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/ReservationController.php`
    *   `app/Services/ReservationService.php`
    *   `app/Models/ApiReservation.php`

### **2. Subscription & Tier System**
*   **Smart Stacking:** The `SubscriptionService` prevents "lower tier" purchases if a higher-level plan (Ultra/Max) is already active.
*   **Dynamic Resolution:** `TierResolutionService` calculates a member's tier (Standard, Plus, Ultra, Max) in real-time based on the count of active subscriptions for the individual or family unit.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/SubscriptionController.php`
    *   `app/Services/SubscriptionService.php`
    *   `app/Services/TierResolutionService.php`
    *   `config/tiers.php`

### **3. Loyalty Program**
*   **Transactional Balance:** While the current balance is cached on the `Member` model, every point change is backed by a record in `loyalty_points` and a detailed `LoyaltyAuditLog`.
*   **Tier Multipliers:** Credits for renewals and bookings are dynamically scaled based on the member's current tier multipliers defined in `config/loyalty.php`.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/LoyaltyController.php`
    *   `app/Services/LoyaltyCalculatorService.php`
    *   `app/Models/LoyaltyPoint.php`

---

## Sprint 4 — Payments & Multi-channel Notifications

Finalizes the ecosystem with external gateway integrations, localized security, and automated communication.

### **1. Payment Integration (Konnect)**
*   **Driver Pattern:** Uses a `PaymentManager` to handle different providers. The `KonnectProvider` wraps the low-level HTTP communication with the Konnect v2 API.
*   **Secure Webhooks:** `PaymentController::webhook` performs HMAC-SHA256 signature verification against `X-Konnect-Signature` to ensure payloads are authentic.
*   **Key Files:** 
    *   `app/Http/Controllers/Api/V1/PaymentController.php`
    *   `app/Services/PaymentService.php`
    *   `app/Services/Payment/Providers/KonnectProvider.php`

### **2. Multi-channel Notifications**
*   **Custom Channels:** Beyond standard `mail` and `database`, a custom `SmsChannel` (Twilio) formats and routes messages to Tunisian phone numbers.
*   **Push Notifications:** `PushNotificationService` integrates with FCM. Tokens are tracked per member and per device for precise targeting.
*   **Hybrid Delivery:** Notifications often use a hybrid `via()` pattern, combining auto-routed mail/SMS with manually triggered push updates for immediate consistency.
*   **Key Files:** 
    *   `app/Services/Members/PushNotificationService.php`
    *   `app/Channels/SmsChannel.php`
    *   `app/Notifications/LoyaltyPointsUpdatedNotification.php`

### **3. Geo-Fencing & Security**
*   **RestrictToTunisia Middleware:** Applied to payment routes to block non-Tunisian traffic using `GeoLocationService` (backed by `ip-api.com`).
*   **IP Rotation Detection:** Flags members who significantly change locations between consecutive payment attempts to prevent account sharing or fraud.
*   **Key Files:** 
    *   `app/Http/Middleware/RestrictToTunisia.php`
    *   `app/Services/GeoLocationService.php`
