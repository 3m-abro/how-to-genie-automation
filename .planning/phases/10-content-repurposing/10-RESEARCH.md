# Phase 10: Content Repurposing — Research

**Researched:** 2026-03-13  
**Domain:** n8n workflow refactor — config-driven repurposing, timezone, idempotency, JSON contracts  
**Confidence:** HIGH (codebase + PITFALLS + established satellite patterns)

## Summary

Phase 10 refactors `content/v3.0 — Content Repurposing Engine.json` into a config-first, timezone-aware, idempotent workflow that produces 3–5 platform-native formats from a config-driven set and logs to config-driven tabs. No new stack: reuse Config Loader, Content Log + today filter (same as Multi-Language/Video/WhatsApp), WordPress REST by slug, and existing HTML-strip + LLM-per-format pattern. Critical additions: Config Loader at start; WORDPRESS_URL and all sheet/tab IDs from config; timezone-aware "today" (CONTENT_DAY_TIMEZONE || TIMEZONE); idempotency check before any append (post_id+date or slug+date in Repurposed Content tab); config-driven format list (REPURPOSE_FORMATS) so which formats run is config, not fixed code; and JSON success/data/error envelope + Parse & Validate after each LLM. Extensibility: adding a format later = new config value + one workflow branch (or one loop iteration), no refactor of core logic.

**Primary recommendation:** Refactor in place: insert Config Loader and "Filter today's post" (timezone) after trigger; replace hardcoded sheet/URL with config expressions; add "Read Repurposed Content / check idempotency" before fetch; gate each format branch on REPURPOSE_FORMATS; add Parse & Validate Code node after each LLM; then single write path to Repurposed Content (and optional queue tabs) with idempotency = skip when key already exists.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **Formats (3–5):** Config-driven format set (e.g. REPURPOSE_FORMATS); not a fixed list in code. All 5 formats equal; no priority ordering. Community content: planner's choice (generic "Community content", rename/specialize, or drop for Phase 10). Extensible by config: adding a format later = config change + workflow branch; no code refactor.
- **Output tabs and queues:** Planner's discretion — summary tab shape, queue tabs, config key naming (REPURPOSED_CONTENT_TAB, queue tab keys), column contract.
- **Idempotency:** Planner's discretion — idempotency key (post_id+date vs slug+date), behavior when "already repurposed" (skip run vs skip appends vs update row), where to check, meaning of "date".
- **LLM execution:** Planner's discretion — execution order (sequential vs parallel vs capped parallel), failure behavior per format, retries, model/temperature (single vs per-format).
- **Claude's discretion:** All "Planner's discretion" areas: researcher and planner choose concrete options and document in plan and CONFIG-KEYS / HOWTOGENIE as appropriate.

### Deferred Ideas (OUT OF SCOPE)
- REP-05 (1:10 repurposing, 10 asset types; optional auto-publish to platforms) — future phase. Phase 10 delivers 3–5 config-driven formats and logging only.
- Downstream workflows that consume Repurposed Content or queue tabs — not in Phase 10 scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| REP-01 | Repurposing workflow reads today's post from Content Log (timezone-aware) and produces 3–5 platform-native formats | Config Loader first; Read Content Log with CONTENT_LOG_TAB; Code node "Filter today's post" using CONTENT_DAY_TIMEZONE \|\| TIMEZONE and toLocaleDateString('en-CA', { timeZone }); filter by date + status !== publish_failed. REPURPOSE_FORMATS from config drives which of 3–5 formats run. |
| REP-02 | Repurposing workflow strips HTML and uses LLM per format; logs to Repurposed Content (and queues) in config-driven tabs | Keep "🧹 Clean & Extract Article" pattern (strip HTML, extract title/url/excerpt/headings); one LLM node per format with Parse & Validate after each. Tab names from config (REPURPOSED_CONTENT_TAB, optional queue tab keys); document column contract in plan. |
| REP-03 | Repurposing uses Config Loader and WORDPRESS_URL from config; idempotent (no duplicate append same post/date) | Execute Workflow Config Loader at start; all documentId/sheetName and WordPress URL from $('⚙️ Load Config').item.json. Before append: read Repurposed Content tab (or designated tab), check for idempotency key (post_id+date or slug+date); if exists skip append (or update row per planner choice). |
| REP-04 | Repurposing runs after publish (e.g. Noon); no YOUR_* placeholders | Schedule trigger 0 12 * * * (or orchestrator hour 12). Replace every YOUR_GOOGLE_SHEET_ID and your-blog.com with config expressions; grep workflow JSON for YOUR_ before sign-off. |
</phase_requirements>

