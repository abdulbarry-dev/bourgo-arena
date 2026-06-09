# Mobile API Authentication & Request Flow

This document traces every mobile API request through middleware, controllers, services, and models — providing the complete lifecycle needed to build sequence diagrams.

---

## 1. Request Routing & Middleware Chain

All mobile API routes are prefixed `/api/v1/`. The middleware stack defined in `bootstrap/app.php` applies globally:

- **`api` group**: `ForceJsonResponse`, `SetLocale`
- **Per-route**: `api.access`, `throttle:*`, `auth:sanctum`, `verified.account`, `onboarding.completed`, `tunisia_geo`, `course.access`

### Middleware Alias Table

| Alias | Class | Responsibility |
|-------|-------|----------------|
| `api.access` | `EnsureApiAccess` | Validate bearer token — device token (with X-Device-ID binding) or Sanctum fallback |
| `verified.account` | `EnsureAccountIsVerified` | Block if member has **neither** email nor phone verified |
| `onboarding.completed` | `EnsureOnboardingIsCompleted` | Block if `onboarding_completed_at` is null or no verified contact method |
| `course.access` | `EnsureUserHasCourseAccess` | Check member's plan includes the requested course |
| `tunisia_geo` | `RestrictToTunisia` | Geo-block payments to Tunisia (IP lookup) |
| `role` | `EnsureUserHasRole` | Role-based authorization |
| `verified` | `EnsureEmailVerifiedOrIsStaff` | Email verification check (staff exempt) |

### Rate Limiter Table

| Name | Dev Behavior | Production Behavior | Used By |
|------|-------------|-------------------|---------|
| `api.auth` | `Limit::none()` | 10/min per IP + 10/min per identifier | `login`, `register`, `complete-registration` |
| `api.otp` | `Limit::none()` | 5 per 10min per IP + 5 per 10min per identifier | `send-otp`, `verify-otp`, `forgot-password`, `reset-password`, `request-family-otp` |
| `api.password` | `Limit::none()` | 5/min per IP + 5/min per user | `update-password` |
| `payments` | Always active (configurable, default 10/min) | Same | `payments/initiate` |
| `device/register` | Always active (3/min per IP, hardcoded in route) | Same | `device/register` |
| `login` (Fortify) | Always active (5/min per email+IP) | Same | Web login only |
| `two-factor` (Fortify) | Always active (5/min per session) | Same | Web 2FA only |

---

## 2. Token Hierarchy & Executive Summary

```
┌──────────────────────────────────────────────────────┐
│                  REQUEST ENTRY                        │
├──────────────────────────────────────────────────────┤
│ 1. Extract Bearer token from Authorization header     │
│ 2. Check if it's a Device Access Token (first)        │
│ 3. If yes → validate X-Device-ID binding              │
│ 4. If no → fall back to Sanctum PersonalAccessToken   │
│ 5. If neither → 401                                   │
└──────────────────────────────────────────────────────┘
```

Three token types are used:

| Token | Issued By | Scope | Sent As | Abilities |
|-------|-----------|-------|---------|-----------|
| **Device Access Token** (`device_access_tokens`) | `device/register`, `device/refresh` | Guest device-bound API access | `Bearer {token}` + `X-Device-ID` header | Not ability-based — membership determined by `member_id` FK |
| **Sanctum Token** (`personal_access_tokens`) | Any auth endpoint | Authenticated member session | `Bearer {token}` | `verification`, `onboarding`, `deletion-cancellation`, or `*` |
| **Sanctum Token with `deletion-cancellation` ability** | `POST /auth/login` when account is scheduled for deletion | Limbo — only OTP verification to cancel deletion | `Bearer {token}` | `deletion-cancellation` |

### Token Lifecycle Diagram (Conceptual)

```
Fresh Launch → device/register ──→ Device Token (guest)
                                        │
                                        ▼
                              POST /auth/login → Sanctum Token
                                        │
                                        ▼
                              POST /device/link (bind device to member)
                                        │
                              ┌─────────┴──────────┐
                              ▼                    ▼
                     Subsequent API        Token expires or
                     calls use device       user logs out
                     token (middleware
                     sets member via
                     member_id FK)

                     Logout: POST /device/logout → revoke device token
                             POST /auth/logout → delete Sanctum token
```

---

## 3. Device Token Endpoints (No `auth:sanctum` required)

### 3a. `POST /api/v1/device/register` — Register Device (Guest)

**Route in `routes/api.php`:28-30**
```php
Route::post('device/register', [DeviceRegistrationController::class, 'register'])
    ->middleware('throttle:3,1')
    ->name('api.v1.device.register');
```

**Middleware chain:** `ForceJsonResponse` → `SetLocale` → `throttle:3,1`

**No `api.access` middleware** — this is the only endpoint (besides webhooks) accessible without any token.

#### Flow

