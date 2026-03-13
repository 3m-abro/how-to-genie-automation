---
phase: 10
slug: content-repurposing
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-03-13"
---

# Phase 10 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual / UAT + grep checklist (no n8n test suite in repo) |
| **Config file** | htg_config.csv (config keys for repurposing) |
| **Quick run command** | `grep -E 'YOUR_|your-blog' "content/v3.0 — Content Repurposing Engine.json"` (expect no matches after refactor) |
| **Full suite command** | Run workflow in n8n twice same day; inspect Content Log filter, idempotency, Sheets output |
| **Estimated runtime** | ~5 min manual |

---

## Sampling Rate

- **After every task commit:** Run quick grep (no YOUR_* / your-blog)
- **After every plan wave:** Manual run in n8n + Sheets inspection
- **Before `$gsd-verify-work`:** Full UAT (run twice same day; verify idempotency and format count)
- **Max feedback latency:** Manual

---

## Verification Dimensions (from RESEARCH.md)

| Dimension | What to verify | How |
|-----------|----------------|-----|
| Config-first | No YOUR_* or your-blog.com; Config Loader runs first; all sheet/URL from config | Grep workflow JSON; run and inspect first nodes |
| Timezone today | Correct "today" in owner TZ; correct row from Content Log | Set TIMEZONE in config; run on day with one post; assert filter picks that row |
| Idempotency | Second run same day does not append duplicate row | Run twice same day; Repurposed Content has one row for that post/date |
| Formats 3–5 | Only enabled formats (from REPURPOSE_FORMATS) run; 3–5 total | Set REPURPOSE_FORMATS to 3 formats; run; assert only 3 format outputs; change to 5 and re-run |
| HTML strip + LLM | Post body stripped of HTML; each format gets LLM output; Parse & Validate used | Inspect Clean & Extract output; each format branch has Parse & Validate; no raw prose to Sheets |
| Schedule | Runs after publish (e.g. Noon) | Schedule trigger 0 12 * * * or orchestrator hour 12 |
| Docs | New config keys and tab names in docs/HOWTOGENIE.md (and htg_config.csv) | Checklist: REPURPOSE_FORMATS, REPURPOSED_CONTENT_TAB, queue tab keys if any |

---

## Per-Requirement Verification Map

| Req ID | Behavior | Verification type | How |
|--------|----------|-------------------|-----|
| REP-01 | Read today's post (timezone); produce 3–5 formats | UAT | Run with post today; run with no post; check format count from config |
| REP-02 | Strip HTML; LLM per format; log to config-driven tabs | UAT + checklist | Inspect Clean & Extract; Parse & Validate per format; tab names from config |
| REP-03 | Config Loader + WORDPRESS_URL; idempotent | UAT + grep | Config first; URL from config; run twice, no duplicate row |
| REP-04 | Noon; no YOUR_* | Schedule + grep | Cron 0 12 * * *; grep YOUR_ and your-blog |

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Content Log filter picks today's post in owner TZ | REP-01 | n8n runtime | Set TIMEZONE; run on day with one published post; verify filter output has that row |
| Idempotency: no duplicate row on second run | REP-03 | Sheets + n8n | Run workflow twice same day; check Repurposed Content tab has one row per post/date |
| Only REPURPOSE_FORMATS formats run | REP-01 | n8n + config | Set REPURPOSE_FORMATS to 3; run; verify 3 format outputs; repeat with 5 |
| No YOUR_* or your-blog.com in workflow JSON | REP-04 | grep | `grep -E 'YOUR_|your-blog' "content/v3.0 — Content Repurposing Engine.json"` → no matches |

---

## Wave 0 Requirements

- No automated test suite for n8n workflows in repo. Phase 10 verification is manual run + grep + Sheets inspection. This file lists exact grep commands and UAT steps for each requirement.

---

## Validation Sign-Off

- [ ] All requirements have verification (UAT or grep) defined
- [ ] Manual steps documented for REP-01, REP-02, REP-03, REP-04
- [ ] Quick grep command run after refactor (no YOUR_* / your-blog)
- [ ] `nyquist_compliant` set true in frontmatter when manual strategy approved

**Approval:** pending