## Standard Stack

### Core
| Component | Version / reference | Purpose | Why standard |
|-----------|--------------------|---------|--------------|
| n8n | (instance) | Workflow runtime | Existing; all satellites run in n8n |
| core/01_Config_Loader.json | — | Load htg_config (or Data Table) into execution context | Single source for GOOGLE_SHEET_ID, tab names, WORDPRESS_URL, TIMEZONE, REPURPOSE_FORMATS |
| Ollama (lmChatOllama) | llama3.2:latest (or qwen2.5:7b per rules) | Per-format LLM | Same as existing repurposing and orchestrator agents |
| Google Sheets | — | Content Log (read), Repurposed Content + queues (write) | Data backbone; tab names from config |

### Supporting
| Component | Purpose | When to use |
|-----------|---------|-------------|
| HTTP Request | WordPress REST API GET by slug | Fetch full post body after filtering today's row from Content Log |
| Code (plain JS) | Timezone today, idempotency check, parse LLM JSON, build format list from config | After Config Loader, after Content Log read, after each LLM, before Sheets append |
| IF / Switch | No post today; already repurposed; per-format enable | Early exit and config-driven branching |

### Alternatives considered
| Instead of | Could use | Tradeoff |
|------------|-----------|----------|
| Config Loader sub-workflow | Inline config read | Config Loader is project standard; keeps one place for secrets/config |
| One LLM node with "format" input | One LLM node per format | Per-format allows different prompts/temps and simpler parsing; project already uses per-format nodes |
| Run all 5 formats always | REPURPOSE_FORMATS list | Requirement is 3–5 and config-driven; list allows 3, 4, or 5 and future formats |

**Installation:** None. All components exist in repo (Config Loader, Content Log read pattern, repurposing workflow). Only refactor and new config keys (htg_config.csv / Data Table).

## Architecture Patterns

### Recommended flow (high level)
1. Schedule Trigger (Noon) → Execute Workflow (Config Loader).
2. Read Content Log (documentId/tab from config) → Code "Filter today's post" (timezone-aware, status !== publish_failed).
3. IF no post today → end (no error; optional log).
4. Read Repurposed Content tab (config-driven name) → Code "Already repurposed?" (idempotency key = slug+date or post_id+date). IF already present → skip to end (idempotent).
5. HTTP Request: fetch post by slug from WORDPRESS_URL.
6. Code "Clean & Extract Article" (strip HTML, excerpt, headings).
7. Code "Build format list" from REPURPOSE_FORMATS (e.g. comma-separated: twitter,linkedin,ig_carousel,podcast,community). Output one item per enabled format or route via Switch.
8. Per format: LLM (platform-native prompt) → Parse & Validate (success/data/error envelope) → collect.
9. Merge/collect all format outputs → Code "Build repurposed payload".
10. Append to Repurposed Content (and optional queue tabs); column contract documented.

### Pattern 1: Timezone-aware "today" (Content Log filter)
**What:** Compute today as YYYY-MM-DD in owner timezone; filter Content Log rows by that date and status.  
**When:** Any satellite that reads "today's post" from Content Log.  
**Source:** growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json, Video Production Engine, WhatsApp & Telegram (same pattern).