```
Mobile App                    Server                         Database
    │                           │                                │
    │  POST /api/v1/device/     │                                │
    │  register                 │                                │
    │  Headers:                 │                                │
    │  Content-Type: appl. json │                                │
    │                           │                                │
    │  Body:                    │                                │
    │  {                        │                                │
    │    "device_id": "uuid",   │                                │
    │    "platform": "android", │                                │
    │    "app_version": "1.2.0",│                                │
    │    "device_fingerprint":  │                                │
    │      { model, os, ... },  │                                │
    │    "integrity_token": ".."│                                │
    │  }                        │                                │
    │──────────────────────────>│                                │
    │                           │                                │
    │    middleware: ForceJsonResponse                            │
    │    middleware: SetLocale                                    │
    │    middleware: throttle:3,1 (IP-based, 3 req/min)           │
    │                           │                                │
    │    RegisterDeviceRequest  │                                │
    │    (FormRequest)          │                                │
    │    ────────────────       │                                │
    │    authorize(): true      │                                │
    │    rules():               │                                │
    │      device_id       → required, string, uuid              │
    │      platform        → required, in:android,ios            │
    │      app_version     → required, string, max:20            │
    │      device_fingerprint → nullable, array                  │
    │        .model        → nullable, string, max:255            │
    │        .os_version   → nullable, string, max:255            │
    │        .locale       → nullable, string, max:10             │
    │        .timezone     → nullable, string, max:100            │
    │        .manufacturer → nullable, string, max:255            │
    │      integrity_token → required, string                     │
    │                           │                                │
    │    DeviceAttestationService::verifyAppVersion()             │
    │    ────────────────────────────────────────                  │
    │    Reads config('app.min_app_version.android|ios')           │
    │    Uses PHP version_compare(version, min, '>=')              │
    │                           │                                │
    │    ── [fails] → 422 with required_version ──────>│          │
    │                           │                                │
    │    DeviceAttestationService::verify()                        │
    │    ─────────────────────────────                              │
    │      If local/testing:                                      │
    │        verifyDev(token):                                    │
    │          token === config('app.dev_integrity_bypass_token')  │
    │      If production:                                         │
    │        match platform:                                       │
    │          'android' → verifyPlayIntegrity()  [TODO stub]      │
    │          'ios'     → verifyAppAttest()       [TODO stub]     │
    │        Returns false if credentials not configured           │
    │                           │                                │
    │    ── [fails] → 422 integrity failed ──────────>│           │
    │                           │                                │
    │    Generate Str::random(64) token                           │
    │    TTL from config('app.device_token_ttl', 30) days          │
    │                           │                                │
    │    DeviceAccessToken::forDevice(device_id)->first()          │
    │    ──────────────────────────────────────                    │
    │    Scope: where('device_id', $deviceId)                     │
    │                          │                                 │
    │    ── [exists] ──────────────────────────────────────────>│ │
    │    │                     │    UPDATE existing record:       │ │
    │    │                     │    token = new random            │ │
    │    │                     │    device_fingerprint, platform  │ │
    │    │                     │    app_version, integrity_passed │ │
    │    │                     │    integrity_payload             │ │
    │    │                     │    ip_address                    │ │
    │    │                     │    expires_at = now + TTL        │ │
    │    │                     │    is_revoked = false            │ │
    │    │                     │    revoked_at = null             │ │
    │    ── [doesn't exist] ─────────────────────────────────>│  │
    │                          │    INSERT new record             │ │
    │                          │    All fields from request       │ │
    │                          │    member_id = null (guest)      │ │
    │                          │                                │ │
    │    DeviceAccessTokenResource                              │ │
    │    ──────────────────────                                 │ │
    │    Returns: id, device_id, token, platform,                │ │
    │             app_version, expires_at, created_at            │ │
    │                          │                                │ │
    │  <── 201 ────────────────────────────────────────────────│ │
    │  {                                                       │ │
    │    "data": {                                             │ │
    │      "id": 1,                                            │ │
    │      "device_id": "uuid",                                │ │
    │      "token": "random64",                                │ │
    │      "platform": "android",                              │ │
    │      "app_version": "1.2.0",                             │ │
    │      "expires_at": "2026-07-09T...",                     │ │
    │      "created_at": "2026-06-09T..."                      │ │
    │    }                                                     │ │
    │  }                                                       │ │
```

**Key points:**
- Rate-limited to 3 requests per minute per IP
- No token required (not behind `api.access`)
- Re-registration (same `device_id`) **updates** the existing token — rotates the token, resets expiry and revocation
- Device Attestation stubs return `false` in production unless credentials are configured (safe default)

---

### 3b. `POST /api/v1/device/refresh` — Rotate Token

**Route in `routes/api.php`:34-36**
```php
Route::post('device/refresh', [DeviceRegistrationController::class, 'refresh'])
    ->name('api.v1.device.refresh');
```

**Middleware chain:** `ForceJsonResponse` → `SetLocale` → `api.access` → controller

#### Flow

```
Mobile App                    EnsureApiAccess                    Server                          Database
    │                           │                                  │                               │
    │  POST /device/refresh     │                                  │                               │
    │  Headers:                 │                                  │                               │
    │    Authorization:         │                                  │                               │
    │      Bearer {dev_token}   │                                  │                               │
    │    X-Device-ID: {uuid}    │                                  │                               │
    │──────────────────────────>│                                  │                               │
    │                           │─────────────────────────────────>│                               │
    │                           │                                  │                               │
    │    ──── EnsureApiAccess ────                                   │                               │
    │    │ If testing → skip                                       │                               │
    │    │ Extract BearerToken                                     │                               │
    │    │                                                         │                               │
    │    │ DeviceAccessToken::forToken(token)->active()->first()    │                              │
    │    │─────────────────────────────────────────────────────────────────────────────────────────>│
    │    │<── [found] ─────────────────────────────────────────────────────────────────────────────│
    │    │                                                         │                               │
    │    │ Check X-Device-ID header present ── [no] → 401          │                               │
    │    │ Check X-Device-ID === device_token.device_id ── [no]→401│                               │
    │    │                                                         │                               │
    │    │ Update last_used_at, ip_address                          │                              │
    │    │─────────────────────────────────────────────────────────────────────────────────────────>│
    │    │                                                         │                               │
    │    │ Check device_token.member_id ── [null, guest] → no Auth │                               │
    │    │                                                         │                               │
    │    │── pass to controller ──────────────────────────────────>│                               │
    │                           │                                  │                               │
    │                           │    Generate new Str::random(64)  │                               │
    │                           │    TTL from config               │                               │
    │                           │                                  │                               │
    │                           │    UPDATE token, expires_at,     │                              │
    │                           │    ip_address                    │                              │
    │                           │──────────────────────────────────────────────────────────────────>│
    │                           │                                  │                               │
    │                           │  <── 200 ────────────────────────│                               │
    │                           │  {                               │                               │
    │                           │    "data": {                     │                               │
    │                           │      "token": "new_random64",    │                               │
    │                           │      "expires_at": "2026-07-.."  │                               │
    │                           │    }                             │                               │
    │  <────────────────────────│──────────────────────────────────│                               │
```

