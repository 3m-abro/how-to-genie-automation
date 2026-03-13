---
phase: 06-affiliate-seo-feedback
plan: "03"
subsystem: seo
tags: [n8n, ga4, google-sheets, orchestrator, topic-selection]

# Dependency graph
requires:
  - phase: 06-affiliate-seo-feedback
    provides: Config Loader, REFRESH_CANDIDATES_TAB key
provides:
  - GA4 → Refresh Candidates tab writer (daily 5 AM)
  - Orchestrator reads Refresh Candidates and injects refresh_candidates into Agent 1 (Topic Research) context
affects: Phase 6 (SEO-01 satisfied)

# Tech tracking
tech-stack:
  added: []
  patterns: GA4 runReport → threshold filter → Sheets append; orchestrator multi-source research context (Reddit + Existing Topics + Refresh Candidates)

key-files:
  created: growth/06_Refresh_Candidates_Writer.json
  modified: core/08_Orchestrator_v3.json

key-decisions:
  - "Refresh Candidates Writer runs at 5 AM so candidates are ready before 8 AM orchestrator"
  - "Threshold: REFRESH_VIEWS_MIN or VIRAL_VIEWS_7D_MIN from config; default 100"
  - "Append-only to REFRESH_CANDIDATES_TAB; orchestrator reads all rows and builds refresh_candidates string"

patterns-established:
  - "Refresh candidates: dedicated writer workflow (GA4 → Sheets) + orchestrator read and inject into Agent 1 user_message"

requirements-completed: [SEO-01]

# Metrics
duration: ~8min
completed: "2026-03-13"
---

# Phase 06 Plan 03: GA4 → Topic Selection Summary

**GA4 performance data above a configurable threshold is written to the Refresh Candidates tab; the orchestrator reads that tab and injects refresh_candidates into Agent 1 (Topic Research) context so high-traffic topics can be prioritized or refreshed.**

## Performance

- **Duration:** ~8 min
- **Tasks:** 2
- **Files created:** 1 (growth/06_Refresh_Candidates_Writer.json)
- **Files modified:** 1 (core/08_Orchestrator_v3.json)

## Accomplishments

- Refresh Candidates Writer workflow: schedule 5 AM, Config Loader, GA4 runReport (7d dimensions/metrics), filter by REFRESH_VIEWS_MIN (or VIRAL_VIEWS_7D_MIN), append date/post_url/post_title/views_7d/keyword to REFRESH_CANDIDATES_TAB
- Orchestrator: new node 📡 Load Refresh Candidates (Sheets read from REFRESH_CANDIDATES_TAB); Build Research Context adds refresh_candidates from that node; Agent 1 user_message includes "TOPICS TO PRIORITIZE (refresh/amplify): {{ refresh_candidates }}"
- SEO-01 satisfied: GA4 integration feeds performance data back into topic selection

## Task Commits

1. **Task 1: Create Refresh Candidates Writer workflow** — `9b4862b` (feat)
2. **Task 2: Orchestrator — read Refresh Candidates and inject into Agent 1** — `75bf7c6` (feat)

## Files Created/Modified

- `growth/06_Refresh_Candidates_Writer.json` — New workflow: trigger 5 AM, Config Loader, GA4 runReport, filter by threshold, append to Refresh Candidates tab
- `core/08_Orchestrator_v3.json` — Added 📡 Load Refresh Candidates node; Build Research Context includes refresh_candidates; Agent 1 prompt references it

## Decisions Made

- Append-only to Refresh Candidates tab (no clear-then-append); orchestrator reads all rows and builds a comma-separated list; optional dedupe by post_url can be done when reading
- REFRESH_VIEWS_MIN or fallback VIRAL_VIEWS_7D_MIN, default 100, so more posts qualify as candidates than viral amplifier
- Load Refresh Candidates placed after Load Config and feeding Build Research Context (same pattern as Load Existing Topics and Fetch Reddit)

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None

## User Setup Required

- Ensure htg_config (or Config Loader source) includes: GOOGLE_SHEET_ID, REFRESH_CANDIDATES_TAB (tab name), GA4_PROPERTY_ID, GOOGLE_ANALYTICS_TOKEN, REFRESH_VIEWS_MIN (optional; default 100 or reuse VIRAL_VIEWS_7D_MIN), WORDPRESS_URL
- Create a "Refresh Candidates" sheet tab if not present; columns: date, post_url, post_title, views_7d, keyword
- Replace REPLACE_WITH_CONFIG_LOADER_ID in 06_Refresh_Candidates_Writer.json with actual Config Loader workflow ID when importing to n8n

## Next Phase Readiness

- SEO-01 complete; 06-04 (SEO Interlinking) can proceed independently
- Manual verification: run Refresh Candidates Writer then orchestrator; inspect Agent 1 input for refresh_candidates content

## Self-Check: PASSED

- SUMMARY.md present; commits 9b4862b and 75bf7c6 verified.

---
*Phase: 06-affiliate-seo-feedback*
*Completed: 2026-03-13*
