# Phase 9: Competitor Intelligence — Context

**Gathered:** 2026-03-13  
**Status:** Ready for planning

<domain>
## Phase Boundary

Competitor workflow runs on schedule (e.g. every 3h), reads RSS + Reddit from config (COMPETITOR_RSS_FEEDS and Reddit list from config), writes deduplicated, recency-ordered trend list to a config-driven Sheet tab. Uses Config Loader first; delay/IF after each HTTP to avoid 429/blocking. No hardcoded YOUR_GOOGLE_SHEET_ID or sheet names (COMP-01–COMP-04).
</domain>

<decisions>
## Implementation Decisions

### Output (tabs and shape)
- **Claude's discretion:** One trend list tab vs multiple tabs (Content Ideas Queue, Backlink Opportunities); row meaning (per trend item vs per opportunity); column set (minimal vs richer); append-only vs replace per run. COMP-02 specifies "config-driven Sheet tab" (singular) for the trend list.

### Reddit source list
- **Claude's discretion:** Use same REDDIT_SUBREDDITS as Topic Research vs separate COMPETITOR_REDDIT_SUBREDDITS; fallback when key missing/empty; public Reddit JSON vs RapidAPI; min upvotes / limit per sub (keep current or make configurable).

### Google Trends and Ahrefs
- **Google Trends:** Keep as best-effort. Call Google Trends; on non-200 or parse error, log and continue; trend list may include a Google Trends section when it works.
- **Ahrefs, Trends row shape, other APIs:** Claude's discretion (e.g. Ahrefs in or out of Phase 9; how Trends rows appear in the list; no other APIs required for Phase 9).

### Deduplication and recency
- **Claude's discretion:** Dedupe key (URL vs normalised title vs both); sort date (published vs detected_at); single merged list vs sections; cap on list size per run or no cap.

### Claude's Discretion
- All areas above where "Claude's discretion" is noted: planner chooses concrete options (tabs, columns, Reddit key, API usage, dedupe/sort/merge/cap) and documents them in the plan.
</decisions>

<code_context>
## Existing Code Insights

### Reusable assets
- **core/01_Config_Loader.json** — Execute at start; read config from `$('⚙️ Load Config').item.json` (GOOGLE_SHEET_ID, COMPETITOR_RSS_FEEDS, etc.). Config source is n8n Data Table populated from htg_config.csv.
- **content/02_Topic_Research_Engine_v2.json** — Pattern: Config Loader first, then `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID` and config-driven sheet names for Google Sheets nodes.

### Existing competitor workflow
- **growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json** — Has 3h trigger; hardcoded competitor list + subreddits in "🎯 Load Monitoring Targets" Code node; no Config Loader; no delay between HTTPs; YOUR_GOOGLE_SHEET_ID and fixed sheet names ("Competitor Intelligence", "Content Ideas Queue", "Backlink Opportunities"). Reddit already uses User-Agent: HowToGenie-Bot/1.0. Contains Google Trends and Ahrefs nodes. Primary refactor: add Config Loader, read COMPETITOR_RSS_FEEDS (and Reddit list) from config, config-driven sheet/tab, delay + IF after each HTTP.

### Config
- **htg_config.csv** — Already has COMPETITOR_RSS_FEEDS, REDDIT_SUBREDDITS, RAPIDAPI_REDDIT_HOST/BASE. Add COMPETITOR_INTEL_TAB (or equivalent) for trend list tab name; planner may add other keys (e.g. CONTENT_IDEAS_QUEUE_TAB) if multiple tabs chosen.

### Integration points
- Workflow is called by schedule only (no Execute Workflow from Orchestrator in scope). Topic Research / Orchestrator reading the trend list is deferred (COMP-05).
</code_context>

<specifics>
## Specific Ideas

- User delegated most choices to planner ("you decide"). Google Trends explicitly kept as best-effort; all other output shape, Reddit source, Ahrefs, dedupe/recency/merge/cap left to planner.
</specifics>

<deferred>
## Deferred Ideas

- COMP-05 (competitor trend list feeds into Topic Research / Orchestrator) — future phase. Phase 9 delivers the trend list to a sheet tab only.
- Backlink Opportunities / Ahrefs — in or out of Phase 9 is planner's discretion; if out, defer to a later phase.
</deferred>

---
*Phase: 09-competitor-intelligence*  
*Context gathered: 2026-03-13*
