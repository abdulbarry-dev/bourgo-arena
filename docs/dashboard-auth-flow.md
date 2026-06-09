# Dashboard (Web) Authentication & Request Flow

This document traces every dashboard request through the web middleware stack, Fortify authentication pipeline, role-based authorization, and view rendering — providing the complete lifecycle for building sequence diagrams.

---

## 1. Architecture Overview

The dashboard uses **Laravel Fortify** (session-based) authenticating against the **`User` model** (staff accounts: Admin/Manager). This is a completely separate auth system from the mobile API, which uses **Sanctum tokens** against the **`Member` model**.

| Aspect | Dashboard (Web) | Mobile API |
|--------|----------------|------------|
| Guard | `web` (session) | `sanctum` (token) |
| Auth package | Laravel Fortify | Custom `EnsureApiAccess` + Sanctum |
| Model | `App\Models\User` | `App\Models\Member` |
| Token type | Session cookie + CSRF | Bearer token |
| User roles | Admin, Manager | Member |
| Registration | Fortify (creates `role=Member`) | `auth/register` API |
| Fortify views | Yes (Blade + Flux UI) | No (JSON only) |

---

## 2. Global Middleware Stack

Defined in `bootstrap/app.php` via `MiddlewareServiceProvider::registerMiddleware()`.

### Web Middleware Group (Laravel defaults + appends)

The `web` middleware group (used by all web routes, including Fortify):

```
Request
  │
  ├── \App\Http\Middleware\EncryptCookies
  ├── \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse
  ├── \Illuminate\Session\Middleware\StartSession
  ├── \Illuminate\View\Middleware\ShareErrorsFromSession
  ├── \Illuminate\Routing\Middleware\SubstituteBindings
  │
  ├── [appended] EnsureUserIsNotBanned
  │     └── Checks Auth::user()->isBanned()
  │         → If banned: logout, invalidate session, regenerate token
  │         → Redirect to login with "Your account is banned" error
  │
  └── [appended] SetLocale
        └── Reads session('locale') and sets app()->setLocale()
```

### Middleware Aliases (used in routes)

| Alias | Class | Purpose |
|-------|-------|---------|
| `auth` | `Illuminate\Auth\Middleware\Authenticate` | Standard session auth guard |
| `verified` | `EnsureEmailVerifiedOrIsStaff` | Blocks unverified users (staff exempt) |
| `role` | `EnsureUserHasRole` | Checks user role against configured list |
| `throttle:` | `Illuminate\Routing\Middleware\ThrottleRequests` | Rate limiting |

---

## 3. Fortify Configuration

**File:** `config/fortify.php`

```php
'guard' => 'web',                    // Session-based auth
'passwords' => 'users',              // Password broker for User model
'username' => 'email',               // Login field
'prefix' => '',                      // Routes at root (/login, /register, etc.)
'middleware' => ['web'],             // Web middleware group
'home' => '/admin',                  // Redirect after login
'views' => true,                     // Fortify renders Blade views
```

### Enabled Features

```php
'features' => [
    Features::resetPasswords(),                    // Forgot/reset password flow
    Features::emailVerification(),                  // MustVerifyEmail for User
    Features::twoFactorAuthentication([             // TOTP 2FA
        'confirm' => true,                         // Must confirm after enabling
        'confirmPassword' => true,                 // Require password to manage 2FA
    ]),
];
```

Note: `registration` and `updateProfileInformation` / `updatePasswords` features are **not** enabled — the app handles these through Livewire settings pages instead.

---

## 4. FortifyServiceProvider Customization

**File:** `app/Providers/FortifyServiceProvider.php`

### 4a. Custom Authentication Logic

```php
Fortify::authenticateUsing(function (Request $request) {
    $user = User::where('email', $request->email)->first();

    if ($user && Hash::check($request->password, $user->password)) {
        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'email' => 'Your account is banned. Reason: ...',
            ]);
        }
        return $user;  // Authentication successful
    }

    return null;  // Authentication failed
});
```

**Flow:**
```
Login Request
  │
  ├── 1. Find User by email
  │      └─ [not found] → return null → Fortify sends validation error
  │
  ├── 2. Hash::check(password)
  │      └─ [fails] → return null → Fortify sends validation error
  │
  ├── 3. Check isBanned()
  │      └─ [banned] → throw ValidationException with message
  │
  └── 4. Return User → Fortify logs in via SessionGuard
```

### 4b. Rate Limiting

