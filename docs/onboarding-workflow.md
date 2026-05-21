# Account Onboarding Workflow

Complete Flutter-ready documentation for the account onboarding flow for both newly registered accounts and returning accounts with incomplete onboarding.

## Overview

Account onboarding happens in two scenarios:

1. **New Registration** — A new user registers, verifies email/phone, then completes onboarding
2. **Login with Pending Onboarding** — An existing user who didn't complete onboarding logs in and is prompted to finish

Both scenarios converge to the same `complete-registration` endpoint.

## Flutter Mobile Contract

Use the backend state as the source of truth.

- `pending_verification` or `pending_additional_verification` means the user must complete OTP verification first.
- `pending_onboarding` means the user is authenticated but must finish setup before accessing the home screen.
- `active` means the user can enter the app normally.

When login returns `pending_onboarding`, the app should show a modal that says the account setup is not completed, with a `Complete Setup` button that continues onboarding and a `Cancel` button that closes the modal and keeps the user on the login screen.

---

## Flow 1: New Registration → Onboarding

### Step 1: Register Account

**Endpoint:** `POST /api/v1/auth/register`

**Authentication:** None

**Request Body:**
```json
{
  "email": "john@example.com",
  "phone": "22555666",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!"
}
```

### Validation Rules

- `email` — required, must be valid email, unique across members
- `phone` — required, string, max 20 chars, unique across members
- `password` — required, string, must pass default password rules, confirmed

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email/phone.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "state": "pending_verification",
    "user": {
      "id": "11",
      "name": null,
      "email": "john@example.com",
      "phone": "22555666",
      "date_of_birth": null,
      "gender": null,
      "is_parent_account": false,
      "status": "pending_verification",
      "state": "pending_verification"
    },
    "verification_status": {
      "email_verified": false,
      "phone_verified": false,
      "is_verified": false
    }
  }
}
```

**What Happens:**
- Account is created in `pending_verification` state
- OTP is automatically generated and sent to the email/phone
- Token returned has `verification` ability only (limited scope)
- User must now verify their email or phone (see separate verification workflow)

### Step 2: Verify Email/Phone

The user must verify at least one contact method. Refer to your account verification workflow doc. After successful verification, the account state transitions to `pending_onboarding`.

### Step 3: Complete Onboarding

**Endpoint:** `POST /api/v1/auth/complete-registration`

**Authentication:** Required (`Bearer {token_from_step_1}`)

**Middleware Requirements:**
- `auth:sanctum` — Authenticated
- `onboarding.completed` is NOT required on this endpoint (so users with `pending_onboarding` can access it)

**Request Body:**
```json
{
  "name": "John Smith",
  "email": "john@example.com",
  "phone": "22555666",
  "date_of_birth": "1990-05-23",
  "gender": "male",
  "is_parent_account": false,
  "pin": "1234"
}
```

### Onboarding Validation Rules

All fields are required:

| Field | Type | Constraints | Example |
|-------|------|-------------|---------|
| `name` | string | required, max 255 chars | "John Smith" |
| `email` | string | required, valid email, unique (except own), max 255 | "john@example.com" |
| `phone` | string | required, max 20 chars, unique (except own) | "22555666" |
| `date_of_birth` | date | required, must be before today | "1990-05-23" |
| `gender` | string | required, must be `male` or `female` | "male" |
| `is_parent_account` | boolean | required | false |
| `pin` | string | required, exactly 4 digits/chars | "1234" |

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration completed successfully.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "state": "active",
    "user": {
      "id": "11",
      "name": "John Smith",
      "email": "john@example.com",
      "phone": "22555666",
      "date_of_birth": "1990-05-23",
      "gender": "male",
      "is_parent_account": false,
      "status": "active",
      "state": "active",
      "total_check_ins": 0,
      "loyalty_points": 0
    },
    "verification_status": {
      "email_verified": true,
      "phone_verified": false,
      "is_verified": true
    }
  }
}
```

