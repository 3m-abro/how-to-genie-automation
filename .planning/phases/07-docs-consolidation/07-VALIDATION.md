---
phase: 7
slug: docs-consolidation
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-13
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution. Documentation-only: structural check + manual UAT.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Structural check (required sections) + manual UAT |
| **Config file** | None |
| **Quick run command** | `grep -E '^## (Workflows|Config Keys|Schedule|UI|Archive)' docs/HOWTOGENIE.md` (adjust path/section names to plan) |
| **Full suite command** | Same + manual: "Doc is single authoritative reference; no conflicting setup docs" |
| **Estimated runtime** | ~5 seconds (structural); manual UAT per phase gate |

---

## Sampling Rate

- **After every task commit:** Quick structural grep if planner adds verification script; otherwise manual spot-check.
- **After every plan wave:** Structural check + manual check for DOCS-02 (no competing setup docs).
- **Before `$gsd-verify-work`:** Doc exists, required sections present, pointer/deprecation applied; UAT confirms single reference.
- **Max feedback latency:** 10 seconds (structural only)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 07-01-* | 01 | 1 | DOCS-01 | structural | `test -f docs/HOWTOGENIE.md && grep -qE '^## (Workflows|Config Keys|Schedule|UI|Archive)' docs/HOWTOGENIE.md` (adjust path/section names to plan) | ❌ W0 | ⬜ pending |
| 07-01-* | 01 | 1 | DOCS-02 | manual | N/A — verify no other "main" setup doc competes; workflow list + config reference present | — | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Verification script or documented grep commands for required sections (path and section names depend on planner choice).
- [ ] No pytest/jest needed; structural + manual verification only.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Single authoritative reference; no parallel conflicting doc sets | DOCS-02 | Subjective; no automated "competing doc" check | Confirm no other "main" setup doc competes; workflow list + config reference present for updates |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 10s for structural
- [ ] `nyquist_compliant: true` set in frontmatter when ready

**Approval:** pending
