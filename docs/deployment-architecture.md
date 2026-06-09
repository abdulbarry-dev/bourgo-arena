# Deployment Architecture

> Bourgo Arena is deployed on **Laravel Cloud** — a fully managed platform running on AWS EKS/Kubernetes with **Octane (FrankenPHP)**. Laravel Cloud provides compute (app cluster), Postgres (database), and Managed Queues — all provisioned through its dashboard with no configuration files.

---

## Architecture Overview

See `docs/diagrams/deployment.puml` for the full PlantUML deployment diagram. The diagram covers three layers:

1. **Development & CI/CD** — Git push triggers, GitHub Actions (Laravel), Codemagic (Flutter)
2. **Runtime (Laravel Cloud)** — Octane (FrankenPHP), Scheduler, Managed Queue, Postgres, shared filesystem
3. **Consumers** — Flutter mobile app (api.bourgoarena.com) and admin browser (app.bourgoarena.com)

### Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| **Single app for both API + Dashboard** | Same Laravel instance serves `api.bourgoarena.com` (Sanctum, Member model) and `app.bourgoarena.com` (Fortify, User model). Two separate auth systems sharing one codebase. |
| **Session driver: `database`** | Shared across all Cloud replicas. Each request reads the session from Postgres. No Redis dependency. |
| **Cache driver: `database`** | Rate limiter and cache data stored in Postgres. File driver (`CACHE_STORE=file`) is not shared across replicas and must not be used on Cloud. |
| **Queue driver: `database`** | Jobs dispatched to Postgres. Laravel Cloud Managed Queues can be enabled later without code changes — they natively support the `database` driver. |
| **Filesystem: `local`** | Laravel Cloud provides a shared writable filesystem across replicas. `FILESYSTEM_DISK=local` uses the `storage/` directory. No external storage service needed. |
| **Runtime: Octane (FrankenPHP)** | Enabled in Cloud dashboard. Caddy/FrankenPHP keeps PHP in memory between requests. No Nginx. |
| **No WebSockets / Reverb** | Broadcasting is disabled (`default => null`). No Reverb package installed. `config/reverb.php` has been removed. |

---

## Environment Strategy

| Aspect | Production | Staging |
|--------|------------|---------|
| **Git branch** | `main` | `develop` |
| **Domain** | `app.bourgoarena.com`, `api.bourgoarena.com` | Cloud auto-assigned URL |
| **Deploy trigger** | Merge PR to `main` | Push to `develop` |
| **Database** | Production Postgres (auto-provisioned) | Staging Postgres (auto-provisioned) |
| **Queue** | Managed Queue (production) | Managed Queue (staging) |
| **APP_DEBUG** | `false` | `false` |
| **LOG_LEVEL** | `warning` | `debug` |
| **KONNECT_SANDBOX** | `false` | `true` |

### Environment Variables

Variables set in **both** environments via Laravel Cloud dashboard:

| Variable | Source |
|----------|--------|
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Postgres resource (auto-injected) |
| `APP_KEY` | Generated during `cloud ship` |
| `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` | `database` (env override if needed) |
| `FILESYSTEM_DISK` | `local` |

Variables specific to each environment (set manually):

| Variable | Production | Staging |
|----------|------------|---------|
| `APP_ENV` | `production` | `staging` |
| `APP_URL` | `https://app.bourgoarena.com` | `https://{staging-url}.cloud.laravel.com` |
| `KONNECT_API_KEY` | Production key | Sandbox key |
| `KONNECT_API_SECRET` | Production secret | Sandbox secret |
| `KONNECT_SANDBOX` | `false` | `true` |
| `RESEND_API_KEY` | Production key | Test key |
| `TWILIO_ACCOUNT_SID` | Production | Test |
| `TWILIO_AUTH_TOKEN` | Production | Test |
| `TWILIO_FROM_NUMBER` | Production | Test |
| `FIREBASE_PROJECT` | Production | Test |
| `FIREBASE_CREDENTIALS` | Production JSON | Dev JSON |
| `MIN_ANDROID_VERSION` | Current release | `1.0.0` |
| `MIN_IOS_VERSION` | Current release | `1.0.0` |
| `DEVICE_TOKEN_TTL` | `30` | `30` |
| `DEV_INTEGRITY_BYPASS_TOKEN` | (not set) | `dev-bypass` |

---

## First Deployment Runbook

### Prerequisites

- Laravel Cloud account with billing configured
- GitHub repository connected to Laravel Cloud
- `LARAVEL_CLOUD_TOKEN` added to GitHub repository secrets

### Step 1 — Install Cloud CLI

```bash
composer global require laravel/cloud-cli
export PATH=$PATH:$(composer global config bin-dir --absolute)
```

### Step 2 — Authenticate

```bash
cloud login
```

### Step 3 — Create Application in Dashboard

