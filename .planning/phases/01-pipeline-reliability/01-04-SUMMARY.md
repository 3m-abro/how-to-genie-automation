---
phase: 01-pipeline-reliability
plan: 04
subsystem: pipeline
tags: n8n, google-sheets, telegram, qc-rejection

# Dependency graph
requires:
  - phase: 01-pipeline-reliability
    provides: Parse & Validate QC, QC Approved? IF, Config Loader, Inject Approved Topic
provides:
  - QC rejection writes full row to Rejected Posts sheet (REJECTED_POSTS_TAB)
  - Blog Idea Backlog row updated to status=rejected when topic had _row
  - Telegram alert on every QC rejection with topic, score, reasons
affects: Phase 1 (01-05 publish_failed path), Phase 5 (alerts)

# Tech tracking
tech-stack:
  added: []
  patterns: Code → Sheets append/update, IF gating optional Backlog update, same-row fan-out to Sheets + Telegram

key-files:
  created: []
  modified: [core/08_Orchestrator_v3.json]

key-decisions:
  - "QC rejected path: Rejected Posts tab name and sheet ID from config (REJECTED_POSTS_TAB, GOOGLE_SHEET_ID); Alert Handler node removed from false branch"
  - "Backlog update only when use_approved_topic and topic._row present; IF skip_backlog_update === false → Update Backlog Row"

patterns-established:
  - "QC rejection chain: Build row → Write to Rejected Posts Sheet; parallel branches for Backlog update (conditional) and Telegram alert"

requirements-completed: [PIPE-04]

# Metrics
duration: 4
completed: "2026-03-12"
---

# Phase 01 Plan 04: QC Rejection Path Summary

**QC rejection path: Rejected Posts sheet append, conditional Blog Idea Backlog update, and Telegram QC Rejected alert (PIPE-04).**

## Performance

- **Duration:** 4 min
- **Started:** 2026-03-12T06:36:30Z
- **Completed:** 2026-03-12T06:40:30Z
- **Tasks:** 2
- **Files modified:** 1

## Accomplishments

- Replaced QC rejected dead-end (Alert Handler with empty inputs) with Build QC Rejection Row → Write to Rejected Posts Sheet
- Rejection row fields: date, topic, primary_keyword, qc_score, rejection_reasons, word_count, agent_fallbacks_used, run_timestamp; sheet/tab from config REJECTED_POSTS_TAB and GOOGLE_SHEET_ID
- Conditional backlog update: Build Backlog Rejection Update outputs skip_backlog_update or Row_Number + status; IF "Backlog Update Needed?" → Update Backlog Row (rejected) on BLOG_IDEA_TAB when topic had _row
- Telegram: QC Rejected node sends message with topic, qc_score, rejection_reasons on every QC rejection

## Task Commits

Each task was committed atomically:

1. **Task 1: Add Build QC Rejection Row and Write to Rejected Posts Sheet** - `7d74953` (feat)
2. **Task 2: Conditional Backlog update and Telegram QC rejection alert** - `a25d0c9` (feat)

## Files Created/Modified

- `core/08_Orchestrator_v3.json` - Added Build QC Rejection Row (Code), Write to Rejected Posts Sheet (Sheets append), Build Backlog Rejection Update (Code), Backlog Update Needed? (IF), Update Backlog Row (rejected) (Sheets update), Telegram: QC Rejected; removed Alert: QC Rejected from false branch; wired QC false → Build Row → [Write Sheet, Build Backlog Update, Telegram] and Backlog Update → IF → Update Backlog Row

## Decisions Made

- Rejected Posts tab and document ID read from config (REJECTED_POSTS_TAB, GOOGLE_SHEET_ID or SPREADSHEET_ID fallback) so user adds one "Rejected Posts" tab and one config row
- Backlog update uses matchingColumns Row_Number; only runs when Inject Approved Topic has use_approved_topic and Parse & Validate Topic has _row (topic from backlog)
- agent_fallbacks_used built from Parse Prompt Package (agent_0), Topic (agent_1 fallback_topic/error), Parse & Validate QC (agent_5); Assemble does not expose agent_4 parse_error in current output

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None

## User Setup Required

- Create "Rejected Posts" tab in Google Sheets with columns: date, topic, primary_keyword, qc_score, rejection_reasons, word_count, agent_fallbacks_used (and optionally run_timestamp)
- Add REJECTED_POSTS_TAB to n8n htg_config data table with value "Rejected Posts" (plan user_setup)

## Next Phase Readiness

- QC rejection path complete; 01-05 can proceed with Assemble Content Log Row and publish_failed path
- Rejected Posts tab and config key must be created before first QC rejection run

## Self-Check: PASSED

- core/08_Orchestrator_v3.json exists and contains Build QC Rejection Row, Write to Rejected Posts Sheet, Build Backlog Rejection Update, Telegram: QC Rejected
- Commits 7d74953 and a25d0c9 present in git log

---
*Phase: 01-pipeline-reliability*
*Completed: 2026-03-12*
