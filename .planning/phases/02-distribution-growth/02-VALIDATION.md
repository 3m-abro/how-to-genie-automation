---
phase: 2
slug: distribution-growth
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-03-12"
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | n8n manual execution + JSON parse of workflow files |
| **Config file** | None — test via n8n UI execution panel and config data table |
| **Quick run command** | `node -e "JSON.parse(require('fs').readFileSync('growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json'))"` and same for `growth/HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json` |
| **Full suite command** | Trigger each growth workflow from Schedule Trigger or Execute Node on "⚙️ Load Config"; verify enable gate, today filter, and log behavior |
| **Estimated runtime** | ~60s (JSON parse) + manual run time |

---

## Sampling Rate

- **After every task commit:** Run quick JSON parse on modified workflow file(s)
- **After every plan wave:** Run both growth workflows manually with config enabled and disabled/no-post scenarios; spot-check Sheets (and Telegram if available)
- **Before `$gsd-verify-work`:** All GROW-01 and GROW-02 behaviors in Manual-Only Verifications table verified
- **Max feedback latency:** 120 seconds (parse + manual check)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 02-01-* | 01 | 1 | GROW-01 | manual | JSON parse workflow | ✅ W0 | ⬜ pending |
| 02-02-* | 02 | 1 | GROW-02 | manual | JSON parse workflow | ✅ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Config Loader invoked as first step in both growth workflows (Execute Workflow + IF on enable flag)
- [ ] "Get today's post" Code node filters Content Log by config timezone date and status !== publish_failed in both workflows
- [ ] Multi-language: when no valid post, workflow stops without writing to Multilingual Content
- [ ] Messaging: when no post or zero subscribers, append exactly one row to Messaging Distribution Log then stop
- [ ] Config keys MULTI_LANGUAGE_ENABLED, MESSAGING_DIGEST_ENABLED, MESSAGING_SUBSCRIBERS_TAB (and optionally CONTENT_DAY_TIMEZONE) in htg_config for testing
- [ ] Subscriber sheet tab with columns: Platform, Chat ID or Phone, Language (optional), Status (active/inactive)
- [ ] Messaging Distribution Log tab exists for append (Status, Recipients, reason, etc.)

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Multi-language enabled → workflow runs | GROW-01 | n8n + Sheets + WP | Set MULTI_LANGUAGE_ENABLED=true; run workflow; verify translation and 8 publishes |
| Multi-language disabled → silent exit | GROW-01 | n8n | Set MULTI_LANGUAGE_ENABLED=false or missing; run; verify no Sheets append, no HTTP |
| No today's post (multi-language) → exit, no Multilingual row | GROW-01 | n8n + Sheets | Empty Content Log or only publish_failed for today; run; verify no Multilingual Content append |
| Today's post exists → 8 subdomains + 8 log rows | GROW-01 | n8n + Sheets + WP | Content Log has today's row with status published; run; verify 8 WP posts and 8 Multilingual Content rows |
| Messaging enabled → workflow runs | GROW-02 | n8n + Telegram | Set MESSAGING_DIGEST_ENABLED=true; run; verify subscribers loaded, Telegram send |
| Messaging disabled → silent exit | GROW-02 | n8n | Set MESSAGING_DIGEST_ENABLED=false or missing; run; verify no Messaging Distribution Log row |
| No today's post (messaging) → one Skipped row, exit | GROW-02 | n8n + Sheets | No valid Content Log row for today; run; verify one row: Status=Skipped, reason=no_post_today |
| Zero active subscribers → one Skipped row (Recipients=0) | GROW-02 | n8n + Sheets | Subscriber tab empty or all Status≠active; run; verify one Skipped row, Recipients=0 |
| Post + subscribers → send digest, log Sent row | GROW-02 | n8n + Telegram + Sheets | Valid post and ≥1 active subscriber; run; verify messages sent and one Sent row in log |

---

## Validation Sign-Off

- [ ] All tasks have manual verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without verification step
- [ ] Wave 0 covers all MISSING references (Config Loader, today filter, skip behavior, config keys, tabs)
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s for parse; manual steps documented
- [ ] `nyquist_compliant: true` set in frontmatter when Wave 0 and manual checks satisfied

**Approval:** pending
