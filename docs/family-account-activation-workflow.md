# Family Account Activation Workflow

Complete end-to-end workflow for enabling family account feature after OTP verification.

## Overview

The family account feature allows a verified member to manage child family members under their parent account. The activation flow is a 3-step process:

1. **Request OTP** — Member requests OTP for family feature verification
2. **Verify OTP** — Member submits OTP code and receives auth token
3. **Enable Family Feature** — Member enables family account feature with the token

---

## Step 1: Request Family OTP

**Endpoint:** `POST /api/v1/auth/request-family-otp`

**Authentication:** Required (`Bearer {token}`)

**Permissions:** Only members (not staff/managers)

**Request Headers:**
```json
{
  "Authorization": "Bearer {authenticated_token}",
  "Content-Type": "application/json"
}
```

**Request Body (Optional):**
```json
{
  "method": "email"  // or "phone" / "sms" / omit for auto-detect
}
```

**Method Logic:**
- `"email"` — Send OTP to verified email (requires `email_verified_at` to be set)
- `"phone"` or `"sms"` — Send OTP to verified phone (requires `phone_verified_at` to be set)
- Omitted/null — Auto-detect: prefers verified phone → verified email → any available contact

**Success Response (200):**
```json
{
  "success": true,
  "message": "OTP code sent to your registered email.",
  "data": null
}
```

**Error Responses:**

| Status | Error Message | Reason |
|--------|---------------|--------|
| 403 | "Only members can request family OTP." | User is staff/manager, not a member |
| 422 | "Your email is not verified." | Selected email but not verified |
| 422 | "Your phone number is not verified." | Selected phone but not verified |
| 422 | "No contact information found for this account." | No email or phone available |
| 429 | "Too many OTP requests..." | Rate limited (3 per 5 minutes) |

---

## Step 2: Verify OTP

**Endpoint:** `POST /api/v1/auth/verify-otp`

**Authentication:** Not required (OTP verification is entry-level auth)

**Request Body:**
```json
{
  "identifier": "abdelbaribenkamel@gmail.com",  // email or phone used in Step 1
  "otp": "123456"  // 6-digit code from email/SMS
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "OTP verified successfully.",
  "data": {
    "valid": true,
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "state": "active",  // "active", "pending_verification", "pending_onboarding", etc.
    "user": {
      "id": "11",
      "name": "test testtor",
      "first_name": "test",
      "last_name": "testtor",
      "email": "abdelbaribenkamel@gmail.com",
      "phone": "22555666",
      "avatar_url": null,
      "loyalty_points": 0,
      "birth_date": "2006-05-23",
      "gender": "male",
      "status": "active",
      "state": "active",
      "is_parent_account": false,  // ← Currently false, will be enabled in Step 3
      "subscription_level": null,
      "subscription_expiry": null,
      "total_check_ins": 0,
      "children": []
    },
    "verification_status": {
      "email_verified": true,
      "phone_verified": false,
      "is_verified": true
    }
  }
}
```

**Error Responses:**

| Status | Error Message | Reason |
|--------|---------------|--------|
| 422 | "Invalid or expired OTP code." | Wrong OTP or expired (typically 10 min TTL) |
| 429 | "Too many OTP verification attempts..." | Rate limited (3 per 5 minutes) |

**Key Points:**
- Token returned is valid for full member access (including protected endpoints)
- `is_parent_account` will be `false` at this point — do NOT show family features yet
- Token has full capabilities (`*` abilities)
- State indicates account status (use this to decide next screen)

---

## Step 3: Enable Family Account Feature

**Endpoint:** `POST /api/v1/family/enable-feature`

**Authentication:** Required (`Bearer {token}`)

**Middleware Requirements:**
- `auth:sanctum` — Must be authenticated
- `verified.account` — Account must be verified
- `onboarding.completed` — Onboarding must be completed

**Request Headers:**
```json
{
  "Authorization": "Bearer {token_from_step_2}",
  "Content-Type": "application/json"
}
```

**Request Body:**
```json
{}  // No body needed
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Family account feature enabled successfully",
  "data": {
    "id": "11",
    "name": "test testtor",
    "first_name": "test",
    "last_name": "testtor",
    "email": "abdelbaribenkamel@gmail.com",
    "phone": "22555666",
    "avatar_url": null,
    "loyalty_points": 0,
    "birth_date": "2006-05-23",
    "gender": "male",
    "status": "active",
    "state": "active",
    "is_parent_account": true,  // ← NOW ENABLED ✓
    "subscription_level": null,
    "subscription_expiry": null,
    "total_check_ins": 0,
    "children": []
  }
}
```

**Error Responses:**

| Status | Error Message | Reason |
|--------|---------------|--------|
| 401 | "Unauthenticated." | Token missing, invalid, or expired |
| 403 | "Forbidden." | User is not a member |
| 400 | "Family account feature already enabled" | Already a parent account |

---

## Complete Flutter Integration Example

### Step 1: Request OTP

```dart
// Request family OTP with auto-detection of contact method
Future<void> requestFamilyOtp() async {
  try {
    final response = await http.post(
      Uri.parse('https://api.bourgoarena.com/api/v1/auth/request-family-otp'),
      headers: {
        'Authorization': 'Bearer $userToken',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      print('OTP sent: ${data['message']}'); // "OTP code sent to your registered email."
      
      // Navigate to OTP verification screen
      navigateTo(OtpVerificationScreen());
    } else {
      final error = jsonDecode(response.body);
      showError(error['message']); // Show error message to user
    }
  } catch (e) {
    showError('Failed to request OTP: $e');
  }
}
```

