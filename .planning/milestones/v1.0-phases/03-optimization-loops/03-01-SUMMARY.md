---
phase: 03-optimization-loops
plan: 01
subsystem: growth
tags: n8n, ollama, google-sheets, config-loader, ab-testing

# Dependency graph
requires:
  - phase: 02-distribution-growth
    provides: Config Loader pattern, config-driven sheet/tab, IF enable gate
provides:
  - A/B workflow runs at 6 AM when A_B_TESTING_ENABLED is true; exits without write when disabled or no yesterday post
  - Variant generation (headline + intro + CTA) via LLM with success/data/error envelope; one row appended to A/B tab
  - Phase 3 config keys doc (A/B and Viral) for htg_config and Sheets tabs
affects: 03-02 (Viral Amplifier uses same config pattern)

# Tech tracking
tech-stack:
  added: []
  patterns: Execute Workflow Config Loader → normalize flag → IF enabled → Sheets read → Code filter yesterday → IF has post → HTTP WP → prompt build → Ollama HTTP → Parse & Validate → Sheets append

key-files:
  created: .planning/phases/03-optimization-loops/03-CONFIG-KEYS.md
  modified: growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json

key-decisions:
  - "A/B variants logged to Sheets only; no WP injection or 6 PM results flow (per CONTEXT)"
  - "LLM invoked via HTTP Request to Ollama /api/generate for full prompt control and JSON-only schema"

patterns-established:
  - "Yesterday filter: CONTENT_DAY_TIMEZONE, date === yesterday, status !== publish_failed; noPostYesterday exit"
  - "Parse & Validate after LLM: strip ```json, parse, success/data/error envelope, fallback with error code/message"

requirements-completed: [GROW-03]

# Metrics
duration: 12
completed: "2026-03-12"
---

# Phase 03 Plan 01: A/B Testing Activation Summary

**Config-gated A/B workflow with Load Config, yesterday filter, headline/intro/CTA variant generation via Ollama, and Sheets-only log to A/B tab (no WP injection).**

## Performance

- **Duration:** ~12 min
- **Tasks:** 2
- **Files modified:** 2 (workflow JSON, 03-CONFIG-KEYS.md)

## Accomplishments

- Execute Workflow (Config Loader) as first node after 6 AM trigger; normalize A_B_TESTING_ENABLED; IF enabled → Read Content Log (config doc/tab) → Filter yesterday's post (timezone, status ≠ publish_failed); IF has post → Fetch WP post by slug → generate variants → append to A/B tab.
- When disabled or no valid yesterday row (or publish_failed), workflow exits without writing to A/B tab.
- First post from WP response (array); Build variant prompt with JSON-only schema (success/data/error, original_*, variant_*); Ollama HTTP /api/generate; Parse & Validate with fallback; Inject A/B node removed; Log Active Test uses config GOOGLE_SHEET_ID and AB_TESTS_TAB; columns: test_id, post_url, original_title, original_intro, original_cta, variant_title, variant_intro, variant_cta, created_at, status, winner.
- 6 PM branch removed (Test Results Check, Load Active Tests, Fetch Test Results, AI Analyze, Parse Analysis, Winner/Update/Log, Optimization Playbook).
- 03-CONFIG-KEYS.md lists Phase 3 keys: A_B_TESTING_ENABLED, AB_TESTS_TAB, CONTENT_LOG_TAB, GOOGLE_SHEET_ID, WORDPRESS_URL, CONTENT_DAY_TIMEZONE; and for Viral (Plan 02): VIRAL_AMPLIFIER_ENABLED, VIRAL_AMPLIFIER_TAB, VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN, GA4_PROPERTY_ID, GOOGLE_ANALYTICS_TOKEN; required tabs AB Tests / AB Tests Active, Viral Amplifier.

## Task Commits

1. **Task 1: Config Loader, enable gate, Read Content Log, yesterday filter** - `ef49cce` (feat)
2. **Task 2: Fetch WP, LLM variants, Parse & Validate, Append A/B; 03-CONFIG-KEYS** - `09ad5e9` (feat)

## Files Created/Modified

- `growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json` - Config Loader, enable gate, yesterday filter, First post, Build prompt, Ollama HTTP, Parse & Validate, config-driven Log; 6 PM branch and Inject A/B removed
- `.planning/phases/03-optimization-loops/03-CONFIG-KEYS.md` - Phase 3 config keys and required Sheets tabs (A/B and Viral)

## Decisions Made

- Used HTTP Request to Ollama /api/generate instead of keeping lmChatOllama so the prompt could start with "Return only valid JSON" and include the exact schema (success/data/error, original_*, variant_*) per project rules.
- Winner column present but empty/TBD for manual or future-auto use (no WP tracking in Phase 3).

## Deviations from Plan

None - plan executed as written.

## Issues Encountered

None.

## User Setup Required

None beyond existing Config Loader and htg_config. Ensure A_B_TESTING_ENABLED, GOOGLE_SHEET_ID, CONTENT_LOG_TAB, WORDPRESS_URL, CONTENT_DAY_TIMEZONE (or TIMEZONE), and AB_TESTS_TAB (or default "AB Tests") are set; create "AB Tests" or "AB Tests Active" tab in the same spreadsheet if missing.

## Next Phase Readiness

- Plan 03-02 (Viral Amplifier) can use same Config Loader pattern and 03-CONFIG-KEYS.md for Viral keys and Viral Amplifier tab.
- A/B workflow is ready for manual run in n8n (enable flag, Content Log with yesterday row, WP and Ollama available).

## Self-Check: PASSED

- 03-01-SUMMARY.md and 03-CONFIG-KEYS.md present; commits ef49cce, 09ad5e9 verified.

---
*Phase: 03-optimization-loops*
*Completed: 2026-03-12*
