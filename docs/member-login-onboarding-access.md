# Member Login, Verification, and Onboarding Access

This document explains how a member login session is handled when onboarding is incomplete, how the API middleware gates access, and what the Flutter app should do before showing the home screen.

## Mobile Agent Contract

Use the backend response state to drive the Flutter screen:

- `pending_verification` and `pending_additional_verification` stay on the verification flow.
- `pending_onboarding` shows the setup modal with `Complete Setup` and `Cancel` actions.
- `active` enters the home screen.

The app should never infer readiness from UI state alone. Always re-check the API response and verification snapshot after OTP or onboarding completion.

## Goal

A member should only reach the Flutter home screen when both conditions are true:

1. Onboarding is completed.
2. At least one OTP verification method is verified, meaning either email or phone is verified.

If either condition is missing, the app must keep the user in the appropriate verification or onboarding flow.

## Main Request Flow

### Login endpoint

The login route is defined in [routes/api.php](routes/api.php). It points to [AuthController@login](app/Http/Controllers/Api/V1/AuthController.php).

At login, the backend does not immediately force the user to finish onboarding. Instead, it inspects the member state and returns a token with limited abilities when needed:

- `pending_verification` or `pending_additional_verification` returns a verification-scoped token.
- `pending_onboarding` returns an onboarding-scoped token.
- `active` returns a full-access token.

The key login branch is in [app/Http/Controllers/Api/V1/AuthController.php](app/Http/Controllers/Api/V1/AuthController.php#L38) and the onboarding branch is at [app/Http/Controllers/Api/V1/AuthController.php](app/Http/Controllers/Api/V1/AuthController.php#L87).

### Member state helpers

Member state and verification status are derived from the model helpers in [app/Models/Member.php](app/Models/Member.php):

- [isVerified](app/Models/Member.php#L72) returns true if email or phone is verified.
- [getVerificationStatus](app/Models/Member.php#L87) returns the verification snapshot used by the API.
- [isOnboardingCompleted](app/Models/Member.php#L100) checks whether onboarding is done.

## Middleware Gates

API routes that require full member access are grouped under `auth:sanctum`, `verified.account`, and `onboarding.completed` in [routes/api.php](routes/api.php#L49).

Those aliases are registered in [app/Providers/MiddlewareServiceProvider.php](app/Providers/MiddlewareServiceProvider.php#L64) and [app/Providers/MiddlewareServiceProvider.php](app/Providers/MiddlewareServiceProvider.php#L65).

### `verified.account`

[EnsureAccountIsVerified](app/Http/Middleware/EnsureAccountIsVerified.php#L19) blocks a member when neither email nor phone is verified.

If both are unverified, the middleware returns:

- HTTP 403
- `code: ADDITIONAL_VERIFICATION_REQUIRED`
- `state: pending_additional_verification`

### `onboarding.completed`

[EnsureOnboardingIsCompleted](app/Http/Middleware/EnsureOnboardingIsCompleted.php#L19) blocks a member when onboarding is not complete.

If onboarding is incomplete, the middleware returns:

- HTTP 403
- `message: Must complete onboarding to access your account.`
- `code: ONBOARDING_INCOMPLETE`
- `state: pending_onboarding`
- `required_action: complete_onboarding`
- `cta: Complete Setup`

## Login Session Behavior

### Case 1: Member has not verified any OTP method

If the member has neither verified email nor verified phone, login returns a verification state instead of home access.

Expected app behavior:

- Keep the user in the OTP verification flow.
- Do not navigate to home.
- After verification, refresh the token and re-check state.

### Case 2: Member has at least one verified OTP method, but onboarding is incomplete

If one verification method is complete and onboarding is still pending, login returns:

- `state: pending_onboarding`
- an onboarding-scoped token
- `code: ONBOARDING_INCOMPLETE`
- `required_action: complete_onboarding`
- `cta: Complete Setup`

The member should be routed to the onboarding flow, not home.

### Case 3: Member has verified at least one method and completed onboarding

Only this case should open the Flutter home screen.

The app can treat the session as ready when:

- `state === active`, or
- `verification_status.onboarding_completed === true` and at least one of `email_verified` or `phone_verified` is true.

If login returns `pending_onboarding`, the app should show a modal that says the account setup is not completed, with a `Complete Setup` button that continues onboarding and a cancel button that closes the modal and stays on the login screen.

## OTP Verification Flow

Email and phone verification are handled by [AuthController@verifyEmail](app/Http/Controllers/Api/V1/AuthController.php#L403) and [AuthController@verifyPhone](app/Http/Controllers/Api/V1/AuthController.php#L456).

After a successful OTP check, the backend refreshes the member, updates the token abilities, and returns the latest state plus verification snapshot.

Important transitions:

- If the member is still pending onboarding, the response keeps `state: pending_onboarding`.
- If onboarding is complete, the response can move the member to `active`.

## Onboarding Completion Flow

Onboarding is completed through [AuthController@completeRegistration](app/Http/Controllers/Api/V1/AuthController.php#L359).

When onboarding is finished:

- The member record is updated with profile data.
- `onboarding_completed_at` is set.
- The current token is revoked.
- A new full-access token is issued.
- The member becomes `active`.

## Flutter Navigation Rule

Use the API response state and verification snapshot to decide where the user goes next:

### Navigate to OTP verification

When login returns:

- `pending_verification`
- `pending_additional_verification`

### Navigate to onboarding

When login returns:

- `pending_onboarding`

### Navigate to home

Only when both are true:

- onboarding is complete
- at least one OTP method is verified

If the backend response does not clearly show that both conditions are satisfied, keep the user out of the home screen and re-check the verification status first.

## Practical Summary

- Login is permissive and returns a limited token when the member is not ready.
- Middleware blocks protected routes until verification and onboarding are both satisfied.
- The Flutter app should use the login state as the first router, then use `verification_status` as the final gate before home.
- The modal copy should use the same wording everywhere: `Account setup is not completed` and `Complete Setup`.

## Related Files

- [routes/api.php](routes/api.php)
- [app/Http/Controllers/Api/V1/AuthController.php](app/Http/Controllers/Api/V1/AuthController.php)
- [app/Http/Middleware/EnsureAccountIsVerified.php](app/Http/Middleware/EnsureAccountIsVerified.php)
- [app/Http/Middleware/EnsureOnboardingIsCompleted.php](app/Http/Middleware/EnsureOnboardingIsCompleted.php)
- [app/Models/Member.php](app/Models/Member.php)
- [app/Providers/MiddlewareServiceProvider.php](app/Providers/MiddlewareServiceProvider.php)
