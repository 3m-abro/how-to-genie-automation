---
phase: 03-optimization-loops
plan: 02
subsystem: growth
tags: n8n, ga4, google-sheets, viral-amplifier, config-loader

# Dependency graph
requires:
  - phase: 02-distribution-growth
    provides: Config Loader pattern, GOOGLE_SHEET_ID and tab config
provides:
  - Viral Content Amplifier Engine: config-gated, GA4-only, append to Viral Amplifier tab with promotion_status=pending
  - Viral Amplifier Queue: read pending, append to Social Queue, update row to promotion_status=sent
affects: Phase 3 validation, Social Queue Processor

# Tech tracking
tech-stack:
  added: []
  patterns: Execute Workflow (Config Loader) → normalize flag → IF enabled → GA4 runReport; viral threshold Code; append by config sheet/tab; separate Queue workflow for pending→Social Queue→sent

key-files:
  created: growth/HowTo-Genie v4.0 — Viral Amplifier Queue.json, .planning/phases/03-optimization-loops/03-CONFIG-KEYS.md
  modified: growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json

key-decisions:
  - "Viral Amplifier tab columns include row_id for stable update by matchingColumns in Queue workflow"
  - "Social Queue append uses WP_Post_URL, Caption, Platform, Status=Queued, Scheduled_Time, Retry_Count to match Queue Processor v2 expectations"
  - "Queue schedule 0 */6 * * * to align with Viral Engine; no Blotato sub-workflow in Phase 3 — direct append to Social Queue"

patterns-established:
  - "Viral detection: config VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN; GA4 metricValues[0..3] = views, avg_session_duration, engagement_rate, bounce_rate"
  - "Update Viral row by row_id (matchingColumns) instead of row number for robustness"

requirements-completed: [GROW-04]

# Metrics
duration: 0
completed: "2026-03-12"
---

# Phase 03 Plan 02: Viral Amplifier Activation Summary

**Viral Content Amplifier runs every 6 hours when enabled, uses GA4-only config-driven thresholds, appends qualifying rows to a Viral Amplifier tab with promotion_status=pending; Viral Amplifier Queue reads pending rows, appends to Social Queue for organic re-promotion, and marks rows promotion_status=sent.**

## Performance

- **Duration:** (recorded via state tools)
- **Tasks:** 2
- **Files created:** 2 (Viral Amplifier Queue JSON, 03-CONFIG-KEYS.md)
- **Files modified:** 1 (Viral Content Amplifier Engine JSON)

## Accomplishments

- Viral Content Amplifier Engine refactored: first node after trigger is Execute Workflow (Load Config); VIRAL_AMPLIFIER_ENABLED normalized and gated; GA4 URL and Bearer token from config; Detect Viral Content uses VIRAL_VIEWS_7D_MIN and VIRAL_ENGAGEMENT_MIN; append to VIRAL_AMPLIFIER_TAB with date, post_url, post_title, viral_score, views_7d, engagement_rate, bounce_rate, avg_session_duration, amplify, promotion_status, detected_at, row_id.
- GSC and all paid campaign nodes (Facebook Ads, Google Ads, Reddit Queue, Log Amplification, Email) removed; single append to Viral Amplifier tab replaces Viral Score IF and downstream.
- New Viral Amplifier Queue workflow: schedule 0 */6 * * *, Load Config, Read Viral Amplifier tab, Filter pending (promotion_status=pending), append to Social Queue (config SOCIAL_QUEUE_TAB) with Status=Queued and Scheduled_Time, update Viral Amplifier row to promotion_status=sent via row_id.
- 03-CONFIG-KEYS.md added with Viral and A/B config keys for Phase 3.

## Task Commits

1. **Task 1: Config Loader, enable gate, GA4-only, config thresholds, append to Viral Amplifier tab** — `3f26fdb` (feat)
2. **Task 2: Viral Amplifier Queue workflow — read pending, append to Social Queue, update to sent** — `a986703` (feat)

## Files Created/Modified

- `growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json` — Refactored to Config Loader, IF gate, GA4-only, config thresholds, append to Viral tab; GSC and paid nodes removed.
- `growth/HowTo-Genie v4.0 — Viral Amplifier Queue.json` — New workflow: read Viral tab, filter pending, append to Social Queue, update row to sent.
- `.planning/phases/03-optimization-loops/03-CONFIG-KEYS.md` — Viral and A/B config key reference.

## Decisions Made

- Use row_id (detected_at + post_url) for updating Viral Amplifier row to sent so Queue workflow can match row without relying on sheet row index.
- Direct append to Social Queue with columns compatible with Queue Processor v2 (WP_Post_URL, Caption, Platform, Status, Scheduled_Time, Retry_Count); no Blotato Execute Workflow in Phase 3 per plan.

## Deviations from Plan

None — plan executed as written. IF branch order for "Any pending?" was set so false branch (has pending) continues to Prepare Social Queue rows.

## Issues Encountered

None.

## User Setup Required

None beyond existing config: ensure GOOGLE_SHEET_ID, GA4_PROPERTY_ID, GOOGLE_ANALYTICS_TOKEN, VIRAL_AMPLIFIER_TAB, SOCIAL_QUEUE_TAB, VIRAL_AMPLIFIER_ENABLED, VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN, WORDPRESS_URL are set in Config Loader source. Replace REPLACE_WITH_CONFIG_LOADER_ID in both workflow JSONs with the actual Config Loader workflow ID in n8n.

## Next Phase Readiness

- Viral path is ready for manual verification (run Viral with enabled + GA4 data; run Queue with pending rows; confirm Social Queue and Viral tab update).
- Phase 3 Plan 01 (A/B Testing) remains to be executed for full Optimization Loops.

## Self-Check: PASSED

- growth/HowTo-Genie v4.0 — Viral Amplifier Queue.json: FOUND
- .planning/phases/03-optimization-loops/03-02-SUMMARY.md: FOUND
- .planning/phases/03-optimization-loops/03-CONFIG-KEYS.md: FOUND
- Commits 3f26fdb, a986703: FOUND

---
*Phase: 03-optimization-loops*
*Completed: 2026-03-12*
