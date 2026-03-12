---
phase: 04-content-satellites
plan: 01
subsystem: content-satellites
tags: [n8n, ollama, blotato, google-sheets, video-production]

# Dependency graph
requires:
  - phase: 01-pipeline-reliability
    provides: Content Log shape, status semantics, Config Loader
provides:
  - Video Production Engine (10:30 AM) with VIDEO_PRODUCTION_ENABLED gate and today's post filter
  - One post → multiple scripts (TikTok, YT Short, ig_reel) via LLM; each submitted to Blotato via 14_Video_Production
  - Video Log one row per video with date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at
affects: [04-content-satellites, dashboards]

# Tech tracking
tech-stack:
  added: []
  patterns: [Config Loader → normalize flag → IF enabled; Filter today's post (timezone, status); LLM JSON envelope + Parse & Validate; Execute Workflow per script; Build Video Log Row → Log with defineBelow columns]

key-files:
  created: [growth/HowTo-Genie v4.0 — Video Production Engine.json]
  modified: [social/14_Video_Production.json]

key-decisions:
  - "Video Production Engine uses Ollama /api/generate (qwen2.5:7b) for script generation; Blotato in sub-workflow only"
  - "Video Log written by 14_Video_Production from Build Video Log Row (not caller); one row per video with explicit columns"

patterns-established:
  - "Video satellite: Schedule → Config Loader → VIDEO_PRODUCTION_ENABLED gate → Read Content Log → Filter today → Fetch post → LLM scripts → Parse & Validate → Loop Execute 14_Video_Production"
  - "Sub-workflow log row: Code node builds canonical row shape; Google Sheets append with defineBelow from that node (not from notify payload)"

requirements-completed: [GROW-05]

# Metrics
duration: 15
completed: 2026-03-12
---

# Phase 4 Plan 1: Video Production Satellite Summary

**10:30 AM Video Production Engine with config gate, today's post filter, multi-script LLM generation, and Blotato via 14_Video_Production; Video Log one row per video with job_id_or_video_id.**

## Performance

- **Duration:** ~15 min
- **Started:** (execution start)
- **Completed:** 2026-03-12
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments

- Video Production Engine workflow: 10:30 AM cron, Execute Workflow Config Loader, normalize VIDEO_PRODUCTION_ENABLED; when disabled, workflow ends without reading Sheets or calling Blotato.
- Today's post filter (timezone, status ≠ publish_failed); no post → exit clean (no Video Log append). Fetch post via WordPress REST; Ollama generates TikTok/YT Short/ig_reel scripts; Parse & Validate; Execute 14_Video_Production per script.
- 14_Video_Production: Build Video Log Row node builds { date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at }; Log Video Production receives from Build Video Log Row with defineBelow columns (no longer from Notify).

## Task Commits

Each task was committed atomically:

1. **Task 1: Create Video Production Engine workflow** - `2452a82` (feat)
2. **Task 2: Fix 14_Video_Production so Video Log has one row per video with required columns** - `dbc89ed` (fix)

## Files Created/Modified

- `growth/HowTo-Genie v4.0 — Video Production Engine.json` - New workflow: schedule 10:30, Config Loader, enable gate, Content Log read, filter today, fetch post, LLM scripts, parse, split, Execute 14_Video_Production per script.
- `social/14_Video_Production.json` - Build Video Log Row node; Publish → Build Success Notify + Build Video Log Row; Log input from Build Video Log Row with explicit column mapping.

## Decisions Made

- None beyond plan: Blotato-only for video (no Pictory/InVideo); Video Log append-only in sub-workflow with required columns; workflowId for Config Loader and 14_Video_Production set after import (documented in notes).

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required. After import: set Execute Workflow workflowId for Config Loader and for 14_Video_Production to your instance IDs; ensure VIDEO_PRODUCTION_ENABLED and VIDEO_LOG_TAB (and GOOGLE_SHEET_ID, CONTENT_LOG_TAB, WORDPRESS_URL) are in config.

## Next Phase Readiness

- Video satellite (GROW-05) activated; one row per video in Video Log with confirmation ID.
- Plan 04-02 (email newsletter) can proceed independently.

## Self-Check: PASSED

- FOUND: .planning/phases/04-content-satellites/04-01-SUMMARY.md
- FOUND: growth/HowTo-Genie v4.0 — Video Production Engine.json
- FOUND: commits 2452a82, dbc89ed

---
*Phase: 04-content-satellites*
*Completed: 2026-03-12*
