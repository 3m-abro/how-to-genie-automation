# Phase 6: Affiliate & SEO Feedback - Research

**Researched:** 2026-03-13
**Domain:** n8n affiliate registry, RSS ingestion, GA4→topic selection, SEO interlinking
**Confidence:** HIGH (in-repo patterns), MEDIUM (external feeds)

## Summary

Phase 6 wires four flows: (1) an **affiliate product registry** (current, niche-relevant) populated and refreshed from Muncheye/CBEngine RSS (and optionally ClickBank); (2) an **Affiliate Link Manager** workflow that runs on a schedule and updates that registry; (3) **GA4 performance data** written to a refresh-candidates list consumed by Agent 1 (Topic Research) for topic selection; (4) the **SEO Interlinking** engine running weekly (e.g. Sunday 3 AM) with config-driven sheet and optional WordPress updates. All depend on Phase 1 (Config Loader, Content Log, orchestrator). The codebase already contains reusable workflows: Config Loader, Affiliate Research v2 (Monday 7 AM, ClickBank + Muncheye HTML + scoring), Affiliate Link Registry (extracts links from article content → AFFILIATE_LINKS_TAB), Viral Amplifier (GA4 runReport → Viral Amplifier tab), and the v4.0 SEO Interlinking engine (hardcoded sheet ID, Build Content Index → LLM → parse → append to "Internal Linking Log" and optional WP PATCH). Planner choices are: registry shape/tab, manager schedule and RSS vs API sources, refresh-candidates storage and who writes it, and SEO output (Sheets only vs Sheets + WP).

**Primary recommendation:** Use Config Loader at the start of every Phase 6 workflow; adopt/adapt `affiliate/10_Affiliate_Research_v2.json` for RSS-based registry refresh (Muncheye RSS + CBEngine parameterized RSS); add a dedicated refresh-candidates writer (new workflow or extend Viral Amplifier) and inject that list into orchestrator’s Build Research Context for Agent 1; wire SEO Interlinking to config (sheet/tab from config, fix YOUR_* placeholders) and decide output target (Sheets only vs Sheets + WP PATCH) per CONTEXT.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- Registry shape and niches (AFF-01): Planner decides — where the registry lives (same sheet vs separate), row shape and columns, at least one active product per niche (bootstrap vs manual seed), niche column values (five fixed vs config-driven).
- Affiliate Link Manager schedule and update policy (AFF-02): Planner decides — schedule (e.g. weekly Monday 7 AM vs daily), append vs upsert/dedupe when writing from RSS, reuse/adapt `affiliate/10_Affiliate_Research_v2.json` vs new workflow, data sources (Muncheye + CBEngine RSS only vs including ClickBank API).
- GA4 → refresh candidates for Agent 1 (SEO-01): Planner decides — where the list lives (new tab vs Content Log columns), threshold(s), who writes it (dedicated workflow vs extend Viral Amplifier), how Agent 1 uses it (read-only vs read + write-back).
- SEO Interlinking output and application (SEO-02): Planner decides — output (Sheets only vs Sheets + direct WP updates), which posts to update if auto-applying, safety/rollback, schedule/config (fixed Sunday 3 AM vs configurable).

### Claude's Discretion
- All of the above: planner chooses concrete design for registry, manager workflow, refresh-candidates flow, and SEO interlinking output/application within the phase boundary.

