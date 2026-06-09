# Flutter API Integration Guide: Security & EnsureApiAccess

This guide explains how to correctly integrate a Flutter application with the Bourgo Arena API, focusing on the `EnsureApiAccess` middleware, multi-layered security, and token management.

---

## 1. Core Security Concepts

The API uses a dual-token system enforced by the `EnsureApiAccess` middleware:

1.  **Device Access Token**: A long-lived token bound to a specific physical device. Used for guest access and as the primary transport for all mobile requests.
2.  **Sanctum Personal Access Token**: A session token issued after user login. Used for identity verification and administrative actions.

### The "Golden Rule" of Requests
To avoid **401 Unauthorized** errors, every request must satisfy the middleware's conditions:
*   **Bearer Token**: Present in the `Authorization` header.
*   **Device ID Binding**: If using a Device Token, the `X-Device-ID` header must match the ID stored in the database.
*   **Platform Identification**: `X-Platform` or `X-App-Platform` should be sent to identify the client.

---

## 2. Required Headers

Always include these headers in your base API client (e.g., Dio interceptor):

| Header | Description | Required | Example |
| :--- | :--- | :--- | :--- |
| `Authorization` | `Bearer {token}` | Yes | `Bearer a1b2c3...` |
| `X-Device-ID` | Unique UUID for the device | Yes (for Device Tokens) | `550e8400-e29b...` |
| `X-Platform` | Client platform | Yes | `android`, `ios`, or `web` |
| `X-App-Version` | Current app version | Yes (for registration) | `1.0.0` |
| `Accept` | Must be `application/json` | Yes | `application/json` |

> **Dev Tip**: In `local` or `testing` environments, you can bypass token requirements by sending `X-Platform: web`. This is useful for initial testing or web-based debugging.

---

## 3. The Authentication Lifecycle

### Step 1: Device Registration (Guest Mode)
On the very first app launch, you must register the device to get a `Device Access Token`.

**Endpoint**: `POST /api/v1/device/register`
**Payload**:
```json
{
  "device_id": "UUID-HERE",
  "platform": "android",
  "app_version": "1.0.0",
  "integrity_token": "..." // Required for production android/ios
}
```
**Storage**: Save the returned `token` and your `device_id` in **Flutter Secure Storage**.

### Step 2: User Login
When the user logs in, they receive a `Sanctum Token`.

**Endpoint**: `POST /api/v1/auth/login`
**Storage**: Save this token as the "User Session Token".

### Step 3: Device Linking (The Critical Step)
To associate the "Guest Device" with the "Authenticated User", you **must** link them. If you skip this, the `EnsureApiAccess` middleware won't know which user belongs to the device token.

**Endpoint**: `POST /api/v1/device/link`
**Headers**:
*   `Authorization: Bearer {USER_SANCTUM_TOKEN}`
**Payload**:
```json
{
  "device_id": "UUID-HERE"
}
```

---

## 4. Flutter Implementation (Dio Example)

### A. Secure Storage Wrapper
```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  static const _storage = FlutterSecureStorage();

  static Future<void> saveDeviceToken(String token) => _storage.write(key: 'device_token', value: token);
  static Future<String?> getDeviceToken() => _storage.read(key: 'device_token');
  
  static Future<void> saveDeviceId(String id) => _storage.write(key: 'device_id', value: id);
  static Future<String?> getDeviceId() => _storage.read(key: 'device_id');
}
```

### B. Dio Interceptor for Security Headers
```dart
import 'package:dio/dio.dart';
import 'secure_storage.dart';

class ApiInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final deviceToken = await SecureStorage.getDeviceToken();
    final deviceId = await SecureStorage.getDeviceId();
    
    // 1. Set Base Headers
    options.headers['Accept'] = 'application/json';
    options.headers['X-Platform'] = 'android'; // or Platform.isIOS ? 'ios' : 'android'
    options.headers['X-App-Version'] = '1.0.0';

    // 2. Set Authentication
    if (deviceToken != null) {
      options.headers['Authorization'] = 'Bearer $deviceToken';
    }

    // 3. Set Device ID (Required for EnsureApiAccess)
    if (deviceId != null) {
      options.headers['X-Device-ID'] = deviceId;
    }

    return handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      // Logic for token refresh or re-registration goes here
      print("Authentication Failed: Check Device ID and Token alignment.");
    }
    return handler.next(err);
  }
}
```

---

## 5. Handling Security Layers Correctly

1.  **Integrity Checks**:
    *   In production, the `device/register` endpoint requires an `integrity_token`.
    *   **Android**: Use `Play Integrity API`.
    *   **iOS**: Use `DeviceCheck` (App Attest).
    *   The backend validates these via `DeviceAttestationService`.

2.  **Geo-Restriction (`tunisia_geo`)**:
    *   Sensitive endpoints (like Payment Initiation) are restricted to Tunisian IPs.
    *   If you receive a **403 Forbidden** with `error: "geo_restricted"`, verify your VPN/Proxy settings.

3.  **App Versioning**:
    *   The `X-App-Version` header is checked against `config('app.min_app_version')`.
    *   If the version is too old, you will get a **422 Unprocessable Content** error.

4.  **Token Refreshing**:
    *   Device tokens expire (default 30 days).
    *   Use `POST /api/v1/device/refresh` periodically or when receiving a 401 on a previously valid token.

---

## 6. Common Troubleshooting (401 Checklist)

*   [ ] **Are you sending `X-Device-ID`?** Middleware rejects device tokens without it.
*   [ ] **Does `X-Device-ID` match the registration ID?** Tokens are permanently bound to the ID they were created with.
*   [ ] **Is the token active?** Check if it has been revoked (`is_revoked`) or expired.
*   [ ] **Did you link the device?** If you are trying to access user-specific data (e.g., `/member/profile`) but `member_id` is null on the `device_access_tokens` table, you will get 401/403 errors. Run the `device/link` endpoint after user login.
*   [ ] **Local Dev?** Try adding `X-Platform: web` to your headers to bypass mobile-specific integrity checks.
