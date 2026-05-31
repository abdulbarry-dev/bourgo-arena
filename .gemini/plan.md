# NFC Access System — Execution-Ready Implementation Plan for Gemini

## Goal

Implement a member-facing NFC access system with:

1. **Physical NFC Card Status API**
2. **Digital NFC Readiness + Setup APIs**
3. Clean separation between:

   * physical NFC cards
   * smartphone/digital NFC access
4. Compatibility with:

   * existing member auth patterns
   * Hikvision terminal infrastructure
   * current API response conventions

---

# Existing Architecture Discovery Summary

## Existing NFC Infrastructure

Already implemented:

* `NfcCard` model
* `NfcCardPolicy`
* `NfcCardAssignment` Livewire component
* `HikvisionTerminal` model
* `TerminalCheckInController`
* `TerminalAuthMiddleware`
* `CheckInEvent` model
* NFC card migration
* Member device token infrastructure

## Existing API Conventions

Current API style uses:

```json
{
  "success": true,
  "message": "Some message",
  "data": {}
}
```

Auth structure already exists under:

```php
routes/api.php
app/Http/Controllers/Api/V1/*
```

Verified middleware patterns:

* authenticated member routes
* account verification middleware
* API V1 grouping conventions

---

# Architecture Decision Summary

## Approved Decisions

### 1. Separate physical and digital NFC

Do NOT combine them into one endpoint.

Reason:

* simpler mobile logic
* easier future provisioning
* clearer business rules

---

### 2. Hybrid support detection

Digital NFC support should depend on:

#### Client-provided capabilities

Example:

* NFC enabled
* device supports Host Card Emulation
* OS version

AND

#### Server-side compatibility policy

Example:

* supported phone models
* supported OS versions
* blocked manufacturers

---

### 3. Global readiness only

Do NOT implement per-terminal compatibility in v1.

Return only:

```json
"is_ready": true
```

instead of terminal-specific access matrices.

---

# Final API Design

---

# 1. Physical NFC Status Endpoint

## Endpoint

```http
GET /api/v1/member/nfc/physical-status
```

## Auth

```php
auth:sanctum
EnsureAccountIsVerified
```

---

## Purpose

Return whether the member already has:

* assigned NFC card
* active card
* usable access state
* fallback access methods

---

## Response Example — Active Card

```json
{
  "success": true,
  "message": "Physical NFC status retrieved.",
  "data": {
    "has_card": true,
    "card_uid": "A1B2C3D4",
    "card_status": "active",
    "is_ready": true,
    "fallback_methods": [
      "pin",
      "fingerprint"
    ]
  }
}
```

---

## Response Example — No Card

```json
{
  "success": true,
  "message": "No physical NFC card assigned.",
  "data": {
    "has_card": false,
    "card_uid": null,
    "card_status": null,
    "is_ready": false,
    "fallback_methods": [
      "pin",
      "fingerprint"
    ]
  }
}
```

---

# 2. Digital NFC Readiness Endpoint

## Endpoint

```http
GET /api/v1/member/nfc/digital-status
```

---

## Purpose

Return whether smartphone NFC setup is:

* supported
* configured
* eligible
* blocked
* pending

---

## Query/Input

Client sends capability information.

Example:

```json
{
  "device_model": "Samsung S24",
  "os_version": "Android 15",
  "nfc_enabled": true,
  "supports_hce": true
}
```

Can be:

* query params
* request body
* headers

Recommended:
validated request body.

---

## Response Example — Supported + Ready

```json
{
  "success": true,
  "message": "Digital NFC supported.",
  "data": {
    "supported": true,
    "configured": true,
    "eligible": true,
    "is_ready": true,
    "setup_status": "completed",
    "fallback_methods": [
      "pin",
      "fingerprint",
      "physical_card"
    ]
  }
}
```

---

## Response Example — Unsupported Device

```json
{
  "success": true,
  "message": "Device does not support digital NFC.",
  "data": {
    "supported": false,
    "configured": false,
    "eligible": false,
    "is_ready": false,
    "setup_status": "unsupported",
    "fallback_methods": [
      "pin",
      "fingerprint",
      "physical_card"
    ]
  }
}
```

---

# 3. Digital NFC Setup Trigger Endpoint

## Endpoint

```http
POST /api/v1/member/nfc/digital-setup
```

---

## Purpose

Start or confirm digital NFC setup lifecycle.

This endpoint:

* stores setup state
* validates compatibility
* prevents duplicate active setups
* records device metadata

NOT included in v1:

* direct Hikvision sync
* terminal provisioning

---

## Request Payload

```json
{
  "device_model": "Samsung S24",
  "os_version": "Android 15",
  "device_identifier": "hashed-device-id",
  "supports_hce": true,
  "nfc_enabled": true
}
```

---

## Response Example — Setup Started

```json
{
  "success": true,
  "message": "Digital NFC setup initialized.",
  "data": {
    "setup_status": "pending",
    "supported": true,
    "eligible": true
  }
}
```

---

## Response Example — Already Configured

```json
{
  "success": true,
  "message": "Digital NFC already configured.",
  "data": {
    "setup_status": "completed",
    "supported": true,
    "eligible": true
  }
}
```

---

# Database Changes

---

# New Table Required

## Reason

Current schema has:

* physical NFC cards
* device tokens

