# Phase 5: Live Dashboards & Monitoring - Research

**Researched:** 2026-03-12
**Domain:** Laravel BFF dashboards, n8n REST API, Google Sheets read, Telegram alerts, scheduled monitoring
**Confidence:** HIGH (CONTEXT.md locks stack; n8n/Sheets/Telegram patterns verified via docs and existing code)

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- Dashboards live in the Laravel app as views; all data via Laravel API. Matches existing `/mission-control` and `MissionControlController`.
- Laravel is the only backend that talks to n8n and Google Sheets; frontend only calls Laravel. Credentials and caching stay in one place.
- Revenue dashboard (DASH-01): post counts, traffic estimates, affiliate clicks from existing Sheets tabs (Content Log, Revenue Tracker, etc.); no new "dashboard" sheet required. Backend may derive aggregates from those tabs.
- ADHD Mission Control (DASH-02): per workflow show last execution result (success/failure + time), "next run", and "active"; module list shows last run outcome, next run, and status (running/stopped/error). Data from n8n workflows + executions API.
- All alerts consolidated to Telegram: QC rejection, publish_failed, and "workflow X failed" all go to the same Telegram channel. Config-driven channel type (Discord | Slack | Telegram) with user choice to consolidate to Telegram; single destination for all failure alerts. Message body includes workflow name, error, timestamp (and for QC/publish: topic/reason as today).
- Webhook/token: in env (e.g. Laravel `.env`) when Laravel sends; when n8n sends (e.g. error branches), use n8n credential or config so no secrets in workflow JSON.
- Poll n8n: a monitor (Laravel cron or n8n health-check workflow) runs every 5–10 min, calls n8n executions API, detects failed or missing runs, then sends Telegram alert. No change to existing workflow logic.
- Expected-run rule: schedule list + last run. Config or list of scheduled workflow IDs + cron; monitor checks "should have run in last window" and "last run in that window = error"; alert on failure or "no run when expected".
- Weekly summary (DASH-04): content at least posts published, top performer, revenue estimate for the week; data source Sheets at minimum (Content Log, Revenue Tracker or equivalent); delivery email to owner (inbox); Sunday as baseline; planner may make day/time configurable.

### Claude's Discretion
- Exact config key names (e.g. WEEKLY_SUMMARY_DAY, TELEGRAM_BOT_TOKEN).
- Poll interval and whether any workflow gets an error-branch for faster alerts.
- Laravel vs n8n for monitor and for weekly summary sender.
- Exact weekly summary fields and email format.

### Deferred Ideas (OUT OF SCOPE)
- Discord/Slack as optional second channel (config-driven) — can be added later if desired.
- Real-time dashboard updates (WebSocket) — out of scope; 5-min cache acceptable.

</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DASH-01 | Revenue dashboard fetches live data from Google Sheets (replaces hardcoded demo data) | Laravel reads Sheets via google/apiclient or revolution/laravel-google-sheets; aggregate from Content Log, Revenue Tracker; expose /api/dashboard/revenue (or equivalent); frontend consumes API |
| DASH-02 | ADHD Mission Control dashboard shows real system status from n8n API | Laravel calls GET /api/v1/workflows and GET /api/v1/executions (per workflow or filtered); map to modules with last run, next run, status; extend /api/n8n/status or add /api/dashboard/mission-control; 5-min cache acceptable |
| DASH-03 | System health monitor sends Discord/Slack alert when any scheduled workflow fails | Consolidated to Telegram per CONTEXT; monitor (Laravel scheduler or n8n workflow) polls executions every 5–10 min; detect error or missing expected run; send Telegram via Laravel Http or n8n HTTP node; message: workflow name, error, timestamp |
| DASH-04 | Weekly summary report auto-generated and sent to owner (posts, revenue, top performers) | Laravel scheduler (e.g. Sunday) or n8n workflow; read Content Log + Revenue Tracker from Sheets; build summary; send via Laravel Mail or n8n Send Email; day/time configurable (e.g. WEEKLY_SUMMARY_DAY) |

</phase_requirements>

---

## Summary

Phase 5 wires the existing ADHD Mission Control and Revenue dashboards to live data and adds failure alerts plus a weekly email. The Laravel app (reference in `ui/laravel-admin-panel.php`) is the single backend: it talks to n8n (workflows + executions API) and Google Sheets, and sends Telegram alerts and weekly email. Frontend (existing React components or Blade views) only calls Laravel API; no credentials in the browser.

