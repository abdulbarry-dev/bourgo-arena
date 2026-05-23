# Payment Gateway Selection

Date: 2026-05-23
Scope: Konnect-only payment architecture

## Decision

- Primary and only gateway: Konnect

## Rationale

- Konnect is the supported Tunisian payment provider for the platform.
- Keeping one gateway removes fallback complexity, duplicate webhook logic, and divergent test fixtures.
- The codebase now uses a single `KonnectGateway` service for initiation, verification, and refunds.

## What Was Implemented

- Gateway service:
    - `app/Services/PaymentGateway/KonnectGateway.php`
- Service registration and config:
    - `app/Providers/PaymentServiceProvider.php`
    - `config/payment.php`
- API and admin flows:
    - `app/Http/Controllers/Api/V1/PaymentController.php`
    - `app/Http/Controllers/Api/V1/ReservationController.php`
    - `app/Livewire/Admin/Subscriptions/SubscriptionEnrollmentFlyout.php`

## Configuration

- Konnect keys:
    - `KONNECT_API_KEY`
    - `KONNECT_API_SECRET`
    - `KONNECT_SANDBOX=true|false`
    - `KONNECT_WEBHOOK_SECRET`

## Validation Evidence

- Feature test suite: `tests/Feature/PaymentGatewayTest.php`
- Coverage includes:
    - service resolution
    - credentials validation
    - initiate/verify/refund mock flows
    - missing credentials failure handling

## Follow-up

- Run sandbox smoke with real credentials in staging.
- Record gateway SLA observations for Konnect callback success rate and latency.
