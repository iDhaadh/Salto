# SALTO Battery Monitor

Monitors **SALTO Space** lock battery status by reading the SALTO MS SQL Server
database (read-only) and sends **email** + **WhatsApp** alerts when a lock goes
**Low** or **Flat/Dead**. Includes a web dashboard for status and alert history.

SALTO Space can *display* battery status but does not push alerts — this app
fills that gap.

## What it does

- Polls the SALTO database on a schedule (default every 15 min).
- Opens an alert when a lock is Low/Flat; **de-duplicates** so you aren't spammed
  (one alert per lock, with a reminder every N hours until resolved).
- Resolves the alert and (optionally) notifies recovery when the battery is normal.
- Sends **email** (SMTP) and **WhatsApp** (Meta Cloud API, template messages).
- Dashboard: lock overview with battery badges, alert history, editable settings,
  and a "send test notification" button.

## Architecture

```
SALTO MS SQL (read-only)  ──►  Laravel app  ──►  Email (SMTP)
                                  │              WhatsApp (Meta Cloud API)
                                  ▼
                        App DB (MySQL): locks cache,
                        alerts, notification log, settings
```

Two DB connections: `salto` (sqlsrv, **SELECT only**) and the default `mysql`
for the app's own data. See `config/database.php` and `config/salto.php`.

## Quick start (Docker)

Everything runs in containers — including the Microsoft ODBC driver for SQL
Server, so there's nothing to install on the host except Docker.

```bash
cp .env.example .env          # then edit (SALTO_DB_*, mail, WhatsApp, recipients)
docker compose up -d --build
```

Open <http://localhost:8080> — default login **admin@example.com / password**
(change via `ADMIN_EMAIL` / `ADMIN_PASSWORD` before first boot, or update later).

Services: `app` (PHP-FPM), `web` (Nginx :8080), `appdb` (MySQL), `queue`
(notification worker), `scheduler` (runs `salto:check` on the interval).

> Preview the dashboard with sample data (no SALTO needed):
> `docker compose exec app php artisan db:seed --class=DemoSeeder`

## Connecting to SALTO — Phase 0 discovery (do this once)

The table/column holding battery status varies by SALTO Space version, so it is
**configurable**, not hard-coded. After filling in `SALTO_DB_*` in `.env`:

```bash
# 1. Find battery-related columns / candidate lock tables
docker compose exec app php artisan salto:discover

# 2. Inspect a candidate table's data
docker compose exec app php artisan salto:discover --sample=tb_DOOR
```

Then set these in `.env` to match what you found:

```
SALTO_LOCK_TABLE=tb_DOOR
SALTO_COL_ID=ID
SALTO_COL_NAME=NAME
SALTO_COL_LOCATION=NAME
SALTO_COL_BATTERY=BATTERY_STATUS
SALTO_COL_LASTSEEN=LAST_UPDATE
```

If a single-table query isn't enough (e.g. you need a join for location), set
`SALTO_RAW_SQL` to a full read-only `SELECT` that aliases columns to
`id, name, location, battery, last_seen`.

Finally map the raw battery values to states in `config/salto.php`
(`battery_map`) — confirm the real values via `salto:discover --sample`.

Test the read end-to-end:

```bash
docker compose exec app php artisan salto:check
```

## WhatsApp setup (Meta Cloud API)

Outbound alerts must use **pre-approved templates**. In Meta Business / WhatsApp
Manager, create two body templates taking 4 parameters in order —
`{{1}}` lock name, `{{2}}` location, `{{3}}` status, `{{4}}` timestamp — e.g.:

> ⚠️ SALTO lock *{{1}}* ({{2}}) battery is *{{3}}* as of {{4}}. Please service it.

Then set in `.env`: `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_ID`,
`WHATSAPP_TEMPLATE_LOW`, `WHATSAPP_TEMPLATE_FLAT`. Add recipient numbers in the
dashboard Settings page (E.164, e.g. `+9607712345`).

Send a test through all enabled channels:

```bash
docker compose exec app php artisan salto:test-notify
# or use the "Send test notification" button on the Settings page
```

## Commands

| Command | Purpose |
|---|---|
| `salto:check` | One monitoring cycle (scheduled automatically). |
| `salto:discover` | Find SALTO battery tables/columns (read-only). |
| `salto:test-notify` | Send a sample alert to configured recipients. |

## Tests

```bash
docker compose exec app php artisan test
```

Covers the alert lifecycle (open → de-dupe → reminder → resolve), unknown-state
handling, and battery-value mapping.

## Production notes (Linux)

- The same `docker compose up -d --build` works on the Linux server.
- Put SMTP and WhatsApp secrets in `.env` (never commit it).
- Use a **read-only** SQL login scoped to the SALTO database.
- The `queue` and `scheduler` containers must stay running for alerts to fire;
  `restart: unless-stopped` is already set. **Restart `queue` after deploying
  code changes** so the worker picks them up.
- Without Docker, the app is a standard Laravel project: install the MS ODBC
  Driver 18 + `pdo_sqlsrv`, run `php artisan migrate --seed`, add a cron entry
  for `schedule:run`, and run `queue:work` under systemd/Supervisor.
