# Phase 9: Competitor Intelligence — Research

**Researched:** 2026-03-13  
**Domain:** n8n workflow refactor — config-driven RSS/Reddit/Sheets, rate limiting, deduplication  
**Confidence:** HIGH

## Summary

Phase 9 refactors the existing Competitor Intelligence workflow to use the project’s Config Loader pattern, config-driven RSS and Reddit source lists, a config-driven Sheet tab for the trend list, and delay/IF after each HTTP call to avoid 429/blocking. The current workflow hardcodes competitors, subreddits, `YOUR_GOOGLE_SHEET_ID`, and three sheet names; it has no delay between HTTP requests and no response validation. Research is anchored in the existing codebase (Config Loader, Topic Research, htg_config.csv) and n8n docs for Wait node and rate limiting.

**Primary recommendation:** Add Config Loader as first step (Execute Workflow → `$('⚙️ Load Config').item.json`). Parse COMPETITOR_RSS_FEEDS and Reddit list from config (with fallbacks). Use Loop Over Items + HTTP Request + IF (status) + Wait (e.g. 2s) per source; merge all branches into one list, dedupe by URL (or URL + normalised title), sort by date, write to a single config-driven tab. Add COMPETITOR_INTEL_TAB to htg_config.csv. Keep Google Trends as best-effort (IF after fetch, continue on non-200/parse error). Defer or gate Ahrefs/Backlink branch per CONTEXT.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- Competitor workflow runs on schedule (e.g. every 3h), reads RSS + Reddit from config (COMPETITOR_RSS_FEEDS and Reddit list from config), writes deduplicated, recency-ordered trend list to a config-driven Sheet tab.
- Config Loader first; delay/IF after each HTTP to avoid 429/blocking.
- No hardcoded YOUR_GOOGLE_SHEET_ID or sheet names (COMP-01–COMP-04).
- Google Trends: best-effort only; on non-200 or parse error, log and continue; trend list may include a Google Trends section when it works.

### Claude's Discretion
- One trend list tab vs multiple tabs; row meaning; column set; append-only vs replace per run.
- Reddit: same REDDIT_SUBREDDITS as Topic Research vs separate COMPETITOR_REDDIT_SUBREDDITS; fallback when key missing/empty; public Reddit JSON vs RapidAPI; min upvotes/limit per sub.
- Ahrefs, Trends row shape, other APIs (in or out of Phase 9).
- Dedupe key (URL vs normalised title vs both); sort date (published vs detected_at); single merged list vs sections; cap on list size per run or no cap.

### Deferred Ideas (OUT OF SCOPE)
- COMP-05 (competitor trend list feeds into Topic Research / Orchestrator) — future phase. Phase 9 delivers the trend list to a sheet tab only.
- Backlink Opportunities / Ahrefs — in or out of Phase 9 is planner's discretion; if out, defer to a later phase.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| COMP-01 | Competitor workflow runs on schedule (e.g. every 3h) and reads RSS + Reddit from config (COMPETITOR_RSS_FEEDS) | Existing workflow has Schedule Trigger `0 */3 * * *`; Config Loader returns single item with config; COMPETITOR_RSS_FEEDS and REDDIT_SUBREDDITS already in htg_config.csv. Use Execute Workflow → Config Loader first; parse feeds/subs from config. |
| COMP-02 | Competitor workflow writes deduplicated, recency-ordered trend list to config-driven Sheet tab | Single tab name from config (add COMPETITOR_INTEL_TAB to htg_config.csv). Google Sheets node: documentId = `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`, sheetName = config key. Dedupe in Code node (e.g. by URL); sort by published/detected_at; write one row per trend (columns at planner’s discretion). |
| COMP-03 | Competitor workflow uses Config Loader first; delay/IF after each HTTP to avoid 429/blocking | Config Loader pattern: Topic Research + SEO Interlinking use Execute Workflow then `$('⚙️ Load Config').item.json`. Rate limiting: n8n Wait node "After Time Interval" (e.g. 2s); IF node after each HTTP to check status code; on non-2xx continue with empty/error object and log. |
| COMP-04 | No hardcoded YOUR_GOOGLE_SHEET_ID or sheet names; config-gated | All sheet IDs and tab names from `$('⚙️ Load Config').item.json` (e.g. GOOGLE_SHEET_ID, COMPETITOR_INTEL_TAB). No literal YOUR_* or fixed tab strings in workflow JSON. |
</phase_requirements>

