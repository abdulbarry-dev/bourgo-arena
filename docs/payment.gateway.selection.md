# Payment Gateway Selection

Date: 2026-03-29  
Scope: Phase 1 - Task 1.2 (US-028)

## Decision

- Primary gateway: Konnect
- Secondary fallback gateway: Paymee

## Rationale

- Konnect fit: Strong local Tunisia alignment and straightforward payment flow for immediate checkout.
- Sandbox reliability: Stable mockable endpoints for the current integration and test setup.
- Operational simplicity: Cleaner primary path while retaining multi-driver support through `PaymentGatewayManager`.
- Risk control: Paymee remains fully implemented as a fallback driver to reduce vendor lock-in risk.

## What Was Implemented

- Driver contract and manager:
    - `app/Services/PaymentGateway/Contracts/PaymentGatewayDriver.php`
    - `app/Services/PaymentGateway/PaymentGatewayManager.php`
- Concrete drivers:
    - `app/Services/PaymentGateway/Drivers/KonnectDriver.php`
    - `app/Services/PaymentGateway/Drivers/PaymeeDriver.php`
- Service registration and config:
    - `app/Providers/PaymentServiceProvider.php`
    - `config/payment.php`

## Configuration

- Default driver key: `PAYMENT_GATEWAY=konnect`
- Konnect keys:
    - `KONNECT_API_KEY`
    - `KONNECT_API_SECRET`
    - `KONNECT_SANDBOX=true|false`
- Paymee keys:
    - `PAYMEE_API_KEY`
    - `PAYMEE_API_SECRET`
    - `PAYMEE_SANDBOX=true|false`
    - `PAYMEE_WEBHOOK_URL`

## Validation Evidence

- Feature test suite: `tests/Feature/PaymentGatewayTest.php`
- Coverage includes:
    - driver resolution
    - credentials validation
    - initiate/verify/refund mock flows
    - invalid driver handling
    - missing credentials failure handling

## Follow-up

- Run sandbox smoke with real credentials in staging.
- Record gateway SLA observations (latency, callback success rate) over 1 week.
- If production constraints change, switch default driver by env without code changes.
