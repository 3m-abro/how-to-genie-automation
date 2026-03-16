# HowTo-Genie Mission Control (Laravel)

Laravel backend for the HowTo-Genie automation platform. Serves the **Mission Control** dashboard, weekly summary (view + email), revenue dashboard API, n8n status/trigger endpoints, and failure alerts. Designed for the “look at it once per week” workflow: dashboard and APIs are cached (5 min) to limit n8n/Sheets calls.

Part of the [how-to-genie-automation](../) repo. For the full system (n8n workflows, config, docs), see the root [README](../README.md) and [docs/HOWTOGENIE.md](../docs/HOWTOGENIE.md).

---

## What this app does

| Feature | Routes / entry points | Purpose |
|--------|------------------------|---------|
| **Mission Control dashboard** | `GET /mission-control` | Single-view status: system health, today’s progress, weekly wins, priorities, streak, next actions, quick stats. Data from n8n + Google Sheets + local DB; cached 5 min. |
| **Weekly summary** | `GET /weekly-summary` | Weekly recap view (posts, views, revenue, top post, action items). |
| **Weekly summary email** | `WEEKLY_SUMMARY_*` env + scheduler | Optional mailable sent on a chosen weekday/time (SMTP via `MAIL_*`). |
| **Quick actions** | `POST /api/quick-action/{action}` | Trigger n8n workflows (e.g. run pipeline now). |
| **n8n trigger** | `POST /api/n8n/trigger/{workflow}` | Webhook-style trigger for a workflow by name. |
| **n8n status** | `GET /api/n8n/status` | Workflow list and recent executions (for dashboards). |
| **Revenue dashboard API** | `GET /api/dashboard/revenue` | Revenue/traffic data for the revenue dashboard UI. |

**Console commands**

- `n8n:failure-monitor` — Check n8n executions for failures; optionally send Telegram alerts.
- `weekly-summary:send` — Send the weekly summary email (run via scheduler or manually).

---

## Requirements

- **PHP** 8.2+
- **Composer**
- **Node/npm** (for Vite assets)
- **n8n** running (default `http://localhost:5678`); optional API key for listing/triggering.
- **Google Sheets** (Content Log, Revenue Tracker) — optional; used for dashboard data.
- **Telegram** (optional) — for failure alerts.
- **SMTP** (optional) — for weekly summary email.

---

## Setup

1. **From repo root**, enter the Laravel app:
   ```bash
   cd laravel
   ```

2. **Install dependencies**
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Environment**
   - Copy `.env.example` to `.env` and set at least:
     - `APP_NAME`, `APP_URL`
     - `N8N_BASE_URL` (default `http://localhost:5678`), `N8N_API_KEY` (optional, for n8n API)
     - `GOOGLE_SHEET_ID`, `GOOGLE_CONTENT_LOG_TAB`, `GOOGLE_REVENUE_TRACKER_TAB`; `GOOGLE_APPLICATION_CREDENTIALS` path for Sheets API
   - Optional: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` for alerts; `WEEKLY_SUMMARY_RECIPIENT`, `WEEKLY_SUMMARY_DAY`, `WEEKLY_SUMMARY_TIME` and `MAIL_*` for weekly email.

4. **Database** (SQLite by default)
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

5. **Frontend**
   ```bash
   npm install
   npm run build
   ```

6. **Run**
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000/mission-control` (or your `APP_URL`).

---

## Env reference (HowTo-Genie)

| Variable | Purpose |
|----------|---------|
| `N8N_BASE_URL` | n8n instance URL (default `http://localhost:5678`) |
| `N8N_API_KEY` | n8n API key (optional; for workflows/executions) |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` | Telegram alerts (optional) |
| `GOOGLE_SHEET_ID` | Google Sheets spreadsheet ID |
| `GOOGLE_CONTENT_LOG_TAB` | Sheet tab name for content log (default `Content Log`) |
| `GOOGLE_REVENUE_TRACKER_TAB` | Sheet tab for revenue (default `Revenue Tracker`) |
| `GOOGLE_APPLICATION_CREDENTIALS` | Path to Google service account JSON (Sheets API) |
| `WEEKLY_SUMMARY_RECIPIENT` | Email address for weekly summary |
| `WEEKLY_SUMMARY_DAY` | Day of week (0=Sunday … 6=Saturday) |
| `WEEKLY_SUMMARY_TIME` | Time (e.g. `08:00`) |

---

## Project structure (relevant to Mission Control)

- `app/Http/Controllers/` — `MissionControlController`, `N8nWebhookController`, `DashboardController`
- `app/Services/` — `N8nApiService`, `GoogleSheetsService`, `TelegramAlertService`
- `app/Models/` — `ContentLog`, `SystemStatus`, `User`
- `app/Console/Commands/` — `N8nFailureMonitorCommand`, `WeeklySummaryCommand`
- `app/Mail/` — `WeeklySummaryMailable`
- `resources/views/mission-control/` — dashboard and weekly-summary Blade views
- `config/services.php` — n8n, Telegram, Google, weekly_summary config

---

## Tests

```bash
composer test
# or
php artisan test
```

Includes Feature tests for Mission Control API, Revenue Dashboard API, Weekly Summary command, and n8n failure monitor command.

---

## License

Part of the HowTo-Genie automation repo. See repo root for license or usage terms.