1. Go to [cloud.laravel.com](https://cloud.laravel.com) → New Application
2. Connect GitHub repository
3. Create two environments:
   - **Production** — branch `main`
   - **Staging** — branch `develop`

### Step 4 — Provision Resources per Environment

In each environment's resource tab:

1. **Postgres** — Create database (starts at minimum tier)
2. **Managed Queue** — Enable (auto-scales to zero)
3. **Octane** — Toggle on in environment settings (uses FrankenPHP)

### Step 5 — Set Environment Variables

For each environment, set the variables listed in the table above via the Cloud dashboard.

### Step 6 — Configure Custom Domains (Production Only)

| Domain | SSL |
|--------|-----|
| `app.bourgoarena.com` | Auto-provisioned (ACME) |
| `api.bourgoarena.com` | Auto-provisioned (ACME) |

Set `APP_URL` to `https://app.bourgoarena.com` after domains are verified.

### Step 7 — Generate Cloud CLI Token for CI/CD

```bash
cloud auth:token --add
```

Copy the token and save as `LARAVEL_CLOUD_TOKEN` in GitHub repository secrets. Also save `LARAVEL_CLOUD_APP_ID` (from the Cloud dashboard URL or CLI).

### Step 8 — First Deploy (Manual)

Trigger the workflow from GitHub Actions:

```
Actions → Deploy to Laravel Cloud → Run workflow (branch: main)
```

Or deploy via CLI:

```bash
cloud deploy --app=<APP_ID> --environment=production --commit=main --wait
```

### Step 9 — Verify Health

```bash
curl https://app.bourgoarena.com/up
# Expected: {"status": "healthy"}
```

---

## Day-to-Day Deployments

### Automatic Deployments

| Event | Action |
|-------|--------|
| Push to `develop` | Tests run → if pass, deploy to **staging** |
| PR merged to `main` | Tests run → if pass, deploy to **production** |
| PR opened | Tests + lint run (no deploy) |

### Manual Deployment

```bash
# Via CLI
cloud deploy --app=<APP_ID> --environment=production --commit=<hash>

# Via GitHub Actions
Actions → Deploy to Laravel Cloud → workflow_dispatch → choose branch
```

### What the CI/CD Pipeline Does

```
git push (develop/main)
       │
       ▼
  ┌──────────┐
  │  Tests   │  ← lint + Pest tests
  └────┬─────┘
       │ pass
       ▼
  ┌──────────┐
  │  Deploy  │  ← cloud deploy --commit=<sha> --wait
  └────┬─────┘
       │
       ▼
   Laravel Cloud:
    1. Clone repo
    2. Docker build (Composer install --no-dev, npm ci, npm run build)
    3. Run deploy commands (php artisan migrate --force)
    4. Run `php artisan octane:reload` (warm up new Octane workers)
    5. Zero-downtime cutover
```

---

## Rollback Procedures

### Rollback via Git Revert (Recommended)

```bash
git revert HEAD        # Revert the last deploy commit
git push origin main   # Triggers automatic deploy
```

### Rollback via Cloud CLI (Emergency)

```bash
cloud deploy \
  --app=<APP_ID> \
  --environment=production \
  --commit=<previous-stable-hash> \
  --wait
```

### Rollback via GitHub Actions

1. Go to Actions → Deploy to Laravel Cloud
2. Run workflow → choose `main` branch
3. In Cloud dashboard, select the previous successful deployment
4. Click "Redeploy"

### Database Rollback

If the last deploy included a migration that needs reverting:

```bash
php artisan migrate:rollback --force
```

> **Caution:** Rollback is destructive. Prefer a forward fix migration.

---

## Mobile App Version Management

The application enforces minimum app versions via env vars:

| Variable | Purpose | Set in |
|----------|---------|--------|
| `MIN_ANDROID_VERSION` | Minimum Android version (e.g. `2.1.0`) | Cloud dashboard per environment |
| `MIN_IOS_VERSION` | Minimum iOS version (e.g. `2.1.0`) | Cloud dashboard per environment |

### Version Bump Workflow

```
1. Flutter team releases v2.1.0 to App Store / Play Store
2. After store approval, update MIN_ANDROID_VERSION=2.1.0 and MIN_IOS_VERSION=2.1.0
3. Deploy to production
4. Old clients receive 426 Upgrade Required on next API call
```

### Integrity Verification

Play Integrity (Android) and App Attest (iOS) stubs are in `app/Services/DeviceAttestationService.php`:
- Return `true` in dev/testing with bypass token
- Return `false` in production if credentials aren't configured (safe default — blocks unverified devices)
- Return `true` when real credentials are provisioned

---

## CI/CD Pipeline

The deployment diagram in `docs/diagrams/deployment.puml` includes the full CI/CD flow across both tracks:

| Track | Repository | CI/CD Tool | Deploys To |
|-------|------------|-----------|------------|
| Laravel Backend | This repo | GitHub Actions | Laravel Cloud (staging / production) |
| Flutter Mobile | Separate repo | Codemagic | App Store / Play Store |

### GitHub Secrets Required

| Secret | Used By | Source |
|--------|---------|--------|
| `LARAVEL_CLOUD_TOKEN` | `deploy-laravel-cloud.yml` | `cloud auth:token --add` |
| `LARAVEL_CLOUD_APP_ID` | `deploy-laravel-cloud.yml` | Cloud dashboard |
| `FLUX_USERNAME` | `tests.yml`, `deploy-laravel-cloud.yml` | Flux UI license |
| `FLUX_LICENSE_KEY` | `tests.yml`, `deploy-laravel-cloud.yml` | Flux UI license |

### Rendering the Diagram

```bash
# CLI (requires plantuml.jar or docker)
docker run --rm -v $(pwd)/docs/diagrams:/diagrams plantuml/plantuml -tsvg /diagrams/deployment.puml

# VS Code: install "PlantUML" extension → Alt+D to preview
# Online: https://www.plantuml.com/plantuml/uml/
```
