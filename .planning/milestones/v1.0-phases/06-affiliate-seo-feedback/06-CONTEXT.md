# Phase 6: Affiliate & SEO Feedback - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

The affiliate link registry is current and niche-relevant, the Affiliate Link Manager refreshes it automatically from Muncheye/CBEngine RSS (and optionally other sources), GA4 performance data routes back into topic selection via a refresh-candidates list that Agent 1 (Topic Research) can use, and the weekly SEO Interlinking engine runs on schedule (e.g. Sunday 3 AM) and writes internal-link recommendations to Sheets and/or WordPress. Requirements in scope: AFF-01, AFF-02, SEO-01, SEO-02. No new capabilities beyond these.

</domain>

<decisions>
## Implementation Decisions

### Registry shape and niches (AFF-01)

- User delegated all choices to planner: where the registry lives (same sheet vs separate), row shape and columns, how to ensure at least one active product per niche (bootstrap vs manual seed), and niche column values (five fixed vs config-driven).

### Affiliate Link Manager schedule and update policy (AFF-02)

- User delegated all choices to planner: schedule (e.g. weekly Monday 7 AM vs daily), append vs upsert/dedupe when writing from RSS, reuse/adapt `affiliate/10_Affiliate_Research_v2.json` vs new workflow, and data sources (Muncheye + CBEngine RSS only vs including ClickBank API).

### GA4 → refresh candidates for Agent 1 (SEO-01)

- User delegated all choices to planner: where the list lives (new tab vs Content Log columns), threshold(s) for "refresh candidate" (reuse viral vs separate), who writes it (dedicated workflow vs extend Viral Amplifier), and how Agent 1 uses it (read-only vs read + write-back).

### SEO Interlinking output and application (SEO-02)

- User delegated all choices to planner: output (Sheets only vs Sheets + direct WP updates), which posts to update if auto-applying (all vs top N), safety/rollback (none vs before snapshot to Sheets), and schedule/config (fixed Sunday 3 AM vs configurable day/time).

### Claude's Discretion

- All of the above: planner chooses concrete design for registry, manager workflow, refresh-candidates flow, and SEO interlinking output/application within the phase boundary.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets

- `core/01_Config_Loader.json` — All Phase 6 workflows call Config Loader at start; read GOOGLE_SHEET_ID, tab names (e.g. AFFILIATE_REGISTRY_TAB, REFRESH_CANDIDATES_TAB, CONTENT_LOG_TAB), and any enable/GA4/affiliate keys. Same pattern as Phases 2–5.
- `affiliate/10_Affiliate_Research_v2.json` — Monday 7 AM, Config Loader, ClickBank API + Muncheye fetch, scoring; can be adapted for RSS-only or extended with CBEngine RSS; output currently not wired to a registry tab.
- `affiliate/15_Affiliate_Link_Registry.json` — Sub-workflow that extracts affiliate links from article content and logs to Sheets (AFFILIATE_LINKS_TAB); not the product registry. Use for "links found in posts" logging if needed; registry of products per niche is separate.
- `content/v4.0 — SEO Interlinking Intelligence Engine.json` — Sunday 3 AM, reads posts (hardcoded YOUR_GOOGLE_SHEET_ID), Build Content Index, LLM link recommendations, parse; needs Config Loader, sheet/tab from config, and output decision (Sheets only vs WP PATCH).
- Phase 3 Viral Amplifier already uses GA4 (runReport, VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN); SEO-01 refresh candidates are a separate use (topic selection input for Agent 1).

### Established Patterns

- Execute Workflow for Config at start; IF enabled then continue else exit. Tab and sheet IDs from config.
- Content Log row shape from Phase 1; "today" / "yesterday" from config timezone.
- Parse & Validate after every LLM; JSON envelope. No secrets in workflow JSON.
- Orchestrator: Agent 1 currently gets Reddit + existing_keywords from Load Existing Topics; no refresh-candidates input yet — to be added per planner design.

### Integration Points

- Affiliate Manager: schedule → Config Loader → fetch Muncheye/CBEngine (and optionally ClickBank) → score/filter → write to registry tab (append or upsert per planner).
- Refresh candidates: GA4 data → threshold logic → write to Refresh Candidates tab or Content Log columns; orchestrator or Agent 1 prep node reads that list and injects into Agent 1 context.
- SEO Interlinking: Config Loader → read Content Log (or full library) → Build Content Index → LLM per batch → parse → write to Interlink Recommendations tab and/or PATCH WordPress posts per planner.

</code_context>

<specifics>
## Specific Ideas

- No specific product references or "I want it like X" — standard automation approach within phase boundary.
- Setup guide and ROADMAP reference five niches: productivity, finance, home, health, tech; planner may use these or a config-driven list.

</specifics>

<deferred>
## Deferred Ideas

- Add more affiliate platforms later: Digistore24, Commission Junction, Warrior Plus, etc. — capture for roadmap/backlog; Phase 6 stays with Muncheye/CBEngine (and any planner-chosen ClickBank use).

</deferred>

---

*Phase: 06-affiliate-seo-feedback*
*Context gathered: 2026-03-12*
