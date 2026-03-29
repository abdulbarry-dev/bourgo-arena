# CI/CD Procedures

## Pipeline Overview

- `lint.yml`: static formatting checks.
- `tests.yml`: test suite for push and PR.
- `deploy-staging.yml`: auto-deploy on merge to `develop`.
- `deploy-production.yml`: manual deploy (`workflow_dispatch`) with explicit release ref.

## Required GitHub Secrets

- Staging
    - `STAGING_HOST`
    - `STAGING_SSH_USER`
    - `STAGING_SSH_PRIVATE_KEY`
    - `STAGING_SSH_PORT`
    - `STAGING_APP_PATH`
- Production
    - `PRODUCTION_HOST`
    - `PRODUCTION_SSH_USER`
    - `PRODUCTION_SSH_PRIVATE_KEY`
    - `PRODUCTION_SSH_PORT`
    - `PRODUCTION_APP_PATH`
- Notifications
    - `SMTP_HOST`
    - `SMTP_PORT`
    - `SMTP_USERNAME`
    - `SMTP_PASSWORD`
    - `DEPLOY_NOTIFY_TO`
    - `DEPLOY_NOTIFY_FROM`
- Flux package access
    - `FLUX_USERNAME`
    - `FLUX_LICENSE_KEY`

## Staging Deploy Procedure

1. Merge into `develop`.
2. `deploy-staging` verifies lint/tests/build.
3. On success, deployment executes over SSH and runs:
    - `composer install --no-dev`
    - `php artisan migrate --force`
    - `npm run build`
    - `php artisan optimize`
4. Email notification is sent for success/failure.

## Production Deploy Procedure

1. Create release tag from validated commit.
2. Trigger `deploy-production` manually and pass `release_ref`.
3. Environment protection in GitHub (`production`) handles approval gate.
4. Pipeline runs verify stage then SSH deploy stage.
5. Email notification is sent for success/failure.

## Rollback Procedure

If deployment fails after checkout or migration:

1. Identify last known good tag (example: `v2026.03.29-1`).
2. On server:

```bash
cd "$PRODUCTION_APP_PATH"
git fetch --all --tags
git checkout <last-good-tag>
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
php artisan migrate:rollback --step=1 --force || true
npm ci
npm run build
php artisan optimize:clear
php artisan optimize
```

3. Re-run health checks (`/up`, login, dashboard, API check-in endpoint).
4. Create incident note with:
    - failed release ref
    - rollback tag
    - root cause
    - corrective action

## Safety Notes

- Never store secrets in repository files.
- Always deploy production from immutable tag or commit SHA.
- Keep DB backups before major migration releases.
