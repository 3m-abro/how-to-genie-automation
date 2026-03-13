# Phase 1: Pipeline Reliability - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Harden the core 8 AM orchestrator so it never silently fails or stops mid-run. Every run — success, partial failure, or QC rejection — must write a structured, machine-readable result to Google Sheets and notify via Telegram. The pipeline must continue to produce at least one published or logged result each day without manual intervention.

Requirements in scope: PIPE-01, PIPE-02, PIPE-03, PIPE-04, PIPE-05.
Growth satellites, dashboards, affiliates, and SEO are out of scope for this phase.

</domain>

<decisions>
## Implementation Decisions

### LLM Fallback Behavior (PIPE-02)
- When a Parse & Validate node cannot parse the LLM response, use fallback defaults and **continue the pipeline** — do not abort
- The fallback post IS published to WordPress (keeps daily post cadence intact)
- Write `parse_error=true` to the Sheets Content Log row so it's visible in weekly review
- Log **per-agent** fallback flags: `parse_error_agents=['agent_2','agent_4']` — not a single boolean — so failures are diagnosable
- All remaining downstream agents (SEO, QC, Social) still run on fallback content — QC may naturally reject low-quality fallback output

### Ollama Node Type (PIPE-02 prerequisite)
- Switch all Ollama httpRequest nodes in the orchestrator to the **Ollama Agent (Central) sub-workflow** (`core/Ollama Agent (Central).json`)
- Each agent call passes **temperature as a parameter** to the sub-workflow (per-agent temperature is preserved: 0.3 for QC, 0.9 for creative agents)
- Centralizes LLM config, timeout, and retry logic in one place

### WordPress Node Type + Retry (PIPE-03)
- Switch WordPress publish from httpRequest nodes to **n8n's native WordPress nodes**
- Retry policy: **3 attempts with 30-second delay** between each
- On final failure: write `status=publish_failed` to Content Log row + send Telegram alert
- When status is `publish_failed`, **block all satellite workflow triggers** — no social posts, no video, no translation with a dead WP URL

### QC Rejection Routing (PIPE-04)
- Write a full structured row to a dedicated **'Rejected Posts'** Google Sheets tab (not Content Log, not Error Log)
- Rejection row fields: `date`, `topic`, `primary_keyword`, `qc_score`, `rejection_reasons` (array), `word_count`, `agent_fallbacks_used`
- Mark the topic as `status=rejected` in the **Blog Idea Backlog** tab so Agent 1 never re-selects it on the next run
- Send a **Telegram alert** on every QC rejection: "QC rejected [topic] — score [X]/10, reason: [reason]. Fresh topic tomorrow."
- The 'Rejected Posts' tab is separate from Content Log to keep the log clean (published posts only)

### Config Runtime Source (PIPE-05)
- The existing n8n data table + Config Loader sub-workflow already satisfies PIPE-05 (reads at runtime, no re-import needed)
- Phase 1 action: **verify and document** that Config Loader (`01_Config_Loader.json`) is called at the START of the orchestrator run (before any agents), and that all key config values flow through correctly to each agent
- `htg_config.csv` in the repo is a documentation reference kept in sync with the n8n data table manually — no new sync mechanism needed

### Workflow Node Audit
- Audit and correct node types in: **all workflows EXCEPT the `/archive` directory**
- Focus of node-type changes: core orchestrator (`08_Orchestrator_v3.json`) and Ollama Agent (Central)
- Archive criterion: **move to `/archive` if superseded by a newer version of the same workflow** (e.g., v1 orchestrator when v3 is canonical)
- Keep anything without a clear newer replacement

### Claude's Discretion
- Exact retry implementation pattern (Wait node + loop vs. n8n retry settings)
- Which specific Code node fields to add/update for per-agent error flags
- How to detect "superseded" workflows during audit (by name pattern and version number)

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- `core/Ollama Agent (Central).json` — Central LLM sub-workflow; all 8 agents should route through this. Currently unused by orchestrator (agents call Ollama directly via httpRequest).
- `core/01_Config_Loader.json` — Config Loader sub-workflow with `Get Config(s)` and `Get Secret(s)` dataTable nodes. Already called via `executeWorkflow` node in orchestrator.
- `core/07_Approval_Poller.json` — Approval polling sub-workflow; may be relevant to QC flow.
- Existing Parse & Validate nodes already have try/catch with fallback defaults — extend rather than replace them.
- Telegram node (`📱 Telegram: Article Published`) already wired for success alerts — use same pattern for rejection and failure alerts.

### Established Patterns
- **Parse & Validate pattern**: `raw.match(/```json\n([\s\S]*?)\n```/) || raw.match(/(\{[\s\S]*\})/)` with try/catch and fallback object — used consistently after every LLM node
- **Node naming with emoji prefixes**: `🔍 Agent 1`, `✅ Parse & Validate Topic` — maintain this convention
- **`executeWorkflow` for sub-workflow calls**: Config Loader, Alert workflows all use this node type
- **Google Sheets `append` operation** for log rows — already used by `📊 Log to Google Sheets` node

### Integration Points
- `✅ QC Approved?` IF node → false branch → `⚠️ Alert: QC Rejected` (dead-end, needs Sheets + Backlog update nodes added)
- `📝 Publish to WordPress` (httpRequest) → needs to become WordPress native node + retry wrapper
- `📊 Log to Google Sheets` on success path → needs `parse_error`, `parse_error_agents`, `status` fields added
- Config Loader call (`⚙️ Load Config`) is mid-workflow — verify it runs before Agent 0, not after it
- Satellite triggers (`🎨 Queue via Blotato`, `🚀 Trigger Content Calendar Manager`) must be gated on `publish_failed` check

</code_context>

<specifics>
## Specific Ideas

- "Use what is recommended and best approach" — user explicitly delegates implementation choices to Claude for node types and retry patterns
- n8n is self-hosted on a VPS; no access to n8n environment variables feature — data table is the correct config storage
- htg_config.csv is kept as a documentation/reference artifact only; n8n data table is the canonical runtime source
- User rarely changes config values at runtime — verification pass is sufficient, no sync mechanism needed

</specifics>

<deferred>
## Deferred Ideas

- Adding satellite workflow node-type fixes (e.g., social, growth, monitoring workflows) — those are corrected in Phases 2–6 when each satellite is activated
- Building a CSV → n8n data table sync mechanism — not needed given low config-change frequency
- Approval polling integration into QC rejection flow — investigate in planning whether 07_Approval_Poller is currently used

</deferred>

---

*Phase: 01-pipeline-reliability*
*Context gathered: 2026-03-12*