**Key points:**
- Requires `api.access` — device token + X-Device-ID required
- Generates a brand new random token each time (rotation)
- Does NOT require member binding (guest devices can refresh)
- Recommended to call when token is within 7 days of expiry

---

### 3c. `POST /api/v1/device/link` — Bind Device to Member

**Route in `routes/api.php`:38-39**
```php
Route::post('device/link', [DeviceRegistrationController::class, 'link'])
    ->name('api.v1.device.link');
```

**Middleware chain:** `ForceJsonResponse` → `SetLocale` → `api.access` → controller

**Auth mechanism:** The endpoint gets the authenticated member via `$request->user('sanctum')`. The client sends the Sanctum token (obtained after login). `EnsureApiAccess` falls through to Sanctum `PersonalAccessToken::findToken()`, and Laravel's Sanctum guard resolves the member from the Bearer token when the controller calls `$request->user('sanctum')`.

#### Flow

```
Mobile App                    EnsureApiAccess                    DeviceRegistrationController      Database
    │                           │                                  │                               │
    │  POST /device/link        │                                  │                               │
    │  Headers:                 │                                  │                               │
    │    Authorization:         │                                  │                               │
    │      Bearer {sanctum_tok} │                                  │                               │
    │    Content-Type: app/json │                                  │                               │
    │  Body: { device_id }      │                                  │                               │
    │──────────────────────────>│                                  │                               │
    │                           │                                  │                               │
    │    EnsureApiAccess:                                          │                               │
    │    1. Try device token → not found                           │                               │
    │    2. Try Sanctum token → found                              │                               │
    │    3. Pass through                                           │                               │
    │                           │─────────────────────────────────>│                               │
    │                           │                                  │                               │
    │                           │    Check device_id in body       │                               │
    │                           │    ── [missing] → 422            │                               │
    │                           │                                  │                               │
    │                           │    $request->user('sanctum')      │                               │
    │                           │    ── [null/not Member] → 401    │                               │
    │                           │                                  │                               │
    │                           │    DeviceAccessToken::            │                              │
    │                           │      forDevice(device_id)         │                              │
    │                           │      ->active()->first()          │                              │
    │                           │──────────────────────────────────────────────────────────────────>│
    │                           │<── [not found] → 404 ────────────────────────────────────────────│
    │                           │                                  │                               │
    │                           │    UPDATE member_id = member.id  │                              │
    │                           │    last_verified_at = now()       │                              │
    │                           │──────────────────────────────────────────────────────────────────>│
    │                           │                                  │                               │
    │  <── 200 { "message": "Device linked successfully" } ───────│                               │
```

**Key points:**
- Client sends the Sanctum token (obtained after login)
- Controller manually resolves authenticated member via `$request->user('sanctum')`
- Binds `member_id` on the device token so subsequent device-token-authenticated requests can identify the member
- Once linked, the device token can be used for all subsequent API calls (middleware sets the Auth user from `member_id`)

---

### 3d. `POST /api/v1/device/logout` — Revoke Device Token

**Route in `routes/api.php`:41-42**
```php
Route::post('device/logout', [DeviceRegistrationController::class, 'logout'])
    ->name('api.v1.device.logout');
```

**Middleware chain:** `ForceJsonResponse` → `SetLocale` → `api.access` → controller

**Auth mechanism:** Device token (NOT Sanctum). The `api.access` middleware validates the device token. The controller does NOT call `$request->user()`. It just uses the `device_id` from the request body.

#### Flow

```
Mobile App                    EnsureApiAccess                    DeviceRegistrationController      Database
    │                           │                                  │                               │
    │  POST /device/logout      │                                  │                               │
    │  Headers:                 │                                  │                               │
    │    Authorization:         │                                  │                               │
    │      Bearer {device_tok}  │                                  │                               │
    │    X-Device-ID: {uuid}    │                                  │                               │
    │  Body: { device_id }      │                                  │                               │
    │──────────────────────────>│                                  │                               │
    │                           │                                  │                               │
    │    EnsureApiAccess:       │                                  │                               │
    │    1. Find device token   │                                  │                               │
    │    2. Validate X-Device-ID│                                  │                               │
    │    3. Update last_used_at │                                  │                               │
    │    4. No member_id (guest)│                                  │                               │
    │    5. Pass through        │                                  │                               │
    │                           │─────────────────────────────────>│                               │
    │                           │                                  │                               │
    │                           │    Check device_id in body       │                               │
    │                           │    ── [missing] → 422            │                               │
    │                           │                                  │                               │
    │                           │    DeviceAccessToken::            │                              │
    │                           │      forDevice(device_id)         │                              │
    │                           │      ->active()->first()          │                              │
    │                           │──────────────────────────────────────────────────────────────────>│
    │                           │                                  │                               │
    │                           │    [found] UPDATE is_revoked=true │                              │
    │                           │             revoked_at=now()       │                              │
    │                           │──────────────────────────────────────────────────────────────────>│
    │                           │                                  │                               │
    │                           │    [not found] → silently continue                               │
    │                           │                                  │                               │
    │  <── 200 "Device logged out successfully" ──────────────────│                               │
```

**Key points:**
- Requires a **device token** (not Sanctum) — the route is behind `api.access` only, no `auth:sanctum`
- Always returns 200 even if device token wasn't found (idempotent — already logged out)
- Sets `is_revoked=true` and `revoked_at=now()`
- After revocation, the same token will fail the `active()` scope in subsequent requests

