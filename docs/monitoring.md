# Payment Monitoring & Observability Checklist

This document lists minimal monitoring and observability steps to operate payment reconciliation safely.

## What to monitor
- Webhook 4xx/5xx rates (rejects and server errors)
- Queue backlog (jobs waiting, recent failures)
- `failed_jobs` count and recent exceptions
- Payment reconciliation failures and reconciliation latency
- Payment volume and error rate by gateway (Konnect)

## Events emitted
- `App\Events\PaymentReconciled` — emitted when a payment is marked `paid` or `refunded`. Use to increment counters and trigger receipts.
- `App\Events\PaymentReconcileFailed` — emitted on failed reconciliation attempts.

## Integration notes
- Wire these events to your metrics backend (StatsD/Datadog) or error tracking (Sentry). Example listener:

```
// in EventServiceProvider.php
protected $listen = [
  \App\Events\PaymentReconciled::class => [\App\Listeners\RecordPaymentMetrics::class],
  \App\Events\PaymentReconcileFailed::class => [\App\Listeners\AlertOpsTeam::class],
];
```

- For Sentry, set `SENTRY_DSN` and add the Sentry Laravel SDK. Log exceptions in `ReconcilePaymentJob::failed()` will be captured.

## Alerts & thresholds (suggested)
- Failed job rate & failed_jobs > 5 in 1 hour → PagerDuty / Slack alert
- Webhook 5xx spike (+50% baseline) → Alert
- Queue length > 1000 → Alert
- Payment reconciliation latency > 10s (median) → Alert

## Quick ops commands
- View recent failed jobs:

```bash
php artisan queue:failed
```

- Retry a specific failed job:

```bash
php artisan queue:retry {id}
```

- Flush failed jobs (use with caution):

```bash
php artisan queue:flush
```

## Next actions
- Install APM / metrics provider and wire the two events to custom listeners that emit metrics.
- Implement a `RecordPaymentMetrics` listener to increment counters for success/refund/failure.
- Configure dashboards and alerts based on thresholds above.