Revenue dashboard (DASH-01) needs Laravel to read Google Sheets (Content Log, Revenue Tracker, and any affiliate tab) and expose aggregates — post counts, traffic estimates, affiliate clicks — via an API. Use the Google Sheets API with a service account (google/apiclient or revolution/laravel-google-sheets); sheet ID and tab names from config/env.

ADHD dashboard (DASH-02) needs Laravel to call n8n’s GET /api/v1/workflows and GET /api/v1/executions (with status and optionally workflowId). Map workflows to “modules,” and for each show last execution result (success/error + time), next run (from schedule or heuristic), and active (workflow.active). The reference controller already calls /api/v1/workflows but uses only active/updatedAt; it must be extended to use the executions API for last run outcome. n8n API auth: X-N8N-API-KEY header (create key in n8n Settings > n8n API).

Failure monitor (DASH-03): run a job every 5–10 minutes (Laravel scheduler or a dedicated n8n “health check” workflow). Fetch recent executions (e.g. status=error or list then filter by status); optionally maintain a list of scheduled workflow IDs and cron expressions to detect “expected run in window but last run = error or missing.” On failure, POST to Telegram sendMessage (api.telegram.org/bot{token}/sendMessage) with workflow name, error message, timestamp. Phase 1 already sends QC rejection and publish_failed to Telegram; use the same channel and token (env).

Weekly summary (DASH-04): run once per week (e.g. Sunday; configurable). Read Content Log and Revenue Tracker from Sheets; compute posts published, top performer, revenue estimate; send one email to the owner via Laravel Mail (SMTP) or an n8n “Send Email” workflow. Planner may add streak/health line and optional n8n workflow health.

**Primary recommendation:** Implement in Laravel: (1) add Google Sheets service (service account) and endpoints for revenue aggregates and weekly-summary data; (2) extend MissionControlController (or equivalent) to fetch workflows + executions from n8n and return module list with last run/next run/status; (3) add a scheduled command or job that polls n8n executions, applies expected-run logic, and sends Telegram on failure; (4) add a weekly scheduled command that builds summary from Sheets and sends email via Laravel Mail. Use 5-min cache for dashboard APIs; no cache for the failure-check or weekly-summary logic.

---

## Standard Stack

### Core
| Library / System | Version / Ref | Purpose | Why Standard |
|------------------|---------------|---------|--------------|
| Laravel | 10+ / 11 | Backend API, scheduler, Mail | Existing reference (laravel-admin-panel.php); BFF for n8n + Sheets |
| n8n REST API | v1 | Workflows list, executions list/filter | Official API; workflows + executions drive dashboard and monitor |
| Google Sheets API | v4 | Read Content Log, Revenue Tracker | Project standard; Sheets is system of record (CLAUDE.md) |
| Telegram Bot API | sendMessage | Failure alerts, same channel as Phase 1 | Already used for QC/publish_failed; single destination per CONTEXT |

### Supporting
| Library / Approach | Purpose | When to Use |
|--------------------|---------|-------------|
| google/apiclient or revolution/laravel-google-sheets | Read Sheets from Laravel | DASH-01, DASH-04 data source |
| Laravel Http::withHeaders(['X-N8N-API-KEY' => ...]) | Call n8n from Laravel | DASH-02, DASH-03 monitor |
| Laravel Scheduler (cron) | Run monitor every 5–10 min; weekly summary on Sunday | DASH-03, DASH-04 |
| Laravel Mail (SMTP) or Mailable | Send weekly summary email | DASH-04 delivery |
| Http::post(Telegram sendMessage URL) | Send alert from Laravel | DASH-03 when Laravel runs monitor |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Laravel polling n8n | n8n “health check” workflow that calls executions and sends Telegram | Keeps monitor inside n8n; Laravel still needed for dashboards and Sheets |
| google/apiclient | revolution/laravel-google-sheets | Laravel-native API; both require service account and sheet ID/tab names |
| Plain Http to Telegram | irazasyed/telegram-bot-sdk | SDK adds dependency; simple POST is enough for sendMessage |

**Installation (Laravel):**
```bash
composer require google/apiclient
# OR composer require revolution/laravel-google-sheets
# No Telegram SDK required if using Http::post to api.telegram.org
```

---

## Architecture Patterns