---

## 4. The `EnsureApiAccess` Middleware (Detailed)

**File:** `app/Http/Middleware/EnsureApiAccess.php`

This is the gatekeeper for ALL routes except `device/register` and payment webhooks. It must always be understood first.

### Decision Tree

```
                     ┌─────────────────────────────┐
                     │     Request enters            │
                     │     EnsureApiAccess           │
                     └─────────────┬───────────────┘
                                   │
                                   ▼
                     ┌─────────────────────────────┐
                     │  config('app.env') ===       │
                     │  'testing'?                  │
                     └──────┬─────────────┬────────┘
                            │ YES          │ NO
                            ▼              ▼
                     Pass through    Extract BearerToken
                                     from Authorization
                                     header
                                     │
                                     ▼
                            ┌────────────────┐
                            │ Token present?  │
                            └──┬──────────┬──┘
                               │ NO       │ YES
                               ▼          ▼
                            Return      DeviceAccessToken::
                            401         forToken(token)
                                        ->active()->first()
                                            │
                                            ▼
                                  ┌─────────────────┐
                                  │ Found device     │
                                  │ token?           │
                                  └──┬──────────┬────┘
                                     │ YES      │ NO
                                     ▼          ▼
                            ┌────────────────┐  Sanctum
                            │ X-Device-ID     │  PersonalAccessToken::
                            │ header          │  findToken(token)
                            │ present?        │      │
                            └──┬──────────┬───┘      ▼
                               │ NO       │ YES  ┌────────────┐
                               ▼          ▼      │ Found?     │
                            Return      Check    └──┬──────┬──┘
                            401         device_id    │ YES   │ NO
                                        matches      │      Return
                                        token's       │      401
                                        device_id    ▼
                                        ┌──────┐  Pass through
                                        │ Match│      │
                                        └──┬───┘      ▼
                                   NO ─────┤ YES   ┌──────────────────┐
                                   Return  └──────>│ Update last_used  │
                                   401             │ at, ip_address    │
                                                   └────────┬─────────┘
                                                            │
                                                            ▼
                                                  ┌──────────────────┐
                                                  │ Token has        │
                                                  │ member_id?       │
                                                  └──┬──────────┬────┘
                                                     │ YES      │ NO
                                                     ▼          ▼
                                                  Auth::guard   Pass through
                                                  ('sanctum')   (guest request)
                                                  ->setUser()
                                                     │
                                                     ▼
                                                  Pass through
```

### Middleware Code Pseudocode

```
handle(request, next):
    if testing → return next(request)                        // LINE 16-18

    token = request.bearerToken()                             // LINE 20
    if no token → return 401                                  // LINE 22-24

    deviceToken = DeviceAccessToken::forToken(token)          // LINE 26-29
                               ->active()
                               ->first()

    if deviceToken:                                           // LINE 31
        deviceId = request.header('X-Device-ID')              // LINE 32

        if no deviceId → return 401                           // LINE 34-36
        if deviceId != deviceToken.device_id → 401            // LINE 38-40

        deviceToken.update(last_used_at, ip_address)          // LINE 42

        if deviceToken.member_id:                             // LINE 44
            member = deviceToken.member                       // LINE 45
            if member:                                        // LINE 47
                Auth::guard('sanctum')->setUser(member)       // LINE 48

        return next(request)                                  // LINE 52

    // Fall through to Sanctum
    sanctumToken = PersonalAccessToken::findToken(token)      // LINE 55
    if no sanctumToken → return 401                           // LINE 57-59

    return next(request)                                      // LINE 61
    // Note: Sanctum user is NOT set here — it's resolved
    // later by Laravel's auth system when auth:sanctum
    // middleware runs or when $request->user('sanctum') is called
```

**Important behavior after the middleware:**
- If the route also has `auth:sanctum` middleware (e.g., auth routes), Sanctum's middleware runs AFTER `api.access`. It resolves the user again from the Bearer token.
- If the route does NOT have `auth:sanctum`, the controller can still call `$request->user('sanctum')` to resolve the user.
- When `api.access` sets the user via `Auth::guard('sanctum')->setUser()`, the user is available to subsequent middleware and the controller.

---

## 5. Sanctum Token Endpoints (Behind `api.access` + `auth:sanctum`)

All auth routes are protected by `api.access` first, then optionally `auth:sanctum`.

### 5a. `POST /api/v1/auth/login` — Member Login

**Route in `routes/api.php`:69**
```php
Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:api.auth')
    ->name('api.v1.auth.login');
```

**Middleware chain:** `ForceJsonResponse` → `SetLocale` → `api.access` → `throttle:api.auth`

**Note:** No `auth:sanctum` — this is a public auth endpoint (but behind `api.access`).

#### Flow

