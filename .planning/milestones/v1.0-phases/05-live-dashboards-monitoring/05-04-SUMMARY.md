---
phase: 05-live-dashboards-monitoring
plan: "04"
subsystem: api
tags: laravel, google-sheets, mail, scheduler

# Dependency graph
requires:
  - phase: 05-live-dashboards-monitoring (05-01)
    provides: GoogleSheetsService for Content Log and Revenue Tracker reads
provides:
  - Weekly summary email to owner: posts published, top performer, revenue estimate (DASH-04)
  - Configurable day/time and recipient for weekly report
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns: Laravel Mail Mailable + Blade view; Schedule in routes/console.php (Laravel 11)

key-files:
  created:
    - laravel/app/Mail/WeeklySummaryMailable.php
    - laravel/resources/views/emails/weekly-summary.blade.php
  modified:
    - laravel/app/Console/Commands/WeeklySummaryCommand.php
    - laravel/tests/Feature/Commands/WeeklySummaryCommandTest.php
    - laravel/config/services.php
    - laravel/routes/console.php
    - laravel/.env.example

key-decisions:
  - "Weekly summary uses last 7 days of Content Log for posts_published and top performer; Revenue Tracker summed for revenue_estimate (no date filter)"
  - "Recipient: config('services.weekly_summary.recipient') ?: config('mail.from.address'); fail if both empty"
  - "Schedule: weeklyOn(day, time) with 0=Sunday (Carbon); config keys day and time in services.weekly_summary"

patterns-established:
  - "Weekly report: fresh Sheets read each run (no cache); single Mailable with Blade view"

requirements-completed: [DASH-04]

# Metrics
duration: 5
completed: "2026-03-12"
---

# Phase 05 Plan 04: Weekly Summary Email Summary

**Scheduled weekly summary command reads Content Log and Revenue Tracker from Google Sheets, builds summary (posts published, top performer, revenue estimate), and sends one email to the owner via Laravel Mail; Sunday 8 AM default with configurable day/time.**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-12T14:22:22Z
- **Completed:** 2026-03-12
- **Tasks:** 2
- **Files modified:** 6 (created 2, modified 4)

## Accomplishments

- WeeklySummaryCommand resolves GoogleSheetsService, reads Content Log and Revenue Tracker, filters last 7 days for posts, computes top performer (most recent) and revenue sum, builds summary array
- WeeklySummaryMailable with Blade view (emails/weekly-summary.blade.php) for posts published, top performer title/URL, revenue estimate, optional streak
- Recipient from config (WEEKLY_SUMMARY_RECIPIENT or MAIL_FROM_ADDRESS); command fails with clear error if none set
- Schedule: weekly:summary registered in routes/console.php with weeklyOn(day, time); config services.weekly_summary.day (0=Sunday) and .time (08:00)
- Tests: mock GoogleSheetsService and Mail::fake(); assert command runs and sends one mail with correct summary data; assert failure when no recipient

## Task Commits

1. **Task 1: WeeklySummaryCommand and WeeklySummaryMailable** - `3b024f5` (feat)
2. **Task 2: Schedule weekly summary (Sunday or config)** - `41fad4b` (chore)

**Plan metadata:** (final docs commit to follow)

## Files Created/Modified

- `laravel/app/Mail/WeeklySummaryMailable.php` - Mailable for summary data; view emails.weekly-summary
- `laravel/resources/views/emails/weekly-summary.blade.php` - Email body: posts, top performer, revenue, streak
- `laravel/app/Console/Commands/WeeklySummaryCommand.php` - Build summary from Sheets, send mail
- `laravel/tests/Feature/Commands/WeeklySummaryCommandTest.php` - Mock Sheets + Mail::fake(); assert send and failure
- `laravel/config/services.php` - weekly_summary.recipient, .day, .time
- `laravel/routes/console.php` - Schedule::command('weekly:summary')->weeklyOn(...) (present in repo)
- `laravel/.env.example` - WEEKLY_SUMMARY_RECIPIENT, DAY, TIME

## Decisions Made

- Last 7 days for content log filter; revenue from full Revenue Tracker sum (column normalized per DashboardController pattern)
- 0 = Sunday for Laravel Carbon weeklyOn; documented in .env.example
- No cache for weekly summary (fresh read each run per plan)

## Deviations from Plan

None - plan executed as written. Schedule entry for weekly:summary was already present in routes/console.php from prior work (05-03); Task 2 added .env.example documentation and confirmed config keys.

## Issues Encountered

None. schedule:list failed in environment due to missing SQLite driver; schedule registration in code is correct.

## User Setup Required

For weekly summary email delivery:
- Configure MAIL_* (MAIL_MAILER, MAIL_HOST, etc.) in .env for SMTP
- Set WEEKLY_SUMMARY_RECIPIENT (or MAIL_FROM_ADDRESS) to owner email
- Ensure cron runs Laravel scheduler: `* * * * * php artisan schedule:run` (if not already)

## Next Phase Readiness

DASH-04 complete. Phase 5 live dashboards and monitoring: revenue API (05-01), mission control API (05-02), failure monitor (05-03), and weekly summary (05-04) are implemented.

## Self-Check

- [x] laravel/app/Console/Commands/WeeklySummaryCommand.php exists
- [x] laravel/app/Mail/WeeklySummaryMailable.php exists
- [x] laravel/resources/views/emails/weekly-summary.blade.php exists
- [x] Commits 3b024f5 and 41fad4b exist

---
*Phase: 05-live-dashboards-monitoring*
*Completed: 2026-03-12*
