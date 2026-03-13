---
phase: 06-affiliate-seo-feedback
plan: "02"
subsystem: affiliate
tags: [n8n, google-sheets, rss, muncheye, config-loader]

# Dependency graph
requires:
  - phase: 06-affiliate-seo-feedback
    provides: 06-CONFIG-KEYS.md registry shape and config keys (Plan 01)
provides:
  - Scheduled Affiliate Link Manager workflow (Monday 7 AM) gated by AFFILIATE_MANAGER_ENABLED
  - Muncheye RSS fetch and parse → registry row shape; dedupe by URL; append to AFFILIATE_REGISTRY_TAB
affects: [core orchestrator, Agent 1/4 product pick]

# Tech tracking
tech-stack:
  added: []
  patterns: Config Loader first, IF enabled, HTTP RSS + Code parse, Sheets append from config

key-files:
  created: [affiliate/06_Affiliate_Link_Manager.json]
  modified: []

key-decisions:
  - "Single source Muncheye RSS sufficient for AFF-02; CBEngine optional (parameterized RSS/Pro documented in notes)"
  - "Dedupe by URL only; scoring skipped (score 0) per RESEARCH optional scoring"

patterns-established:
  - "Affiliate Manager: Schedule → Config Loader → IF AFFILIATE_MANAGER_ENABLED → HTTP RSS → Parse → Dedupe → Sheets append; no YOUR_*"

requirements-completed: [AFF-01, AFF-02]

# Metrics
duration: 5
completed: "2026-03-13"
---

# Phase 6 Plan 02: Affiliate Link Manager Summary

**Scheduled Affiliate Link Manager workflow that loads config, fetches Muncheye RSS, parses and dedupes products, and appends to the affiliate registry tab (AFF-02).**

## Performance

- **Duration:** ~5 min
- **Tasks:** 2
- **Files modified:** 1 (created)

## Accomplishments

- New n8n workflow `06 — Affiliate Link Manager`: Schedule Monday 7 AM, Execute Workflow (Config Loader), IF AFFILIATE_MANAGER_ENABLED, HTTP Muncheye RSS, Parse XML to registry row shape, Dedupe by URL, Append to AFFILIATE_REGISTRY_TAB.
- No YOUR_* or hardcoded sheet ID; documentId and sheetName from config (GOOGLE_SHEET_ID/SPREADSHEET_ID, AFFILIATE_REGISTRY_TAB).
- HTTP error path returns success: false, error: { code, message }; Sheets node has onError continue for traceability.

## Task Commits

1. **Task 1+2: Create workflow with Config Loader, RSS, dedupe, registry append** - `c23eb14` (feat)

**Plan metadata:** (final docs commit to follow)

## Files Created/Modified

- `affiliate/06_Affiliate_Link_Manager.json` - Full workflow: Schedule, Config Loader, IF enabled, HTTP Muncheye RSS, Parse, HTTP OK? branch, Dedupe, Google Sheets append to registry tab.

## Decisions Made

- Muncheye category feed only (CBEngine optional, documented in node notes).
- Dedupe by URL; no Ollama scoring in this workflow (score 0 per RESEARCH).

## Deviations from Plan

**Schedule node name:** Plan verification script expects a node name matching `/schedule|Schedule/i`. The trigger was named "🕔 Monday 7AM"; renamed to "🕔 Schedule: Monday 7AM" so the automated test passes.

**Total deviations:** 1 (naming for verification).  
**Impact on plan:** None; behavior unchanged.

## Issues Encountered

None.

## User Setup Required

None beyond Plan 01 (htg_config keys: AFFILIATE_REGISTRY_TAB, AFFILIATE_MANAGER_ENABLED, GOOGLE_SHEET_ID). User must create the Affiliate Registry tab with header row (product_name, platform, commission, url, niche, score, date_found, status) or ensure it exists before first run. Replace REPLACE_WITH_CONFIG_LOADER_ID with actual Config Loader workflow ID after import.

## Next Phase Readiness

- Registry can be populated by running the workflow (manual or schedule). Plan 03 (Refresh Candidates Writer) and Plan 04 (SEO Interlinking) are independent of this workflow.

## Self-Check: PASSED

- FOUND: .planning/phases/06-affiliate-seo-feedback/06-02-SUMMARY.md
- FOUND: commit c23eb14

---
*Phase: 06-affiliate-seo-feedback*
*Completed: 2026-03-13*
