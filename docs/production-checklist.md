# Production Checklist

## Backoffice centrale

Configurare `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=...
APP_URL=https://...
BACKOFFICE_USERNAME=...
BACKOFFICE_PASSWORD=...
LOG_LEVEL=warning
SESSION_EXPIRE_ON_CLOSE=true
SESSION_SECURE_COOKIE=true
```

`APP_URL` deve essere l'URL HTTPS pubblico del backoffice. `BACKOFFICE_PASSWORD` deve essere una password forte e non deve restare `change-me`.

Comandi deploy:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Cron scheduler:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Dashboard Restaurant Plus sorgente

Configurare `.env`:

```env
PRIVATE_REPORT_TOKEN=token_lungo_random
PRIVATE_REPORT_REVENUE_UNIT=unknown
```

Pulire cache config:

```bash
php artisan optimize:clear
```

Test endpoint:

```bash
curl -H "Authorization: Bearer TOKEN" "https://DOMINIO/api/private/report-summary"
```

## Primo collegamento

1. Creare il sito nel backoffice con URL HTTPS.
2. Inserire il token privato.
3. Eseguire una sync singola.
4. Controllare lo snapshot creato.
5. Controllare eventuali `data_warnings`.
6. Controllare `storage/logs/reports-sync.log`.
7. Non affidarsi allo scheduler prima di una sync manuale riuscita.
8. Collegare il primo sito reale da solo; aggiungere gli altri solo dopo una sync manuale riuscita.

## Verifica revenue

Query consigliata sul DB dashboard:

```sql
SELECT MIN(tot_price), MAX(tot_price), AVG(tot_price) FROM orders;
```

Regola pratica:

- Media tipo `1500/3000`: probabilmente centesimi.
- Media tipo `15/30`: probabilmente euro.

Poi aggiornare la dashboard sorgente:

```env
PRIVATE_REPORT_REVENUE_UNIT=cents
```

oppure:

```env
PRIVATE_REPORT_REVENUE_UNIT=euros
```

e rilanciare:

```bash
php artisan optimize:clear
```

## Note sviluppo locale

In sviluppo locale su HTTP, impostare temporaneamente `SESSION_SECURE_COOKIE=false` nel proprio `.env` locale. Non usare questa impostazione in produzione.