## Standard Stack

### Core
| Library / Component | Version / Ref | Purpose | Why Standard |
|--------------------|----------------|---------|--------------|
| n8n (workflow engine) | Project default | Orchestration, HTTP, Code, Schedule, Google Sheets | Project standard; existing workflows in repo |
| core/01_Config_Loader.json | Existing | Load config from Data Table (htg_config + secrets) into single `{ json: config }` | Single source of truth; used by Topic Research, SEO Interlinking, Viral Amplifier |
| htg_config.csv | Repo root | Key/value config (GOOGLE_SHEET_ID, COMPETITOR_RSS_FEEDS, REDDIT_SUBREDDITS, etc.) | Loaded by Config Loader; add COMPETITOR_INTEL_TAB |
| Google Sheets node | n8n built-in | Read/write trend list | Project data backbone; config-driven documentId + sheetName |

### Supporting
| Component | Purpose | When to Use |
|-----------|---------|-------------|
| n8n Wait node | Delay between HTTP requests | After each RSS/Reddit/Trends fetch to avoid 429 |
| n8n IF node | Check HTTP status / parse success | After every HTTP Request; branch to parse vs error path |
| Code node (plain JS) | Parse RSS/Reddit/JSON, dedupe, sort, build rows | No external libs; return `[{ json }]` or `[{ json }, ...]` |
| Execute Workflow node | Call Config Loader subworkflow | First node after Schedule Trigger; then reference `$('⚙️ Load Config').item.json` |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Config Loader subworkflow | Inline Data Table read + Build Config | Subworkflow already used everywhere; keeps pattern consistent |
| Public Reddit JSON API | RapidAPI Reddit | CONTEXT leaves choice to planner; RAPIDAPI_* already in config; public API simpler, no key |
| Single trend list tab | Multiple tabs (Content Ideas, Backlink) | COMP-02 specifies one config-driven tab for trend list; extra tabs at planner’s discretion |

**Installation:** No new packages. n8n workflows are JSON; config is CSV + n8n Data Table.

## Architecture Patterns

### Recommended flow (high level)
1. Schedule Trigger (e.g. `0 */3 * * *`) → Execute Workflow (Config Loader).
2. Code: From config get COMPETITOR_RSS_FEEDS (split by comma), Reddit list (REDDIT_SUBREDDITS or COMPETITOR_REDDIT_SUBREDDITS with fallback). Output one item per RSS URL and one per subreddit (and optionally one for Google Trends).
3. Loop Over Items (batch size 1) or equivalent: for each item, HTTP Request → IF (status 2xx) → Parse (Code) → [optional Wait] → next. On IF false: log error, pass through empty/error object.
4. Merge all branches into one list (Merge node or Code that aggregates).
5. Code: Dedupe (e.g. by URL), sort by date (published or detected_at), optionally cap length. Shape rows for Sheets (columns per planner).
6. Google Sheets: append or replace (planner choice). documentId = `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`, sheetName = `$('⚙️ Load Config').item.json.COMPETITOR_INTEL_TAB`.

### Pattern 1: Config Loader first, then config reference
**What:** Run Config Loader subworkflow; downstream nodes read config via `$('⚙️ Load Config').item.json.<KEY>`.
**When:** Any workflow that needs GOOGLE_SHEET_ID, tab names, or other keys.
**Example (from Topic Research / project):**
- Trigger → Execute Workflow (workflowId = Config Loader).
- Next node: Code uses `$input.first().json` (the config object) or later nodes use `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`, `$('⚙️ Load Config').item.json.BLOG_IDEA_TAB`.
- Google Sheets node: documentId `={{ $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID }}`, sheetName `={{ $('⚙️ Load Config').item.json.<TAB_KEY> }}`.