```
Mobile App                    EnsureApiAccess                    AuthOrchestrationService         Database
    │                           │                                  │                               │
    │  POST /auth/login         │                                  │                               │
    │  Headers:                 │                                  │                               │
    │    Authorization: Bearer  │                                  │                               │
    │      {device_token}       │                                  │                               │
    │    X-Device-ID: {uuid}    │                                  │                               │
    │  Body: { email, password }│                                  │                               │
    │──────────────────────────>│                                  │                               │
    │                           │                                  │                               │
    │    EnsureApiAccess:       │                                  │                               │
    │    1. Find device token   │                                  │                               │
    │    2. Validates X-Device-ID                                  │                               │
    │    3. Update last_used_at │                                  │                               │
    │    4. No member_id (guest) → pass through                    │                               │
    │                           │                                  │                               │
    │    throttle:api.auth      │                                  │                               │
    │    (Limit::none in dev)   │                                  │                               │
    │                           │─────────────────────────────────>│                               │
    │                           │                                  │                               │
    │    LoginRequest validation:                                  │                               │
    │    - email + password OR phone + password                    │                               │
    │                           │                                  │                               │
    │                           │    LoginDTO::fromRequest()        │                               │
    │                           │                                  │                               │
    │                           │    AuthOrchestrationService::     │                              │
    │                           │      login(dto)                   │                              │
    │                           │                                  │                              │
    │                           │    1. Find member by email/phone  │                             │
    │                           │─────────────────────────────────────────────────────────────────>│
    │                           │<── [not found] → throws ValidationException                     │
    │                           │                                  │                              │
    │                           │    2. Hash::check(password,      │                              │
    │                           │       member->password)           │                              │
    │                           │    [fails] → throws ValidationException                          │
    │                           │                                  │                              │
    │                           │    3. CHECK: member scheduled    │                              │
    │                           │       for deletion?               │                              │
    │                           │       │                          │                              │
    │                           │       ├── YES: generate OTP,     │                              │
    │                           │       │   create Sanctum token    │                              │
    │                           │       │   with abilities:         │                              │
    │                           │       │   ['deletion-cancellation']│                            │
    │                           │       │   Return state:           │                              │
    │                           │       │   'pending_deletion_      │                              │
    │                           │       │    cancellation'           │                             │
    │                           │       │                          │                              │
    │                           │       └── NO → continue          │                              │
    │                           │                                  │                              │
    │                           │    4. CHECK member state:        │                              │
    │                           │       state = member->status     │                              │
    │                           │       │  ?? member->state        │                              │
    │                           │       │                          │                              │
    │                           │       ├─ 'pending_verification'  │                              │
    │                           │       │  OR                       │                              │
    │                           │       │  'pending_additional_    │                              │
    │                           │       │   verification'           │                             │
    │                           │       │  → verification token    │                              │
    │                           │       │    abilities: ['verification']                           │
    │                           │       │    state: unchanged       │                              │
    │                           │       │                          │                              │
    │                           │       ├─ not verified            │                              │
    │                           │       │  (member login without    │                              │
    │                           │       │   isVerified true)         │                             │
    │                           │       │  → verification token    │                              │
    │                           │       │    abilities: ['verification']                           │
    │                           │       │    state: 'pending_       │                              │
    │                           │       │     additional_verification'                             │
    │                           │       │                          │                              │
    │                           │       ├─ onboarding not          │                              │
    │                           │       │   completed               │                             │
    │                           │       │  → onboarding token      │                              │
    │                           │       │    abilities: ['onboarding']                             │
    │                           │       │    state: 'pending_       │                              │
    │                           │       │     onboarding'            │                             │
    │                           │       │    code: 'ONBOARDING_     │                              │
    │                           │       │     INCOMPLETE'            │                             │
    │                           │       │    required_action:       │                              │
    │                           │       │     'complete_onboarding'  │                             │
    │                           │       │                          │                              │
    │                           │       └─ fully verified +        │                              │
    │                           │          onboarding done         │                              │
    │                           │        → full access token       │                              │
    │                           │          abilities: ['*']         │                             │
    │                           │          state: member state     │                              │
    │                           │                                  │                              │
    │    MemberResource wraps member data                          │                              │
    │                           │                                  │                              │
    │  <── 200 { success: true, message, data: {                 │                              │
    │    token, state, user, verification_status } }              │                              │
```

**Response examples:**
- **Full access:** `{ token: "...", state: "active", user: {...}, verification_status: {...} }`
- **Pending verification:** `{ token: "...", state: "pending_additional_verification", code: "ADDITIONAL_VERIFICATION_REQUIRED", user: {...}, verification_status: {...} }`
- **Pending onboarding:** `{ token: "...", state: "pending_onboarding", code: "ONBOARDING_INCOMPLETE", required_action: "complete_onboarding", cta: "Complete Setup", user: {...}, verification_status: {...} }`
- **Pending deletion cancellation:** `{ token: "...", state: "pending_deletion_cancellation", code: "ACCOUNT_DELETION_PENDING", user: {...}, verification_status: {...} }`

---

### 5b. `POST /api/v1/auth/register` — Member Registration

**Route in `routes/api.php`:70**
```php
Route::post('auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:api.auth')
    ->name('api.v1.auth.register');
```

**Middleware chain:** `ForceJsonResponse` → `SetLocale` → `api.access` → `throttle:api.auth` → controller

```
Mobile App                    AuthController                        AuthService                Database
    │                           │                                     │                         │
    │  POST /auth/register      │                                     │                         │
    │  Headers: { device_token }│                                     │                         │
    │  Body: { name, email,     │                                     │                         │
    │    phone, password, ... } │                                     │                         │
    │──────────────────────────>│                                     │                         │
    │                           │                                     │                         │
    │  RegisterRequest validates│                                     │                         │
    │                           │  AuthService::register(RegisterDTO) │                        │
    │                           │────────────────────────────────────>│                         │
    │                           │                                     │  Member::create({        │
    │                           │                                     │    name, email, phone,   │
    │                           │                                     │    password: Hash::make, │
    │                           │                                     │    status: 'pending_    │
    │                           │                                     │      verification',      │
    │                           │                                     │    state: 'pending_     │
    │                           │                                     │      verification',      │
    │                           │                                     │    ...                   │
    │                           │                                     │  })                      │
    │                           │                                     │─────────────────────────>│
    │                           │                                     │<── Member ──────────────│
    │                           │<── Member ─────────────────────────│                         │
    │                           │                                     │                         │
    │  OtpService::generate(    │                                     │                         │
    │    identifier)            │                                     │                         │
    │                           │                                     │                         │
    │  member->createToken(     │                                     │                         │
    │    'auth_token',          │                                     │                         │
    │    ['verification'])      │                                     │                         │
    │                           │                                     │                         │
    │  <── 201 { data: {       │                                     │                         │
    │    token, user,           │                                     │                         │
    │    state: 'pending_      │                                     │                         │
    │     verification',        │                                     │                         │
    │    verification_status } }│                                     │                         │
```

