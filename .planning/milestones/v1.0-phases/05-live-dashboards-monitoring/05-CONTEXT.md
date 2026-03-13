# Phase 5: Live Dashboards & Monitoring - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

The owner can open either dashboard and see real system data (Revenue dashboard: live post counts, traffic estimates, affiliate data from Sheets; ADHD Mission Control: real n8n workflow run status). When any scheduled workflow fails, a Telegram alert arrives within ~10 minutes. Every week (Sunday or configurable day), a weekly summary is sent to the owner's inbox with posts published, top performer, and revenue estimate. Requirements in scope: DASH-01, DASH-02, DASH-03, DASH-04. No new capabilities beyond these.

</domain>

<decisions>
## Implementation Decisions

### Dashboard hosting and data path

- Dashboards live in the Laravel app as views; all data via Laravel API. Matches existing `/mission-control` and `MissionControlController`.
- Laravel is the only backend that talks to n8n and Google Sheets; frontend only calls Laravel. Credentials and caching stay in one place.
- Revenue dashboard (DASH-01): post counts, traffic estimates, affiliate clicks from existing Sheets tabs (Content Log, Revenue Tracker, etc.); no new "dashboard" sheet required. Backend may derive aggregates from those tabs.
- ADHD Mission Control (DASH-02): per workflow show last execution result (success/failure + time), "next run", and "active"; module list shows last run outcome, next run, and status (running/stopped/error). Data from n8n workflows + executions API.

### Alert channel (DASH-03)

- All alerts consolidated to Telegram: QC rejection, publish_failed, and "workflow X failed" all go to the same Telegram channel.
- Config-driven channel type (Discord | Slack | Telegram) with user choice to consolidate to Telegram; single destination for all failure alerts. Message body includes workflow name, error, timestamp (and for QC/publish: topic/reason as today).
- Webhook/token: in env (e.g. Laravel `.env`) when Laravel sends; when n8n sends (e.g. error branches), use n8n credential or config so no secrets in workflow JSON. Planner picks per sender.

### Failure detection

- Poll n8n: a monitor (Laravel cron or n8n health-check workflow) runs every 5–10 min, calls n8n executions API, detects failed or missing runs, then sends Telegram alert. No change to existing workflow logic.
- Expected-run rule: schedule list + last run. Config or list of scheduled workflow IDs + cron; monitor checks "should have run in last window" and "last run in that window = error"; alert on failure or "no run when expected".
- Who runs the monitor and how strict "within 10 minutes" is: planner's choice (Laravel scheduler vs n8n workflow; poll interval and/or error branches for critical workflows).

### Weekly summary (DASH-04)

- Content: at least posts published, top performer, revenue estimate for the week; planner may add streak/health line.
- Data source: Sheets at minimum (Content Log, Revenue Tracker or equivalent); planner may add n8n for workflow health.
- Delivery: email to owner (inbox); planner picks mechanism (Laravel Mail or n8n send-email).
- When: Sunday as baseline; planner may make day/time configurable (e.g. config keys).

### Claude's Discretion

- Exact config key names (e.g. WEEKLY_SUMMARY_DAY, TELEGRAM_BOT_TOKEN).
- Poll interval and whether any workflow gets an error-branch for faster alerts.
- Laravel vs n8n for monitor and for weekly summary sender.
- Exact weekly summary fields and email format.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets

- `ui/adhd-mission-control.tsx` — React component with hardcoded `systemStatus`, `weeklyWins`, `priorities`; replace with live data from Laravel API (n8n workflow/execution status, today progress, modules).
- `ui/revenue-dashboard.tsx` — React with hardcoded `revenueData`, `trafficData`, `contentStats`, `agentActivity`, `topPosts`; replace with data from Laravel API (Sheets-sourced aggregates).
- `ui/laravel-admin-panel.php` — Defines routes `/mission-control`, `/weekly-summary`, `/api/n8n/status`, `/api/n8n/trigger/{workflow}`; `MissionControlController` uses `Cache::remember(300)`, calls n8n at `http://localhost:5678` (e.g. `/api/v1/workflows`). Controller currently uses Eloquent `ContentLog`, `SystemStatus`; Phase 5 will need to source from Sheets and n8n executions API where appropriate, or keep Laravel as BFF that aggregates from both.

### Established Patterns

- Config Loader and htg_config.csv for single source of truth; no secrets in workflow JSON.
- Google Sheets as system of record (Content Log, Revenue Tracker, etc.); satellites read/write via config sheet ID and tab names.
- Phase 1: Telegram already used for QC rejection and publish_failed; Phase 5 extends to workflow-failure alerts on same channel.

### Integration Points

- Laravel: add or extend endpoints that return workflow list + last execution per workflow (n8n API); and Sheets-derived revenue/traffic/post counts for Revenue dashboard. Weekly summary: Laravel scheduler or n8n workflow that reads Sheets (and optionally n8n), sends email.
- Failure monitor: either Laravel scheduler (cron every 5–10 min) calling n8n executions API and sending Telegram, or dedicated n8n "health check" workflow on schedule doing the same. Schedule list (workflow IDs + cron) from config or code.

</code_context>

<specifics>
## Specific Ideas

- User chose to consolidate all alerts to Telegram (no Discord/Slack required unless added later via config).
- "Within 10 minutes" for failure alert: best effort via poll interval or error branches; planner decides strictness vs over-engineering.

</specifics>

<deferred>
## Deferred Ideas

- Discord/Slack as optional second channel (config-driven) — can be added later if desired.
- Real-time dashboard updates (WebSocket) — out of scope; 5-min cache acceptable.

</deferred>

---
*Phase: 05-live-dashboards-monitoring*
*Context gathered: 2026-03-12*