### Pattern 2: Delay + IF after HTTP
**What:** After each HTTP Request, IF node checks response status (or body); on success go to parse, on failure go to error path (log, return empty/structured error). Optionally Wait node before next HTTP to avoid rate limits.
**When:** Any workflow that hits multiple external APIs (RSS, Reddit, Google Trends).
**Example (n8n docs / community):** Loop Over Items (size 1) → HTTP Request → IF (e.g. `$json.statusCode === 200` or body exists) → Parse → Wait (After Time Interval, 2 seconds) → loop back. IF false → Set/Code to record error, then continue or merge.

### Pattern 3: Parse RSS in Code node (no external lib)
**What:** In Code node, read `$input.first().json` (raw response body as string or object), use regex or string split to extract `<item>` blocks and then title, link, pubDate, description. Return array of items `{ title, url, published, snippet, detected_at, source }`.
**When:** RSS feeds (competitor blogs). Project rules: plain JS only, no require/import.
**Example (from existing competitor workflow):**
- `const xml = $input.first().json;` (or `.body` / `.data` depending on HTTP node output).
- Match `<item>...</item>`, then per item extract title, link, pubDate, description; trim and push to array.
- `return items.map(i => ({ json: i }));`

### Anti-Patterns to Avoid
- **Hardcoding sheet ID or tab names:** Always use config keys (e.g. COMPETITOR_INTEL_TAB) and `$('⚙️ Load Config').item.json.*`.
- **No delay between many HTTP calls:** Causes 429 or IP blocking; use Wait (e.g. 2s) between requests when iterating over feeds/subs.
- **Ignoring HTTP errors:** Add IF after HTTP; on non-2xx or parse failure, don’t assume valid data; pass structured error or empty array and continue.
- **Using YOUR_* placeholders:** Project rule: no YOUR_* in workflow JSON; use credentials and config only.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Config storage | Custom env or separate service | htg_config.csv + Config Loader (Data Table) | Project standard; single source of truth |
| Rate limiting | Custom backoff in Code only | n8n Wait node (After Time Interval) after each HTTP | Native, persists across batches; clear in UI |
| RSS parsing | External npm XML parser | Code node with string/regex extraction (existing pattern) | No external libs in n8n Code nodes; current workflow already does this |
| Deduplication | External DB or cache | Code node: normalise URL, use Map/Set by URL (or URL+title), then sort | Single run; no cross-run state required for COMP-02 |

**Key insight:** The phase is a refactor plus hardening (config, delay, IF). Reuse existing RSS/Reddit parse logic from the current competitor workflow; replace hardcoded lists and sheet refs with config.

## Common Pitfalls

### Pitfall 1: Execute Workflow output shape
**What goes wrong:** Caller expects `$('⚙️ Load Config').item.json` but gets undefined or wrong structure.
**Why:** Execute Workflow returns the **last node’s output** of the subworkflow. Config Loader’s last node returns `[{ json: config }]`, so the parent receives one item; `$('⚙️ Load Config').item.json` is the config object. If the subworkflow had multiple items or a different last node, reference would break.
**How to avoid:** Keep Config Loader’s final node as a single item with the full config object. In competitor workflow, use exactly one Execute Workflow node and reference it by the same name (e.g. `⚙️ Load Config`).
**Warning signs:** Downstream nodes fail with “cannot read property GOOGLE_SHEET_ID of undefined”.

### Pitfall 2: RSS feed URL format
**What goes wrong:** Some sites use `/feed`, others `/rss` or `/feed/`; appending `/feed/` to base URL (current workflow) may 404.
**Why:** COMPETITOR_RSS_FEEDS in htg_config.csv is already a comma-separated list of full URLs (e.g. `https://competitor1.com/feed`). Using these as-is avoids guessing; do not derive from a base URL unless the config stores base URLs.
**How to avoid:** Store full feed URLs in COMPETITOR_RSS_FEEDS; in Code node split by comma and trim; pass each URL to HTTP Request as-is.
**Warning signs:** 404 or empty parse for some competitors.

### Pitfall 3: Reddit API and User-Agent
**What goes wrong:** Reddit returns 429 or blocks requests without a proper User-Agent.
**Why:** Reddit’s API guidelines require a descriptive User-Agent. Current workflow already uses `User-Agent: HowToGenie-Bot/1.0`.
**How to avoid:** Keep User-Agent header on Reddit HTTP Request; use delay between subreddit requests if looping.
**Warning signs:** 429 or empty response from Reddit.

