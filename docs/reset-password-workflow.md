# Reset Password Workflow

Flutter-ready documentation for the API password reset flow.

## Overview

The reset password flow is OTP-based and does not require an authenticated session. The user:

1. Requests a reset OTP with their email or phone identifier
2. Receives the OTP by email or SMS
3. Submits the OTP with a new password
4. Logs in again with the new password

---

## Step 1: Request Reset OTP

**Endpoint:** `POST /api/v1/auth/forgot-password`

**Authentication:** None

**Request Body:**
```json
{
  "identifier": "abdelbaribenkamel@gmail.com"
}
```

The `identifier` can be:
- Email address
- Phone number

**Success Response (200):**
```json
{
  "success": true,
  "message": "If an account exists with this identifier, an OTP has been sent.",
  "data": null
}
```

**Important behavior:**
- The API returns a generic success message even when no account exists.
- This avoids account enumeration.
- If the identifier belongs to a member account that is not verified yet, the API returns a 403 error.

**Error Responses:**

| Status | Message | Code | Meaning |
|--------|---------|------|---------|
| 403 | `Your account is not verified. Please verify your account first.` | `EMAIL_NOT_VERIFIED` | Member account is not verified yet |
| 422 | OTP generation error message | - | OTP service failed or rate limit/cooldown issue |
| 429 | Throttle response | - | Too many reset requests |

**Flutter notes:**
- Show the same generic confirmation message for privacy.
- If the API returns 403, route the user to the account verification flow instead of the reset form.
- Keep the `identifier` so it can be reused in Step 2.

---

## Step 2: Submit OTP and New Password

**Endpoint:** `POST /api/v1/auth/reset-password`

**Authentication:** None

**Request Body:**
```json
{
  "identifier": "abdelbaribenkamel@gmail.com",
  "otp": "123456",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

### Validation Rules

- `identifier` is required
- `otp` is required and must be exactly 6 characters
- `password` must satisfy the app's default password rules
- `password_confirmation` is required because the password uses the `confirmed` rule

### Success Response (200)
```json
{
  "success": true,
  "message": "Password reset successfully.",
  "data": null
}
```

### Error Responses

| Status | Message | Meaning |
|--------|---------|---------|
| 403 | `Your account is not verified.` | Member account is still unverified |
| 404 | `User not found.` | No account matches the identifier |
| 422 | `Invalid or expired OTP code.` | Wrong OTP or expired OTP |
| 422 | Password validation error | Password does not meet app rules |
| 429 | Throttle response | Too many attempts |

### What the backend does

On successful OTP verification, the backend:
- Verifies the OTP against the identifier
- Marks the OTP as used
- Hashes and stores the new password
- Returns success without issuing a login token

For member accounts, the OTP verification step can also update verification timestamps and member state internally before the password is changed.

---

## Step 3: Log In Again

After a successful reset, the user should log in again using the new password.

**Login Endpoint:** `POST /api/v1/auth/login`

Do not assume the reset flow returns a session or token. The user must authenticate again after the password is changed.

---

## Flutter Integration Example

### Request OTP

```dart
Future<void> requestPasswordResetOtp(String identifier) async {
  final response = await http.post(
    Uri.parse('https://api.bourgoarena.com/api/v1/auth/forgot-password'),
    headers: {
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'identifier': identifier,
    }),
  );

  final data = jsonDecode(response.body);

  if (response.statusCode == 200) {
    // Show generic confirmation message.
    showSuccess(data['message']);
    navigateTo(ResetPasswordOtpScreen(identifier: identifier));
  } else if (response.statusCode == 403 && data['code'] == 'EMAIL_NOT_VERIFIED') {
    showError(data['message']);
    // Redirect to account verification flow.
  } else {
    showError(data['message'] ?? 'Failed to request password reset OTP.');
  }
}
```

### Submit OTP + New Password

```dart
Future<void> resetPassword({
  required String identifier,
  required String otp,
  required String password,
  required String passwordConfirmation,
}) async {
  final response = await http.post(
    Uri.parse('https://api.bourgoarena.com/api/v1/auth/reset-password'),
    headers: {
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'identifier': identifier,
      'otp': otp,
      'password': password,
      'password_confirmation': passwordConfirmation,
    }),
  );

  final data = jsonDecode(response.body);

  if (response.statusCode == 200) {
    showSuccess(data['message']);

    // Clear any temporary reset state.
    await secureStorage.delete(key: 'password_reset_identifier');
    await secureStorage.delete(key: 'password_reset_otp');

    // Route user to login screen.
    navigateTo(LoginScreen());
  } else {
    showError(data['message'] ?? 'Password reset failed.');
  }
}
```

---

## Recommended UI Flow

```
┌─────────────────────────────────────────┐
│  Forgot Password Screen                 │
│                                         │
│  [Email or Phone Field]                 │
│  [Send Reset OTP Button]                │
└────────────┬────────────────────────────┘
             ↓
┌─────────────────────────────────────────┐
│  OTP Verification + New Password        │
│                                         │
│  [OTP Input: 6 digits]                 │
│  [New Password Field]                  │
│  [Confirm Password Field]              │
│  [Reset Password Button]               │
└────────────┬────────────────────────────┘
             ↓
┌─────────────────────────────────────────┐
│  Password Reset Successful              │
│                                         │
│  [Go to Login Screen]                   │
└─────────────────────────────────────────┘
```

---

## Important Implementation Notes

- Use the same `identifier` for both steps.
- The OTP is exactly 6 digits.
- The backend does not issue a login token after reset.
- After success, the user must log in again.
- Preserve the identifier in local state or secure storage only for the duration of the reset flow.
- If the user is a member and not yet verified, send them through the verification flow before password reset.
- The API may deliver OTP by email or SMS depending on the account contact and notification routing.

---

## Error Handling Checklist

- [ ] Show a generic success message for unknown identifiers
- [ ] Show verified-account guidance on 403 `EMAIL_NOT_VERIFIED`
- [ ] Validate OTP length client-side before submitting
- [ ] Require password confirmation in the form
- [ ] Handle expired or incorrect OTP with a retry path
- [ ] Handle rate limiting by disabling submit temporarily
- [ ] Clear reset state after success
- [ ] Redirect to login after password reset

---

## Test Checklist

- [ ] Request OTP with a valid email
- [ ] Request OTP with a valid phone number
- [ ] Request OTP with an unknown identifier
- [ ] Request OTP for an unverified member and confirm 403
- [ ] Reset password with valid OTP and matching confirmation
- [ ] Reset password with invalid OTP and confirm 422
- [ ] Reset password with mismatched confirmation and confirm validation error
- [ ] Confirm user must log in again after reset