**Key points:**
- Member is created in `pending_verification` state
- Sanctum token has ability `verification` — can only access routes that don't require `verified.account` or full access
- OTP is generated immediately and sent to the registered identifier
- Device token is NOT automatically linked — Flutter must call `device/link` after receiving the Sanctum token

---

### 5c. `POST /api/v1/auth/send-otp` — Generate OTP

**Route in `routes/api.php`:71**
```php
Route::post('auth/send-otp', [AuthController::class, 'sendOtp'])
    ->middleware('throttle:api.otp')
    ->name('api.v1.auth.send-otp');
```

**Flow:** `api.access` → `throttle:api.otp` → `AuthController::sendOtp()`
- Validates `identifier` (email or phone) via `SendOtpRequest`
- Calls `OtpService::generate($identifier)`
- OTP is stored on the `Member` model directly (or `otp_codes` table for non-members)
- Has cooldown: 60 seconds between sends (configurable)
- Returns 200 `{ success: true, message: "OTP code sent successfully." }`

**`OtpService::generate()` details:**
1. Check cache cooldown key → if still hot, return cached code without sending
2. Find Member by identifier
3. If Member: store OTP code (hashed), expiry, zero attempts on the Member model
4. If not Member: store in `otp_codes` table, invalidating old codes
5. Send via Notification (SendOtpCode) — email or SMS based on identifier
6. For deletion-cancellation flow, sends to all verified channels automatically

---

### 5d. `POST /api/v1/auth/verify-otp` — Verify OTP

**Route in `routes/api.php`:72**
```php
Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:api.otp')
    ->name('api.v1.auth.verify-otp');
```

**Flow:** `api.access` → `throttle:api.otp` → `AuthController::verifyOtp()`

```
Mobile App                    AuthOrchestrationService          OtpService              Database
    │                           │                                 │                       │
    │  Body: { identifier, otp }│                                 │                       │
    │──────────────────────────>│                                 │                       │
    │                           │  OtpService::verify(id, code)   │                      │
    │                           │────────────────────────────────>│                       │
    │                           │                                 │  For Members:          │
    │                           │                                 │  - Check hashed OTP   │
    │                           │                                 │  - Check expiry        │
    │                           │                                 │  - Check attempts ≤ 5  │
    │                           │                                 │                       │
    │                           │                                 │  If VALID:             │
    │                           │                                 │  - Clear OTP fields    │
    │                           │                                 │  - Set email/phone     │
    │                           │                                 │    verified_at          │
    │                           │                                 │  - Update state:       │
    │                           │                                 │    * neither verified  │
    │                           │                                 │      → pending_        │
    │                           │                                 │        additional_verif│
    │                           │                                 │    * one verified +    │
    │                           │                                 │      no onboarding     │
    │                           │                                 │      → pending_onboard │
    │                           │                                 │    * one verified +    │
    │                           │                                 │      onboarding done   │
    │                           │                                 │      → active          │
    │                           │                                 │  - Cancel deletion if  │
    │                           │                                 │    scheduled           │
    │                           │                                 │                       │
    │                           │  If valid → create Sanctum      │                       │
    │                           │  token with abilities matching  │                       │
    │                           │  new member state               │                       │
    │                           │                                 │                       │
    │  <── 200 { data: { valid, token, state, user,              │                       │
    │    verification_status } }│                                 │                       │
```

**Key OTP verify logic in `OtpService::verify()`:**

```
verify(identifier, code):
    member = Member::where(email|phone = identifier)->first()

    if member:
        if no otp_code or expired → return false
        if otp_attempts >= 5 → throw "Too many attempts"
        if ! Hash::check(code, otp_code) → increment attempts, return false

        // VALID CODE
        clear otp fields

        if identifier is email → set email_verified_at = now
        if identifier is phone → set phone_verified_at = now

        // Update state (only for non-active members)
        if member already 'active':
            keep 'active' (don't regress)
        else:
            if neither email nor phone verified → 'pending_additional_verification'
            elif onboarding not completed → 'pending_onboarding'
            else → 'active'

        // Cancel any scheduled deletion
        scheduled_for_deletion_at = null

        member.save()
        return true

    // Fallback to otp_codes table
    ...
```

---

### 5e. `POST /api/v1/auth/forgot-password` — Send Reset OTP

**Route in `routes/api.php`:73**
```php
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:api.otp')
    ->name('api.v1.auth.forgot-password');
```

