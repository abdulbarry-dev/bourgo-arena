# Apply this prompt on the mobile application endpoint logic

Implement strict account verification and onboarding state management for authentication flows without breaking existing API behavior.

GOAL
Users must not fully authenticate unless:

1. OTP/email verification is completed
2. Required onboarding is completed

The implementation must remain backward-compatible and isolated from unrelated application logic.

ACCOUNT STATES
Introduce standardized account states:

* pending_verification
* pending_onboarding
* active
* suspended (future-safe optional)

DATABASE REQUIREMENTS
Add/support fields such as:

* email_verified_at
* onboarding_completed_at
* otp_code
* otp_expires_at
* otp_attempts
* status

OTP SECURITY REQUIREMENTS

* OTP expiration: 5–10 minutes
* Invalidate previous OTP when generating new one
* Add resend cooldown
* Add attempt limiting
* Hash OTP values before storing
* Prevent OTP reuse

REGISTRATION FLOW
After registration:

* create account in pending_verification state
* send OTP
* do NOT fully activate account

Response example:

{
"success": true,
"state": "pending_verification",
"message": "OTP verification required"
}

LOGIN FLOW
When login is attempted:

1. If email not verified:
   Return:
   {
   "code": "EMAIL_NOT_VERIFIED",
   "state": "pending_verification"
   }

2. If verified but onboarding incomplete:
   Return:
   {
   "state": "pending_onboarding"
   }

3. If fully active:
   Return token + active state

PASSWORD RESET FLOW
If account email is NOT verified:

* deny password reset
* return:
  {
  "code": "EMAIL_NOT_VERIFIED"
  }

Require verification before password reset is allowed.

ONBOARDING COMPLETION
After onboarding completion:

* set onboarding_completed_at
* update status to active

SESSION/TOKEN RULES
Do NOT issue full-access tokens for:

* pending_verification users

Optionally:

* issue temporary limited tokens for verification-only endpoints

MIDDLEWARE / GUARDS
Add centralized guards/middleware enforcing:

* verified users only
* onboarding-completed users only

Ensure existing protected routes remain stable.

API DESIGN REQUIREMENTS
All auth responses should include a consistent auth state:

{
"token": "...",
"user": {},
"state": "active"
}

SUPPORTED STATES:

* pending_verification
* pending_onboarding
* active

CLEANUP
Optionally:

* add scheduled cleanup for abandoned unverified accounts after X days

SAFETY REQUIREMENTS

* Do NOT break existing APIs
* Preserve backward compatibility
* Avoid changing unrelated business logic
* Add defensive validation
* Add proper exception handling
* Keep implementation modular

TESTING
Add/update tests for:

* registration + OTP verification
* expired OTP
* resend OTP
* login before verification
* onboarding incomplete
* password reset before verification
* successful activation flow
* invalid OTP attempts
* token restrictions

ARCHITECTURE
Follow clean Laravel architecture:

* Services
* Actions/use-cases
* DTOs/resources if already used
* Form requests
* Middleware/policies where appropriate

Avoid controller-heavy logic.