### Pitfall 4: Google Trends response format
**What goes wrong:** Parse error or crash when processing Google Trends response.
**Why:** Response may have `)]}'` prefix or non-JSON; API may change or block.
**How to avoid:** CONTEXT says best-effort: after HTTP, IF status 200 and body parseable; in Code strip `)]}'` and try JSON.parse; on catch return empty array. Do not fail the whole run.
**Warning signs:** Workflow fails at Parse Google Trends; no trends in sheet.

## Code Examples

### Getting config and building RSS + Reddit list (Code node after Config Loader)
```javascript
// After Execute Workflow (Config Loader). Input: one item with config object.
const config = $input.first().json;
const sheetId = config.GOOGLE_SHEET_ID || '';
const tabName = config.COMPETITOR_INTEL_TAB || 'Competitor Intelligence';

const feedUrls = (config.COMPETITOR_RSS_FEEDS || '')
  .split(',').map(s => s.trim()).filter(Boolean);
const subreddits = (config.REDDIT_SUBREDDITS || config.COMPETITOR_REDDIT_SUBREDDITS || '')
  .split(',').map(s => s.trim()).filter(Boolean);

const items = [
  ...feedUrls.map(url => ({ json: { type: 'rss', url } })),
  ...subreddits.map(s => ({ json: { type: 'reddit', subreddit: s.startsWith('r/') ? s : 'r/' + s } }))
];
if (items.length === 0) return [{ json: { no_sources: true, config: { sheetId, tabName } } }];
return items;
```

### Dedupe and sort trend list (Code node before Sheets)
```javascript
const all = $input.all().map(i => i.json).filter(r => r && r.url);
const byUrl = new Map();
for (const r of all) {
  const url = (r.url || '').trim();
  if (!url) continue;
  const existing = byUrl.get(url);
  if (!existing || (r.published && (!existing.published || r.published > existing.published)))
    byUrl.set(url, r);
}
const sorted = [...byUrl.values()].sort((a, b) => {
  const da = a.published || a.detected_at || '';
  const db = b.published || b.detected_at || '';
  return db.localeCompare(da);
});
return sorted.slice(0, 500).map(r => ({ json: r })); // optional cap
```

### Google Sheets node (config-driven)
- documentId: `={{ $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID }}`
- sheetName: `={{ $('⚙️ Load Config').item.json.COMPETITOR_INTEL_TAB }}`
- operation: append (or replace at planner’s discretion)
- columns: map trend fields (e.g. title, url, source, published, detected_at) to sheet columns.

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Hardcoded competitor list in Code node | COMPETITOR_RSS_FEEDS from config | Phase 9 | Single place to edit feeds (htg_config.csv) |
| YOUR_GOOGLE_SHEET_ID + fixed tab names | GOOGLE_SHEET_ID + COMPETITOR_INTEL_TAB from config | Phase 9 | No placeholders; config-gated |
| No delay between HTTP calls | Wait node after each HTTP (or per batch) | Phase 9 | Fewer 429s and blocks |
| No IF after HTTP | IF node checks status; error path continues | Phase 9 | Robust to single-source failures |

**Deprecated/outdated:** Keeping literal "YOUR_GOOGLE_SHEET_ID" or "Competitor Intelligence" in workflow JSON is out of scope; COMP-04 forbids it.

## Open Questions

1. **Ahrefs / Backlink branch**  
   - What we know: CONTEXT says in or out of Phase 9 is planner’s discretion; current workflow has Ahrefs nodes and YOUR_AHREFS_API_KEY.  
   - What’s unclear: Whether to remove, gate by config key, or keep and add IF/credentials.  
   - Recommendation: Planner either removes Ahrefs from Phase 9 scope and defers, or adds a config key (e.g. AHREFS_ENABLED) and credentials reference; no YOUR_*.