```javascript
const config = $('⚙️ Load Config').item.json;
const tz = config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC';
const today = new Date().toLocaleDateString('en-CA', { timeZone: tz });
const rows = $input.all().map(i => i.json);
const dateKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'date') || 'date';
const statusKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'status') || 'status';
const urlKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase().includes('url') || k === 'WP URL') || 'wp_url';
const valid = rows.filter(r => {
  const d = (r[dateKey] || r.date || '').toString().slice(0, 10);
  const s = (r[statusKey] || r.status || '').toLowerCase();
  return d === today && s !== 'publish_failed';
});
if (valid.length === 0) return [{ json: { noPostToday: true } }];
const latest = valid[valid.length - 1];
const wpUrl = latest[urlKey] || latest.wp_url || '';
const slug = wpUrl.split('/').filter(Boolean).pop() || '';
return [{ json: { ...latest, slug, noPostToday: false } }];
```

### Pattern 2: Idempotency check before append
**What:** Read Repurposed Content tab; if a row already exists for this post/date key, skip append (or update).  
**When:** Before any Sheets append for repurposed outputs.  
**Source:** PITFALLS.md Pitfall 8; CONTEXT.md.

```javascript
// After reading Repurposed Content tab: rows = $input.all().map(i => i.json)
const config = $('⚙️ Load Config').item.json;
const filterRow = $('Filter today\'s post').item.json;  // or exact node name
const slug = filterRow.slug || (filterRow['WP URL'] || '').split('/').filter(Boolean).pop();
const contentDate = filterRow.date || filterRow.Date || new Date().toLocaleDateString('en-CA', { timeZone: config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC' });
const key = `${slug}|${String(contentDate).slice(0, 10)}`;
const dateCol = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'date') || 'Date';
const urlCol = Object.keys(rows[0] || {}).find(k => k.toLowerCase().includes('url') || k === 'Source URL') || 'Source URL';
const already = rows.some(r => {
  const rSlug = (r[urlCol] || '').split('/').filter(Boolean).pop();
  const rDate = String(r[dateCol] || '').slice(0, 10);
  return `${rSlug}|${rDate}` === key;
});
return [{ json: { alreadyRepurposed: already, slug, contentDate } }];
```

### Pattern 3: Config-driven format list (branching)
**What:** Parse REPURPOSE_FORMATS (e.g. "twitter,linkedin,ig_carousel,podcast,community"); output one item per enabled format for routing.  
**When:** To run only 3–5 formats selected by config; extensible for new format IDs.  
**Source:** growth/HowTo-Genie v4.0 — Competitor Intelligence (Build Source List from COMPETITOR_RSS_FEEDS + REDDIT_SUBREDDITS; Switch by type).

```javascript
const config = $('⚙️ Load Config').item.json;
const raw = (config.REPURPOSE_FORMATS || 'twitter,linkedin,ig_carousel,podcast,community').toString().trim();
const formats = raw ? raw.split(',').map(s => s.trim()).filter(Boolean) : [];
// Output one item per format for downstream Switch/Loop, or drive IF conditions per format
return formats.map(f => ({ json: { format: f } }));
```

### Anti-patterns to avoid
- **Hardcoded sheet/URL:** No YOUR_GOOGLE_SHEET_ID, no your-blog.com; use config expressions everywhere.
- **"Today" without timezone:** Do not use `new Date().toISOString().slice(0,10)` for Content Log filter; use CONTENT_DAY_TIMEZONE || TIMEZONE.
- **Append without idempotency check:** Always check Repurposed Content (or designated tab) for existing post/date before appending.
- **LLM output without validation:** Every LLM node must be followed by a Parse & Validate Code node (success/data/error envelope; fallback defaults on parse error).
- **Node name mismatch in Code:** Any `$('Node Name')` must match the exact node `name` in the workflow (including emoji).

## Don't Hand-Roll

| Problem | Don't build | Use instead | Why |
|---------|-------------|-------------|-----|
| Config storage | Custom config node or env per workflow | Config Loader sub-workflow + $('⚙️ Load Config').item.json | Single source; all satellites use it |
| "Today" in timezone | Server date or UTC slice | toLocaleDateString('en-CA', { timeZone: tz }) with config TIMEZONE | PITFALLS 3, 8; Multi-Language/Video/WhatsApp pattern |
| Idempotency | Append every run | Read tab → check slug+date (or post_id+date) → skip or update | PITFALLS 8; duplicate rows break downstream and logs |
| LLM JSON parsing | Ad hoc regex only | success/data/error envelope + Parse & Validate Code node with fallback | n8n-json-contracts; ollama-json-only rules |
| HTML stripping | External library | Inline Code: replace(/<[^>]*>/g,' '), entities, trim | Existing "Clean & Extract Article"; no deps in n8n Code |