```php
// Custom 'login' limiter (referenced by config/fortify.php limiters.login)
RateLimiter::for('login', function (Request $request) {
    $throttleKey = Str::lower($request->email) . '|' . $request->ip();
    return Limit::perMinute(5)->by($throttleKey);
});

// Custom 'two-factor' limiter
RateLimiter::for('two-factor', function (Request $request) {
    return Limit::perMinute(5)->by($request->session()->get('login.id'));
});
```

### 4c. Registered Views

```php
Fortify::loginView(fn () => view('livewire.auth.login'));
Fortify::registerView(fn () => view('livewire.auth.register'));
Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
```

### 4d. Custom Actions

```php
Fortify::createUsersUsing(CreateNewUser::class);
Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
```

**CreateNewUser** (`app/Actions/Fortify/CreateNewUser.php`):
- Validates name, email, password
- Creates User with `role = UserRole::Member`
- Note: Registered users are Members, not staff — they cannot access dashboard

**ResetUserPassword** (`app/Actions/Fortify/ResetUserPassword.php`):
- Validates new password
- `$user->forceFill(['password' => $input['password']])->save()`

---

## 5. Fortify Route Inventory

All routes registered by Fortify at the root prefix:

| Method | URI | Name | Middleware | Description |
|--------|-----|------|-----------|-------------|
| GET | `/login` | `login` | web | Login form |
| POST | `/login` | `login.store` | web, throttle:login | Login submission |
| POST | `/logout` | `logout` | web | Logout |
| GET | `/register` | `register` | web | Registration form |
| POST | `/register` | `register.store` | web | Registration submission |
| GET | `/forgot-password` | `password.request` | web | Forgot password form |
| POST | `/forgot-password` | `password.email` | web | Send reset link |
| GET | `/reset-password/{token}` | `password.reset` | web | Reset password form |
| POST | `/reset-password` | `password.update` | web | Reset password submission |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | web, auth | Verify email link |
| POST | `/email/verification-notification` | `verification.send` | web, auth, throttle:6,1 | Resend verification |
| GET | `/email/verify` | `verification.notice` | web, auth | Verification notice page |
| GET | `/user/confirm-password` | `password.confirm` | web, auth | Confirm password form |
| POST | `/user/confirm-password` | `password.confirm.store` | web, auth, throttle:6,1 | Confirm password |
| POST | `/user/two-factor-authentication` | `two-factor.create` | web, auth, password.confirm | Enable 2FA |
| DELETE | `/user/two-factor-authentication` | `two-factor.destroy` | web, auth, password.confirm | Disable 2FA |
| POST | `/user/confirmed-two-factor-authentication` | `two-factor.confirm` | web, auth | Confirm 2FA setup |
| GET | `/user/two-factor-qr-code` | `two-factor.qr-code` | web, auth | Get 2FA QR code |
| GET | `/user/two-factor-recovery-codes` | `two-factor.recovery-codes` | web, auth | Get recovery codes |
| POST | `/user/two-factor-recovery-codes` | `two-factor.recovery-codes.store` | web, auth | Regenerate recovery codes |
| POST | `/two-factor-challenge` | `two-factor.login` | web, throttle:two-factor | 2FA challenge submission |
| GET | `/two-factor-challenge` | `two-factor.challenge` | web | 2FA challenge form |

---

## 6. Login Request Lifecycle

### Full Flow: GET /login → POST /login → Redirect

