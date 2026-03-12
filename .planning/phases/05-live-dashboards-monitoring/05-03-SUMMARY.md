---
phase: 05-live-dashboards-monitoring
plan: "03"
subsystem: monitoring
tags: laravel, n8n, telegram, scheduler, cron

# Dependency graph
requires:
  - phase: 05-live-dashboards-monitoring
    provides: Phase 1 QC/publish_failed Telegram channel (same channel for failure alerts)
provides:
  - Telegram alert when any scheduled n8n workflow execution fails (workflow name, error, timestamp)
  - Scheduled failure monitor every 5 minutes so alerts arrive within ~10 min
affects: 05-live-dashboards-monitoring (DASH-04 weekly summary can reuse Telegram/alert patterns)

# Tech tracking
tech-stack:
  added: []
  patterns: "Laravel Http to n8n API (X-N8N-API-KEY), Telegram Bot API sendMessage (no SDK), 24h alert dedupe cache"

key-files:
  created: laravel/app/Services/TelegramAlertService.php
  modified: laravel/app/Console/Commands/N8nFailureMonitorCommand.php, laravel/tests/Feature/Commands/N8nFailureMonitorCommandTest.php, laravel/routes/console.php

key-decisions:
  - "Failure monitor runs in Laravel scheduler (everyFiveMinutes); cron must run php artisan schedule:run"
  - "Same Telegram channel as Phase 1 QC/publish_failed; no Discord/Slack in scope"
  - "24h already-alerted cache (execution id) to avoid duplicate alerts for same run"

patterns-established:
  - "TelegramAlertService: single sendMessage via Http::post to api.telegram.org; no SDK"
  - "N8nFailureMonitorCommand: GET /api/v1/executions?status=error&limit=50; extract workflow name, error, startedAt; no cache of monitor result"

requirements-completed: [DASH-03]

# Metrics
duration: ~8min
completed: "2026-03-12"
---

# Phase 05 Plan 03: N8n Failure Monitor Summary

**Failure monitor that polls n8n error executions and sends a Telegram alert per failure (workflow name, error, timestamp), scheduled every 5 minutes so alerts arrive within ~10 minutes.**

## Performance

- **Duration:** ~8 min
- **Started:** 2026-03-12T14:20:35Z
- **Completed:** 2026-03-12
- **Tasks:** 2
- **Files modified:** 4 (1 created, 3 modified)

## Accomplishments

- TelegramAlertService: sendMessage via Telegram Bot API (Http::post, no SDK); same channel as Phase 1 QC/publish_failed
- N8nFailureMonitorCommand: fetches n8n GET /api/v1/executions with status=error and limit=50; sends one Telegram message per execution with workflow name, error message, startedAt; 24h already-alerted cache to avoid duplicates
- Monitor scheduled every 5 minutes in routes/console.php (Laravel 11)
- Tests: mock n8n HTTP and TelegramAlertService; assert alert content and dedupe behavior

## Task Commits

Each task was committed atomically:

1. **Task 1: TelegramAlertService and N8nFailureMonitorCommand** - `c03be9f` (feat)
2. **Task 2: Schedule monitor every 5–10 minutes** - `daf28bb` (feat)

**Plan metadata:** (final docs commit with SUMMARY, STATE, ROADMAP, REQUIREMENTS)

## Self-Check: PASSED

- TelegramAlertService.php exists; N8nFailureMonitorCommand.php and routes/console.php updated; 05-03-SUMMARY.md created.
- Commits c03be9f and daf28bb present in git log.

## Files Created/Modified

- `laravel/app/Services/TelegramAlertService.php` - Sends one message via Telegram Bot API sendMessage; logs on missing config or send failure
- `laravel/app/Console/Commands/N8nFailureMonitorCommand.php` - Polls n8n executions (error), builds alert text (workflow, error, time), calls TelegramAlertService; 24h cache; resilient to n8n connection failure
- `laravel/tests/Feature/Commands/N8nFailureMonitorCommandTest.php` - Mocks n8n and TelegramAlertService; tests no errors, alert content, and dedupe
- `laravel/routes/console.php` - Schedule::command('n8n:check-failures')->everyFiveMinutes(); comment re cron

## Decisions Made

- Schedule in routes/console.php (Laravel 11 has no app/Console/Kernel.php)
- 24h TTL for already-alerted cache (execution id) to avoid duplicate alerts
- On n8n connection failure: log and return SUCCESS so scheduler does not treat as command failure

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing Critical] Resilient to n8n connection failure**
- **Found during:** Task 1 (verification: php artisan n8n:check-failures with n8n down)
- **Issue:** Command threw ConnectionException when n8n unreachable; scheduled runs would fail repeatedly
- **Fix:** Wrapped n8n GET in try-catch; log warning and return SUCCESS
- **Files modified:** laravel/app/Console/Commands/N8nFailureMonitorCommand.php
- **Committed in:** c03be9f (Task 1 commit)

**2. [Scope - File location] Schedule in routes/console.php not Kernel.php**
- **Found during:** Task 2
- **Issue:** Plan referenced app/Console/Kernel.php; project is Laravel 11 and has no Kernel
- **Fix:** Registered schedule in routes/console.php (Schedule::command(...)->everyFiveMinutes())
- **Files modified:** laravel/routes/console.php
- **Impact:** Same behavior; no functional change

---

**Total deviations:** 1 auto-fixed (resilience), 1 file-location (Laravel 11)
**Impact on plan:** Resilience fix necessary for production scheduler. File location is framework convention.

## Issues Encountered

- Test initially asserted Telegram request via Http::fake; Telegram URL matching was brittle. Switched to mocking TelegramAlertService and asserting sendMessage() argument content.

## User Setup Required

Per plan user_setup: Telegram (same as Phase 1). Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env. For n8n API: N8N_BASE_URL and N8N_API_KEY. Cron must run `* * * * * php artisan schedule:run` for the 5-minute schedule to take effect.

## Next Phase Readiness

- DASH-03 satisfied. Ready for DASH-04 (weekly summary) or other Phase 5 plans.
- No blockers.

---
*Phase: 05-live-dashboards-monitoring*
*Completed: 2026-03-12*
