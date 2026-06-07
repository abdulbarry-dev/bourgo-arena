# Laravel Cloud CI/CD & Infrastructure Guide

This guide provides a comprehensive overview of the automated deployment pipeline and infrastructure management for the Bourgo Arena application on Laravel Cloud.

## 1. Environment & Secrets Management

Configuration for Laravel Cloud is managed at two levels: **GitHub Secrets** (for authentication/deployment) and **Laravel Cloud Environment Variables** (for application runtime).

### A. GitHub Repository Secrets
To enable automated deployments, navigate to your repository **Settings > Secrets and variables > Actions** and add:

| Secret | Description |
| :--- | :--- |
| `LARAVEL_CLOUD_TOKEN` | API token generated from the [Laravel Cloud User Settings](https://cloud.laravel.com/user/api-tokens). |
| `LARAVEL_CLOUD_APP_ID` | The unique ID of your application (found in the application dashboard). |

### B. Laravel Cloud Environment Variables (Web Dashboard)
Navigate to your environment (e.g., `production`) in the Laravel Cloud dashboard and configure the following variables. These are stored securely and injected into your application at runtime.

#### Database Configuration (Serverless Postgres)
Configure the following database credentials in the Laravel Cloud dashboard. These were provided specifically for the `production` environment:

| Key | Value |
| :--- | :--- |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `ep-cold-bonus-a57up21g.aws-us-east-2.pg.laravel.cloud` |
| `DB_PORT` | `5432` |
| `DB_USERNAME` | `laravel` |
| `DB_PASSWORD` | `npg_8KrUHFPnQpL7` (Must be set in Cloud Dashboard) |
| `DB_DATABASE` | `laravel` (Default for this cluster) |

#### Application Configuration
| Key | Value |
| :--- | :--- |
| `APP_NAME` | `Bourgo Arena` |
| `APP_ENV` | `production` |
| `APP_KEY` | `[Generate using 'php artisan key:generate']` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-domain.com` |

---

## 2. GitHub Actions Deployment Workflow

The deployment logic is defined in `.github/workflows/deploy.yml`. This workflow automates the build process and triggers the Laravel Cloud deployment.

### Automated Deployment Workflow (`.github/workflows/deploy.yml`)

```yaml
name: Deploy to Laravel Cloud

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          tools: composer:v2

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '22'

      - name: Install Dependencies
        run: |
          composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
          npm ci

      - name: Build Frontend
        run: npm run build

      - name: Install Laravel Cloud CLI
        run: composer global require laravel/cloud-cli

      - name: Deploy to Laravel Cloud
        run: |
          export PATH=$PATH:$(composer global config bin-dir --absolute)
          cloud deploy --app=${{ secrets.LARAVEL_CLOUD_APP_ID }} --environment=production --commit=${{ github.sha }} --wait -n
        env:
          LARAVEL_CLOUD_TOKEN: ${{ secrets.LARAVEL_CLOUD_TOKEN }}

      - name: Deployment Monitoring
        run: |
          export PATH=$PATH:$(composer global config bin-dir --absolute)
          cloud deploy:monitor -n
        env:
          LARAVEL_CLOUD_TOKEN: ${{ secrets.LARAVEL_CLOUD_TOKEN }}
```

---

## 3. Planning & Enabled Features

Laravel Cloud offers several managed features that should be planned for during setup.

### A. Computing & Instance Sizing
Laravel Cloud allows you to scale your application by choosing different instance sizes.
- **Web Instances**: Handles HTTP requests.
- **Autoscaling**: Enabled by default for Serverless Postgres and can be configured for web instances based on concurrency.

### B. Background Processes
Background tasks should be explicitly defined in your `.cloud/config.json` or through the dashboard:
- **Queues**: Configure worker processes (e.g., `php artisan queue:work`) to handle asynchronous jobs like `GenerateTournamentBracketJob`.
- **Scheduled Tasks (Cron)**: Laravel's task scheduler (`php artisan schedule:run`) is automatically supported. Ensure the scheduler is enabled in your environment settings.

### C. Managed Services
- **Managed Queues**: Integrated support for Redis or SQS-based queues.
- **Asset Storage**: Use S3-compatible buckets for file uploads (managed via `FILESYSTEM_DISK=s3`).
- **Database Clusters**: Highly available PostgreSQL clusters with automated backups.

---

## 4. Deployment Monitoring & Troubleshooting

If a deployment fails, use the following CLI commands to diagnose the issue:

1.  **Check Logs**: `cloud deploy:monitor -n`
2.  **View Command History**: `cloud command:list production -n`
3.  **Run Tinker**: `cloud tinker production --code='dump(config("database.connections.pgsql"))' -n`
4.  **Remote Artisan**: `cloud command:run production --cmd='php artisan migrate:status' -n`

---

## 5. Deployment Procedures Checklist

1. [ ] Generate `APP_KEY` for the production environment.
2. [ ] Provision the **Serverless Postgres** cluster.
3. [ ] Map the **Custom Domain** and verify DNS records.
4. [ ] Configure **Background Workers** for tournament generation and notifications.
5. [ ] Ensure **Vite** is properly configured to use the production URL for assets.
