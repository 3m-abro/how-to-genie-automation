---
phase: 05-live-dashboards-monitoring
plan: "01"
subsystem: ui
tags: laravel, google-sheets, react, recharts, dashboard

# Dependency graph
requires: []
provides:
  - Laravel app with config and Wave 0 test scaffold
  - GoogleSheetsService reading Content Log and Revenue Tracker (normalized headers)
  - GET /api/dashboard/revenue with 5-min cache returning content_stats, revenue_data, traffic_data, top_posts, agent_activity
  - Revenue dashboard UI fetching from API (useEffect + fetch), loading/error state, configurable API base
affects: 05-live-dashboards-monitoring (Mission Control, alerts, weekly summary)

# Tech tracking
tech-stack:
  added: google/apiclient (Sheets API)
  patterns: Laravel BFF for dashboards; Cache::remember(300) for dashboard APIs; header normalization (strtolower) for Sheets columns

key-files:
  created: laravel/app/Services/GoogleSheetsService.php
  modified: laravel/app/Http/Controllers/DashboardController.php, laravel/config/services.php, laravel/.env.example, laravel/routes/api.php, laravel/tests/Feature/Dashboard/RevenueDashboardApiTest.php, ui/revenue-dashboard.tsx

key-decisions:
  - "Revenue API returns snake_case (content_stats, revenue_data) for Laravel convention; frontend maps to camelCase state"
  - "GoogleSheetsService returns [] when credentials/sheet not configured so API and tests work without Sheets"
  - "Traffic and affiliate/social data derived from Content Log or left empty until future sheets/tabs"

patterns-established:
  - "Dashboard data from Sheets via dedicated service; controller aggregates and caches"
  - "Revenue dashboard: configurable API base (window.API_BASE or REACT_APP_API_URL) for embedding in Laravel or SPA"

requirements-completed: [DASH-01]

# Metrics
duration: ~15min
completed: "2026-03-12"
---

# Phase 05 Plan 01: Live Dashboards & Monitoring Summary

**Google Sheets–backed revenue API and Revenue dashboard UI wired to live data (post counts, traffic estimates, affiliate/revenue from Content Log and Revenue Tracker).**

## Performance

- **Duration:** ~15 min
- **Tasks:** 3
- **Files modified:** 6+ (Laravel service, controller, routes, tests; UI component)

## Accomplishments

- GoogleSheetsService reads Content Log and Revenue Tracker via google/apiclient; normalizes header keys to avoid Date/date breakage.
- GET /api/dashboard/revenue returns aggregates (content_stats, revenue_data, traffic_data, top_posts, agent_activity) with 5-min cache.
- Revenue dashboard React component fetches from API with loading/error state; no hardcoded demo arrays.

## Task Commits

1. **Task 1: Bootstrap Laravel app, config, Wave 0 tests** - `2cf290c` (feat) — pre-existing in repo
2. **Task 2: GoogleSheetsService and GET /api/dashboard/revenue** - `64fd212` (feat)
3. **Task 3: Wire Revenue dashboard UI to API** - `6bac5d2` (feat)

## Files Created/Modified

- `laravel/app/Services/GoogleSheetsService.php` - Read Sheets ranges; Content Log / Revenue Tracker helpers; normalized row arrays
- `laravel/app/Http/Controllers/DashboardController.php` - buildRevenuePayload from Sheets; Cache::remember(300)
- `ui/revenue-dashboard.tsx` - useEffect + fetch /api/dashboard/revenue; loading/error; state from API

## Decisions Made

- Use google/apiclient (already in composer) for Sheets; support credentials as file path or JSON string.
- Revenue Tracker column detection via findColumnKey with candidates (month, total, adsense, adsterra, affiliates, posts).
- Frontend accepts empty arrays when API returns no data; affiliateData/socialData left as placeholders (not from current API).

## Deviations from Plan

None - plan executed as written.

## Issues Encountered

None.

## User Setup Required

Owner must set in Laravel `.env`: `GOOGLE_SHEET_ID`, `GOOGLE_APPLICATION_CREDENTIALS` (path to service account JSON or inline JSON). Enable Google Sheets API and share the sheet with the service account email. For embedded dashboard, set `window.API_BASE` or `REACT_APP_API_URL` to Laravel base URL if not same origin.

## Next Phase Readiness

- Revenue dashboard (DASH-01) satisfied; ready for Mission Control live data (05-02), failure monitor (05-03), weekly summary (05-04).
- Laravel test scaffold (MissionControlApiTest, N8nFailureMonitorCommandTest, WeeklySummaryCommandTest) present; implementations in later plans.

## Self-Check: PASSED

- 05-01-SUMMARY.md present
- Commits 2cf290c, 64fd212, 6bac5d2 verified

---
*Phase: 05-live-dashboards-monitoring*
*Completed: 2026-03-12*
