# Onboarding State Machine

This document maps the complete member verification and onboarding lifecycle — the progressive state machine that gates API access from initial registration through to full platform access.

---

## Table of Contents

1. [Overview](#overview)
2. [State Definitions](#state-definitions)
3. [Complete State Transition Diagram](#complete-state-transition-diagram)
4. [Login Decision Tree](#login-decision-tree)
5. [Middleware Access Matrix](#middleware-access-matrix)
6. [OTP Verification — The Transition Engine](#otp-verification--the-transition-engine)
7. [Endpoint-by-Endpoint Flows](#endpoint-by-endpoint-flows)
   - [Registration](#1-registration)
   - [Login (Password)](#2-login-password)
   - [Login (OTP-Based)](#3-login-otp-based)
   - [Verify Email / Verify Phone](#4-verify-email--verify-phone)
   - [Skip Additional Verification](#5-skip-additional-verification)
   - [Complete Registration](#6-complete-registration)
   - [Account Deletion](#7-account-deletion)
8. [Token Abilities by State](#token-abilities-by-state)
9. [Rate Limiting](#rate-limiting)
10. [Key Files Reference](#key-files-reference)

---

## Overview

A member progresses through four persistent states on the `members` table (columns `status` and `state`, always set identically):

```
pending_verification → pending_additional_verification → pending_onboarding → active
```

Each transition is gated by specific verification or onboarding actions. A transient fifth state (`pending_deletion_cancellation`) appears only in API responses when a member's account is scheduled for deletion.

Two independent axes determine the current state:

| Axis | Check | Field(s) |
|------|-------|----------|
| **Verification** | At least one contact method verified? | `email_verified_at`, `phone_verified_at` |
| **Onboarding** | Onboarding form completed? | `onboarding_completed_at` |

---

## State Definitions

| State | Stored in DB? | Meaning | Has Verified Contact? | Onboarding Done? | Token Ability |
|-------|:---:|---------|:---:|:---:|---|
| `pending_verification` | Yes | Freshly registered, no contacts verified | No | No | `verification` |
| `pending_additional_verification` | Yes | Verification started but neither contact verified yet | No | No | `verification` |
| `pending_onboarding` | Yes | At least one contact verified, onboarding form not submitted | Yes | No | `onboarding` |
| `active` | Yes | Fully operational — verified AND onboarded | Yes | Yes | `*` |
| `pending_deletion_cancellation` | No | Account scheduled for deletion; login returns this transient state | N/A | N/A | `deletion-cancellation` |

### Key Model Methods (on `Member`)

```
isVerified()                  → email_verified_at !== null || phone_verified_at !== null
isFullyVerified()             → email_verified_at !== null && phone_verified_at !== null
isOnboardingCompleted()       → onboarding_completed_at !== null && isVerified()
isPendingVerification()       → state === 'pending_verification'
isPendingAdditionalVerif..()  → state === 'pending_additional_verification'
isPendingOnboarding()         → state === 'pending_onboarding'
isActive()                    → state === 'active'
```

> `isOnboardingCompleted()` requires BOTH conditions. An account with `onboarding_completed_at` set but no verified contacts is treated as NOT onboarded. See Edge Case: [Unverified Accounts in `pending_onboarding`](#edge-case-unverified-accounts-in-pending_onboarding-state).

---

## Complete State Transition Diagram

```
                              ┌────────────────────────────────────────┐
                              │            ACCOUNT DELETION            │
                              │  (scheduled_for_deletion_at            │
                              │   = now() + 48 hours)                  │
                              │  All tokens revoked                    │
                              └────────────────────┬───────────────────┘
                                                   │
                                                   ▼
                              ┌────────────────────────────────────────┐
                              │    pending_deletion_cancellation       │
                              │    (transient — API response only)     │
                              │    Token: [deletion-cancellation]      │
                              └────────────────────┬───────────────────┘
                                                   │
                                       verify OTP (cancel deletion)
                                       → scheduled_for_deletion_at = null
                                       → return to original state
                                                   │
                                                   ▼
   ┌──────────┐          register          ┌──────────────────────────┐
   │ NEW USER │───────────────────────────▶│  pending_verification     │
   └──────────┘                            │  Token: [verification]    │
                                           └────────────┬─────────────┘
                                                        │
                                            login OR verify-otp
                                                        │
                                                        ▼
                                           ┌──────────────────────────┐
                                           │ pending_additional_       │
                                           │ verification              │
                                           │ Token: [verification]     │
                                           └────────────┬─────────────┘
                                                        │
                                   verify email OR phone (OTP)
                                   → at least one contact verified
                                                        │
                                                        ▼
                                           ┌──────────────────────────┐
                             ┌─────────────│   pending_onboarding      │◀────── skip-additional-
                             │             │   Token: [onboarding]     │        verification
                             │             └────────────┬─────────────┘   (only when exactly
                             │                          │                   1 of 2 verified)
                             │         complete-registration
                             │         → onboarding_completed_at = now()
                             │                          │
                             │                          ▼
                             │             ┌──────────────────────────┐
                             │             │         active            │
                             │             │     Token: [*]            │◀── verify second method
                             │             └──────────────────────────┘     while active
                             │                                                  (state preserved)
                             │
                             │
                             ▼
                   ┌────────────────────┐
                   │ EDGE CASE:         │
                   │ User in pending_   │
                   │ onboarding with NO │
                   │ verified contacts  │
                   │ logs in            │
                   │ → redirected to    │
                   │   pending_additional│
                   │   verification     │
                   └────────────────────┘
```

### Transition Table

| From | Trigger | To | What Happens |
|------|---------|----|--------------|
| (none) | `POST /auth/register` | `pending_verification` | Member created, OTP sent, `[verification]` token issued |
| `pending_verification` | Login or verify-otp | `pending_additional_verification` | State normalized (login always normalizes these two into one response state) |
| `pending_additional_verification` | Verify email OR phone (OTP) | `pending_onboarding` | At least one `*_verified_at` set; state moved forward |
| `pending_onboarding` | `POST /auth/complete-registration` | `active` | `onboarding_completed_at = now()`, `status/state = 'active'`, all tokens rotated, `[*]` token issued |
| Any state with exactly 1 verified | `POST /auth/skip-additional-verification` | `pending_onboarding` | Second contact verification skipped; `[onboarding]` token issued |
| Already `active` | Verify second contact method | `active` (preserved) | State explicitly preserved — does not regress |
| `active` | `POST /auth/delete-account` | (deletion scheduled) | `scheduled_for_deletion_at = now() + 48h`, all tokens revoked |
| Any (with deletion scheduled) | Login | `pending_deletion_cancellation` (transient) | OTP sent to all verified channels, `[deletion-cancellation]` token |
| `pending_deletion_cancellation` | Verify OTP | Back to original state | `scheduled_for_deletion_at = null`, deletion cancelled |

---

## Login Decision Tree

When a member logs in via `POST /api/v1/auth/login`, `AuthOrchestrationService::login()` evaluates conditions in priority order:

```
                            ┌─────────────────────────┐
                            │ 1. Find member by email │
                            │    or phone              │
                            │ 2. Validate password     │
                            └────────────┬────────────┘
                                         │
                                         ▼
                            ┌─────────────────────────┐
                            │ 3. scheduled_for_        │
                            │    deletion_at in future? │
                            └──────┬──────────┬───────┘
                                   │ YES      │ NO
                                   ▼          ▼
                         ┌──────────────┐   ┌─────────────────────────┐
                         │ deletion-    │   │ 4. state IN ['pending_   │
                         │ cancellation │   │  verification', 'pending_│
                         │ token         │   │  additional_verification']?│
                         │ OTP sent to  │   └──────┬──────────┬───────┘
                         │ all verified │          │ YES      │ NO
                         │ channels     │          ▼          ▼
                         └──────────────┘  ┌──────────────┐  ┌─────────────────────────┐
                                           │ verification │  │ 5. isVerified() ===     │
                                           │ token         │  │    false?                │
                                           │ state: pending│  │ (neither contact verified)│
                                           │ _additional_  │  └──────┬──────────┬───────┘
                                           │ verification  │         │ YES      │ NO
                                           └──────────────┘         ▼          ▼
                                                              ┌──────────┐  ┌─────────────────────┐
                                                              │ verif..  │  │ 6. isOnboarding     │
                                                              │ token    │  │    Completed() ===   │
                                                              │ state:   │  │    false?            │
                                                              │ pending_ │  └──────┬──────────┬───┘
                                                              │ additional│        │ YES      │ NO
                                                              │ verif..   │        ▼          ▼
                                                              └──────────┘  ┌──────────┐  ┌──────────┐
                                                                            │onboarding│  │ full     │
                                                                            │ token    │  │ access   │
                                                                            │ state:   │  │ [*] token│
                                                                            │ pending_ │  │ state:   │
                                                                            │ onboarding│ │ member   │
                                                                            │ required_│  │ .state   │
                                                                            │ action:  │  └──────────┘
                                                                            │ complete_│
                                                                            │ onboarding│
                                                                            └──────────┘
```

> Steps 4 and 5 both yield the same outcome (`pending_additional_verification` + `[verification]` token) but through different paths. Step 4 catches the two named states. Step 5 is a catch-all for any state where contacts remain unverified (e.g., admin-set `pending_onboarding` with no verified contacts).

---

## Middleware Access Matrix

Two middleware gates control progressive API access:

### `verified.account` (`EnsureAccountIsVerified`)

**Check**: `email_verified_at !== null || phone_verified_at !== null`

| Condition | Result |
|-----------|--------|
| Neither contact verified | **403** — `code: ADDITIONAL_VERIFICATION_REQUIRED`, `state: pending_additional_verification` |
| At least one contact verified | **Pass** |

> Even members in `pending_additional_verification` state pass this check IF one contact is already verified and they're verifying the second.

### `onboarding.completed` (`EnsureOnboardingIsCompleted`)

**Check**: `isOnboardingCompleted()` — which internally checks `onboarding_completed_at !== null && isVerified()`

| Condition | Result |
|-----------|--------|
| Onboarding not completed | **403** — `code: ONBOARDING_INCOMPLETE`, `state: pending_onboarding`, `required_action: complete_onboarding`, `cta: Complete Setup` |
| Onboarding completed | **Pass** |

### Route Access by State

| Route Group | Middleware Stack | `pending_verification` | `pending_add._verification` | `pending_onboarding` | `active` |
|-------------|-----------------|:---:|:---:|:---:|:---:|
| Public Auth | `api.access` only (or no auth) | ✅ | ✅ | ✅ | ✅ |
| Auth'd (no gates) | `api.access` + `auth:sanctum` | ✅ | ✅ | ✅ | ✅ |
| Complete Registration | `api.access` + `auth:sanctum` + `verified.account` | ❌ 403 | ❌ 403 (if unverified) / ✅ (if 1 verified) | ✅ | ✅ |
| Full Access | `api.access` + `auth:sanctum` + `verified.account` + `onboarding.completed` | ❌ 403 | ❌ 403 | ❌ 403 `ONBOARDING_INCOMPLETE` | ✅ |

### Complete Route-to-Middleware Mapping

| Endpoints | Middleware Stack |
|-----------|-----------------|
| `login`, `register`, `send-otp`, `verify-otp`, `forgot-password`, `reset-password`, `device/register` | `api.access` (or no auth for device/register) — **no gates** |
| `logout`, `verify-email`, `verify-phone`, `verification-status`, `notifications`, `skip-additional-verification`, `request-family-otp`, `delete-account` | `api.access` + `auth:sanctum` — **no gates** |
| `complete-registration` | + `verified.account` |
| `profile`, `subscriptions`, `reservations`, `courses`, `family`, `payments`, `loyalty`, `device-token`, `events` | + `verified.account` + `onboarding.completed` |

> Notifications (`GET /notifications`, `POST /notifications/mark-all-read`) are accessible WITHOUT verification or onboarding gates — only `auth:sanctum` required. Members in any state with a valid token can receive notifications.

---

## OTP Verification — The Transition Engine

`OtpService::verify()` is called from multiple endpoints and serves as the primary state transition engine. After marking a contact as verified, it determines the new state:

```
OtpService::verify($user, $code):
  │
  ├─ Validate: not expired, not exceeded max attempts, correct code
  │
  ├─ Mark identifier as verified:
  │   ├─ email → email_verified_at = now()
  │   └─ phone → phone_verified_at = now()
  │
  ├─ Clear OTP fields: otp_code, otp_expires_at, otp_attempts
  │
  ├─ CRITICAL: Clear scheduled_for_deletion_at = null
  │   (cancels any pending account deletion)
  │
  └─ Determine new state:
      │
      ├─ IF state === 'active'
      │   └─ Preserve 'active'
      │      (verifying secondary method while already active)
      │
      └─ ELSE
          │
          ├─ IF neither email_verified_at nor phone_verified_at set
          │   └─ state = 'pending_additional_verification'
          │
          ├─ ELSE IF !isOnboardingCompleted()
          │   └─ state = 'pending_onboarding'
          │
          └─ ELSE
              └─ state = 'active'
```

### Callers of `OtpService::verify()`

| Endpoint | Caller | Context |
|----------|--------|---------|
| `POST /auth/verify-otp` | `AuthOrchestrationService::verifyOtp()` → `OtpService::verify()` | OTP-based login |
| `POST /auth/verify-email` | `AuthService::verifyIdentifier()` → `OtpService::verify()` | Targeted email verification |
| `POST /auth/verify-phone` | `AuthService::verifyIdentifier()` → `OtpService::verify()` | Targeted phone verification |

---

## Endpoint-by-Endpoint Flows

### 1. Registration

```
POST /api/v1/auth/register
  │
  ├─ Middleware: api.access, throttle:api.auth
  │
  ├─ RegisterRequest validates: name, email, phone, password, etc.
  │
  ├─ AuthService::register(RegisterDTO)
  │   ├─ Member::create([
  │   │     status => 'pending_verification',
  │   │     state  => 'pending_verification',
  │   │     email_verified_at => null,
  │   │     phone_verified_at => null,
  │   │     onboarding_completed_at => null,
  │   │   ])
  │   └─ OtpService::generate($identifier)  // sends OTP immediately
  │
  └─ Return:
      {
        token: "sanctum_token",
        abilities: ["verification"],
        state: "pending_verification",
        verification_status: {
          email_verified: false,
          phone_verified: false,
          onboarding_completed: false
        }
      }
```

### 2. Login (Password)

```
POST /api/v1/auth/login
  │
  ├─ Middleware: api.access, throttle:api.auth
  │
  └─ AuthOrchestrationService::login(LoginDTO)
      │
      ├─ Find member by email or phone
      ├─ Hash::check(password, member.password)
      │
      └─ Decision tree (see Login Decision Tree above)
          │
          ├─ pending_deletion_cancellation → [deletion-cancellation] token + OTP sent
          ├─ pending_verification / pending_add._verification → [verification] token
          ├─ unverified catch-all → [verification] token
          ├─ not onboarded → [onboarding] token + required_action
          └─ full access → [*] token
```

### 3. Login (OTP-Based)

Instead of password, the mobile app may use OTP-based authentication:

```
1. POST /api/v1/auth/send-otp
   └─ Sends a 6-digit OTP to the member's email or phone

2. POST /api/v1/auth/verify-otp
   │
   ├─ OtpService::verify($identifier, $code)
   │   └─ State transition (see OTP Verification above)
   │
   └─ AuthOrchestrationService::verifyOtp()
       └─ Issues new token based on resulting state:
           ├─ pending_verification / pending_add._verification → [verification]
           ├─ pending_onboarding → [onboarding]
           └─ Otherwise → [*]
```

### 4. Verify Email / Verify Phone

```
POST /api/v1/auth/verify-email  OR  POST /api/v1/auth/verify-phone
  │
  ├─ Middleware: api.access, auth:sanctum  (no verification/onboarding gates!)
  │
  ├─ AuthService::verifyIdentifier($member, $type, $code)
  │   └─ OtpService::verify($member, $code)
  │       └─ State transition based on which contact was just verified
  │
  ├─ Deletes current token
  │
  └─ Issues new token with state-appropriate abilities
```

### 5. Skip Additional Verification

Only one contact method is required. Members can skip verifying the second:

```
POST /api/v1/auth/skip-additional-verification
  │
  ├─ Middleware: api.access, auth:sanctum
  │
  ├─ GUARD:
  │   ├─ If !isVerified() → 403 (no contacts verified yet, can't skip)
  │   └─ If isFullyVerified() → 403 (both already verified, nothing to skip)
  │
  ├─ AuthService::skipAdditionalVerification($member)
  │   ├─ status = 'pending_onboarding', state = 'pending_onboarding'
  │   ├─ Deletes current token
  │   └─ Issues new token with ['onboarding']
  │
  └─ Allowed only when: exactly 1 contact verified, the other not
```

### 6. Complete Registration

```
POST /api/v1/auth/complete-registration
  │
  ├─ Middleware: api.access, auth:sanctum, verified.account
  │   (verified.account ensures at least 1 contact verified)
  │
  ├─ CompleteRegistrationRequest validates:
  │     name, email, phone, date_of_birth, gender, is_parent_account
  │
  ├─ AuthService::completeRegistration($member, $dto)
  │   ├─ Updates: name, email, phone, date_of_birth, gender, is_family_account
  │   ├─ Sets: status = 'active', state = 'active'
  │   ├─ Sets: onboarding_completed_at = now()
  │   ├─ Deletes ALL existing tokens
  │   └─ Issues new token with ['*'] ability
  │
  └─ Return: new full-access token + member data
```

### 7. Account Deletion

```
POST /api/v1/auth/delete-account
  │
  ├─ Middleware: api.access, auth:sanctum
  ├─ Validates password
  │
  ├─ MemberService::scheduleAccountDeletion($member, 48 hours)
  │   ├─ scheduled_for_deletion_at = now() + 48h
  │   ├─ Sends AccountDeletionScheduled notification (email + SMS)
  │   └─ Revokes ALL tokens
  │
  └─ Within 48 hours:
      ├─ Login → returns pending_deletion_cancellation, OTP sent
      ├─ Verify OTP → scheduled_for_deletion_at = null, deletion cancelled
      │
      └─ After 48 hours (no cancellation):
          └─ ProcessAccountDeletions scheduled command deletes the member
```

---

## Token Abilities by State

| Login Response State | Sanctum Token Ability | What Can Be Accessed |
|---|---|---|
| `pending_deletion_cancellation` | `['deletion-cancellation']` | `verify-otp` (to cancel deletion), `notifications` |
| `pending_verification` | `['verification']` | `verify-email`, `verify-phone`, `verification-status`, `notifications`, `logout` |
| `pending_additional_verification` | `['verification']` | Same as above |
| `pending_onboarding` | `['onboarding']` | `complete-registration`, `skip-additional-verification`, verification endpoints, `notifications`, `logout` |
| `active` | `['*']` | Everything (full platform access) |

> Token abilities are advisory metadata. The middleware gates (`verified.account`, `onboarding.completed`) are the primary enforcement mechanism.

---

## Edge Cases

### Unverified Accounts in `pending_onboarding` State

**Scenario**: `state='pending_onboarding'`, `email_verified_at=null`, `phone_verified_at=null`, but `onboarding_completed_at` is set.

**What happens on login**: The login flow checks `pending_onboarding` at step 4 (which doesn't match since it checks for `pending_verification`/`pending_additional_verification`). Then step 5 checks `isVerified()` which returns `false`. The member is treated as `pending_additional_verification` — effectively sent back to verification.

**Protection**: `isOnboardingCompleted()` requires BOTH `onboarding_completed_at !== null` AND at least one verified contact. An admin or bug that sets the timestamp without verification is caught by the login flow.

### Password Reset Blocked for Unverified Accounts

Both `forgot-password` and `reset-password` check `!$user->isVerified()` and return **403** with `code: EMAIL_NOT_VERIFIED` if no contacts are verified, preventing unverified accounts from resetting passwords.

### Complete Registration While Unverified

`complete-registration` is gated by `verified.account` middleware. The `AuthService::completeRegistration()` method itself does not verify contacts — it trusts the middleware. If a member reaches this endpoint without any verified contact, the middleware blocks with **403** `ADDITIONAL_VERIFICATION_REQUIRED`.

### Token Rotation on State Transitions

| Action | Token Behavior |
|--------|---------------|
| `complete-registration` | Deletes ALL tokens, issues fresh `[*]` token |
| `skip-additional-verification` | Deletes current token, issues fresh `[onboarding]` token |
| `verify-email` / `verify-phone` | Deletes current token, issues new token with state-appropriate abilities |
| `delete-account` | Deletes ALL tokens |

### Account Deletion OTP Broadcasting

When a member with deletion scheduled logs in, OTP is sent to ALL verified channels (both email AND SMS, not just the preferred one). Verifying the OTP clears the deletion schedule.

---

## Rate Limiting

All auth rate limiters are defined in `RateLimitServiceProvider`. Currently set to `Limit::none()` in non-production. Production limits:

| Limiter | Endpoints | Production Limit |
|---------|-----------|-----------------|
| `api.auth` | `login`, `register`, `complete-registration` | 10/min per IP + 10/min per identifier |
| `api.otp` | `send-otp`, `verify-otp`, `forgot-password`, `reset-password`, `request-family-otp` | 5 per 10min per IP + 5 per 10min per identifier |
| `api.password` | `update-password` | 5/min per IP + 5/min per user |

---

## Key Files Reference

| File | Role |
|------|------|
| `app/Models/Member.php` | State columns (`status`, `state`), verification/onboarding checks |
| `app/Services/Auth/AuthOrchestrationService.php` | Login decision tree, OTP verification orchestration |
| `app/Services/Auth/AuthService.php` | Registration, complete-registration, skip-additional-verification |
| `app/Services/Auth/OtpService.php` | OTP generation, verification (with embedded state transitions) |
| `app/Http/Middleware/EnsureAccountIsVerified.php` | `verified.account` middleware |
| `app/Http/Middleware/EnsureOnboardingIsCompleted.php` | `onboarding.completed` middleware |
| `app/Http/Controllers/Api/V1/AuthController.php` | All auth API endpoints |
| `app/Services/Members/MemberService.php` | Account deletion scheduling |
| `app/Providers/RateLimitServiceProvider.php` | Rate limiter definitions |
| `app/DTOs/Auth/LoginDTO.php` | Login input |
| `app/DTOs/Auth/RegisterDTO.php` | Registration input |
| `app/DTOs/Auth/CompleteRegistrationDTO.php` | Complete-registration input |
| `routes/api.php` | Route definitions with middleware assignments |