**Key insight:** The project standard is config-first, timezone-explicit, and idempotent for all satellites that write to Sheets. Hand-rolling config or "today" or append logic causes wrong day, wrong sheet, or duplicate rows (PITFALLS 2, 3, 8).

## Common Pitfalls

### Pitfall 1: Hardcoded YOUR_* and wrong WordPress URL (PITFALLS 2, 8)
**What goes wrong:** Workflow still has YOUR_GOOGLE_SHEET_ID or your-blog.com; runs fail or write to wrong place.  
**Why:** Template workflow never wired to Config Loader.  
**How to avoid:** Config Loader first; every Google Sheets node: documentId = `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`, sheetName from config (CONTENT_LOG_TAB, REPURPOSED_CONTENT_TAB, etc.). WordPress URL: `($('⚙️ Load Config').item.json.WORDPRESS_URL || '').replace(/\/$/, '')`. Grep for YOUR_ and your-blog before sign-off.  
**Warning signs:** Literal YOUR_GOOGLE_SHEET_ID or your-blog.com in JSON.

### Pitfall 2: "Today" without timezone (PITFALLS 3, 8)
**What goes wrong:** Repurposing picks wrong row or "no post" when server is UTC and content day is owner TZ.  
**How to avoid:** Filter today's post with CONTENT_DAY_TIMEZONE || TIMEZONE and toLocaleDateString('en-CA', { timeZone }).  
**Warning signs:** Filter uses new Date().toISOString().slice(0,10) or no timezone variable.

### Pitfall 3: No idempotency check (PITFALLS 8)
**What goes wrong:** Re-running at Noon twice appends duplicate repurposed rows.  
**How to avoid:** Before append, read Repurposed Content tab; if slug+date (or post_id+date) already exists, skip append (or update row per planner decision).  
**Warning signs:** No "Already repurposed?" check; direct path from "Assemble" to "Log to Repurposed Content".

### Pitfall 4: LLM output not validated (n8n-json-contracts)
**What goes wrong:** Malformed or prose LLM output breaks Assemble/Log nodes.  
**How to avoid:** After every LLM node, add Code node that parses JSON, validates success/data/error envelope, and returns fallback object on parse error.  
**Warning signs:** Downstream node references $json.tweets or $json.data without a prior Parse & Validate step.

### Pitfall 5: Code node $('Node Name') mismatch
**What goes wrong:** Reference to $('Filter today\'s post') or $('Clean & Extract Article') fails if node name in workflow has emoji or different spelling.  
**How to avoid:** Use exact `name` from workflow JSON in every $('...') reference.  
**Warning signs:** Code node returns empty or undefined for fields that should come from another node.

## Code Examples

### WordPress fetch URL from config
```javascript
// In HTTP Request node URL (expression):
={{ ($('⚙️ Load Config').item.json.WORDPRESS_URL || '').replace(/\/$/, '') }}/wp-json/wp/v2/posts?slug={{ $json.slug }}
```

### Clean & Extract Article (existing pattern; keep, ensure input from HTTP response)
Input: WordPress REST post object. Output: title, url, excerpt, clean_content (strip HTML), word_count, headings, post_id. Limit clean_content length for LLM context (e.g. 5000 chars). Source: content/v3.0 — Content Repurposing Engine.json "🧹 Clean & Extract Article".

