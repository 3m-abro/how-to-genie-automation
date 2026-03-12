---
phase: 4
slug: content-satellites
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-12
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None (n8n workflow JSON; no Jest/pytest in repo) |
| **Config file** | N/A |
| **Quick run command** | `node -e "JSON.parse(require('fs').readFileSync('path/to/workflow.json'))"` |
| **Full suite command** | UAT checklist (04-RESEARCH.md Validation Architecture) |
| **Estimated runtime** | ~manual |

---

## Sampling Rate

- **After every task commit:** Validate workflow JSON parses for modified files
- **After every plan wave:** Run UAT checklist for that wave's requirements
- **Before `$gsd-verify-work`:** All GROW-05 and GROW-06 UAT items must pass
- **Max feedback latency:** N/A (manual UAT)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 04-01-* | 01 | 1 | GROW-05 | UAT | See 04-RESEARCH.md Validation Architecture | ✅ | ⬜ pending |
| 04-02-* | 02 | 1 | GROW-06 | UAT | See 04-RESEARCH.md Validation Architecture | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- Existing infrastructure: n8n import/run; no test directory. Optional: small script to assert required nodes/keys in workflow JSON (smoke check).

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Video workflow 10:30, config-gated, today's post → scripts → Blotato → Video Log | GROW-05 | n8n workflows | Enable VIDEO_PRODUCTION_ENABLED; Content Log has today row status ≠ publish_failed; run; check Video Log rows |
| No valid post → exit without Video Log write | GROW-05 | n8n | Run with no today row or publish_failed; confirm no append |
| VIDEO_PRODUCTION_ENABLED false → no Sheets/Blotato | GROW-05 | n8n | Set false; run; confirm no read/call |
| Webhook adds subscriber to ConvertKit/MailerLite; ESP sends first email | GROW-06 | ESP + n8n | POST webhook; confirm subscriber + first email in 5 min |
| EMAIL_NEWSLETTER_ENABLED false → 200 OK, no ESP add | GROW-06 | n8n | Set false; POST; confirm 200 and no subscriber |

---

## Validation Sign-Off

- [ ] All tasks have UAT verify or JSON parse check
- [ ] Sampling continuity: per-task JSON validation where workflow files change
- [ ] Wave 0: optional smoke script for node/key presence
- [ ] No watch-mode flags
- [ ] `nyquist_compliant: true` set in frontmatter when UAT checklist is satisfied

**Approval:** pending