```
Browser                         Laravel                          Fortify                     Database
  │                                │                                │                           │
  │  GET /login                    │                                │                           │
  │───────────────────────────────>│                                │                           │
  │                                │                                │                           │
  │    Middleware: web group                                       │                           │
  │    (session, cookies, CSRF,                                   │                           │
  │     EnsureUserIsNotBanned,                                     │                           │
  │     SetLocale)                                                 │                           │
  │                                │                                │                           │
  │    Fortify::loginView()                                       │                           │
  │    → resources/views/                                          │                           │
  │      livewire/auth/login.blade                                 │                           │
  │                                │                                │                           │
  │    Login form renders:                                        │                           │
  │    - Flux UI components                                       │                           │
  │    - Email + Password fields                                  │                           │
  │    - "Remember me" checkbox                                   │                           │
  │    - "Forgot your password?" link                             │                           │
  │    - "Sign up" link (if route exists)                         │                           │
  │                                │                                │                           │
  │  <── 200 Login Page ─────────────────────────────────────────│                           │
  │                                │                                │                           │
  │  ─────────────────────────────────────────────────────────────│                           │
  │                                │                                │                           │
  │  POST /login                   │                                │                           │
  │  Body: { email, password }     │                                │                           │
  │───────────────────────────────>│                                │                           │
  │                                │                                │                           │
  │    Middleware: web group                                       │                           │
  │                                │                                │                           │
  │    Fortify authentication                                     │                           │
  │    pipeline (Laravel\Fortify\                                  │                           │
  │     Actions\AttemptToAuthenticate):                            │                           │
  │                                │                                │                           │
  │    Custom authenticateUsing() │                                │                           │
  │    ────────────────────────   │                                │                           │
  │    1. User::where(email)      │                                │                           │
  │    ───────────────────────────────────────────────────────────────────────────────────────>│
  │    <── [found/not found] ──────────────────────────────────────────────────────────────────│
  │                                │                                │                           │
  │    2. Hash::check(password)    │                                │                           │
  │    ── [fails] → return null → validation error                 │                           │
  │                                │                                │                           │
  │    3. Check isBanned()         │                                │                           │
  │    ── [banned] → throw ValidationException                     │                           │
  │                                │                                │                           │
  │    4. Return User → Fortify calls                             │                           │
  │       Auth::guard('web')->login(user, remember)                │                           │
  │                                │                                │                           │
  │    ──── If 2FA enabled ────                                    │                           │
  │    │ Fortify checks user->two_factor_secret                   │                           │
  │    │ If set → redirects to /two-factor-challenge              │                           │
  │    │ POST /two-factor-challenge verifies TOTP code            │                           │
  │    │ Rate limited: 5/min per session('login.id')               │                           │
  │                                │                                │                           │
  │    On success:                                                 │                           │
  │    Fortify redirects to config('fortify.home') = /admin       │                           │
  │                                │                                │                           │
  │    ──── web.php redirect ────                                  │                           │
  │    │ Route::redirect('admin', 'dashboard') (line 9)           │                           │
  │    │ → Redirects /admin to /dashboard                         │                           │
  │                                │                                │                           │
  │    /dashboard [middleware: auth, verified, role:admin,manager] │                           │
  │                                │                                │                           │
  │    EnsureEmailVerifiedOrIsStaff                                │                           │
  │    ──────────────────────────                                 │                           │
  │    If User implements MustVerifyEmail AND email not verified: │                           │
  │      - Staff (Admin/Manager) → skip, allow access             │                           │
  │      - Non-staff → redirect to verification.notice            │                           │
  │                                │                                │                           │
  │    EnsureUserHasRole('admin','manager')                        │                           │
  │    ─────────────────────────────────                            │                           │
  │    Check user->role->value in ['admin', 'manager']             │                           │
  │    If not → 403 Forbidden                                     │                           │
  │                                │                                │                           │
  │    Dashboard Livewire component                               │                           │
  │    (App\Livewire\Admin\Analytics\Dashboard)                    │                           │
  │                                │                                │                           │
  │  <── 200 Dashboard ──────────────────────────────────────────│                           │
```

### Login Error Paths

| Scenario | Response |
|----------|----------|
| Invalid credentials | Redirect back to `/login` with `{ email: ["auth.failed"] }` error |
| Account banned | Redirect back to `/login` with `{ email: "Your account is banned..." }` error |
| Rate limited (5/min) | Redirect back to `/login` with rate limit error |
| 2FA required | Redirect to `/two-factor-challenge` |
| Email not verified | Redirect to `/email/verify` (unless staff) |

---

## 7. After-Login Route Tree

All authenticated routes are in `routes/web.php:36-42` inside `middleware(['auth', 'verified'])`.

### `routes/web.php`

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('role:admin,manager')
        ->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
