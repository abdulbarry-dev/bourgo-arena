# Device Attestation Configuration

This document covers the four environment variables used for mobile device attestation — the security mechanism that verifies requests originate from genuine, untampered app installations.

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture & Flow](#architecture--flow)
3. [Environment Variables](#environment-variables)
   - [PLAY_INTEGRITY_PROJECT_NUMBER](#play_integrity_project_number)
   - [PLAY_INTEGRITY_SERVICE_ACCOUNT_JSON](#play_integrity_service_account_json)
   - [APP_ATTEST_TEAM_ID](#app_attest_team_id)
   - [APP_ATTEST_BUNDLE_ID](#app_attest_bundle_id)
4. [Where to Obtain Each Variable](#where-to-obtain-each-variable)
   - [Google Play Integrity (Android)](#google-play-integrity-android)
   - [Apple App Attest (iOS)](#apple-app-attest-ios)
5. [Current Implementation State](#current-implementation-state)
6. [Behaviour Matrix](#behaviour-matrix)
7. [Related Configuration](#related-configuration)
8. [Testing](#testing)

---

## Overview

When a mobile app (Android or iOS) calls `POST /api/v1/device/register`, it sends an `integrity_token` — a platform-specific cryptographic payload that proves the request came from:

- **Android**: A genuine APK installed from Google Play, running on a device that passes SafetyNet/Play Integrity checks (not rooted, not an emulator, etc.)
- **iOS**: A genuine IPA signed by Apple and installed from the App Store, verified through Apple's App Attest service

The four environment variables below provide the server-side credentials needed to validate these tokens against Google's and Apple's attestation APIs.

---

## Architecture & Flow

```
Mobile App                         Server                          Google / Apple
    │                                 │                                   │
    │  POST /api/v1/device/register   │                                   │
    │  {                              │                                   │
    │    platform: "android",         │                                   │
    │    integrity_token: "base64..." │                                   │
    │  }                              │                                   │
    │────────────────────────────────>│                                   │
    │                                 │                                   │
    │                    RegisterDeviceRequest validates                  │
    │                    (integrity_token required for android/ios)       │
    │                                 │                                   │
    │                    DeviceAttestationService::verify()               │
    │                                 │                                   │
    │                    ┌────────────┴────────────┐                      │
    │                    │  env === local/testing?   │                     │
    │                    └────────────┬────────────┘                      │
    │                         YES │         │ NO                          │
    │                             ▼         ▼                             │
    │              verifyDev(token)    match platform:                    │
    │              token ===           ├─ android → verifyPlayIntegrity() │
    │              config('app.dev_    │              │                   │
    │              integrity_bypass_   │              ▼                   │
    │              token')             │  Check PROJECT_NUMBER &          │
    │                             │    │  SERVICE_ACCOUNT_JSON exist      │
    │                             │    │              │                   │
    │                             │    │  [TODO] Real: call Google Play   │
    │                             │    │  Integrity API to validate token │
    │                             │    │─────────────────────────────────>│
    │                             │    │<────── verdict ──────────────────│
    │                             │    │                                   │
    │                             │    └─ ios → verifyAppAttest()          │
    │                             │              │                        │
    │                             │              ▼                        │
    │                             │  Check TEAM_ID & BUNDLE_ID exist      │
    │                             │              │                        │
    │                             │  [TODO] Real: call Apple's            │
    │                             │  attestation endpoint to validate      │
    │                             │──────────────────────────────────────>│
    │                             │<────── verdict ───────────────────────│
    │                                 │                                   │
    │                      ┌──────────┴──────────┐                        │
    │                      │  integrity passed?   │                        │
    │                      └──────────┬──────────┘                        │
    │                          YES │      │ NO                            │
    │                              ▼       ▼                              │
    │              Generate device  422: "Device integrity                │
    │              access token     verification failed."                 │
    │              (Str::random(64))                                      │
    │                                 │                                   │
    │  <── 201 { token, expires_at }──│                                   │
```

### Affected Endpoint

| Method | Route | Controller | Middleware |
|--------|-------|-----------|------------|
| `POST` | `/api/v1/device/register` | `DeviceRegistrationController::register` | `ForceJsonResponse`, `SetLocale`, `throttle:3,1` |

### Affected Files

| File | Role |
|------|------|
| `config/services.php:49-57` | Reads the four env vars into config |
| `config/app.php:134-139` | Reads `min_app_version` + `dev_integrity_bypass_token` |
| `app/Services/DeviceAttestationService.php` | Core verification logic |
| `app/Http/Controllers/Api/V1/DeviceRegistrationController.php:29-41` | Calls attestation service during registration |
| `app/Http/Requests/Api/V1/RegisterDeviceRequest.php:33-37` | Validates `integrity_token` is present for android/ios |

---

## Environment Variables

### `PLAY_INTEGRITY_PROJECT_NUMBER`

**Service**: Google Play Integrity (Android)

**Used in**: `config/services.php:50`

**Code reference** (`app/Services/DeviceAttestationService.php:25`):
```php
$projectNumber = config('services.play_integrity.project_number');
```

**Description**: The numeric Google Cloud project identifier, not the project ID string. This is required for Google's Play Integrity API to identify which project is making verification requests.

**Format**: A numeric string, e.g. `123456789012`

**Example**:
```
PLAY_INTEGRITY_PROJECT_NUMBER=123456789012
```

---

### `PLAY_INTEGRITY_SERVICE_ACCOUNT_JSON`

**Service**: Google Play Integrity (Android)

**Used in**: `config/services.php:51`

**Code reference** (`app/Services/DeviceAttestationService.php:26`):
```php
$serviceAccountJson = config('services.play_integrity.service_account_json');
```

**Description**: The full JSON key of a Google Cloud service account that has permission to call the Play Integrity API. This is a multi-line JSON object that must be stored as a single line in `.env`.

**Format**: Single-line JSON containing `type`, `project_id`, `private_key_id`, `private_key`, `client_email`, `client_id`, etc.

**Example** (truncated):
```
PLAY_INTEGRITY_SERVICE_ACCOUNT_JSON={"type":"service_account","project_id":"my-project","private_key_id":"abc123","private_key":"-----BEGIN PRIVATE KEY-----\nMIIEvQ...\n-----END PRIVATE KEY-----\n","client_email":"play-integrity@my-project.iam.gserviceaccount.com","client_id":"123456789012345678901","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://oauth2.googleapis.com/token"}
```

> **Important**: When pasting the JSON into `.env`, remove all newlines. The entire JSON must be on one line. Use `echo $(cat service-account.json)` to flatten it from the terminal.

---

### `APP_ATTEST_TEAM_ID`

**Service**: Apple App Attest (iOS)

**Used in**: `config/services.php:55`

**Code reference** (`app/Services/DeviceAttestationService.php:40`):
```php
$teamId = config('services.app_attest.team_id');
```

**Description**: Your Apple Developer Program Team ID. A 10-character alphanumeric string that identifies your development team. Required by Apple's App Attest service to verify attestation objects.

**Format**: Exactly 10 alphanumeric characters, e.g. `A1B2C3D4E5`

**Example**:
```
APP_ATTEST_TEAM_ID=A1B2C3D4E5
```

---

### `APP_ATTEST_BUNDLE_ID`

**Service**: Apple App Attest (iOS)

**Used in**: `config/services.php:56`

**Code reference** (`app/Services/DeviceAttestationService.php:41`):
```php
$bundleId = config('services.app_attest.bundle_id');
```

**Description**: The bundle identifier of your iOS app as registered in Xcode and App Store Connect. This is the unique identifier that ties the attestation to your specific app.

**Format**: Reverse-domain notation, e.g. `com.example.app`

**Example**:
```
APP_ATTEST_BUNDLE_ID=com.bourgo.arena
```

---

## Where to Obtain Each Variable

### Google Play Integrity (Android)

#### Step 1: Enable the Play Integrity API

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Select or create a project for your app
3. Navigate to **APIs & Services** → **Library**
4. Search for "Play Integrity API" and click **Enable**
5. Note the **Project Number** from the project dropdown (top bar) or from **IAM & Admin** → **Settings** → **Project number**

#### Step 2: Create a Service Account

1. Navigate to **IAM & Admin** → **Service Accounts**
2. Click **+ Create Service Account**
3. Name: `play-integrity-verifier` (or similar)
4. Click **Create and Continue**
5. Assign the role: **Play Integrity API** → **Play Integrity Verifier**
6. Click **Done**

#### Step 3: Generate the JSON Key

1. In the Service Accounts list, find the newly created account
2. Click the three-dot menu ⋮ → **Manage Keys**
3. Click **Add Key** → **Create New Key** → **JSON**
4. Download the JSON file
5. Flatten it into a single line:
   ```bash
   echo $(cat /path/to/downloaded-key.json)
   ```
6. Copy the output into `.env` as `PLAY_INTEGRITY_SERVICE_ACCOUNT_JSON=...`

#### Step 4: Link Play Console to Cloud Project

1. Go to [Google Play Console](https://play.google.com/console/)
2. Select your app
3. Navigate to **Setup** → **API access** (under "Play Console" services)
4. Link the Google Cloud project you used above
5. Ensure the service account has been granted access

#### Summary Table

| Variable | Where in Google Cloud Console |
|----------|-------------------------------|
| `PLAY_INTEGRITY_PROJECT_NUMBER` | Dashboard → Project info → **Project number** |
| `PLAY_INTEGRITY_SERVICE_ACCOUNT_JSON` | IAM & Admin → Service Accounts → **Keys → Add Key → JSON** |

---

### Apple App Attest (iOS)

#### Step 1: Get Your Team ID

1. Go to [Apple Developer](https://developer.apple.com/account)
2. Sign in with your Apple Developer Program account
3. Click **Membership** in the sidebar
4. Copy the **Team ID** — it's a 10-character alphanumeric string

#### Step 2: Get Your Bundle ID

1. Go to [App Store Connect](https://appstoreconnect.apple.com/)
2. Navigate to **My Apps** → select your app
3. Under **App Information** → **General Information**, find the **Bundle ID**
4. Alternatively, in Xcode: open your project → select the target → **General** tab → **Bundle Identifier**

> **Note**: The bundle ID must be registered in your Apple Developer account under **Certificates, Identifiers & Profiles** → **Identifiers**. It must also have the **App Attest** capability enabled.

#### Summary Table

| Variable | Where in Apple Ecosystem |
|----------|--------------------------|
| `APP_ATTEST_TEAM_ID` | [developer.apple.com/account](https://developer.apple.com/account) → **Membership** → Team ID |
| `APP_ATTEST_BUNDLE_ID` | Xcode → Target → General → **Bundle Identifier** (e.g. `com.bourgo.arena`) |

---

## Current Implementation State

> **Status as of 2026-06-10**: The attestation verification methods are **stubbed** (placeholder implementations). The current code does **not** perform actual cryptographic verification against Google or Apple's APIs.

### Stub Behaviour

**`verifyPlayIntegrity()`** (`app/Services/DeviceAttestationService.php:20-33`):
```php
protected function verifyPlayIntegrity(string $token): bool
{
    $projectNumber = config('services.play_integrity.project_number');
    $serviceAccountJson = config('services.play_integrity.service_account_json');

    if (! $projectNumber || ! $serviceAccountJson) {
        return false;       // ← Rejects if vars not set
    }

    return true;            // ← Passes unconditionally if vars are set
}
```

**`verifyAppAttest()`** (`app/Services/DeviceAttestationService.php:35-48`):
```php
protected function verifyAppAttest(string $token): bool
{
    $teamId = config('services.app_attest.team_id');
    $bundleId = config('services.app_attest.bundle_id');

    if (! $teamId || ! $bundleId) {
        return false;       // ← Rejects if vars not set
    }

    return true;            // ← Passes unconditionally if vars are set
}
```

### Future Implementation

The TODO comments in the code indicate the planned real implementation:

**Play Integrity** (`app/Services/DeviceAttestationService.php:22-24`):
> `// TODO: Implement real Play Integrity verification using google/apiclient.`
> `// Steps: decrypt the integrity token -> verify the signature -> check`
> `// the device integrity verdict (ctsProfileMatch, basicIntegrity, etc.).`

**App Attest** (`app/Services/DeviceAttestationService.php:37-39`):
> `// TODO: Implement real App Attest verification.`
> `// Steps: verify the attestation object against Apple's verification`
> `// endpoint -> validate the certificate chain -> check the key ID.`

---

## Behaviour Matrix

| Environment | Platform | Env Vars Set? | `integrity_token` in request? | Result |
|-------------|----------|---------------|-------------------------------|--------|
| `local` / `testing` | any | any | `dev-bypass` (default) | ✅ Passes (dev bypass) |
| `local` / `testing` | any | any | `invalid-integrity` | ❌ Fails (dev bypass mismatch) |
| `local` / `testing` | `web` | any | not required | ✅ Passes (web platform skips attestation) |
| `production` | `android` | ✅ Both vars set | any string | ✅ Passes (stub returns true) |
| `production` | `android` | ❌ Missing either var | any string | ❌ Fails (stub returns false) |
| `production` | `ios` | ✅ Both vars set | any string | ✅ Passes (stub returns true) |
| `production` | `ios` | ❌ Missing either var | any string | ❌ Fails (stub returns false) |
| `production` | `android` | ✅ Set | missing from body | ❌ 422 validation error |
| `production` | `ios` | ✅ Set | missing from body | ❌ 422 validation error |

### Key Takeaway

**If you deploy to production without setting these variables, all Android and iOS device registrations will be rejected.** Even with the stub implementation, the presence of the variables is still checked. Setting them (even with dummy/placeholder values) is sufficient to unblock registrations until real verification is implemented.

---

## Related Configuration

### `config/services.php` (lines 49–57)

```php
'play_integrity' => [
    'project_number' => env('PLAY_INTEGRITY_PROJECT_NUMBER'),
    'service_account_json' => env('PLAY_INTEGRITY_SERVICE_ACCOUNT_JSON'),
],

'app_attest' => [
    'team_id' => env('APP_ATTEST_TEAM_ID'),
    'bundle_id' => env('APP_ATTEST_BUNDLE_ID'),
],
```

### `config/app.php` (lines 132–139)

```php
'device_token_ttl' => env('DEVICE_TOKEN_TTL', 30),

'min_app_version' => [
    'android' => env('MIN_ANDROID_VERSION', '1.0.0'),
    'ios' => env('MIN_IOS_VERSION', '1.0.0'),
],

'dev_integrity_bypass_token' => env('DEV_INTEGRITY_BYPASS_TOKEN', 'dev-bypass'),
```

### Additional `.env` Variables in the Same Domain

| Variable | Default | Purpose |
|----------|---------|---------|
| `DEV_INTEGRITY_BYPASS_TOKEN` | `dev-bypass` | Token used in local/testing to bypass attestation |
| `MIN_ANDROID_VERSION` | `1.0.0` | Minimum supported Android app version |
| `MIN_IOS_VERSION` | `1.0.0` | Minimum supported iOS app version |
| `DEVICE_TOKEN_TTL` | `30` | Device access token time-to-live in days |
| `API_WEB_PLATFORM_ENABLED` | `true` | Allow `web` platform in local/testing for debugging |

---

## Testing

### Dev/Local Bypass

In `local` and `testing` environments, the attack path is simplified. Send `integrity_token` with the value matching `DEV_INTEGRITY_BYPASS_TOKEN` (default: `dev-bypass`):

```json
POST /api/v1/device/register
{
    "device_id": "550e8400-e29b-41d4-a716-446655440000",
    "platform": "android",
    "app_version": "1.0.0",
    "integrity_token": "dev-bypass"
}
```

You can also use `"platform": "web"` in local/testing to skip attestation entirely (integrity_token not required).

### Relevant Test File

`tests/Feature/Api/V1/DeviceRegistrationTest.php` — covers:
- Successful registration with valid integrity_token
- Rejection with invalid integrity_token
- Rejection with missing integrity_token on android/ios
- Web platform bypass in local/testing
- App version enforcement (too old → rejected)
