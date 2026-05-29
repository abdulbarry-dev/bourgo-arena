# Mobile Backend Auth and Onboarding Changes

This document describes the backend contract changes made to support the mobile app after removing the terminal access feature and the onboarding pin step.

## What Changed

### 1. Registration completion no longer requires a PIN

The `POST /api/v1/auth/complete-registration` endpoint no longer accepts or validates a `pin` field.

Before this change, mobile onboarding had to send:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "225555000",
  "date_of_birth": "1990-01-01",
  "gender": "female",
  "is_parent_account": false,
  "pin": "1234"
}
```

Now the payload is:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "225555000",
  "date_of_birth": "1990-01-01",
  "gender": "female",
  "is_parent_account": false
}
```

The backend still marks onboarding as complete using `onboarding_completed_at` and the verified contact-state checks. The `pin` value is no longer part of that decision.

### 2. Access history endpoint was removed

The endpoint `GET /api/v1/user/access-history` is no longer exposed.

Any mobile screen, repository, or API client calling that route should be removed. The backend now returns `404` for that path.

### 3. Member onboarding state is now independent of PIN data

The backend no longer reads or writes `pin` as part of member onboarding completion.

That means:

- `Member::isOnboardingCompleted()` now depends on `onboarding_completed_at` plus at least one verified contact method.
- Login and verification flows still work the same way.
- Existing database data that may contain a `pin` value is ignored by the onboarding flow.

## Unchanged Flows

These routes and behaviors are unchanged:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/send-otp`
- `POST /api/v1/auth/verify-otp`
- `POST /api/v1/auth/complete-registration` response shape
- `GET /api/v1/user/verification-status`
- `POST /api/v1/user/verify-email`
- `POST /api/v1/user/verify-phone`

The completion response still returns:

- `token`
- `state`
- `user`
- `verification_status`

## Mobile App Update Checklist

1. Remove any PIN entry field from the onboarding completion screen.
2. Stop sending `pin` in the complete-registration request body.
3. Remove or hide any access-history screen, fetcher, or navigation entry.
4. Keep the existing verification and onboarding state handling as-is.
5. Expect `complete-registration` to succeed without any terminal-access or PIN logic.

## Backend Notes For Flutter

If your app currently branches on onboarding completion with a PIN prompt, replace that branch with a direct completion step after the required profile fields are collected.

If your app was relying on access-history data to populate a profile tab, that tab should be removed or redirected to another supported endpoint.