### Deferred Ideas (OUT OF SCOPE)
- Add more affiliate platforms later: Digistore24, Commission Junction, Warrior Plus, etc. — capture for roadmap/backlog; Phase 6 stays with Muncheye/CBEngine (and any planner-chosen ClickBank use).
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| AFF-01 | Affiliate link registry is populated with current ClickBank/JVZoo products across all 5 niches | Registry tab/shape from config; niches from CONTEXT (productivity, finance, home, health, tech) or config-driven list; bootstrap or manual seed per planner; 10_Affiliate_Research_v2 shows scoring + niche mapping; 15_Affiliate_Link_Registry is for “links in posts” logging, not product registry. |
| AFF-02 | Affiliate Link Manager workflow runs and updates registry from Muncheye/CBEngine RSS feeds | Config Loader → IF enabled → fetch Muncheye RSS (e.g. https://muncheye.com/category/affiliate/other/feed) + CBEngine parameterized RSS (cbengine.com/rss.html?u=...&cbuser=...&r=...); parse; score/filter; write to registry tab (append or upsert per planner). Reuse/adapt 10_Affiliate_Research_v2 (currently HTML + ClickBank API). |
| SEO-01 | GA4 integration feeds performance data back into topic selection (high-traffic topics get refreshed/amplified) | Viral Amplifier already uses GA4 runReport (dateRanges, dimensions pagePath/pageTitle, metrics screenPageViews, averageSessionDuration, engagementRate, bounceRate); config VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN. Reuse same API; write to Refresh Candidates tab (or Content Log columns); orchestrator “Build Research Context” reads that + injects into Agent 1 prompt (currently only reddit_context + existing_keywords from Load Existing Topics). |
| SEO-02 | SEO Interlinking engine runs Sunday 3 AM and updates internal links on published posts | content/v4.0 — SEO Interlinking Intelligence Engine.json: Schedule 0 3 * * 0, read posts (replace YOUR_GOOGLE_SHEET_ID with config), Build Content Index, SplitInBatches(10), LLM “Analyze Linking Opportunities” + “Inject Links Naturally”, Parse → Validate → IF ready_to_update → WP PATCH and/or append to “Internal Linking Log”. Add Config Loader; sheet/tab from config; output Sheets and/or WP per planner. |
</phase_requirements>

## Standard Stack

### Core
| Library / Component | Version / Ref | Purpose | Why Standard |
|--------------------|---------------|---------|--------------|
| n8n | (instance) | Workflow orchestration | Project standard; all automation in n8n. |
| core/01_Config_Loader.json | (sub-workflow) | Runtime config (Key/Value) | Single source; GOOGLE_SHEET_ID, tab names, enable flags, GA4/affiliate keys. |
| Google Sheets | (single doc) | Registry, Content Log, Refresh Candidates, Interlink log | Data backbone; no SQL. |
| Ollama | llama3.2 / qwen2.5:7b | LLM for scoring, interlinking prompts | Local; JSON-only per .cursor/rules. |

### Supporting
| Component | Purpose | When to Use |
|-----------|---------|-------------|
| affiliate/10_Affiliate_Research_v2.json | Reference: Config Loader, ClickBank API, Muncheye fetch, merge, Halal filter, Ollama score, digest | Adapt for RSS-based fetch and registry write. |
| affiliate/15_Affiliate_Link_Registry.json | Extract affiliate links from article content → AFFILIATE_LINKS_TAB | Use for “links found in posts” logging only; product registry is separate. |
| growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json | GA4 runReport, threshold filter, append to tab | Reuse GA4 pattern and optionally extend to write Refresh Candidates. |
| content/v4.0 — SEO Interlinking Intelligence Engine.json | Build Content Index, LLM batches, parse, WP update, Internal Linking Log | Wire to Config Loader; fix YOUR_*; choose Sheets vs WP output. |

### Config keys (Phase 6)
- **Registry / Manager:** `GOOGLE_SHEET_ID`, `AFFILIATE_REGISTRY_TAB` (or same sheet + tab name), `AFFILIATE_MANAGER_ENABLED`, `NICHES` (optional), `HALAL_FILTER_KEYWORDS`, `MIN_CLICKBANK_*` if using ClickBank.
- **Refresh candidates:** `REFRESH_CANDIDATES_TAB` (or store in Content Log columns), `GA4_PROPERTY_ID`, `GOOGLE_ANALYTICS_TOKEN`; optional thresholds (e.g. reuse `VIRAL_VIEWS_7D_MIN`, `VIRAL_ENGAGEMENT_MIN` or separate).
- **SEO Interlinking:** `CONTENT_LOG_TAB`, `INTERLINK_RECOMMENDATIONS_TAB` or `INTERNAL_LINKING_LOG_TAB`, `SEO_INTERLINKING_ENABLED`, `WORDPRESS_URL`; schedule in node (e.g. `0 3 * * 0`) or configurable day/time per planner.

### Alternatives considered
| Instead of | Could use | Tradeoff |
|------------|-----------|----------|
| Muncheye RSS | Muncheye HTML (as in v2) | RSS is more stable for parsing; HTML scraping is fragile. |
| CBEngine parameterized RSS | ClickBank API only | CBEngine requires Pro/site key; ClickBank API already in v2. |
| New “Refresh Candidates” tab | Content Log extra columns | New tab keeps Content Log shape stable; columns avoid extra read. |

**Installation:** No new npm/pip packages; n8n workflows only. Ensure Config Loader workflow ID is set in Execute Workflow nodes where used.

## Architecture Patterns

### Recommended flow order
1. **Config Loader** (Execute Workflow) → **IF enabled** → continue else stop.
2. **Affiliate Manager:** Schedule → Config Loader → fetch Muncheye RSS + CBEngine RSS (and optionally ClickBank) → parse → merge/dedupe → score/filter (Ollama optional) → write to registry tab (append or upsert/dedupe by product key).
3. **Refresh candidates:** GA4 runReport (same as Viral Amplifier) → apply threshold → write to Refresh Candidates tab (or Content Log); **orchestrator** “Build Research Context” (or new prep node) reads that tab and adds `refresh_candidates` (e.g. titles/keywords/URLs) to the payload passed to Agent 1.
4. **SEO Interlinking:** Schedule (e.g. Sunday 3 AM) → Config Loader → read Content Log (config sheet + CONTENT_LOG_TAB) → Build Content Index → SplitInBatches → LLM analyze → parse → write to Interlink Recommendations / Internal Linking Log; if planner chooses WP updates, add PATCH branch with WORDPRESS_URL from config.

### Pattern: Config-first and tab names from config
- Every Phase 6 workflow starts with Execute Workflow → Config Loader.
- All Google Sheets nodes use `documentId: $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID` (or SPREADSHEET_ID) and `sheetName: $('⚙️ Load Config').item.json.<TAB_KEY> || 'Default Tab Name'`.
- No hardcoded YOUR_GOOGLE_SHEET_ID or your-blog.com in workflow JSON.

### Pattern: Parse & Validate after every LLM
- After each LLM node: Code node that strips ```json, parses, validates required fields, returns fallback envelope on catch (success: false, data: null, error: { code, message }).
- Per .cursor/rules/n8n-json-contracts.mdc: strict schema; no partial objects.

### Pattern: GA4 runReport (from Viral Amplifier)
- POST `https://analyticsdata.googleapis.com/v1beta/properties/{{ GA4_PROPERTY_ID }}:runReport`
- Headers: `Authorization: Bearer {{ GOOGLE_ANALYTICS_TOKEN }}`
- Body: dateRanges (e.g. 7daysAgo–today), dimensions (pagePath, pageTitle), metrics (screenPageViews, averageSessionDuration, engagementRate, bounceRate), orderBys screenPageViews desc, limit 50.
- Code node: map rows to flat objects; filter by config thresholds; output one item per row for Sheets append.

### Anti-patterns to avoid
- **Hardcoding sheet ID or tab names:** Use config keys only.
- **Using 15_Affiliate_Link_Registry as the product registry:** That workflow logs “links found in articles”; the registry is “products per niche” for Agent 1/4 to pick from.
- **Skipping IF enabled gate:** Each satellite should check an enable flag from config and exit cleanly when false.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Config storage | Custom env or hardcoded IDs | Config Loader (htg_config / data tables) | Single source; same pattern as Phases 2–5. |
| GA4 reporting | Custom analytics client | GA4 Data API runReport (HTTP Request) as in Viral Amplifier | Correct auth, dimensions, and limits. |
| RSS parsing | Custom feed parser service | HTTP Request + Code node (parse XML/JSON from feed response) | n8n-native; no extra services. |
| WordPress post update | Custom WP client | WordPress REST API PATCH (or POST) with wordpressApi credential | Existing pattern in SEO Interlinking workflow. |

**Key insight:** The repo is n8n + Sheets + Ollama only; no separate app is required for affiliate or SEO feedback. Reuse existing nodes and sub-workflows.

## Common Pitfalls

### Pitfall 1: Confusing “Affiliate Link Registry” with “Affiliate product registry”
- **What goes wrong:** Using `15_Affiliate_Link_Registry` (AFFILIATE_LINKS_TAB) as the product registry.
- **Why:** 15_ logs links extracted from article content. AFF-01/AFF-02 require a **product** registry (products per niche) for topic/SEO agents to choose from.
- **How to avoid:** Define a separate tab (e.g. AFFILIATE_REGISTRY_TAB) and workflow that writes rows like: product name, platform, commission, url, niche, score, date_found, status.

### Pitfall 2: Hardcoded YOUR_* in SEO Interlinking workflow
- **What goes wrong:** content/v4.0 — SEO Interlinking has `documentId: "YOUR_GOOGLE_SHEET_ID"` and WP update URL with `your-blog.com`.
- **Why:** Legacy placeholder.
- **How to avoid:** Replace with `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID` and WORDPRESS_URL; add Config Loader as first node and read sheet/tab from config.

### Pitfall 3: Load Existing Topics vs Refresh Candidates
- **What goes wrong:** Orchestrator “📡 Load Existing Topics” reads BLOG_IDEA_TAB and feeds `existing_keywords` into Build Research Context. Refresh candidates are a different input (high-traffic topics to prioritize or refresh).
- **Why:** existing_keywords = “don’t repeat”; refresh_candidates = “consider refreshing or amplifying these.”
- **How to avoid:** Add a separate read of Refresh Candidates tab (or columns) and merge a `refresh_candidates` string/list into the same context object that Build Research Context (or a new node) passes to Agent 1’s user_message.

### Pitfall 4: Muncheye HTML vs RSS
- **What goes wrong:** 10_Affiliate_Research_v2 fetches Muncheye homepage HTML and regex-scrapes; fragile.
- **Why:** CONTEXT specifies “Muncheye/CBEngine RSS”. Muncheye offers category RSS (e.g. `https://muncheye.com/category/affiliate/other/feed`).
- **How to avoid:** Use HTTP Request to Muncheye RSS URL; parse XML in Code node (item title, link, description); map to same row shape as registry.

### Pitfall 5: CBEngine feed URL
- **What goes wrong:** CLAUDE.md lists `https://www.cbengine.com/feeds/cbengine.xml`; that URL returned 404 on fetch (2026-03-13).
- **Why:** CBEngine uses parameterized RSS: `https://cbengine.com/rss.html?u=[site_key]&cbuser=[affiliate_id]&r=[records]&c=[category]&q=[search_term]` (Pro/site key required).
- **How to avoid:** Document CBEngine as optional; use Muncheye RSS as primary; if CBEngine is used, add config keys for site key and affiliate id and build URL in Code node.

## Code Examples

### Config Loader usage (existing)
```javascript
// In any workflow: first node Execute Workflow → core/01_Config_Loader.json
// Then reference: $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID
// Sheet read: documentId = $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID,
//             sheetName = $('⚙️ Load Config').item.json.AFFILIATE_REGISTRY_TAB || 'Affiliate Registry'
```

### GA4 runReport body (from Viral Amplifier)
```json
{
  "dateRanges": [{ "startDate": "7daysAgo", "endDate": "today" }],
  "dimensions": [{ "name": "pagePath" }, { "name": "pageTitle" }],
  "metrics": [
    { "name": "screenPageViews" },
    { "name": "averageSessionDuration" },
    { "name": "engagementRate" },
    { "name": "bounceRate" }
  ],
  "orderBys": [{ "metric": { "metricName": "screenPageViews" }, "desc": true }],
  "limit": 50
}
```

### Build Research Context extension (conceptual)
- Current: `reddit_context`, `existing_keywords`, `today`, `total_existing` from Load Existing Topics + Fetch Reddit.
- Add: read Refresh Candidates tab (or Content Log columns) in a separate node or merge into the same branch; build `refresh_candidates` (e.g. list of titles or keywords).
- Pass to Agent 1: e.g. append to user_message: “TOPICS TO PRIORITIZE (refresh/amplify): {{ refresh_candidates }}”.

### SEO Interlinking: sheet/tab from config
- Replace `documentId: "YOUR_GOOGLE_SHEET_ID"` with `documentId: $('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`.
- Replace `sheetName: "Internal Linking Log"` with `sheetName: $('⚙️ Load Config').item.json.INTERNAL_LINKING_LOG_TAB || 'Internal Linking Log'`.
- Ensure “Load All Published Posts” also uses config for documentId and tab (e.g. CONTENT_LOG_TAB or dedicated “Content Library” tab).

## State of the Art

| Old Approach | Current Approach | Impact |
|--------------|------------------|--------|
| Hardcoded sheet ID in workflows | Config Loader + tab keys in htg_config | Phase 1+; all new workflows use config. |
| Muncheye HTML scrape | Muncheye RSS (category feed) | More reliable; use RSS when available. |
| No GA4 → topic selection | Refresh Candidates tab + Agent 1 context | SEO-01; high-traffic topics feed back into topic choice. |

**Deprecated / avoid:** Static YOUR_* placeholders; posting to WP without WORDPRESS_URL from config.

## Open Questions

1. **CBEngine Pro / site key**
   - What we know: CBEngine RSS is parameterized and may require Pro; static cbengine.xml URL 404.
   - What’s unclear: Whether project will use CBEngine; if so, store site_key/cbuser in config (secrets).
   - Recommendation: Plan for Muncheye RSS + optional CBEngine (config-driven URL); document need for CBEngine Pro if used.

2. **Refresh candidates threshold**
   - What we know: Viral Amplifier uses VIRAL_VIEWS_7D_MIN and VIRAL_ENGAGEMENT_MIN for “viral” posts.
   - What’s unclear: Same thresholds for “refresh candidate” (topic selection) or lower bar (e.g. more posts).
   - Recommendation: Planner can reuse same config keys or add REFRESH_CANDIDATES_VIEWS_MIN etc.

3. **Orchestrator “Load Existing Topics” data source**
   - What we know: In 08_Orchestrator_v3, Load Existing Topics reads BLOG_IDEA_TAB (not Content Log); Build Research Context expects rows with `Keyword` or column index 2.
   - What’s unclear: Whether “existing topics” are from Blog Idea Backlog or Content Log in production; CONTEXT says “existing_keywords from Load Existing Topics”.
   - Recommendation: Leave Load Existing Topics as-is unless planner consolidates; add refresh_candidates from the new tab/columns and merge into context.

## Validation Architecture

### Test framework
| Property | Value |
|----------|-------|
| Framework | No n8n-specific test runner in repo; Laravel PHPUnit for API/dashboard (Phase 5). |
| Config file | laravel/phpunit.xml |
| Quick run | `cd laravel && php artisan test --filter=MissionControlApi` (or relevant suite) |
| Full suite | `cd laravel && php artisan test` |

### Phase requirements → verification map
| Req ID | Behavior | Test Type | How to Verify |
|--------|----------|-----------|----------------|
| AFF-01 | Registry populated with products across 5 niches | Manual / UAT | Run Affiliate Manager (or bootstrap); inspect registry tab; ≥1 product per niche. |
| AFF-02 | Manager runs and updates registry from Muncheye/CBEngine RSS | Manual / UAT | Trigger workflow on schedule or manually; confirm new/updated rows in registry tab. |
| SEO-01 | GA4 data feeds into topic selection | Manual / UAT | Run refresh-candidates writer; run orchestrator; confirm Agent 1 prompt or context includes refresh list. |
| SEO-02 | SEO Interlinking runs Sunday 3 AM and updates internal links | Manual / UAT | Run workflow (trigger or manual); confirm Interlink/Internal Linking log rows and optional WP post content updated. |

### Sampling rate
- **Per task commit:** No automated tests for n8n workflows; rely on manual run + sheet inspection.
- **Phase gate:** All four requirements verified via UAT (run workflows, check Sheets and optionally WP).

### Wave 0 gaps
- No n8n workflow unit tests in repo; verification is manual and document-based.
- Optional: add 06-CONFIG-KEYS.md (like 02/03) listing Phase 6 config keys and required tabs for htg_config.
- If Laravel exposes any Phase 6 API (e.g. refresh candidates read), add PHPUnit test for that endpoint.

## Sources

### Primary (HIGH confidence)
- .planning/phases/06-affiliate-seo-feedback/06-CONTEXT.md — Phase boundary, decisions, code context.
- core/01_Config_Loader.json, affiliate/10_Affiliate_Research_v2.json, affiliate/15_Affiliate_Link_Registry.json, content/v4.0 — SEO Interlinking Intelligence Engine.json, growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json, core/08_Orchestrator_v3.json — In-repo patterns and node shapes.
- .cursor/rules/n8n-rule.mdc, n8n-json-contracts.mdc, ollama-json-only.mdc — Conventions.
- .planning/REQUIREMENTS.md — AFF-01, AFF-02, SEO-01, SEO-02.

### Secondary (MEDIUM confidence)
- Muncheye RSS: WebSearch + muncheye.com category feed (e.g. /category/affiliate/other/feed).
- CBEngine: cbengine.com/rss.html parameterized feed; Pro/site key noted in docs; static feeds.cbengine.xml 404.

### Tertiary (LOW confidence)
- CBEngine exact RSS URL for non-Pro users — not verified; document as optional and config-driven.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — All components are in-repo or documented in CLAUDE.md/CONTEXT.
- Architecture: HIGH — Same Config Loader + IF enabled + Sheets pattern as Phases 2–5.
- Pitfalls: HIGH — From code review; Muncheye RSS and CBEngine from WebSearch + single fetch (404).

**Research date:** 2026-03-13
**Valid until:** ~30 days; re-check CBEngine RSS URL if Phase 6 adopts it.

---

## RESEARCH COMPLETE

Phase 6 research is complete. Findings support planning for: (1) affiliate product registry and Affiliate Link Manager (Muncheye/CBEngine RSS, optional ClickBank), (2) GA4 → Refresh Candidates → Agent 1 context, (3) SEO Interlinking with Config Loader and configurable output (Sheets and/or WP). No blockers; open points (CBEngine Pro, refresh thresholds, Load Existing Topics source) are planner choices or config decisions.