### Step 2: Verify OTP

```dart
// User enters 6-digit OTP code
Future<bool> verifyFamilyOtp(String email, String otpCode) async {
  try {
    final response = await http.post(
      Uri.parse('https://api.bourgoarena.com/api/v1/auth/verify-otp'),
      headers: {
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'identifier': email,
        'otp': otpCode,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final userData = data['data'];
      final familyToken = userData['token'];
      
      // Store token for next step
      await secureStorage.write(key: 'family_token', value: familyToken);
      
      // Check user state
      if (userData['state'] == 'active') {
        print('OTP verified successfully!');
        // Proceed to Step 3
        return true;
      } else {
        showError('Account state: ${userData['state']}');
        return false;
      }
    } else {
      final error = jsonDecode(response.body);
      showError(error['message']); // "Invalid or expired OTP code."
      return false;
    }
  } catch (e) {
    showError('OTP verification failed: $e');
    return false;
  }
}
```

### Step 3: Enable Family Feature

```dart
// Enable family account feature
Future<void> enableFamilyFeature() async {
  try {
    final token = await secureStorage.read(key: 'family_token');
    
    final response = await http.post(
      Uri.parse('https://api.bourgoarena.com/api/v1/family/enable-feature'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final updatedUser = data['data'];
      
      // Verify family account is enabled
      if (updatedUser['is_parent_account'] == true) {
        print('Family account enabled successfully!');
        
        // Store updated user data
        await secureStorage.write(key: 'user_data', value: jsonEncode(updatedUser));
        
        // Navigate to family management screen
        navigateTo(ManageFamilyScreen());
      }
    } else {
      final error = jsonDecode(response.body);
      showError(error['message']);
    }
  } catch (e) {
    showError('Failed to enable family feature: $e');
  }
}
```

### Complete Workflow in One Flow

```dart
Future<void> activateFamilyAccountFeature() async {
  // Step 1: Request OTP
  await requestFamilyOtp();
  
  // User navigates to OTP screen and enters code...
  // Step 2: Verify OTP (triggered by user submitting OTP form)
  bool otpVerified = await verifyFamilyOtp(userEmail, otpCode);
  
  if (otpVerified) {
    // Step 3: Enable family feature
    await enableFamilyFeature();
  }
}
```

---

## UI/UX Flow for Flutter

```
┌─────────────────────────────────────────┐
│  Family Account Setup Screen            │
│                                         │
│  [Request OTP Button]                   │
│  "Send verification code to your       │
│   registered email/phone"              │
└────────────┬────────────────────────────┘
             ↓
┌─────────────────────────────────────────┐
│  OTP Verification Screen                │
│                                         │
│  Enter 6-digit code sent to:            │
│  abdelbaribenkamel@gmail.com            │
│                                         │
│  [Input Field: _ _ _ _ _ _]             │
│  [Verify Button]                        │
│  [Resend OTP Link]                      │
└────────────┬────────────────────────────┘
             ↓
┌─────────────────────────────────────────┐
│  Activating Family Feature...           │
│  (Show loading spinner)                 │
└────────────┬────────────────────────────┘
             ↓
┌─────────────────────────────────────────┐
│  ✓ Family Account Activated!            │
│                                         │
│  You can now manage family members      │
│  Add children and manage their access   │
│                                         │
│  [Go to Family Management] (or auto)    │
└─────────────────────────────────────────┘
```

---

## After Family Feature is Enabled

Once family account is activated (`is_parent_account = true`), the following endpoints become available:

### Family Management Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/family/children` | List all child members |
| POST | `/api/v1/family/children` | Add a new child member |
| PUT | `/api/v1/family/children/{member}` | Update child info |
| DELETE | `/api/v1/family/children/{member}` | Remove child from family |
| POST | `/api/v1/family/disable-feature` | Disable family feature (archive children) |

**Note:** All family endpoints require:
- `auth:sanctum` — Authenticated member
- `verified.account` — Account verified
- `onboarding.completed` — Onboarding done

---

## Key Data Model Fields

```
Member Model:
├── is_family_account (boolean) — Indicates if member is parent account
├── parent_id (nullable) — ID of parent (null for parent, set for children)
├── children (relationship) — Array of child Member records
├── is_archived (boolean) — For children, indicates if archived
├── status (string) — 'active', 'pending_verification', 'pending_onboarding', etc.
└── state (string) — Account state from status
```

---

## Error Handling Checklist

- [ ] Handle rate limiting (429) — Show "Wait X minutes before trying again"
- [ ] Handle invalid OTP (422) — Show "Incorrect code, please try again"
- [ ] Handle expired OTP (422) — Show "Code expired, request new one"
- [ ] Handle unverified email/phone — Show "Please verify your email/phone first"
- [ ] Handle network errors — Show "Connection failed, please try again"
- [ ] Handle already-enabled family — Show "Family feature already enabled"
- [ ] Store token securely (use Flutter secure_storage or similar)
- [ ] Refresh user profile after enabling to confirm state

---

## Testing Checklist

- [ ] Test full 3-step workflow with valid OTP
- [ ] Test invalid OTP code rejection
- [ ] Test expired OTP handling
- [ ] Test rate limiting after 3 failed attempts
- [ ] Test unverified email/phone rejection
- [ ] Test token persistence across app restart
- [ ] Test family features appear only after `is_parent_account = true`
- [ ] Test navigation flow from OTP verification to family management
- [ ] Test offline handling (queue requests or show error)
- [ ] Test logout clears stored token and family state