```

The `'verified'` middleware alias maps to `EnsureEmailVerifiedOrIsStaff` — it allows staff through without email verification but blocks non-staff.

### `routes/admin.php` — Admin Section

```
/admin/* [middleware: 'auth', 'verified']  [from web.php]
  │
  ├── [middleware: 'role:admin,manager']  (shared)
  │     ├── GET /search → SearchResults Livewire
  │     ├── GET /members → members dashboard view
  │     ├── GET /reservations → ReservationManager Livewire
  │     ├── GET /subscriptions → subscriptions dashboard view
  │     ├── GET /subscriptions/expiring → expiring subscriptions view
  │     ├── GET /subscriptions/{sub} → subscription detail view
  │     ├── GET /course-sessions → CourseSessionManager Livewire
  │     ├── GET /activities → ActivityManager Livewire
  │     └── GET /activities/{activity}/sessions → ActivitySessionManager Livewire
  │
  └── [prefix: 'admin', middleware: 'role:admin']  (admin-only)
        ├── GET /payments/audit → AuditLogs Livewire
        ├── GET /payments/audit/export → PaymentAuditController::exportCsv
        ├── GET /plans → plans dashboard view
        ├── GET /courses → CourseManager Livewire
        ├── GET /managers → Index Livewire (manager management)
        ├── GET /events → EventManager Livewire
        ├── GET /events/{event}/participants → EventParticipants Livewire
        ├── GET /events/{event}/bracket → EventBracketManager Livewire
        └── GET /services → ServiceManager Livewire
```

### `routes/settings.php` — Settings Section

```
/settings/* [middleware: 'auth', 'role:admin,manager']
  │
  ├── /settings → redirects to /settings/profile
  │
  ├── [no 'verified' required]
  │     └── GET /settings/profile → Profile Livewire
  │
  └── [middleware: 'verified']
        ├── GET /settings/appearance → Appearance Livewire
        ├── GET /settings/language → Language Livewire
        └── GET /settings/security → Security Livewire
              └── [additional: password.confirm if 2FA enabled]
```

---

## 8. Verdict Middleware (EnsureEmailVerifiedOrIsStaff)

**File:** `app/Http/Middleware/EnsureEmailVerifiedOrIsStaff.php`

This middleware replaces Laravel's built-in `verified` middleware to allow staff (Admin/Manager) to access the dashboard without verifying their email.

```
handle(request, next):
    user = request.user()

    if user && user instanceof MustVerifyEmail && ! user.hasVerifiedEmail():
        if user.isStaff():        // Admin or Manager
            return next(request)  // Skip verification for staff
        else:
            if request expects JSON:
                abort 403, "Your email address is not verified."
            else:
                redirect to verification.notice page

    return next(request)
```

---

## 9. Role Middleware (EnsureUserHasRole)

**File:** `app/Http/Middleware/EnsureUserHasRole.php`

```
handle(request, next, ...roles):
    user = request.user()

    if ! user → abort 403

    currentRole = user->role->value   // e.g., 'admin', 'manager', 'member'
    allowedRoles = parse comma-separated list from arguments

    if currentRole not in allowedRoles → abort 403

    return next(request)
```

The `UserRole` enum (`app/UserRole.php`):
```php
enum UserRole: string {
    case Member = 'member';
    case Admin = 'admin';
    case Manager = 'manager';
}
```

---

## 10. EnsureUserIsNotBanned Middleware (Global Web)

**File:** `app/Http/Middleware/EnsureUserIsNotBanned.php`

Applied globally to all web routes via the `web` middleware group.

```
handle(request, next):
    if Auth::check() && Auth::user().isBanned():
        reason = user->ban_reason ?? 'Administrative decision.'
        Auth::logout()
        request.session().invalidate()
        request.session().regenerateToken()
        redirect to login with { email: "Your account is banned. Reason: ..." }

    return next(request)
```

This runs on every web request, including authenticated and unauthenticated pages.

---

## 11. Two-Factor Authentication Flow

### Enabling 2FA

```
User clicks "Enable 2FA" on /settings/security
  │
  ├── Middleware: auth → verified → role:admin,manager → password.confirm
  │
  ├── POST /user/two-factor-authentication
  │     → Fortify enables 2FA (generates two_factor_secret)
  │
  ├── GET /user/two-factor-qr-code
  │     → Returns QR code for authenticator app
  │
  ├── User scans QR and enters TOTP code
  │
  └── POST /user/confirmed-two-factor-authentication
        → Fortify confirms 2FA (sets two_factor_confirmed_at)
```

### 2FA Login Flow

```
POST /login (credentials valid, user has 2FA enabled)
  │
  ├── Fortify stores user ID in session('login.id')
  │
  ├── Redirect to GET /two-factor-challenge
  │     → Renders two-factor-challenge view
  │
  ├── POST /two-factor-challenge { code }
  │     ├── [throttle: 5/min per session('login.id')]
  │     ├── Verifies TOTP code against two_factor_secret
  │     ├── [valid] → log in, redirect to /admin
  │     └── [invalid] → return with errors
```

---

## 12. Email Verification Flow

### Registration + Verification

```
User submits POST /register
  │
  ├── CreateNewUser action creates User with `role = UserRole::Member`
  ├── User implements MustVerifyEmail
  ├── Fortify sends verification email
  └── Redirect to /email/verify (verification.notice)

User clicks link in email:
  GET /email/verify/{id}/{hash}
  │
  ├── Verifies id + hash match User record
  ├── Sets email_verified_at
  └── Redirect to /admin
```

### Verification Notice Page

```
GET /email/verify [middleware: auth]
  │
  ├── Renders verify-email Livewire view
  ├── Shows: "Please verify your email..."
  ├── Button: "Resend verification email" → POST /email/verification-notification
  └── Button: "Log out" → POST /logout
```

### Staff Exemption

Staff (Admin/Manager roles) can access the dashboard without email verification. `EnsureEmailVerifiedOrIsStaff` checks `$user->isStaff()` and bypasses the redirect.

---

## 13. Password Reset Flow

```
1. User clicks "Forgot your password?" on login page
   │
   ├── GET /forgot-password
   │     → Renders forgot-password view
   │
   └── POST /forgot-password { email }
         → Fortify sends password reset link via User::sendPasswordResetNotification()
         → Custom SendPasswordResetNotificationAction handles delivery

2. User clicks reset link in email
   │
   ├── GET /reset-password/{token}
   │     → Renders reset-password view with hidden token field
   │
   └── POST /reset-password { email, password, password_confirmation, token }
         → ResetUserPassword action validates and saves new password
         → Redirect to /admin
```

---

## 14. Complete Request Lifecycle Example

### Admin Journey: Login → Dashboard → Settings

```
1. Browser → GET /login
   ├── web middleware (session, cookies, CSRF, banned, locale)
   ├── Fortify::loginView() → renders login.blade.php
   └── 200 HTML page

2. Browser → POST /login { email: "admin@example.com", password: "..." }
   ├── web middleware
   ├── throttle:login (5/min per email+IP)
   ├── Fortify pipeline:
   │     ├── Custom authenticateUsing():
   │     │     ├── User::where(email = "admin@example.com") → found
   │     │     ├── Hash::check(password) → ✓
   │     │     ├── isBanned() → false
   │     │     └── Return User → Fortify calls Auth::login()
   │     └── 2FA check: disabled → skip
   ├── Fortify redirects to /admin
   ├── web.php: /admin redirects to /dashboard
   └── GET /dashboard
         ├── web middleware
         ├── auth → authenticated (session)
         ├── verified (EnsureEmailVerifiedOrIsStaff):
         │     └── isStaff() → true → bypass
         ├── role:admin,manager
         │     └── user->role = 'admin' → allowed
         └── Dashboard Livewire component renders

3. Browser → GET /subscriptions
   ├── web middleware
   ├── auth → ✓
   ├── verified → ✓ (staff bypass)
   ├── role:admin,manager → ✓
   └── subscriptions dashboard view renders

4. Browser → GET /admin/plans
   ├── web middleware
   ├── auth → ✓
   ├── verified → ✓ (staff bypass)
   ├── role:admin → ✓
   └── plans dashboard view renders

5. Browser → GET /settings/profile
   ├── web middleware
   ├── auth → ✓
   ├── role:admin,manager → ✓
   └── Profile Livewire component renders
```

### Forbidden Journey (Regular Member tries dashboard)

```
1. User registers via Fortify → role = Member

2. Browser → GET /dashboard
   ├── web middleware
   ├── auth → ✓ (authenticated as Member)
   ├── verified:
   │     └── isStaff() → false → not verified → redirect to /email/verify
   └── [If verified somehow] role:admin,manager
         └── user->role = 'member' → 403 Forbidden
```

---

## 15. Error Reference

### Common HTTP Status Codes

| Code | Meaning | Scenarios |
|------|---------|-----------|
| 200 | Success | Page renders |
| 302 | Redirect | Post-login, post-logout, verification redirect |
| 403 | Forbidden | Wrong role, banned account |
| 419 | Session expired | CSRF token mismatch |
| 429 | Rate Limited | Login/2FA throttled |

### Error Responses (Non-JSON)

| Scenario | Response |
|----------|----------|
| Invalid login | Redirect to `/login` with `{ email: "auth.failed" }` |
| Banned login | Redirect to `/login` with `{ email: "Account is banned..." }` |
| Login throttled | Redirect to `/login` with rate limit error |
| 2FA code invalid | Redirect back to `/two-factor-challenge` with errors |
| Email not verified | Redirect to `/email/verify` (unless staff) |
| Role denied | Abort 403 |
| Banned (active session) | Logout → redirect to `/login` with banned message |

### API-Style JSON Responses (for AJAX/Livewire requests)

When the request expects JSON, `EnsureEmailVerifiedOrIsStaff` returns:
```json
{
    "message": "Your email address is not verified."
}
// HTTP 403
```