**What Happens:**
- All onboarding fields are saved to the member account
- Account state transitions from `pending_onboarding` to `active`
- Previous token is revoked
- New token is issued with full `*` abilities (no restrictions)
- User now has full app access

**Error Responses:**

| Status | Message | Meaning |
|--------|---------|---------|
| 401 | "Unauthenticated." | Token missing or invalid |
| 422 | Validation error message | Missing or invalid field |

---

## Flow 2: Login with Pending Onboarding

If a user logs in with incomplete onboarding, the login endpoint handles it.

### Step 1: Login

**Endpoint:** `POST /api/v1/auth/login`

**Authentication:** None

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePassword123!"
}
```

Or with phone:
```json
{
  "phone": "22555666",
  "password": "SecurePassword123!"
}
```

**Response when Onboarding is Pending (200):**
```json
{
  "success": true,
  "message": "Must complete onboarding to unlock your account.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "state": "pending_onboarding",
    "code": "ONBOARDING_INCOMPLETE",
    "required_action": "complete_onboarding",
    "cta": "Complete Setup",
    "user": {
      "id": "11",
      "name": null,
      "email": "john@example.com",
      "phone": "22555666",
      "date_of_birth": null,
      "gender": null,
      "is_parent_account": false,
      "status": "pending_onboarding",
      "state": "pending_onboarding"
    },
    "verification_status": {
      "email_verified": true,
      "phone_verified": false,
      "is_verified": true
    }
  }
}
```

**Key Indicators:**
- `state` is `pending_onboarding`
- Token returned has `onboarding` ability only
- User must complete onboarding before accessing the main app
- Flutter should show the setup modal, not the home screen

### Step 2: Complete Onboarding (Same as Flow 1, Step 3)

Use the same `POST /api/v1/auth/complete-registration` endpoint with the same request body and validation rules. See above for details.

---

## Flutter Implementation Details

The Bourgo Arena mobile application uses Clean Architecture principles. Direct HTTP calls and manual routing have been abstracted away:

*   **API Management**: `ApiClient` (`lib/data/api/api_client.dart`) handles HTTP requests.
*   **Authentication Repository**: `ApiAuthRepository` (`lib/data/repositories/api_auth_repository.dart`) defines the operations (`register`, `verifyOtp`, `completeRegistration`).
*   **State Management**: `AuthStateNotifier` retains `AuthSession` including verification and user data.
*   **Routing**: `GoRouter` (`lib/router.dart`) relies on a global configuration to redirect implicitly based on `AuthState`. For instance: `AuthState.pendingOnboarding` routes the user to `/account-setup` unless resting on a specific entry route so it can show the `OnboardingSetupModal`.

### Flow 1: Register → Verify → Onboard

1. **Register User (`RegisterViewModel`)**: Dispatches a call to `registerUseCase`. The back-end returns a `pending_verification` state. `GoRouter` intercepts this enum state change and routes the user to the `/otp` screen to confirm the code.
2. **Verify User (`OtpViewModel`)**: Dispatches a call to `verifyOtpUseCase`. `ApiAuthRepository` unboxes the response, which could update the `state` to either `pending_additional_verification` or `pending_onboarding`. Wait, if `pending_onboarding`, the user is shown the local `OnboardingSetupModal` prior to moving further in the setup.
3. **Complete Onboarding (`AccountSetupViewModel`)**: Form validates fields locally then dials `ApiAuthRepository.completeRegistration`. The new token provides full abilities, updates the `state` to `active`, forcing `GoRouter` to immediately dispatch the user to `/home`.

### Flow 2: Login with Pending Onboarding

1. **Login User (`LoginViewModel`)**: Dialing `loginUseCase` results in an `AuthSession` with a limited token and `pending_onboarding` flag.
2. **Setup Modal Hook (`LoginViewModel.login`)**: The `LoginViewModel` awaits the modal configuration natively:
   ```dart
   if (session.state == AuthState.pendingOnboarding && context.mounted) {
     final shouldComplete = await OnboardingSetupModal.show(context);
     if (shouldComplete == true && context.mounted) {
       context.push('/account-setup');
     }
   }
   ```
3. **Complete Onboarding**: Functions identically to flow 1.

---

## UI Flow Diagrams

### Flow 1: New User Registration → Onboarding

```
┌─────────────────────────────────┐
│  Register Screen                │
│  [Email] [Phone]                │
│  [Password] [Confirm]           │
│  [Register Button]              │
└────────────┬────────────────────┘
             ↓
