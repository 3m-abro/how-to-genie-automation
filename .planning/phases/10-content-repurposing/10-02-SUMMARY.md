---
phase: 10-content-repurposing
plan: 02
subsystem: workflows
tags: [n8n, repurposing, REPURPOSE_FORMATS, parse-validate, config-tabs]

# Dependency graph
requires:
  - phase: 10-content-repurposing
    provides: Config Loader, timezone filter, idempotency (10-01)
provides:
  - Config-driven format branches (REPURPOSE_FORMATS); Parse & Validate per LLM; Assemble one row per post; config-driven Repurposed Content and optional queue tabs
affects: [10-content-repurposing]

# Tech tracking
tech-stack:
  added: []
  patterns: [Build format list from config, Switch by format, success/data/error envelope per LLM, branch merge then main Merge]

key-files:
  created: []
  modified: [content/v3.0 — Content Repurposing Engine.json, htg_config.csv, docs/HOWTOGENIE.md]

key-decisions:
  - "Only formats in REPURPOSE_FORMATS run; disabled formats output placeholder to Merge so main Merge always receives 5 inputs"
  - "Optional queue tabs: IF nodes (Has Twitter Queue Tab? / Has Podcast Queue Tab?) skip append when config key missing"

patterns-established:
  - "Config-driven format list: Code node reads REPURPOSE_FORMATS, outputs 5 items (skip flag per format); Switch routes to 5 branches"
  - "Parse & Validate after every LLM: envelope success/data/error; parse_error fallback"

requirements-completed: [REP-01, REP-02]

# Metrics
duration: 12min
completed: "2026-03-13"
---

# Phase 10 Plan 02: Config-Driven Formats, Parse & Validate, Assemble and Log Summary

**REPURPOSE_FORMATS gates which of 3–5 formats run; each LLM has Parse & Validate (success/data/error); Assemble produces one row per post; Repurposed Content and optional queue tabs use config; config keys and column contract documented.**

## Performance

- **Duration:** ~12 min
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- Build format list node after Clean & Extract: reads REPURPOSE_FORMATS from config, outputs 5 items (skip: true when format not in list)
- Switch format routes to 5 branches; each branch: Skip this format? → Placeholder or LLM → Parse & Validate → branch Merge → main Merge format outputs
- Parse & Validate (Twitter, LinkedIn, IG Carousel, Podcast, Community): strip markdown, enforce envelope; parse_error fallback
- Assemble repurposed payload: one row per post (Source URL, Date, Timestamp, twitter_text, linkedin_text, ig_carousel_json, podcast_script, community_text)
- Log to Repurposed Content Sheet: sheetName = REPURPOSED_CONTENT_TAB from config; optional Twitter/Podcast queue appends gated by IF (tab name non-empty)
- htg_config.csv: REPURPOSE_FORMATS, REPURPOSED_CONTENT_TAB, TWITTER_QUEUE_TAB, PODCAST_QUEUE_TAB
- docs/HOWTOGENIE.md: Repurposing config keys row; Repurposed Content column contract

## Task Commits

1. **Task 1: Config-driven formats, Parse & Validate, Assemble and log** - `9f7a5aa` (feat)
2. **Task 2: Config keys and docs** - `abf5802` (chore)

## Files Created/Modified

- `content/v3.0 — Content Repurposing Engine.json` — Build format list, Switch format, 5× (Skip? → Placeholder | LLM → Parse & Validate), branch merges, Merge format outputs, Assemble repurposed payload, Log + optional queue IFs
- `htg_config.csv` — REPURPOSE_FORMATS, REPURPOSED_CONTENT_TAB, TWITTER_QUEUE_TAB, PODCAST_QUEUE_TAB
- `docs/HOWTOGENIE.md` — Repurposing keys and Repurposed Content column contract

## Decisions Made

- Five items always emitted from Build format list (skip flag for disabled formats) so Merge always gets 5 inputs; placeholder carries format + success: false, error code 'skipped'
- Optional queue tabs: IF nodes check config key trim non-empty before connecting to Google Sheets append

## Deviations from Plan

None - plan executed as written.

## Issues Encountered

None.

## Self-Check: PASSED

- Workflow JSON valid; grep YOUR_|your-blog = 0
- htg_config.csv and HOWTOGENIE.md contain REPURPOSE_FORMATS, REPURPOSED_CONTENT_TAB
- Commits 9f7a5aa and abf5802 present

---
*Phase: 10-content-repurposing*
*Completed: 2026-03-13*
