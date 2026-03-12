---
phase: 1
slug: pipeline-reliability
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-03-12"
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for Pipeline Reliability. n8n workflows use manual execution; no automated test runner.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | n8n manual execution (no automated test runner) |
| **Config file** | None — test via n8n UI execution panel |
| **Quick run command** | Trigger "Execute Workflow" on `⚙️ Load Config` node directly |
| **Full suite command** | Enable schedule trigger, wait for 8 AM run, inspect execution log |
| **Estimated runtime** | Manual — per scenario |

---

## Sampling Rate

- **After every task commit:** Run quick run (Config Loader node) to confirm workflow loads
- **After every plan wave:** Run full orchestrator once with pinned/fail scenarios from RESEARCH.md
- **Before `$gsd-verify-work`:** All manual scenarios (A–D) in RESEARCH.md must be executed and pass
- **Max feedback latency:** Manual

---

## Per-Requirement Verification Map

| Req ID | Behavior | Test Type | How to Verify | Status |
|--------|----------|-----------|---------------|--------|
| PIPE-01 | Orchestrator completes daily without intervention | Smoke | Check n8n execution history shows success at 8 AM | ⬜ pending |
| PIPE-02 | LLM parse failure → fallback + continue + log | Manual | Pin invalid JSON, run, verify `parse_error` and `parse_error_agents` in Content Log | ⬜ pending |
| PIPE-03 | WP publish retries; status in Sheets | Manual | Wrong WP creds, run, verify 3 retries + `publish_failed` in Sheets | ⬜ pending |
| PIPE-03 | `publish_failed` blocks satellites | Manual | After WP failure, confirm Blotato/Calendar did NOT run | ⬜ pending |
| PIPE-04 | QC rejection → Rejected Posts sheet + Backlog + Telegram | Manual | Pin QC rejected, run, verify row + alert | ⬜ pending |
| PIPE-05 | Config at runtime without re-import | Manual | Change config in data table, run, verify value used | ⬜ pending |

---

## Orchestrator Verification (01-01)

Config Loader verified as first node after triggers; schedule `0 8 * * *`. In `core/08_Orchestrator_v3.json`: (1) Schedule Trigger "🕗 Daily Trigger 8AM" has `cronExpression` "0 8 * * *". (2) Both "🕗 Daily Trigger 8AM" and "⚡ Entry Override" connect only to "⚙️ Load Config"; Load Config then connects to "📡 Load Existing Topics" and "📡 Fetch Reddit Trending". No manual trigger required for daily run.

---

## Wave 0 Requirements

- [ ] `Rejected Posts` tab in Google Sheets
- [ ] `REJECTED_POSTS_TAB` in n8n `htg_config` data table
- [ ] Ollama Agent (Central) credential set in orchestrator (for Agent migration)

*Ref: RESEARCH.md § Wave 0 Gaps*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| All PIPE-* behaviors | PIPE-01–05 | n8n has no unit test runner; validation is execution-based | See RESEARCH.md § Per-Failure-Mode Test Scenarios (A–D) |

---

## Validation Sign-Off

- [ ] All requirements have verification steps in this file or RESEARCH.md
- [ ] Wave 0 gaps completed before PIPE-04/PIPE-02 execution tests
- [ ] `nyquist_compliant: true` set in frontmatter after first full manual pass

**Approval:** pending