**Flow:**
1. Validate identifier via `ForgotPasswordRequest`
2. Find user by identifier via `AuthService::findUserByIdentifier()`
3. If user is Member and NOT verified → return 403 with `EMAIL_NOT_VERIFIED`
4. If user found → generate and send OTP
5. **Always return 200** (don't leak whether account exists)

---

### 5f. `POST /api/v1/auth/reset-password` — Reset Password

**Route in `routes/api.php`:74**
```php
Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:api.otp')
    ->name('api.v1.auth.reset-password');
```

**Flow:**
1. Validate via `ResetPasswordRequest`
2. Find user by identifier
3. If unverified Member → 403
4. Verify OTP via `OtpService::verify()`
5. If invalid/expired → 422
6. If user not found → 404
7. `AuthService::resetPasswordByOtp(user, password)` → update password hash
8. Return 200

---

## 6. Sanctum-Protected Auth Routes (`api.access` + `auth:sanctum`)

These routes require both a valid API token AND an authenticated Sanctum session.

### 6a. `POST /api/v1/auth/logout` — Revoke Sanctum Token

**Route:** Line 77

`api.access` → `auth:sanctum` → `AuthController::logout()`
- `AuthService::logout($member)` → `$member->currentAccessToken()->delete()`
- Only revokes the Sanctum token, NOT the device token
- Returns 200

### 6b. `POST /api/v1/auth/complete-registration` — Complete Onboarding

**Route:** Line 80

`api.access` → `auth:sanctum` → `verified.account` → `throttle:api.auth` → controller

- Requires a verification-scoped Sanctum token AND verified email/phone
- `CompleteRegistrationRequest` validates: name, email, phone, date_of_birth, gender
- `AuthService::completeRegistration()`:
  1. Updates member with provided data, sets `status=active`, `state=active`, `onboarding_completed_at=now()`
  2. Deletes all existing Sanctum tokens
  3. Issues new full-access Sanctum token with abilities `['*']`
- Returns 201 with new token

### 6c. `POST /api/v1/auth/skip-additional-verification`

**Route:** Line 78

`api.access` → `auth:sanctum` → controller

- Checks member is not fully verified but has at least one method verified
- `AuthService::skipAdditionalVerification()`: sets state to `pending_onboarding`, issues onboarding-scoped token
- Returns token with abilities `['onboarding']`

### 6d. `POST /api/v1/auth/delete-account`

**Route:** Line 81

`api.access` → `auth:sanctum` → controller

- Validates password matches
- `MemberService::scheduleAccountDeletion()`: sets `scheduled_for_deletion_at` to 48 hours from now
- Login during this period triggers the deletion-cancellation flow (must verify OTP)

### 6e. `POST /api/v1/auth/request-family-otp`

**Route:** Line 79

`api.access` → `auth:sanctum` → `throttle:api.otp` → controller

- Optionally accepts `method` parameter: `email`, `phone`, `sms`
- Auto-detects identifier priority: verified phone → verified email → any contact
- Generates and sends OTP

---

## 7. User Verification Routes (`api.access` + `auth:sanctum`)

### 7a. `GET /api/v1/user/verification-status`

**Route:** Line 87

Returns `Member::getVerificationStatus()`:
```json
{
    "email_verified": false,
    "phone_verified": true,
    "onboarding_completed": false,
    "is_fully_verified": false,
    "email": "user@example.com",
    "phone": "+216123456",
    "unverified_method": "email"
}
```

### 7b. `POST /api/v1/user/verify-email`

**Route:** Line 88

- Validates `email` + optional `otp`
- If no OTP → generate OTP, return 200 with `{ generated: true }` (same as send-otp)
- If OTP provided → `AuthService::verifyIdentifier(member, email, otp, 'email')`
  - Verifies email belongs to the member
  - Verifies OTP
  - Deletes old Sanctum token, issues new one based on updated state
  - State transition: pending_verification → pending_additional_verification / pending_onboarding / active
- Returns new token, state, verification_status

### 7c. `POST /api/v1/user/verify-phone`

**Route:** Line 89

- Same pattern as verify-email but for phone
- Requires both `phone` and `otp`

---

## 8. Fully Protected Routes (Deep Nesting)

**Middleware layers applied in order:**
1. `ForceJsonResponse` (global API group)
2. `SetLocale` (global API group)
3. `EnsureApiAccess` (`api.access`) — validates device or Sanctum token
4. `auth:sanctum` — authenticates member from Sanctum token
5. `verified.account` — blocks unless email or phone verified
6. `onboarding.completed` — blocks unless onboarding completed
7. Route-specific middleware (`course.access`, `tunisia_geo`)

**Routes affected:**
- `member/profile` (GET/PUT), avatar upload/delete
- `user/profile`, `user/password`, `user/payments`
- `reservations/*` (ongoing, history, store, destroy, payment)
- `member/tier`
- `courses/{course}/sessions/*` (with `course.access`)
- `family/*` (children, members, enable/disable)
- `device-token` (push notification token storage)
- `subscriptions/*` (active, history, store, cancel)
- `user/events`, `events/{event}/register|withdraw|check-in`
- `payments/*` (with `tunisia_geo`)
- `loyalty/*` (pay, payments, balance — with `tunisia_geo`)

---

## 9. Payment Webhooks (No Auth)

**Route:** `POST /api/v1/payments/webhook/{provider}` (Line 177)

- Not behind `api.access` (called by third-party providers)
- Behind `tunisia_geo` middleware (with an exemption for webhook routes)
- Dedicated controller `PaymentController::webhook()`

---

## 10. State Machine Summary

```
                         ┌──────────────────┐
                         │  Registration     │
                         │  (device/register)│
                         └────────┬─────────┘
                                  │ device token issued
                                  ▼
                    ┌─────────────────────────┐
                    │    Guest State           │
                    │  (device token, no       │
                    │   member binding)         │
                    └────────┬────────────────┘
                             │
                ┌────────────┴────────────┐
                │ Member registers/login   │
                │ (auth/register, auth/    │
                │  login)                  │
                └────────────┬────────────┘
                             │
                             ▼
              ┌─────────────────────────────┐
              │   pending_verification        │
              │  (state = pending_verification│
              │   ability: verification)      │
              │   OTP sent immediately        │
              └────────────┬────────────────┘
                           │ verify-otp or verify-email/phone
                           ▼
              ┌─────────────────────────────┐
              │ pending_additional_          │
              │ verification                 │
              │ (one method verified, other  │
              │  still pending or skipped)    │
              │ ability: verification        │
              └──────┬──────────────┬───────┘
                     │              │ skip-additional-verification
                     │ verify       │
                     │ second       ▼
                     │ method  ┌──────────────────────┐
                     │         │  pending_onboarding    │
                     │         │ (ability: onboarding)  │
                     │         └──────┬───────────────┘
                     │                │ complete-registration
                     │                ▼
                     │         ┌──────────────────────┐
                     └────────>│      active            │
                               │ (full access, [*])     │
                               └──────────────────────┘
                                          │
                                          │ logout or delete
                                          ▼
                               ┌──────────────────────┐
                               │   pending_deletion_    │
                               │   cancellation          │
                               │ (ability: deletion-    │
                               │  cancellation)         │
                               │ 48h scheduled deletion │
                               └──────────────────────┘
                                          │
                              ┌───────────┴───────────┐
                              │ login + verify-otp    │ 48h passes
                              │ (cancels deletion)    │
                              ▼                       ▼
                         active                   deleted
                                                   (soft)
```

---

## 11. Database Schema

### `device_access_tokens` Table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint AI PK | |
| `device_id` | uuid | Unique per device |
| `token` | string(64) | Indexed, unique at any point but device can re-register |
| `device_fingerprint` | json | Nullable — model, os_version, locale, timezone, manufacturer |
| `platform` | string | `android` or `ios` |
| `app_version` | string | Max 20 chars |
| `integrity_passed` | boolean | Result of Play Integrity / App Attest |
| `integrity_payload` | text | Raw integrity token sent by client |
| `ip_address` | string | Nullable IP of last request |
| `member_id` | bigint FK→members | Nullable — null = guest |
| `last_verified_at` | datetime | When device integrity was last verified |
| `last_used_at` | datetime | Updated on every middleware pass |
| `expires_at` | datetime | Configurable TTL (default 30 days) |
| `is_revoked` | boolean | true = manually revoked (logout) |
| `revoked_at` | datetime | When it was revoked |
| `created_at` | datetime | |
| `updated_at` | datetime | |

### Member State Fields

| Field | Type | Values |
|-------|------|--------|
| `status` | string | `pending_verification`, `pending_additional_verification`, `pending_onboarding`, `active`, `banned`, `suspended` |
| `state` | string | Same values as status (both updated in tandem) |
| `email_verified_at` | datetime | Nullable |
| `phone_verified_at` | datetime | Nullable |
| `onboarding_completed_at` | datetime | Nullable — set by `complete-registration` |
| `scheduled_for_deletion_at` | datetime | Nullable — set by `delete-account`, cleared on login+OTP |

---

## 12. Full Request Lifecycle Examples

### Example A: Fresh Install → Full Flow

```
1. App first launch
   └→ POST /device/register
      ├→ 3/min IP throttle
      ├→ Version check: 1.2.0 >= 1.0.0 ✓
      ├→ Integrity check: bypass token matches ✓
      ├→ First registration → INSERT device_access_token
      └→ 201 { token: "device_tok_1", device_id: "uuid-1" }

2. User taps "Login"
   └→ POST /auth/login (with device_tok_1 in header, X-Device-ID: uuid-1)
      ├→ EnsureApiAccess: validates device_tok_1 ✓
      ├→ AuthOrch: validates credentials ✓
      ├→ State: pending_verification
      └→ 200 { token: "sanctum_tok_1", state: "pending_verification", ... }

3. App auto-links device (after getting sanctum_tok_1)
   └→ POST /device/link (with sanctum_tok_1)
      ├→ EnsureApiAccess: Sanctum fallback ✓
      ├→ Links device_tok to member: member_id set
      └→ 200 { message: "Device linked successfully" }

4. Verify email
   └→ POST /user/verify-email (with sanctum_tok_1)
      ├→ EnsureApiAccess → auth:sanctum ✓
      ├→ OTP verified, email_verified_at set
      ├→ State: pending_onboarding
      └→ 200 { token: "sanctum_tok_2", state: "pending_onboarding" }

5. Complete registration
   └→ POST /auth/complete-registration (with sanctum_tok_2)
      ├→ EnsureApiAccess → auth:sanctum → verified.account ✓
      ├→ Onboarding saved, status=active
      └→ 201 { token: "sanctum_tok_3", state: "active" }

6. Subsequent API calls use device_tok_1
   └→ GET /search (with device_tok_1, X-Device-ID: uuid-1)
      ├→ EnsureApiAccess: finds device token, member_id=1 → sets Auth
      └→ 200 { ... data ... }

7. Token refresh (within 7 days of expiry)
   └→ POST /device/refresh (with device_tok_1)
      ├→ EnsureApiAccess ✓
      ├→ Token rotated
      └→ 200 { token: "device_tok_2", expires_at: ... }

8. Logout
   └→ POST /device/logout (with device_tok_2)
      ├→ EnsureApiAccess ✓
      ├→ is_revoked = true
      └→ 200 "Device logged out successfully"
```

---

## 13. Error Response Reference

All error responses follow the `ApiResponse` trait convention:

```json
// Success
{ "success": true, "message": "...", "data": { ... } }

// Error
{ "success": false, "message": "...", "errors": {} }
```

### Common HTTP Status Codes

| Code | Meaning | Scenarios |
|------|---------|-----------|
| 200 | Success | All successful operations |
| 201 | Created | `device/register`, `auth/register`, `auth/complete-registration` |
| 401 | Unauthenticated | Missing/invalid token, X-Device-ID mismatch, stolen token |
| 403 | Forbidden | Unverified account, onboarding incomplete, geo-blocked, banned |
| 404 | Not Found | `device/link` when device token not found |
| 422 | Validation Error | Invalid input, outdated app version, integrity failure, expired OTP |
| 429 | Rate Limited | Configured via throttle middleware (retry_after_seconds in response) |
| 500 | Server Error | Unexpected exceptions |

### Auth-Specific 403 Codes

```json
// EnsureAccountIsVerified (verified.account)
{ "code": "ADDITIONAL_VERIFICATION_REQUIRED",
  "state": "pending_additional_verification" }

// EnsureOnboardingIsCompleted
{ "code": "ONBOARDING_INCOMPLETE",
  "state": "pending_onboarding",
  "required_action": "complete_onboarding" }

// ForgotPassword for unverified accounts
{ "code": "EMAIL_NOT_VERIFIED",
  "state": "pending_verification" }
```