### Recommended Project Structure (Laravel)
```
app/
├── Http/Controllers/
│   ├── MissionControlController.php   # Extend: n8n workflows + executions → dashboard data
│   └── DashboardController.php       # Optional: revenue + mission-control API routes
├── Services/
│   ├── GoogleSheetsService.php       # Read Sheets; aggregate for revenue + weekly summary
│   ├── N8nApiService.php              # GET workflows, GET executions (with API key)
│   └── TelegramAlertService.php      # sendMessage (workflow name, error, timestamp)
├── Console/
│   └── Commands/
│       ├── N8nFailureMonitorCommand.php   # Schedule every 5–10 min
│       └── WeeklySummaryCommand.php       # Schedule Sunday (or config day)
routes/
├── api.php                            # /api/dashboard/revenue, /api/dashboard/mission-control (or /api/n8n/status)
├── web.php                            # /mission-control, /revenue (views)
config/
├── services.php                       # n8n base URL, API key, Telegram token, sheet ID, weekly summary day
```

### Pattern 1: Laravel as BFF for Dashboards
**What:** Frontend (Blade + React or SPA) only calls Laravel API. Laravel reads n8n and Google Sheets, caches briefly (e.g. 5 min), returns JSON.
**When to use:** All dashboard data (DASH-01, DASH-02).
**Example:**
```php
// MissionControlController or N8nApiService
$workflows = Http::withHeaders(['X-N8N-API-KEY' => config('services.n8n.api_key')])
    ->timeout(5)
    ->get(config('services.n8n.base_url') . '/api/v1/workflows')
    ->json('data', []);

// For each workflow, optionally GET /api/v1/executions?workflowId={id}&limit=1 to get last run
// Or GET /api/v1/executions?status=error&limit=20 and match by workflowId
```

### Pattern 2: Failure Monitor (Poll + Telegram)
**What:** Scheduled job fetches n8n executions (e.g. status=error or recent list), optionally checks “expected run in window” from a config list of workflow IDs + cron expressions. On failure or missing run, POST to Telegram sendMessage.
**When to use:** DASH-03.
**Example:**
```php
// N8nFailureMonitorCommand or similar
$response = Http::withHeaders(['X-N8N-API-KEY' => config('services.n8n.api_key')])
    ->get(config('services.n8n.base_url') . '/api/v1/executions', [
        'status' => 'error',
        'limit'  => 50,
    ]);
$executions = $response->json('data', []);
foreach ($executions as $exec) {
    // Send Telegram: workflow name, $exec['message'] or error details, $exec['startedAt']
    Http::post('https://api.telegram.org/bot' . config('services.telegram.token') . '/sendMessage', [
        'chat_id' => config('services.telegram.chat_id'),
        'text'    => "⚠️ Workflow failed: {$exec['workflowData']['name']}\nError: {$exec['message']}\nTime: {$exec['startedAt']}",
        'parse_mode' => 'HTML',
    ]);
}
```

### Pattern 3: Weekly Summary from Sheets + Email
**What:** Scheduled command (e.g. Sunday) reads Content Log and Revenue Tracker via GoogleSheetsService, computes posts published, top performer, revenue estimate; sends one email via Laravel Mail.
**When to use:** DASH-04.
**Example:**
```php
// WeeklySummaryCommand
$sheets = app(GoogleSheetsService::class);
$contentLog = $sheets->readRange(config('services.google.sheet_id'), config('services.google.content_log_tab') . '!A:Z');
$revenueTracker = $sheets->readRange(config('services.google.sheet_id'), config('services.google.revenue_tracker_tab') . '!A:Z');
// Aggregate by week; find top performer; sum revenue
Mail::to(config('mail.weekly_summary_recipient'))->send(new WeeklySummaryMailable($summary));
```

### Anti-Patterns to Avoid
- **Frontend calling n8n or Google directly:** Credentials and rate limits stay server-side; Laravel is the only client for n8n and Sheets.
- **Hardcoding workflow names in monitor:** Use config or a list of workflow IDs + schedule so adding/removing workflows doesn’t require code changes.
- **Skipping expected-run logic:** Only polling status=error can miss “workflow didn’t run when it should”; maintain a small schedule list for critical workflows if “within 10 minutes” is to be approximated.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Reading Google Sheets from PHP | Custom OAuth + Sheets HTTP calls | google/apiclient or revolution/laravel-google-sheets | Auth, retries, range parsing, rate limits |
| Parsing n8n execution list | Ad-hoc JSON traversal without pagination | n8n API with limit/cursor and documented response shape | Pagination and status fields vary by version |
| Sending Telegram from Laravel | Custom socket or undocumented API | Http::post to api.telegram.org/bot{token}/sendMessage | Official Bot API; simple JSON body |
| Cron for monitor and weekly | One-off scripts | Laravel Scheduler (Kernel schedule) | Single entry point, logging, env-based |

