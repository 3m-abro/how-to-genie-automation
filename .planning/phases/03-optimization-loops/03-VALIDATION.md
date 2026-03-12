---
phase: 3
slug: optimization-loops
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-12
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None (n8n workflow JSON) |
| **Config file** | N/A |
| **Quick run command** | Manual: execute workflow in n8n UI |
| **Full suite command** | Manual: run A/B at 6 AM and Viral at :00 every 6h; verify Sheets and promotion_status |
| **Estimated runtime** | Manual |

---

## Sampling Rate

- **After every task commit:** Manual run of the modified workflow(s) in n8n
- **After every plan wave:** Same; no automated test script
- **Before `$gsd-verify-work`:** All manual checks below satisfied
- **Max feedback latency:** N/A (manual)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 03-01-* | 01 | 1 | GROW-03 | manual | Run A/B workflow; check A/B tab | N/A | ⬜ pending |
| 03-02-* | 02 | 1–2 | GROW-04 | manual | Run Viral + queue; check Viral Amplifier tab | N/A | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- No automated test framework for n8n workflows in this repo.
- 03-VALIDATION.md lists manual verification steps and expected Sheets state for a consistent checklist.

*Existing infrastructure: manual/smoke in n8n.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| A/B workflow creates and logs variants for yesterday's post when enabled | GROW-03 | n8n JSON; no test runner | Run A/B workflow; check A/B tab has new row with original + variant fields |
| A/B workflow exits without writing when A_B_TESTING_ENABLED is false | GROW-03 | n8n | Set flag false; run; no append to A/B tab |
| A/B workflow exits when no yesterday row or status publish_failed | GROW-03 | n8n | Use Content Log with no yesterday / failed; run; no WP fetch |
| Viral workflow appends rows to Viral Amplifier tab when above threshold and enabled | GROW-04 | n8n | Run Viral workflow; check Viral Amplifier tab for new rows with promotion_status=pending |
| Viral workflow exits without writing when VIRAL_AMPLIFIER_ENABLED is false | GROW-04 | n8n | Set flag false; run; no append |
| Pending viral rows picked up, queued to social, marked sent | GROW-04 | n8n | Add pending row; run viral-queue flow; check Social Queue and promotion_status=sent |

---

## Validation Sign-Off

- [ ] All tasks have manual verify or Wave 0 dependencies
- [ ] Sampling continuity: manual run after each task/wave
- [ ] Wave 0 covers all MISSING references (N/A — manual phase)
- [ ] No watch-mode flags
- [ ] Feedback latency: manual
- [ ] `nyquist_compliant: true` set in frontmatter when manual checklist is complete

**Approval:** pending