But NOTHING stores:

* smartphone NFC setup lifecycle
* compatibility state
* digital access enrollment

---

# New Migration

## Suggested Table

```php
member_digital_nfc_devices
```

---

## Suggested Columns

```php
id
member_id
device_identifier
device_model
os_version
supports_hce
nfc_enabled
setup_status
is_supported
is_active
last_verified_at
created_at
updated_at
```

---

## setup_status Enum

```php
pending
completed
failed
unsupported
revoked
```

---

# Model Layer

## Create

```php
app/Models/MemberDigitalNfcDevice.php
```

---

## Member Relation

Add to `Member.php`

```php
public function digitalNfcDevices()
{
    return $this->hasMany(MemberDigitalNfcDevice::class);
}
```

---

# Compatibility Policy

---

# Create Config

## File

```php
config/digital_nfc.php
```

---

## Example

```php
return [

    'supported_models' => [
        'Samsung S24',
        'Google Pixel 9',
    ],

    'minimum_android_version' => 14,

    'blocked_manufacturers' => [
        'Huawei',
    ],
];
```

---

# Support Resolution Logic

Create service:

```php
app/Services/Nfc/DigitalNfcCompatibilityService.php
```

Responsibilities:

* validate device support
* validate HCE support
* validate OS version
* apply blocked rules

---

# Business Rules

---

# Physical NFC Rules

## READY if:

```php
member has card
AND
card status = active
```

---

# Digital NFC Rules

## SUPPORTED if:

```php
client supports NFC
AND
client supports HCE
AND
server policy allows model
AND
OS version valid
```

---

## ELIGIBLE if:

```php
member verified
AND
member active
AND
supported = true
```

---

## READY if:

```php
setup_status = completed
AND
eligible = true
```

---

# Duplicate Prevention

Only ONE active digital NFC device per member in v1.

When creating new setup:

```php
deactivate previous devices
```

OR reject duplicates.

Recommended:
deactivate previous.

---

# Laravel Structure

---

# Controllers

## Create

```php
app/Http/Controllers/Api/V1/MemberNfcController.php
```

---

# Methods

```php
physicalStatus()
digitalStatus()
setupDigital()
```

---

# Validation

Create Form Requests.

## Suggested Files

```php
app/Http/Requests/Api/V1/DigitalNfcStatusRequest.php

app/Http/Requests/Api/V1/DigitalNfcSetupRequest.php
```

---

# Services

## Create

```php
app/Services/Nfc/
```

Files:

```php
DigitalNfcCompatibilityService.php
DigitalNfcSetupService.php
PhysicalNfcStatusService.php
```

---

# API Resources

Optional but recommended.

## Create

```php
app/Http/Resources/Api/V1/
```

Files:

```php
PhysicalNfcStatusResource.php
DigitalNfcStatusResource.php
```

---

# Route Registration

Update:

```php
routes/api.php
```

---

## Suggested Routes

```php
Route::middleware([
    'auth:sanctum',
    EnsureAccountIsVerified::class,
])->prefix('v1/member/nfc')->group(function () {

    Route::get('/physical-status', [
        MemberNfcController::class,
        'physicalStatus',
    ]);

    Route::get('/digital-status', [
        MemberNfcController::class,
        'digitalStatus',
    ]);

    Route::post('/digital-setup', [
        MemberNfcController::class,
        'setupDigital',
    ]);
});
```

---

# Testing Plan

---

# Feature Tests

Create:

```php
tests/Feature/Api/V1/MemberNfcTest.php
```

---

# Required Test Cases

## Physical NFC

### Should return active card

* assigned card
* active status

---

### Should return no card

* no assignment

---

### Should return inactive state

* suspended card

---

# Digital NFC

### Supported device

### Unsupported device

### Invalid OS version

### Missing HCE support

### Setup already completed

### Setup initialization

### Auth required

### Unverified account blocked

---

# Verification Commands

---

## Run Tests

```bash
php artisan test --filter=MemberNfcTest
```

---

## Route Verification

```bash
php artisan route:list | grep nfc
```

---

## Formatting

```bash
./vendor/bin/pint
```

---

# Scope Boundaries

---

# INCLUDED in v1

✅ Physical NFC status
✅ Digital NFC readiness
✅ Device compatibility checks
✅ Setup lifecycle persistence
✅ Fallback access methods
✅ Member API integration

---

# EXCLUDED in v1

❌ Direct Hikvision provisioning
❌ Per-terminal compatibility
❌ Apple Wallet integration
❌ Background NFC sync
❌ Multi-device active sessions
❌ Biometric enrollment APIs

---

# Recommended Implementation Order

## Phase 1

Database + model layer

---

## Phase 2

Compatibility service

---

## Phase 3

Physical status endpoint

---

## Phase 4

Digital status endpoint

---

## Phase 5

Digital setup endpoint

---

## Phase 6

Feature tests + documentation

---

# Important Notes for Gemini

## Keep controllers thin

Business logic belongs in services.

---

## Follow existing API response format

Always return:

```json
success
message
data
```

---

## Do NOT couple with Hikvision yet

v1 should remain provider-agnostic.

---

## Do NOT merge physical + digital endpoints

Maintain strict separation.

---

## Respect existing auth middleware stack

Must align with current API V1 conventions.