### Parse & Validate after LLM (envelope)
```javascript
const raw = $input.first().json.response || $input.first().json.message?.content || '';
let parsed;
try {
  const match = raw.match(/```json\n([\s\S]*?)\n```/) || raw.match(/(\{[\s\S]*\})/);
  parsed = JSON.parse(match ? (match[1] || match[0]) : raw);
  if (parsed.success === false) parsed = { success: false, data: null, error: parsed.error || { code: '', message: '' } };
  else if (!parsed.data && parsed.success !== false) parsed = { success: true, data: parsed, error: null };
} catch (e) {
  parsed = { success: false, data: null, error: { code: 'parse_error', message: String(e.message) } };
}
return [{ json: parsed }];
```

## State of the Art

| Old (current v3.0) | Target (Phase 10) | Impact |
|-------------------|-------------------|--------|
| YOUR_GOOGLE_SHEET_ID, your-blog.com | Config Loader; GOOGLE_SHEET_ID, WORDPRESS_URL, tab names from config | Correct sheet and blog; no placeholders |
| "Content Log" hardcoded; no timezone | CONTENT_LOG_TAB; today = toLocaleDateString(..., timeZone) | Correct "today's post" in owner TZ |
| No idempotency | Read Repurposed Content; skip if slug+date (or post_id+date) exists | No duplicate rows on re-run |
| Fixed 5 LLM branches | REPURPOSE_FORMATS drives which formats run (3–5) | Config-driven; add format = config + branch |
| 10 assets assembled | 3–5 formats; log to Repurposed Content (+ queues per planner) | REP-05 deferred; Phase 10 scope 3–5 |
| No Parse & Validate after LLM | Parse & Validate Code after each LLM; envelope | Robust to LLM prose/markdown |

**Deprecated / avoid:** Keeping any YOUR_* or literal blog URL; using server date for "today"; appending without idempotency check.

## Open Questions

1. **Exact REPURPOSE_FORMATS format**  
   - What we know: Config-driven list; planner decides key name and shape (e.g. comma-separated string).  
   - Unclear: Exact key (REPURPOSE_FORMATS vs REPURPOSE_FORMATS_CSV); default value when missing (e.g. "twitter,linkedin,ig_carousel,podcast,community" or minimal 3).  
   - Recommendation: Planner define in PLAN.md and CONFIG-KEYS; default to current 5 format IDs if key missing.

2. **Merge strategy for parallel format branches**  
   - What we know: Current workflow has 5 parallel LLM nodes feeding one "Assemble" node (connections in v3.0 JSON may be incomplete; Assemble references each by node name).  
   - Unclear: Whether to use n8n Merge node (wait for all) or Loop over formats (sequential).  
   - Recommendation: Planner choose: (a) parallel branches + Merge then single Assemble, or (b) Loop with one LLM chain per format and collect into array then Assemble once. Parallel is faster; Loop is simpler for dynamic format count.

3. **Queue tabs**  
   - What we know: CONTEXT leaves queue tabs (per-format vs single vs none) and column contract to planner.  
   - Unclear: Which queue tabs to support (e.g. Twitter Queue, Podcast Queue only) and config key names.  
   - Recommendation: Planner define in plan; document in HOWTOGENIE.md; optional queue tabs only if in scope.

## Validation Architecture

Phase 10 is an n8n workflow refactor. Automated unit tests for workflow JSON are not in the repo; verification is manual/UAT and checklist-based. The following dimensions and acceptance criteria support creation of 10-VALIDATION.md and UAT.

### Verification dimensions
| Dimension | What to verify | How |
|-----------|----------------|------|
| Config-first | No YOUR_* or your-blog.com; Config Loader runs first; all sheet/URL from config | Grep workflow JSON; run and inspect first nodes |
| Timezone today | Correct "today" in owner TZ; correct row from Content Log | Set TIMEZONE in config; run on day with one post; assert filter picks that row |
| Idempotency | Second run same day does not append duplicate row | Run twice same day; Repurposed Content has one row for that post/date |
| Formats 3–5 | Only enabled formats (from REPURPOSE_FORMATS) run; 3–5 total | Set REPURPOSE_FORMATS to 3 formats; run; assert only 3 format outputs; change to 5 and re-run |
| HTML strip + LLM | Post body stripped of HTML; each format gets LLM output; Parse & Validate used | Inspect Clean & Extract output; each format branch has Parse & Validate; no raw prose to Sheets |
| Schedule | Runs after publish (e.g. Noon) | Schedule trigger 0 12 * * * or orchestrator hour 12 |
| Docs | New config keys and tab names in docs/HOWTOGENIE.md (and htg_config.csv) | Checklist: REPURPOSE_FORMATS, REPURPOSED_CONTENT_TAB, queue tab keys if any |

