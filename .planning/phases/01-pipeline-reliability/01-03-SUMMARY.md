---
phase: 01-pipeline-reliability
plan: 03
subsystem: pipeline
tags: n8n, wordpress, retry, orchestrator

# Dependency graph
requires: []
provides:
  - Native WordPress publish node with retry (3 tries, 5s wait)
  - Capture WP Post Data Code node (id, link, status)
  - Publish Succeeded? IF gate so satellites run only when status !== publish_failed
affects: 01-05 (will wire false branch to Content Log + Telegram failure)

# Tech tracking
tech-stack:
  added: []
  patterns: native n8n WordPress node; retryOnFail/maxTries/waitBetweenTries; status gate before satellites

key-files:
  created: []
  modified: core/08_Orchestrator_v3.json

key-decisions:
  - "Use n8n native WordPress node (n8n-nodes-base.wordpress) with featuredMediaId in additionalFields (camelCase)"
  - "Retry: maxTries 3, waitBetweenTries 5000 ms (plan cap); 30s delay is user preference, document for future Wait+Loop if needed)"
  - "False branch of Publish Succeeded? left unconnected for 01-05 to add Assemble Content Log Row + Telegram failure"

patterns-established:
  - "WordPress publish: native node → Capture WP Post Data (try/catch) → IF status !== publish_failed → satellites on true only"

requirements-completed: [PIPE-03]

# Metrics
duration: 8
completed: "2026-03-12"
---

# Phase 01 Plan 03: WordPress Native Node and Publish Gate Summary

**Native WordPress publish node with 3 retries and 5s wait; Capture WP Post Data normalizes success/failure; Publish Succeeded? IF gates satellites so Blotato/Calendar/Telegram run only when status !== publish_failed.**

## Performance

- **Duration:** ~8 min
- **Tasks:** 2 completed
- **Files modified:** 1 (core/08_Orchestrator_v3.json)

## Accomplishments

- Replaced "📝 Publish to WordPress" httpRequest with n8n-nodes-base.wordpress (resource post, operation create); title, content, slug, status publish, excerpt and additionalFields.featuredMediaId from Assemble Final Article and Capture Media ID.
- Added retryOnFail: true, maxTries: 3, waitBetweenTries: 5000 on the WordPress node.
- Inserted "🔗 Capture WP Post Data" Code node after WordPress: try returns { id, link, status: 'published' }; catch returns { id: '', link: '', status: 'publish_failed' }.
- Inserted "🔀 Publish Succeeded?" IF node (condition: $json.status !== 'publish_failed'). True branch only connects to Request Google Indexing, Request Bing Indexing, Ping Sitemap, Queue via Blotato, Write to Blog Calendar (and downstream Update Calendar Status, Trigger Content Calendar Manager, Telegram Article Published). False branch has no connections (for 01-05).
- Updated Request Bing Indexing to use $json.link (input from IF true branch) instead of $("📝 Publish to WordPress").item.json.link.

## Task Commits

1. **Task 1 & 2 (combined in one commit):** `3ba9252` (feat) — native WordPress node, retry, Capture WP Post Data, Publish Succeeded? IF and satellite gate.

**Plan metadata:** (final docs commit to follow)

## Files Created/Modified

- `core/08_Orchestrator_v3.json` — WordPress node replaced; Capture WP Post Data and Publish Succeeded? added; connections rerouted through IF true.

## Decisions Made

- Retry interval 5s (plan/interfaces cap); CONTEXT.md mentioned 30s — documented in summary for optional Wait+Loop in future.
- WordPress credential reference: credentials.wordpressApi with name "wordpressApi" (id placeholder for import; user re-attaches in n8n if needed).

## Deviations from Plan

None - plan executed as written. Bing node expression updated to $json.link so it works when receiving input from the IF true branch (same data shape from Capture WP Post Data).

## Issues Encountered

None.

## User Setup Required

None. Re-import workflow into n8n; re-attach WordPress credential to "📝 Publish to WordPress" if the instance uses a different credential id.

## Next Phase Readiness

- 01-05 can connect the false branch of "🔀 Publish Succeeded?" to Assemble Content Log Row (status publish_failed) and Telegram failure alert.
- Satellites (Blotato, Calendar, Trigger, Telegram success) run only when publish succeeds.

## Self-Check: PASSED

- FOUND: .planning/phases/01-pipeline-reliability/01-03-SUMMARY.md
- FOUND: commit 3ba9252

---
*Phase: 01-pipeline-reliability*
*Completed: 2026-03-12*