┌─────────────────────────────────┐
│  Verify Email/Phone             │
│  [OTP Input: 6 digits]          │
│  [Verify Button]                │
│  [Resend OTP Link]              │
└────────────┬────────────────────┘
             ↓
┌─────────────────────────────────┐
│  Onboarding Screen              │
│  [Full Name]                    │
│  [Email] [Phone]                │
│  [Date of Birth]                │
│  [Gender: Male/Female]          │
│  [Family Account: Yes/No]       │
│  [PIN: 4 digits]                │
│  [Complete Registration Button] │
└────────────┬────────────────────┘
             ↓
┌─────────────────────────────────┐
│  ✓ Setup Complete!              │
│  Welcome to the app             │
│  [Continue to Dashboard]        │
└─────────────────────────────────┘
```

### Flow 2: Login with Pending Onboarding

```
┌─────────────────────────────────┐
│  Login Screen                   │
│  [Email or Phone]               │
│  [Password]                     │
│  [Login Button]                 │
└────────────┬────────────────────┘
             ↓
┌─────────────────────────────────┐
│  Account Setup Not Completed    │
│  [Complete Setup] [Cancel]      │
└────────────┬────────────────────┘
             ↓
┌─────────────────────────────────┐
│  Onboarding Screen              │
│  (same form as Flow 1)          │
│  [Complete Registration Button] │
└────────────┬────────────────────┘
             ↓
┌─────────────────────────────────┐
│  ✓ Welcome Back!                │
│  Your account is now active     │
│  [Go to Dashboard]              │
└─────────────────────────────────┘
```

---

## Important Notes

**Token Persistence:**
- After registration, store the `onboarding_token` temporarily
- After onboarding completion, the server issues a new full-access token
- Always replace the token when a new one is issued

**Email/Phone Can Be Updated During Onboarding:**
- The user can provide different email/phone in the onboarding form than the registration form
- These must be unique (unless they were the original values)

**PIN Field:**
- Must be exactly 4 characters
- Can be digits, letters, or mixed
- Used for account security/confirmation later

**Gender Values:**
- Must be exactly `male` or `female` (lowercase)
- No other values are accepted

**State Transitions:**
1. `pending_verification` (after registration)
2. `pending_onboarding` (after email/phone verification)
3. `active` (after onboarding completion)

---

## Error Handling Checklist

- [ ] Handle validation errors on registration (email/phone uniqueness)
- [ ] Handle validation errors on onboarding (date format, gender values, PIN length)
- [ ] Display field-specific validation messages to the user
- [ ] Handle expired tokens on onboarding screen (require re-login)
- [ ] Handle network errors with retry capability
- [ ] Clear temporary tokens on logout or session expiry
- [ ] Prevent navigating back from onboarding to skip it (app logic)

---

## Testing Checklist

- [ ] Test full registration → verification → onboarding flow
- [ ] Test login for account with pending onboarding
- [ ] Test invalid date of birth (future date) and confirm 422
- [ ] Test invalid gender value and confirm 422
- [ ] Test PIN with wrong length and confirm 422
- [ ] Test email uniqueness validation
- [ ] Test phone uniqueness validation
- [ ] Test that old token is revoked after onboarding
- [ ] Test that new token has full `*` abilities
- [ ] Test user can access protected endpoints after onboarding
- [ ] Test login with pending onboarding returns the setup modal state and does not enter home
- [ ] Test logout clears all temporary state
