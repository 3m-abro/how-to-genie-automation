---
phase: 01-pipeline-reliability
verified: "2026-03-12T12:00:00Z"
status: gaps_found
score: 5/6 must-haves verified
gaps:
  - truth: "Changing a value in htg_config.csv takes effect on the next scheduled run without re-importing the workflow JSON (PIPE-05)"
    status: partial
    reason: "Content Log write does not use runtime config for destination. 📊 Log to Google Sheets uses $vars.GOOGLE_SHEET_ID and hardcoded sheetName 'Content Log' instead of $('⚙️ Load Config').item.json."
    artifacts:
      - path: core/08_Orchestrator_v3.json
        issue: "Log to Google Sheets node documentId is {{ $vars.GOOGLE_SHEET_ID }}, sheetName is 'Content Log'; other Sheets nodes use config."
    missing:
      - "Set documentId to ={{ $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID || $('⚙️ Load Config').item.json.SPREADSHEET_ID }} in 📊 Log to Google Sheets"
      - "Optionally use CONTENT_LOG_TAB from config for sheetName if added to htg_config"
---

# Phase 1: Pipeline Reliability Verification Report

**Phase Goal:** The core pipeline runs daily at 8 AM, recovers from transient errors automatically, and always writes a machine-readable result to Google Sheets — whether it succeeded or failed

**Verified:** 2026-03-12  
**Status:** gaps_found  
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths (ROADMAP Success Criteria)

| # | Truth | Status | Evidence |
|---|--------|--------|----------|
| 1 | Orchestrator fires at 8 AM daily and completes without manual trigger | ✓ VERIFIED | `core/08_Orchestrator_v3.json`: Schedule Trigger "🕗 Daily Trigger 8AM" has `cronExpression`: "0 8 * * *". Both "🕗 Daily Trigger 8AM" and "⚡ Entry Override" connect only to "⚙️ Load Config"; Load Config → "📡 Load Existing Topics" / "📡 Fetch Reddit Trending". |
| 2 | Unparseable LLM output → fallback defaults and parse_error in Sheets row | ✓ VERIFIED | Parse & Validate Topic/QC and Parse Prompt Package set `parse_error` in catch; "📋 Assemble Content Log Row" builds `parse_error_agents` from pkg, topic, article, qc; row has `parse_error`, `parse_error_agents`; single path to "📊 Log to Google Sheets". |
| 3 | WordPress publish retries on failure; status published/publish_failed in Content Log, never blank | ✓ VERIFIED | "📝 Publish to WordPress" is `n8n-nodes-base.wordpress` with `retryOnFail: true`, `maxTries: 3`, `waitBetweenTries: 5000`. "🔗 Capture WP Post Data" returns `status: 'published'` or `status: 'publish_failed'`. "📋 Assemble Content Log Row" sets `status: publishFailed ? 'publish_failed' : 'published'`. |
| 4 | QC rejection → structured row to Sheets (reason, score, topic); next-day run picks fresh topic | ✓ VERIFIED | "✅ QC Approved?" false → "📋 Build QC Rejection Row" → "📊 Write to Rejected Posts Sheet" (REJECTED_POSTS_TAB from config); "📋 Build Backlog Rejection Update" + "Backlog Update Needed?" → "📊 Update Backlog Row (rejected)"; "📱 Telegram: QC Rejected". Rejected Posts sheet has date, topic, primary_keyword, qc_score, rejection_reasons, word_count, agent_fallbacks_used. |
| 5 | Config (htg_config) changes take effect on next run without re-import | ⚠️ PARTIAL | "⚙️ Load Config" is first after both triggers; Agents and Write to Rejected Posts use $('⚙️ Load Config').item.json. **Gap:** "📊 Log to Google Sheets" uses `$vars.GOOGLE_SHEET_ID` and hardcoded "Content Log", so Content Log destination is not runtime config. |

**Score:** 5/6 truths fully verified; 1 partial (config for Content Log destination).

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `core/08_Orchestrator_v3.json` | Canonical orchestrator with 8 AM, config, agents, WP retry, QC path, Content Log | ✓ VERIFIED | Exists; schedule "0 8 * * *"; Load Config first; Agents 1–5 executeWorkflow to Ollama Central; Parse & Validate QC with parse_error; WordPress node + retry; Capture WP Post Data; Publish Succeeded?; Assemble Content Log Row; Log to Google Sheets; QC rejection → Build QC Rejection Row → Write to Rejected Posts + Backlog + Telegram. |
| `htg_config.csv` | REJECTED_POSTS_TAB and config keys | ✓ VERIFIED | Contains REJECTED_POSTS_TAB,Rejected Posts; GOOGLE_SHEET_ID, BLOG_IDEA_TAB, etc. |
| `.planning/phases/01-pipeline-reliability/01-VALIDATION.md` | Wave 0 and verification notes | ✓ VERIFIED | Rejected Posts tab columns and n8n htg_config data table step; Config Loader and schedule note. |
| `core/Ollama Agent (Central).json` | Sub-workflow for agents | ✓ VERIFIED | Present; orchestrator references it via executeWorkflow. |

### Key Link Verification

