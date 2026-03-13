---
phase: 08-archive-cleanup
plan: 01
subsystem: infra
tags: shell, n8n, workflow, archive, grep, node

# Dependency graph
requires:
  - phase: 07-docs-consolidation
    provides: docs/HOWTOGENIE.md and archive section
provides:
  - scripts/verify-archive-refs.sh — assert no active JSON references any archived workflow root id (ARCH-02)
  - scripts/caller-audit.sh — list active-dir files that reference a given workflow's root id (caller discovery before move)
  - 08-VALIDATION.md Wave 0 complete and script references
affects: 08-02 (archive moves, update-before-move)

# Tech tracking
tech-stack:
  added: []
  patterns: "Extract root id from n8n workflow JSON (Node); grep id in active dirs only"

key-files:
  created: scripts/verify-archive-refs.sh, scripts/caller-audit.sh
  modified: .planning/phases/08-archive-cleanup/08-VALIDATION.md

key-decisions: []

patterns-established:
  - "Archive verification: collect archive workflow root ids, grep in core/content/growth/social/affiliate/monitoring/email; exit 1 if match"
  - "Caller discovery: one workflow path → root id → grep in active dirs, print matching files"

requirements-completed: [ARCH-02]

# Metrics
duration: 5
completed: "2026-03-13"
---

# Phase 08 Plan 01: Archive Verification Scripts Summary

**Verify-archive-refs and caller-audit scripts for ARCH-02 and update-before-move workflow.**

## Performance

- **Duration:** ~5 min
- **Tasks:** 2
- **Files modified:** 3 (2 created, 1 modified)

## Accomplishments

- `scripts/verify-archive-refs.sh`: for each `archive/*.json`, extracts root `id` (Node); greps that id in active dirs only; exits 1 if any active JSON references an archived workflow id, else 0.
- `scripts/caller-audit.sh`: accepts one workflow path; extracts root id; greps in active dirs; prints caller file paths (one per line); exit 1 if no root id.
- `08-VALIDATION.md`: `wave_0_complete: true`, Wave 0 checklist done, quick run command and caller-audit reference added.

## Task Commits

1. **Task 1: Create verify-archive-refs script** - `1b41fc9` (feat)
2. **Task 2: Create caller-audit script and update VALIDATION** - `41fdea3` (feat)

## Files Created/Modified

- `scripts/verify-archive-refs.sh` - Phase 8 ARCH-02 verification; collect archive ids, grep active dirs, exit 1 on match
- `scripts/caller-audit.sh` - Caller discovery for a single workflow path before moving to archive
- `.planning/phases/08-archive-cleanup/08-VALIDATION.md` - Wave 0 complete, script references

## Decisions Made

None - followed plan as specified.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Wave 0 scripts in place; 08-02 can use `caller-audit.sh` before moving workflows and `verify-archive-refs.sh` after moves.
- No blockers.

## Self-Check: PASSED

- scripts/verify-archive-refs.sh, scripts/caller-audit.sh, 08-01-SUMMARY.md present
- Commits 1b41fc9, 41fdea3 present

---
*Phase: 08-archive-cleanup*
*Completed: 2026-03-13*
