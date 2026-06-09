# Avatar API Documentation

API endpoints for uploading and removing the authenticated member's profile avatar.

## Base URL

All endpoints are relative to `/api/v1/`.

---

## Authentication & Prerequisites

All avatar endpoints require:

| Requirement | Notes |
|---|---|
| **Sanctum token** | Bearer token from login |
| **Verified account** | Email OR phone must be verified |
| **Onboarding completed** | Onboarding flow must be finished |

Authorization failures return `403` with a descriptive JSON body.

### Middleware Error Responses

**Account not verified (403):**
```json
{
  "message": "Your account requires verification.",
  "code": "ADDITIONAL_VERIFICATION_REQUIRED",
  "state": "pending_additional_verification",
  "verification_status": {
    "email_verified": false,
    "phone_verified": false,
    "onboarding_completed": false,
    "is_fully_verified": false,
    "email": "member@example.com",
    "phone": null,
    "unverified_method": "email"
  }
}
```

**Onboarding incomplete (403):**
```json
{
  "message": "Must complete onboarding to access your account.",
  "code": "ONBOARDING_INCOMPLETE",
  "state": "pending_onboarding",
  "required_action": "complete_onboarding",
  "cta": "Complete Setup"
}
```

---

## 1. Upload Avatar

Upload or replace the member's profile avatar.

- **Method:** `POST`
- **URL:** `/api/v1/member/profile/avatar` (primary)
- **Alias:** `POST /api/v1/user/profile/avatar`
- **Content-Type:** `multipart/form-data`

### Request Body

| Field | Type | Required | Constraints |
|---|---|---|---|
| `avatar` | File | Yes | `image`, `mimes:jpg,jpeg,png,webp`, `max:2048` (KB) |

### Validation Errors (422)

```json
{
  "success": false,
  "message": "The avatar field is required. (and 1 more error)",
  "code": "VALIDATION_FAILED",
  "errors": {
    "avatar": [
      "The avatar field is required.",
      "The avatar field must be a file of type: jpg, jpeg, png, webp."
    ]
  }
}
```

### Success Response (200)

The full member profile is returned, with `avatar_url` containing the URL to the uploaded image.

```json
{
  "data": {
    "id": "1",
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "avatar_url": "https://example.com/storage/members/avatars/abc123.webp",
    "loyalty_points": 150,
    "birth_date": "1990-01-01",
    "gender": "male",
    "status": "active",
    "state": "active",
    "is_parent_account": false,
    "preferences": {
      "app": {
        "theme": "system",
        "language": "en"
      },
      "notifications": {
        "push_enabled": true
      }
    },
    "children": [],
    "valid_subscriptions": []
  },
  "success": true,
  "message": "Profile photo updated successfully."
}
```

### Behavior

- If an avatar already exists (and was uploaded via this endpoint, not an external URL), the old file is **deleted from storage** before the new one is stored.
- External URL avatars (e.g. from social login) are not deleted from remote sources.
- Files are stored under `members/avatars/` on the `public` disk.
- Re-uploading replaces the previous avatar immediately.

---

## 2. Delete Avatar

Remove the member's profile avatar.

- **Method:** `DELETE`
- **URL:** `/api/v1/member/profile/avatar` (primary)
- **Alias:** `DELETE /api/v1/user/profile/avatar`
- **Content-Type:** `application/json`

### Request Body

None required.

### Success Response (200)

The full member profile is returned with `avatar_url` set to `null`.

```json
{
  "data": {
    "id": "1",
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "avatar_url": null,
    "loyalty_points": 150,
    "birth_date": "1990-01-01",
    "gender": "male",
    "status": "active",
    "state": "active",
    "is_parent_account": false,
    "preferences": {},
    "children": [],
    "valid_subscriptions": []
  },
  "success": true,
  "message": "Profile photo removed successfully."
}
```

### Behavior

- The stored file is deleted from disk.
- The `avatar` database column is set to `null`.
- If no avatar exists, the operation is a no-op and still returns success.

---

## 3. Get Profile (for reference)

The avatar URL is also available on the profile endpoint.

- **Method:** `GET`
- **URL:** `/api/v1/member/profile`
- **Alias:** `GET /api/v1/user/profile`

The `avatar_url` field in the response follows this logic:

| DB value | `avatar_url` |
|---|---|
| `null` / empty | `null` |
| Full URL (starts with `http`) | Returned as-is |
| Relative path (`members/avatars/abc.webp`) | Prefixed with `{APP_URL}/storage/` |

---

## Authorization Notes

- Only members (the `members` table) can upload/delete avatars. The `User` model (staff/admins) does **not** have avatar support.
- If a non-member token is presented, the endpoint returns `403 Forbidden`.