| From | To | Via | Status | Details |
|------|-----|-----|--------|---------|
| 🕗 Daily Trigger 8AM / ⚡ Entry Override | ⚙️ Load Config | direct connection | ✓ WIRED | connections.main → Load Config |
| ⚙️ Load Config | 📡 Load Existing Topics, 📡 Fetch Reddit Trending | direct | ✓ WIRED | Config is first; downstream nodes read $('⚙️ Load Config').item.json |
| Agents 1–5 | Ollama Agent (Central) | executeWorkflow, workflowInputs (model, user_message, system_message, temperature, num_predict), waitForSubWorkflow | ✓ WIRED | 5 executeWorkflow nodes; temperatures 0.7, 0.8, 0.9, 0.4, 0.3 |
| ✅ Parse & Validate QC | ✅ QC Approved? | output with .approved | ✓ WIRED | IF condition $json.approved === true (boolean equals) |
| ✅ QC Approved? [false] | 📋 Build QC Rejection Row | direct | ✓ WIRED | main[1] → Build QC Rejection Row |
| 📋 Build QC Rejection Row | 📊 Write to Rejected Posts Sheet | append row | ✓ WIRED | sheetName from REJECTED_POSTS_TAB |
| 📝 Publish to WordPress | 🔗 Capture WP Post Data | direct | ✓ WIRED | then → 🔀 Publish Succeeded? |
| 🔀 Publish Succeeded? [true] | 🔍 Request Google Indexing, … | direct | ✓ WIRED | Satellites only on true |
| 🔀 Publish Succeeded? [false] | 📋 Assemble Content Log Row | direct | ✓ WIRED | main[1] → Assemble Content Log Row |
| 🔍 Request Google Indexing | 📋 Assemble Content Log Row | direct | ✓ WIRED | Success path feeds Assemble |
| 📋 Assemble Content Log Row | 📊 Log to Google Sheets | single input | ✓ WIRED | Only incoming connection to Log |
| 📊 Log to Google Sheets | 🔀 Status is publish_failed? | direct | ✓ WIRED | Then → Telegram Publish Failed (true) / Send Success Alert (false) |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| PIPE-01 | 01-01 | Orchestrator runs daily at 8 AM without manual intervention | ✓ SATISFIED | Schedule 0 8 * * *; both triggers → Load Config; no manual step. |
| PIPE-02 | 01-02, 01-05 | Failed LLM nodes fall back and log error to Google Sheets | ✓ SATISFIED | Parse & Validate nodes with parse_error; Assemble Content Log Row has parse_error, parse_error_agents; single Log path. |
| PIPE-03 | 01-03, 01-05 | WordPress publish retries and reports status to Sheets | ✓ SATISFIED | retryOnFail, maxTries 3, waitBetweenTries 5000; Capture WP Post Data; status in Content Log row; Publish Succeeded? gates satellites. |
| PIPE-04 | 01-04 | QC rejection to Sheets log and next-day retry topic | ✓ SATISFIED | Build QC Rejection Row → Write to Rejected Posts Sheet; Backlog update when _row; Telegram QC Rejected. |
| PIPE-05 | 01-01 | Config Loader reads htg_config at runtime | ⚠️ PARTIAL | Load Config first; most nodes use config. Content Log destination (Log to Google Sheets) uses $vars, not config. |

All phase requirement IDs (PIPE-01–PIPE-05) are accounted for; none orphaned.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| core/08_Orchestrator_v3.json | 717 | wordpressApi credential `"id": "placeholder"` | ℹ️ Info | Expected in repo; must be replaced with real credential in n8n. |

No TODO/FIXME in workflow logic; no stub implementations detected.

### Human Verification Required

| Test | Expected | Why human |
|------|----------|----------|
| 8 AM run without manual trigger | Execution history shows successful run at 8 AM | Schedule and connectivity verified in JSON; actual cron requires live n8n. |
| Unparseable LLM → parse_error in Content Log | Pin invalid JSON at an agent; run; row has parse_error true and parse_error_agents populated | No automated test runner for n8n. |
| WP failure → 3 retries then publish_failed in Sheets | Wrong WP creds; run; Content Log row status publish_failed; satellites did not run | Manual execution. |
| QC rejection → Rejected Posts row + Backlog + Telegram | Pin QC rejected; run; row in Rejected Posts; backlog status rejected; Telegram received | Manual. |
| Config change without re-import | Change a value in n8n htg_config data table (or CSV if synced); run; new value used | Content Log destination still uses $vars until gap fix. |

### Gaps Summary

- **Single gap:** PIPE-05 / Success Criterion 5 is only partially met. The Content Log write node ("📊 Log to Google Sheets") does not read the spreadsheet ID (or optional tab name) from runtime config; it uses `$vars.GOOGLE_SHEET_ID` and hardcoded sheet name "Content Log". All other Sheets nodes in the workflow use `$('⚙️ Load Config').item.json` for documentId and sheet names. Updating Log to Google Sheets to use Load Config for documentId (and optionally a CONTENT_LOG_TAB from config) would make "parameter changes take effect without re-importing" apply to the Content Log destination as well.

---

_Verified: 2026-03-12_  
_Verifier: Claude (gsd-verifier)_
