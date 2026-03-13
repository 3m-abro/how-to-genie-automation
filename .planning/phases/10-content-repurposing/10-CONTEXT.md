# Phase 10: Content Repurposing — Context

**Gathered:** 2026-03-13  
**Status:** Ready for planning

<domain>
## Phase Boundary

Repurposing workflow reads today's post from Content Log (timezone-aware), produces 3–5 platform-native formats from config-driven format set, strips HTML and uses LLM per format, logs to config-driven tabs (Repurposed Content and queues), uses Config Loader and WORDPRESS_URL from config, and is idempotent (no duplicate append same post/date). No YOUR_* placeholders. Runs after publish (e.g. Noon). Requirements: REP-01, REP-02, REP-03, REP-04.
</domain>

<decisions>
## Implementation Decisions

### Formats (3–5)
- **Config-driven format set** — Which formats run is determined by config (e.g. REPURPOSE_FORMATS); not a fixed list in code.
- **All 5 formats equal** — No priority ordering; planner decides execution order and failure behavior.
- **Community content** — Planner's choice: keep as generic "Community content", rename/specialize (e.g. Reddit/Facebook), or drop for Phase 10.
- **Extensible by config** — Design from the start so adding a format later = config change + workflow branch; no code refactor required.

### Output tabs and queues
- **Planner's discretion** — Summary tab shape (one row per post vs one row per format), queue tabs (per-format vs single queue vs none), config key naming (REPURPOSED_CONTENT_TAB and any queue tab keys), and column contract for Repurposed Content and queues.

### Idempotency
- **Planner's discretion** — Idempotency key (post_id+date vs slug+date), behavior when "already repurposed" (skip run vs skip appends vs update row), where to check (Repurposed Content tab only vs also queues), and meaning of "date" (content day vs run date).

### LLM execution
- **Planner's discretion** — Execution order (sequential vs parallel vs capped parallel), failure behavior when one format fails (continue vs fail run), retries per format, and model/temperature (single vs per-format overrides).

### Claude's Discretion
- All areas above marked "Planner's discretion": researcher and planner choose concrete options and document them in the plan and in CONFIG-KEYS / HOWTOGENIE as appropriate.
</decisions>

<code_context>
## Existing Code Insights

### Reusable assets
- **core/01_Config_Loader.json** — Execute at start; read config from `$('⚙️ Load Config').item.json` (GOOGLE_SHEET_ID, CONTENT_LOG_TAB, WORDPRESS_URL, TIMEZONE, etc.). Use for all Sheets and HTTP nodes.
- **content/v3.0 — Content Repurposing Engine.json** — Existing repurposing workflow: Noon trigger; reads "Content Log" (hardcoded); fetches post from your-blog.com; 5 LLM branches (Twitter, LinkedIn, IG Carousel, Podcast, Community); "Assemble 10 Content Assets" Code node; writes to Repurposed Content, Twitter Queue, Podcast Queue. Refactor: add Config Loader, config-driven sheet/tab names and WORDPRESS_URL, timezone-aware "today" filter, idempotency check, config-driven format set. Contains HTML strip + extract in "🧹 Clean & Extract Article"; LLM nodes use lmChatOllama (llama3.2, varying temperatures).

### Established patterns
- **Content Log + timezone** — Use TIMEZONE (or CONTENT_DAY_TIMEZONE) from config; compute today as YYYY-MM-DD in that timezone; filter Content Log by that date. See PITFALLS.md and Phase 9 / Topic Research for pattern.
- **JSON contracts** — LLM outputs must use success/data/error envelope; Parse & Validate Code node after each LLM; fallback defaults on parse error (see n8n-json-contracts, ollama-json-only rules).
- **Idempotency** — PITFALLS: check "already repurposed this post/date" before append; key can be post_id+date or slug+date.

### Config
- **htg_config.csv** — Has CONTENT_LOG_TAB, WORDPRESS_URL, TIMEZONE. Add repurposing keys as needed (e.g. REPURPOSE_FORMATS, REPURPOSED_CONTENT_TAB, optional queue tab names).

### Integration points
- Workflow runs on schedule (Noon); reads Content Log (today's post); no Execute Workflow callers in scope. Downstream consumers of Repurposed Content / queues are out of scope for Phase 10.
</code_context>

<specifics>
## Specific Ideas

- User delegated most choices to planner ("you decide") for output tabs, idempotency, and LLM execution. Only formats had explicit decisions: config-driven set, all 5 equal, config-driven from the start for future formats.
</specifics>

<deferred>
## Deferred Ideas

- REP-05 (1:10 repurposing, 10 asset types; optional auto-publish to platforms) — future phase. Phase 10 delivers 3–5 config-driven formats and logging only.
- Downstream workflows that consume Repurposed Content or queue tabs — not in Phase 10 scope.
</deferred>

---
*Phase: 10-content-repurposing*  
*Context gathered: 2026-03-13*
