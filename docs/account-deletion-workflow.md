# Account Deletion Workflow (Member API)

This document specifies the logic and endpoints for the secure 48-hour scheduled account deletion process.

## 1. Requesting Account Deletion

### Endpoint: `POST /api/v1/auth/delete-account`
**Auth Required**: Yes (Bearer Token)

**Request Body**:
```json
{
    "password": "your-current-password"
}
```

**Logic**:
- Validates the password.
- Schedules the account for deletion in **48 hours** (`scheduled_for_deletion_at`).
- Sends a notification to the user's verified Email and/or SMS.
- **Immediately revokes all active session tokens** (logs the user out everywhere).

**Response (200 OK)**:
```json
{
    "success": true,
    "message": "Your account has been scheduled for deletion in 48 hours. You can cancel this by logging back in before then.",
    "data": null
}
```

---

## 2. Cancellation Process (Via Login)

If a user changes their mind, they can cancel the deletion by simply logging back in.

### Step 1: Normal Login
**Endpoint**: `POST /api/v1/auth/login`

**Logic**:
- If the account is scheduled for deletion, the server **will not** grant full access.
- It will automatically trigger an **OTP code** to the user's registered contact method.
- It returns a specific state: `pending_deletion_cancellation`.

**Response (200 OK)**:
```json
{
    "success": true,
    "message": "Your account is scheduled for deletion. An OTP has been sent to your registered contact to cancel the process.",
    "data": {
        "token": "temporary-verification-token",
        "state": "pending_deletion_cancellation",
        "code": "ACCOUNT_DELETION_PENDING",
        "user": { ... },
        "verification_status": { ... }
    }
}
```

### Step 2: Verify OTP to Cancel
**Endpoint**: `POST /api/v1/auth/verify-otp`

**Logic**:
- Upon successful OTP verification, the backend **automatically clears** the `scheduled_for_deletion_at` flag.
- The account is restored to `active` (or its previous state).
- A new full-access token is returned.

---

## 3. Flutter Implementation Notes

### UI/UX Flow:
1.  **Settings Screen**: Add a "Delete Account" button that opens a password confirmation modal.
2.  **Confirmation**: On success, show a success message and redirect the user to the Login screen.
3.  **Login Interception**: 
    *   If the login API returns `state == 'pending_deletion_cancellation'`, the app must navigate the user to the **OTP Verification Screen**.
    *   Show a clear message: "Your account is set to be deleted. Enter the code sent to your phone/email to cancel this request and restore your account."
4.  **Auto-Restoration**: Once verified, the user is back in and the deletion is canceled. No further action is required from the user.

### Background Cleanup:
- A server-side task runs every hour. 
- If `scheduled_for_deletion_at` is in the past, the account is soft-deleted and can no longer be recovered via login.
