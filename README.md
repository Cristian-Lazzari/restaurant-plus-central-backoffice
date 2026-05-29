# Restaurant Plus Reports Backoffice

Private Laravel backoffice for collecting report snapshots from multiple Restaurant Plus dashboards.

## Setup

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Set these values in `.env` before exposing the app:

```env
BACKOFFICE_USERNAME=admin
BACKOFFICE_PASSWORD=change-this-password
```

## Sync

Manual sync from the web UI is available per site or for all active sites.

Artisan sync:

```bash
php artisan reports:sync
php artisan reports:sync --site=1
php artisan reports:sync --from=2026-05-01 --to=2026-05-31
```

The scheduler is configured in `app/Console/Kernel.php`:

```php
$schedule->command('reports:sync')->everyThirtyMinutes();
```