**Key insight:** Google Sheets and n8n both have rate limits and response shapes that are easier to handle with established clients and the official APIs.

---

## Common Pitfalls

### Pitfall 1: n8n Executions List Missing status Field
**What goes wrong:** Some n8n versions return the executions list without a `status` field per item (see e.g. issue #20706); only GET /executions/{id} includes it.
**Why it happens:** API evolution and list vs. detail response shape.
**How to avoid:** Prefer filtering by status=error on the list endpoint when supported; if not, fetch last N executions and then GET each by id for status, or rely on workflow-level “last run” from a single execution fetch per workflow.
**Warning signs:** Dashboard or monitor shows “unknown” for last run status; list response has no `status` key.

### Pitfall 2: Google Sheets Column Name Casing
**What goes wrong:** Content Log and Revenue Tracker may use headers like "Date" vs "date", "WP URL" vs "wp_url". Aggregation or “today’s post” logic breaks.
**Why it happens:** Sheets returns headers as written; Phase 1 and others use lowercase in Code nodes.
**How to avoid:** Normalize header keys (e.g. strtolower or map both variants) when reading in Laravel; document expected column names in config.
**Warning signs:** Revenue dashboard shows zeros; weekly summary “posts published” is wrong.

### Pitfall 3: Caching Failure State for Monitor
**What goes wrong:** Dashboard cache (e.g. 5 min) is reused for the failure-check path; newly failed run isn’t alerted until cache expires.
**Why it happens:** Reusing the same Cache::remember for both dashboard and monitor.
**How to avoid:** Don’t cache the result of the failure monitor; run it on a schedule with fresh requests. Keep 5-min cache only for dashboard API responses.
**Warning signs:** Alerts arrive late or only after “refresh” in UI.

### Pitfall 4: n8n API Key and Base URL in Code
**What goes wrong:** API key or base URL hardcoded in controller; different per environment.
**Why it happens:** Copy-paste from reference or docs.
**How to avoid:** config('services.n8n.base_url'), config('services.n8n.api_key'), and Telegram token/chat_id from .env (or config); never commit secrets.
**Warning signs:** .env.example missing N8N_API_KEY, TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID.

---

## Code Examples

Verified patterns from official sources and project:

### n8n API: List Workflows and Executions
```php
// List workflows (existing pattern in laravel-admin-panel.php)
$response = Http::timeout(3)
    ->withHeaders(['X-N8N-API-KEY' => config('services.n8n.api_key')])
    ->get(config('services.n8n.base_url') . '/api/v1/workflows');

$workflows = $response->json('data', []);

// List recent failed executions (for monitor)
$response = Http::withHeaders(['X-N8N-API-KEY' => config('services.n8n.api_key')])
    ->get(config('services.n8n.base_url') . '/api/v1/executions', [
        'status' => 'error',
        'limit'  => 50,
    ]);
$failed = $response->json('data', []);
```
Source: n8n docs (API authentication, pagination); existing controller uses /api/v1/workflows.

### Telegram sendMessage from Laravel
```php
Http::post('https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/sendMessage', [
    'chat_id'    => config('services.telegram.chat_id'),
    'text'       => $message,
    'parse_mode' => 'HTML',
]);
```
Source: Telegram Bot API; project already uses same URL in workflows (e.g. 14_Video_Production.json) with $vars.TELEGRAM_BOT_TOKEN.

### Laravel Scheduler for Monitor and Weekly Summary
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('n8n:check-failures')->everyFiveMinutes();
    $schedule->command('weekly:summary')->weeklyOn(1, '08:00'); // Sunday 8 AM, or use config
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Dashboard data from Eloquent (ContentLog, SystemStatus) | Dashboard data from Sheets + n8n API | Phase 5 | Single source of truth: Sheets and n8n; no duplicate DB for content log |
| No failure alert for workflow runs | Poll executions + Telegram on error | Phase 5 | Owner notified within ~10 min of failure |
| Weekly summary only as view | Weekly summary as email (Laravel Mail or n8n) | Phase 5 | Inbox delivery; configurable day |

**Deprecated/outdated:** Using only workflow.active and updatedAt for “last run” — use executions API for last run outcome (success/error) and time.

---

## Open Questions

1. **n8n executions list response shape**
   - What we know: GET /api/v1/executions supports status=error (and success, running, waiting); cursor pagination; some versions omit status in list response.
   - What's unclear: Exact fields in each list item (e.g. workflowData.name, message, startedAt) and whether workflowId filter exists on list endpoint.
   - Recommendation: In implementation, call the list endpoint with status=error and log one response; if status or workflow name is missing, add one GET /executions/{id} per item or use workflow list + one execution fetch per workflow for dashboard.

2. **Expected-run window (optional strictness for “within 10 minutes”)**
   - What we know: CONTEXT allows planner to choose strictness; expected-run rule is “schedule list + last run” and “alert on failure or no run when expected.”
   - What's unclear: Whether to implement full “expected in window” (cron expression parse) or only “last run in last 10 min = error.”
   - Recommendation: Start with “recent executions with status=error” and Telegram; add optional config list of workflow IDs + simple schedule (e.g. “daily 8 AM”) for “should have run” if needed.

3. **Laravel app location**
   - What we know: laravel-admin-panel.php is a concatenated reference; CONTEXT says “dashboards live in the Laravel app as views.”
   - What's unclear: Whether the app lives in this repo (e.g. under /laravel or /app) or a separate repo.
   - Recommendation: Planner should assume Laravel app exists or will be created from the reference file; Phase 5 tasks add endpoints and scheduler in that app.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|--------|
| Framework | PHPUnit (Laravel default); no existing tests in repo |
| Config file | phpunit.xml (create if Laravel app is in repo) |
| Quick run command | `php artisan test --filter=Dashboard` or `./vendor/bin/phpunit --filter=Dashboard` |
| Full suite command | `php artisan test` or `./vendor/bin/phpunit` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| DASH-01 | Revenue API returns non-hardcoded aggregates from Sheets | Feature / Integration | `php artisan test --filter=RevenueDashboard` | ❌ Wave 0 |
| DASH-02 | Mission control API returns workflow list with last run/status from n8n | Feature / Integration | `php artisan test --filter=MissionControl` | ❌ Wave 0 |
| DASH-03 | Failure monitor sends Telegram on detected error execution | Feature / Integration (mock n8n + Telegram) | `php artisan test --filter=N8nFailureMonitor` | ❌ Wave 0 |
| DASH-04 | Weekly summary command builds summary and sends email | Feature / Integration (mock Sheets + Mail) | `php artisan test --filter=WeeklySummary` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=<relevant suite>`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green + manual UAT (open dashboards; trigger failure; receive weekly email)

### Wave 0 Gaps
- [ ] `tests/Feature/Dashboard/RevenueDashboardApiTest.php` — covers DASH-01 (mock Sheets or in-memory)
- [ ] `tests/Feature/Dashboard/MissionControlApiTest.php` — covers DASH-02 (mock n8n HTTP)
- [ ] `tests/Feature/Commands/N8nFailureMonitorCommandTest.php` — covers DASH-03 (mock n8n + Telegram)
- [ ] `tests/Feature/Commands/WeeklySummaryCommandTest.php` — covers DASH-04 (mock Sheets + Mail)
- [ ] Laravel test bootstrap and phpunit.xml — if app is in repo and not yet present
- [ ] Framework install: `composer require --dev phpunit/phpunit` (or use Laravel’s default)

---

## Sources

### Primary (HIGH confidence)
- CONTEXT.md — locked decisions, data sources, monitor and weekly summary behavior
- n8n docs (API authentication, pagination) — X-N8N-API-KEY, /api/v1/workflows, /api/v1/executions
- Telegram Bot API — sendMessage endpoint and parameters
- Laravel HTTP client, Scheduler, Mail — official Laravel docs
- ui/laravel-admin-panel.php — existing routes, MissionControlController, n8n base URL, Cache::remember(300)

### Secondary (MEDIUM confidence)
- WebSearch: n8n executions status filter, list response shape (status field issues)
- WebSearch: Laravel + Google Sheets (google/apiclient, revolution/laravel-google-sheets)
- WebSearch: Telegram sendMessage from Laravel (Http::post or telegram-bot-sdk)

### Tertiary (LOW confidence)
- Exact n8n executions list response schema (version-dependent) — validate during implementation

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — CONTEXT locks Laravel + n8n + Sheets + Telegram; packages and API patterns are standard
- Architecture: HIGH — BFF pattern and scheduler/monitor flow are clear from CONTEXT and reference code
- Pitfalls: MEDIUM — n8n list response and column casing are known risks; mitigation is straightforward

**Research date:** 2026-03-12
**Valid until:** ~30 days (Laravel and n8n APIs are stable; verify n8n executions response shape on target version)