### UAT / acceptance criteria (for 10-VALIDATION.md)
- **REP-01:** With one published post today (in config timezone), workflow runs at Noon and produces 3–5 format outputs; with no post today, workflow exits without error and does not append.
- **REP-02:** Article content is stripped of HTML before LLM; each format has dedicated LLM + Parse & Validate; Repurposed Content tab (and any queue tabs) receive rows with documented columns.
- **REP-03:** Config Loader is first; GOOGLE_SHEET_ID, CONTENT_LOG_TAB, WORDPRESS_URL, REPURPOSED_CONTENT_TAB from config; re-running same day does not duplicate rows (idempotency key checked).
- **REP-04:** Schedule is Noon (or hour 12); grep of workflow JSON finds no YOUR_GOOGLE_SHEET_ID, no your-blog.com.

### Test framework
| Property | Value |
|----------|--------|
| Framework | Manual / UAT + grep checklist |
| Config file | N/A (n8n workflow) |
| Quick check | `grep -E 'YOUR_|your-blog' "content/v3.0 — Content Repurposing Engine.json"` (expect no matches after refactor) |
| Full verification | Run workflow in n8n twice (same day); inspect Content Log filter, idempotency, Sheets output |

### Phase requirements → verification map
| Req ID | Behavior | Verification type | How |
|--------|----------|-------------------|-----|
| REP-01 | Read today's post (timezone); produce 3–5 formats | UAT | Run with post today; run with no post; check format count from config |
| REP-02 | Strip HTML; LLM per format; log to config-driven tabs | UAT + checklist | Inspect Clean & Extract; Parse & Validate per format; tab names from config |
| REP-03 | Config Loader + WORDPRESS_URL; idempotent | UAT + grep | Config first; URL from config; run twice, no duplicate row |
| REP-04 | Noon; no YOUR_* | Schedule + grep | Cron 0 12 * * *; grep YOUR_ and your-blog |

### Wave 0 gaps
- No automated test suite for n8n workflows in repo. Phase 10 verification is manual run + grep + Sheets inspection. 10-VALIDATION.md should list exact grep commands and UAT steps for each requirement.

## Sources

### Primary (HIGH confidence)
- .planning/phases/10-content-repurposing/10-CONTEXT.md — decisions, code context, deferred
- .planning/REQUIREMENTS.md — REP-01 to REP-04
- content/v3.0 — Content Repurposing Engine.json — current structure, Clean & Extract, LLM nodes, Sheets append
- core/01_Config_Loader.json — config output shape
- growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json — Config Loader, Read Content Log, Filter today's post (timezone)
- growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json — Config Loader, config-driven source list, Switch
- .planning/research/PITFALLS.md — Pitfalls 2, 3, 8 (config, timezone, idempotency)
- .cursor/rules/n8n-rule.mdc, n8n-json-contracts.mdc, ollama-json-only.mdc — conventions

### Secondary (MEDIUM confidence)
- docs/HOWTOGENIE.md — config keys table, schedule, workflow list
- .planning/research/ARCHITECTURE.md, SUMMARY.md — repurposing data flow and config keys

### Tertiary
- growth/HowTo-Genie v4.0 — Video Production Engine.json, WhatsApp & Telegram — same today filter pattern (already cited via Multi-Language)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — same as other v2.0 satellites; no new dependencies.
- Architecture: HIGH — patterns copied from Multi-Language, Competitor, and PITFALLS.
- Pitfalls: HIGH — PITFALLS and CONTEXT explicitly list repurposing pitfalls and fixes.

**Research date:** 2026-03-13  
**Valid until:** 30 days (stable n8n/Sheets patterns).
