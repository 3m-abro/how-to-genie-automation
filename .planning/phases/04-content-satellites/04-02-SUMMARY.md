---
phase: 04-content-satellites
plan: 02
subsystem: email
tags: n8n, webhook, ConvertKit, MailerLite, config-loader, GROW-06

# Dependency graph
requires:
  - phase: 04-content-satellites
    provides: CONTEXT.md decisions (single provider, ESP sends first email)
provides:
  - Webhook-triggered email workflow with Config Loader and EMAIL_NEWSLETTER_ENABLED gate
  - Single-provider branch (ConvertKit or MailerLite from EMAIL_PROVIDER)
  - 200 OK when disabled without adding to ESP; ESP sends first welcome and sequence
affects: Phase 4 plan 01 (video); Phase 5 dashboards if email status is surfaced

# Tech tracking
tech-stack:
  added: []
  patterns: Execute Workflow (Config Loader) at webhook start; IF enable gate → respond 200 or continue; IF provider branch → one ESP node

key-files:
  created: []
  modified: email/v3.0 — Email Newsletter Automation.json

key-decisions:
  - "ConvertKit: form subscribe (CONVERTKIT_FORM_ID) so ESP sends first email; no workflow 'send now'"
  - "MailerLite: add to group (MAILERLITE_GROUP_ID from config); automation on subscriber_joins_group"
  - "Credentials via n8n (convertKitApi, mailerLiteApi); no YOUR_* in JSON"

patterns-established:
  - "Email webhook: Load Config → Normalize enable flag → IF enabled? false: Respond 200 and stop; true: Validate → Valid Email? → Which Provider? → Add to ESP → Log (optional) → Respond 200"

requirements-completed: [GROW-06]

# Metrics
duration: 5
completed: "2026-03-12"
---

# Phase 4 Plan 2: Email Newsletter Refactor Summary

**Config-gated email newsletter webhook with single-provider (ConvertKit or MailerLite), ESP-driven first welcome and sequence; no workflow-generated AI emails or YOUR_* placeholders.**

## Performance

- **Duration:** ~5 min
- **Tasks:** 2
- **Files modified:** 1

## Accomplishments

- Webhook flow starts with Execute Workflow (Config Loader); EMAIL_NEWSLETTER_ENABLED normalized; when false, Respond to Webhook 200 with `{ success, message }` and workflow stops (no ESP add).
- Single-provider branch from config.EMAIL_PROVIDER (convertkit | mailerlite); only Add to ConvertKit or Add to MailerLite runs per execution.
- ConvertKit: form subscribe (CONVERTKIT_FORM_ID); MailerLite: add to group (MAILERLITE_GROUP_ID); credentials via n8n; sheet/tab from config.
- Removed AI welcome email nodes and sequence builder from main flow; ESP sends first welcome and simplified sequence (e.g. 0, 2, 5 days) configured in ESP.
- No YOUR_* placeholders in JSON.

## Task Commits

1. **Task 1: Add Config Loader and EMAIL_NEWSLETTER_ENABLED gate; respond 200 when disabled** - `8c6a7a8` (feat)
2. **Task 2: Single-provider branch, Add to ESP only, remove AI emails; credentials via n8n** - `0fdf4c6` (feat)

## Files Created/Modified

- `email/v3.0 — Email Newsletter Automation.json` - Config Loader at start, enable gate, Respond (Newsletter Disabled), Which Provider? IF, single Add to ConvertKit / Add to MailerLite, config-based Log Subscriber and Respond 200; AI/sequence nodes removed from webhook path.

## Decisions Made

- Used ConvertKit v3 form subscribe endpoint (CONVERTKIT_FORM_ID) so form/sequence in ConvertKit sends first email.
- MailerLite: single "Add to MailerLite" node with group ID from config; automation on subscriber_joins_group sends first email.
- Kept optional "Log Subscriber to Sheets" with documentId/sheetName from config (GOOGLE_SHEET_ID, NEWSLETTER_SUBSCRIBERS_TAB).

## Deviations from Plan

None - plan executed as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required. User must set REPLACE_WITH_CONFIG_LOADER_ID (or workflow list reference) to their Config Loader workflow ID; create convertKitApi and mailerLiteApi credentials in n8n; add EMAIL_NEWSLETTER_ENABLED, EMAIL_PROVIDER, CONVERTKIT_FORM_ID, MAILERLITE_GROUP_ID, GOOGLE_SHEET_ID (and optionally NEWSLETTER_SUBSCRIBERS_TAB) to config.

## Next Phase Readiness

- Email newsletter webhook is config-gated and single-provider; ready for activation once credentials and config are set.
- Phase 4 Plan 01 (video production) is independent; can proceed in parallel.

## Self-Check: PASSED

- `email/v3.0 — Email Newsletter Automation.json` exists and passes plan verification (Task 1 and Task 2 automated checks).
- Commits 8c6a7a8 and 0fdf4c6 present in repo.

---
*Phase: 04-content-satellites*
*Plan: 02*
*Completed: 2026-03-12*