2. **Replace vs append for trend list tab**  
   - What we know: COMP-02 says “writes deduplicated, recency-ordered trend list” to one tab.  
   - What’s unclear: Replace entire tab each run vs append new rows.  
   - Recommendation: Replace per run is simpler and keeps the sheet as “current trend snapshot”; append requires dedupe in sheet or by run id. Planner to document choice.

3. **Config Loader source (Data Table vs CSV)**  
   - What we know: Config Loader in repo uses n8n Data Table nodes (htg_config, htg_secrets) with fixed IDs. htg_config.csv is the repo/documentation source of keys.  
   - What’s unclear: Whether production n8n instance syncs CSV to Data Table or edits tables directly.  
   - Recommendation: Add COMPETITOR_INTEL_TAB to htg_config.csv and document it in docs/HOWTOGENIE.md; planner assumes config is available via existing Config Loader.

## Validation Architecture

`workflow.nyquist_validation` is true in `.planning/config.json`. This section supports creation of 09-VALIDATION.md.

### Test Framework
| Property | Value |
|----------|--------|
| Framework | No automated tests for n8n workflows in repo |
| Config file | — |
| Quick run command | Manual: import workflow in n8n, run with test config |
| Full suite command | — |

n8n workflows are validated by: (1) JSON valid and importable, (2) no YOUR_* or hardcoded sheet IDs/tab names in JSON, (3) manual run with Config Loader and test sheet. Laravel has `tests/TestCase.php` but no n8n-specific tests.

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| COMP-01 | Schedule + config-driven RSS/Reddit sources | manual | — | N/A |
| COMP-02 | Deduplicated, recency-ordered list to config-driven tab | manual | — | N/A |
| COMP-03 | Config Loader first; delay + IF after each HTTP | manual / grep | `grep -L "YOUR_GOOGLE_SHEET_ID" growth/*Competitor*.json` and inspect for Wait/IF | ✅ workflow file |
| COMP-04 | No hardcoded sheet ID or tab names | grep | `grep -E "YOUR_GOOGLE_SHEET_ID|Competitor Intelligence|Content Ideas Queue|Backlink" growth/*Competitor*.json` → expect no matches | ✅ workflow file |

### Sampling Rate
- **Per task commit:** Grep checks for placeholders and config usage.
- **Per wave merge:** Manual run in n8n with test config and sheet.
- **Phase gate:** No YOUR_* in workflow; manual run writes to config-driven tab.

### Wave 0 Gaps
- No n8n workflow test runner in repo. Validation is manual + grep/JSON checks.
- 09-VALIDATION.md can specify: (1) checklist for manual run (Config Loader, at least one RSS and one Reddit, delay, write to sheet), (2) grep rules for COMP-04, (3) optional JSON schema check for workflow structure.

## Sources

### Primary (HIGH confidence)
- Project: core/01_Config_Loader.json, content/02_Topic_Research_Engine_v2.json, growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json, htg_config.csv, .planning/phases/09-competitor-intelligence/09-CONTEXT.md, .planning/REQUIREMENTS.md
- n8n: Wait node (After Time Interval) — https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.wait
- Execute Workflow output: last node’s output to parent — community/docs (Execute sub-workflow and data)

### Secondary (MEDIUM confidence)
- Rate limiting with Wait + Loop: node-bench.com, n8n docs “Handling API rate limits”
- n8n HTTP Retry On Fail: max 5s delay, 5 tries — custom Wait needed for longer delays

### Tertiary (LOW confidence)
- None.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — project files and existing patterns are the source.
- Architecture: HIGH — Config Loader and Topic Research patterns are in repo; n8n Wait/IF documented.
- Pitfalls: HIGH — from existing competitor workflow and project rules.

**Research date:** 2026-03-13  
**Valid until:** 30 days (stable n8n and project patterns)

---

## RESEARCH COMPLETE

Research is complete. Phase 9 can be planned: refactor competitor workflow to Config Loader, config-driven RSS/Reddit lists and COMPETITOR_INTEL_TAB, delay+IF after each HTTP, single deduplicated recency-ordered trend list to one config-driven tab; Google Trends best-effort; Ahrefs in/out per planner. Validation is manual + grep; no n8n test runner in repo. Planner can produce PLAN.md and 09-VALIDATION.md from this research.
