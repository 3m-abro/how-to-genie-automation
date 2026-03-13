---
phase: 11
slug: voice-audio
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-03-13"
---

# Phase 11 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Manual / smoke (n8n workflow run) |
| **Config file** | htg_config.csv (VOICE_PROVIDER, TTS_SERVER_URL, AUDIO_OUTPUT_PATH, AUDIO_LOG_TAB, etc.) |
| **Quick run command** | `grep -E 'YOUR_|your-blog' "growth/HowTo-Genie v4.0 — Voice & Audio*.json"` (expect no matches after refactor) |
| **Full suite command** | Manual: run full workflow in n8n; verify Content Log + Multilingual read, provider branches, file write and Audio Log append |
| **Estimated runtime** | ~5 min manual |

---

## Sampling Rate

- **After every task commit:** Grep workflow for YOUR_*; verify config keys used
- **After every plan wave:** Manual run in n8n (config with test sheet, optional local TTS server)
- **Before `$gsd-verify-work`:** Manual verification checklist (run workflow; check Sheet + files under AUDIO_OUTPUT_PATH)
- **Max feedback latency:** Manual

---

## Per-Requirement Verification Map

| Req ID | Behavior | Verification type | How |
|--------|----------|-------------------|-----|
| VOICE-01 | Reads today from Content Log + Multilingual (timezone) | manual | Run with TIMEZONE set; verify filter picks today's post and today's Multilingual rows |
| VOICE-02 | Adapts to TTS script; one audio per language; VOICE_PROVIDER branch | manual | Run with local/elevenlabs/google; verify one audio per language row |
| VOICE-03 | Config Loader first; empty Multilingual branch; column contract doc | manual + grep | Config first; IF no rows branch; HOWTOGENIE documents column contract |
| VOICE-04 | Runs after Multi-Language; logs to config-driven tab; local path only | manual | Schedule 16:00 or hour 16; AUDIO_LOG_TAB from config; files under AUDIO_OUTPUT_PATH |

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Content Log + Multilingual filter by today (timezone) | VOICE-01 | n8n runtime | Set TIMEZONE; run on day with one post and Multilingual rows; verify filter output |
| Empty Multilingual branch (no rows today) | VOICE-03 | n8n | Run when no Multilingual rows for today; verify no crash and documented behavior |
| Local / ElevenLabs / Google branches | VOICE-02 | n8n + provider APIs | Set VOICE_PROVIDER to each; run; verify audio produced (or skip local if no TTS server) |
| Audio files under AUDIO_OUTPUT_PATH; log row in AUDIO_LOG_TAB | VOICE-04 | Filesystem + Sheets | Run full workflow; check path and Sheet tab from config |
| No YOUR_* in workflow JSON | VOICE-03 | grep | `grep -E 'YOUR_|your-blog' "growth/HowTo-Genie v4.0 — Voice & Audio*.json"` → no matches |

---

## Wave 0 Requirements

- No automated test suite for n8n workflows in repo. Phase 11 verification is manual run + grep + Sheets/filesystem inspection. Optional: script to validate workflow JSON has no YOUR_* and expected node names (e.g. "⚙️ Load Config", "Filter today's post") — LOW priority.

---

## Validation Sign-Off

- [ ] All requirements have verification (UAT or grep) defined
- [ ] Manual steps documented for VOICE-01–VOICE-04
- [ ] Quick grep command run after refactor (no YOUR_*)
- [ ] `nyquist_compliant` set true in frontmatter when manual strategy approved

**Approval:** pending
