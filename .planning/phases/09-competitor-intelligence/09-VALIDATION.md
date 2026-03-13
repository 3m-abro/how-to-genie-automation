---
phase: 9
slug: competitor-intelligence
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-13
---

# Phase 9 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | No automated tests for n8n workflows in repo |
| **Config file** | — |
| **Quick run command** | `grep -E "YOUR_GOOGLE_SHEET_ID|Competitor Intelligence|Content Ideas Queue|Backlink" growth/*Competitor*.json` → expect no matches |
| **Full suite command** | Manual: import workflow in n8n, run with Config Loader + test sheet |
| **Estimated runtime** | ~30s (grep); manual run as needed |

---

## Sampling Rate

- **After every task commit:** Run quick grep for placeholders and hardcoded tab names
- **After every plan wave:** Manual run in n8n with test config and sheet
- **Before `$gsd-verify-work`:** Grep clean + successful manual run
- **Max feedback latency:** 60 seconds (grep + spot check)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 09-01-* | 01 | 1 | COMP-01 | manual | — | ✅ workflow | ⬜ pending |
| 09-01-* | 01 | 1 | COMP-02 | manual | — | ✅ workflow | ⬜ pending |
| 09-01-* | 01 | 1 | COMP-03 | manual / grep | Inspect for Wait/IF after HTTP | ✅ workflow | ⬜ pending |
| 09-01-* | 01 | 1 | COMP-04 | grep | `grep -L "YOUR_GOOGLE_SHEET_ID" growth/*Competitor*.json` | ✅ workflow | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- Existing infrastructure: workflow JSON + htg_config.csv; no n8n test runner in repo.
- Validation: manual run (Config Loader, at least one RSS + one Reddit, delay, write to sheet) + grep rules for COMP-04.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Schedule + config-driven RSS/Reddit | COMP-01 | n8n runtime | Trigger run; confirm config keys used for feeds/subs |
| Deduplicated recency-ordered list to tab | COMP-02 | Sheets write | Run workflow; check target tab has rows, no dupes by URL, sorted by date |
| Config Loader first; delay + IF after HTTP | COMP-03 | Workflow structure | Inspect nodes: Execute Workflow first; Wait + IF after each HTTP |
| No hardcoded sheet ID or tab names | COMP-04 | Grep | `grep -E "YOUR_GOOGLE_SHEET_ID|Competitor Intelligence|Content Ideas Queue|Backlink" growth/*Competitor*.json` → no matches |

---

## Validation Sign-Off

- [ ] All tasks have manual verify or grep check
- [ ] Sampling continuity: grep after edits; manual run per wave
- [ ] Wave 0: no MISSING references (workflow file exists)
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s for grep
- [ ] `nyquist_compliant: true` set in frontmatter when manual checklist done

**Approval:** pending
