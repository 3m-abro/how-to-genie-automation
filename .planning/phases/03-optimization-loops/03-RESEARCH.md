# Phase 3: Optimization Loops — Research

**Researched:** 2026-03-12
**Domain:** n8n workflow automation, GA4 Data API, A/B variant logging, viral detection, config-gated growth workflows
**Confidence:** HIGH

## Summary

Phase 3 delivers two optimization loops: (1) **A/B Testing (GROW-03)** — 6 AM daily workflow that loads config, reads yesterday’s post from Content Log, fetches post body from WordPress, calls an LLM to generate headline + intro + CTA variants, parses with success/data/error envelope, and appends to a dedicated A/B tab in Sheets (no WP injection, no 6 PM results flow). (2) **Viral Amplifier (GROW-04)** — every-6h workflow that loads config, calls GA4 Data API only (no Search Console), runs a Code node to apply config-driven viral thresholds (views_7d ≥ min, engagement_rate ≥ min), appends qualifying rows to a dedicated “Viral Amplifier” tab with `amplify=true`, `promotion_status=pending`, and full metrics; a separate integration step wires the existing social/Blotato queue to read pending rows, generate posts, append to Social Queue, and set `promotion_status=sent`. Both workflows follow the established pattern: Execute Workflow (Config Loader) → Normalize enable flag → IF enabled → continue else exit. Config is read from the same source as Phase 2 (n8n Data Tables or equivalent Key/Value store consumed by `core/01_Config_Loader.json`). Existing workflow JSON for A/B and Viral are the primary templates; Phase 3 simplifies them (remove WP injection, GSC, paid campaigns, 6 PM branch) and adds config gates plus the viral→social wiring.

**Primary recommendation:** Implement by refactoring `growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json` and `growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json` to start with Config Loader, use config for sheet ID/tabs and feature flags, and add a small “Viral Amplifier Queue” workflow (or equivalent) that reads Viral Amplifier tab for `promotion_status=pending`, invokes the existing Blotato/social formatting path, appends to Social Queue, and updates the row to `sent`.

<user_constraints>

## User Constraints (from CONTEXT.md)

### Locked Decisions

- **A/B (GROW-03):** Variant types = headline + intro + CTA; one alternate variant per element (A vs B). Variants live in Sheets only (A/B tab); no WordPress injection, no draft posts, no custom WP endpoints. Yesterday’s post = latest Content Log row where date = yesterday; if no row or status = publish_failed, skip run (exit cleanly). A/B log columns (conceptual): test_id, post_url, original_title, original_intro, original_cta, variant_title, variant_intro, variant_cta, created_at, status, winner (winner column exists but is manual or future-auto). Config gate: A_B_TESTING_ENABLED; when disabled, exit without writing.
- **Viral (GROW-04):** Amplify flag and metrics in a dedicated Google Sheets tab (e.g. “Viral Amplifier”), not new columns on Content Log. Columns: date, post_url, post_title, viral_score, views_7d, engagement_rate, bounce_rate, avg_session_duration, amplify, promotion_status (pending|sent|skipped), detected_at. Re-promotion = organic only: viral workflow writes amplify=true, promotion_status=pending; Phase 3 wires existing social queue to read pending rows, generate posts, queue them, set promotion_status=sent. No Facebook/Google/Reddit paid campaigns. Viral threshold: config-driven (e.g. VIRAL_VIEWS_7D_MIN default 5000, VIRAL_ENGAGEMENT_MIN default 0.08). Condition: views_7d >= min AND engagement_rate >= min. Analytics source: GA4 only (no Search Console in Phase 3). Config gate: VIRAL_AMPLIFIER_ENABLED; when disabled, exit without writing.

### Claude's Discretion

- Exact A/B tab name and column header names (e.g. AB Tests vs AB Tests Active; Winner vs Winning Variant).
- Number of variants (one vs two alternates) if not specified above.
- Exact viral_score formula and config key names (e.g. VIRAL_SCORE_MIN vs VIRAL_VIEWS_7D_MIN + VIRAL_ENGAGEMENT_MIN).
- How social queue workflow identifies “amplify” rows (by tab name from config, or by promotion_status in a shared tab).
- Parse & Validate schema for “Generate Test Variants” and “Design Amplification Campaign” LLM outputs (success/data/error envelope per project rules).

### Deferred Ideas (OUT OF SCOPE)

- WordPress plugin / custom endpoints for A/B impression tracking and automated winner detection.
- Search Console integration for viral score.
- Paid amplification (Facebook Ads, Google Ads, Reddit queue).
- Automatic A/B winner computation (from GA4 or WP); Phase 3 winner column is manual or future-auto.
- Draft WordPress posts for variants; Sheets-only for Phase 3.

</user_constraints>

<phase_requirements>

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| GROW-03 | A/B testing engine creates and logs variant articles for yesterday's post | Config Loader + IF gate; read Content Log (config-driven sheet/tab); filter by date=yesterday, status≠publish_failed; fetch WP post by URL/slug; LLM “Generate Test Variants” (headline + intro + CTA); Parse & Validate with success/data/error; append to A/B tab; no WP injection or 6 PM results flow. |
| GROW-04 | Viral content amplifier reads GA4 data and promotes high-performing posts | Config Loader + IF gate; GA4 runReport (7d, pagePath, pageTitle, screenPageViews, averageSessionDuration, engagementRate, bounceRate); Code node viral detection using VIRAL_VIEWS_7D_MIN and VIRAL_ENGAGEMENT_MIN; append to Viral Amplifier tab with promotion_status=pending; separate flow reads pending rows, calls Blotato/social formatter, appends to Social Queue, updates row to promotion_status=sent. |

</phase_requirements>

## Standard Stack

### Core

| Component | Version / Reference | Purpose | Why Standard |
|-----------|---------------------|---------|--------------|
| n8n | (instance) | Workflow orchestration | Project standard; workflows are JSON exports. |
| core/01_Config_Loader | (sub-workflow) | Key/Value config at runtime | Used by Phase 2 growth workflows; same pattern for A/B and Viral. |
| Google Sheets | (document ID + tab names from config) | Content Log, A/B tab, Viral Amplifier tab, Social Queue | Single source of truth; no SQL. |
| GA4 Data API | v1beta runReport | 7-day page metrics (views, engagement, bounce, duration) | Official API; Viral template already uses it. |
| Ollama (LLM) | llama3.2:latest or qwen2.5:7b per project rules | Generate Test Variants; optional Design Amplification (organic-only copy) | Local inference; JSON-only prompts per n8n-json-contracts and ollama-json-only. |

### Supporting

| Component | Purpose | When to Use |
|-----------|---------|-------------|
| n8n HTTP Request | GA4 runReport, WordPress REST (fetch post by slug) | GA4 and WP read-only calls. |
| n8n Code node | Yesterday filter, viral threshold, parse LLM response | Plain JS; return `[{ json }]`; no console.log. |
| n8n Execute Workflow | Invoke Config Loader | First node after trigger in both A/B and Viral. |
| n8n Google Sheets node | read (range), append, update | Content Log read; A/B append; Viral Amplifier append + status update. |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Config Loader sub-workflow | Inline config in workflow | Loses single-source config and consistency with Phase 2. |
| GA4 only for viral | GA4 + GSC | CONTEXT locks GA4-only for Phase 3; GSC deferred. |
| Dedicated Viral Amplifier tab | Extra columns on Content Log | CONTEXT locks dedicated tab. |

**Installation:** No new npm/pip packages. All capabilities exist in n8n and existing workflow JSON.

## Architecture Patterns

### Recommended Flow (A/B)

```
Schedule (0 6 * * *) → Execute Workflow (Config Loader) → Normalize A_B_TESTING_ENABLED
→ IF enabled → Read Content Log (GOOGLE_SHEET_ID, CONTENT_LOG_TAB from config)
→ Code: filter date=yesterday, status≠publish_failed; if none, return noPostYesterday
→ IF has row → HTTP GET WP post by slug/URL → LLM Generate Test Variants
→ Parse & Validate (success/data/error) → Append to A/B tab (AB_TESTS_TAB from config)
Else (disabled or no post): exit without writing.
```

### Recommended Flow (Viral)

```
Schedule (0 */6 * * *) → Execute Workflow (Config Loader) → Normalize VIRAL_AMPLIFIER_ENABLED
→ IF enabled → HTTP POST GA4 runReport (7d, pagePath, pageTitle, screenPageViews, averageSessionDuration, engagementRate, bounceRate)
→ Code: apply VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN; build rows with viral_score, views_7d, engagement_rate, bounce_rate, avg_session_duration, amplify=true, promotion_status=pending, detected_at
→ Append to Viral Amplifier tab (VIRAL_AMPLIFIER_TAB from config)
Separate path (scheduled or triggered): Read Viral Amplifier tab where promotion_status=pending
→ For each row: invoke Blotato/social formatter (article_url, article_title) → append to Social Queue → Update Viral Amplifier row to promotion_status=sent.
```

### Pattern: “Yesterday” from Content Log

Reuse the same timezone and key-detection pattern as Phase 2 “today” filter, but compute yesterday:

```javascript
// Conceptual: config from Load Config node
const config = $('⚙️ Load Config').item.json;
const tz = config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC';
const today = new Date().toLocaleDateString('en-CA', { timeZone: tz });
const yesterday = new Date(new Date(today).getTime() - 86400000).toISOString().slice(0, 10);
// Filter rows where date (normalized) === yesterday and status !== 'publish_failed'
```

Use same key detection as Multi-Language/WhatsApp: `dateKey`, `statusKey`, `urlKey` from first row.

### Pattern: Config Gate

Same as Phase 2 (Multi-Language, WhatsApp):

1. Execute Workflow (Config Loader).
2. Code: `const enabled = raw === true || String(raw).toLowerCase() === 'true';` for the feature flag.
3. IF node (strict boolean): `MULTI_LANGUAGE_ENABLED === true` → true branch continues; false branch exits (no downstream nodes).

Apply to `A_B_TESTING_ENABLED` and `VIRAL_AMPLIFIER_ENABLED`.

### Pattern: GA4 runReport Request

Viral template already uses the correct endpoint and body shape. Use config for property ID and token:

- URL: `https://analyticsdata.googleapis.com/v1beta/properties/{{ $json.GA4_PROPERTY_ID }}:runReport`
- Headers: `Authorization: Bearer {{ $json.GOOGLE_ANALYTICS_TOKEN }}` (from config/secrets).
- Body: dateRanges 7daysAgo–today; dimensions pagePath, pageTitle; metrics screenPageViews, averageSessionDuration, engagementRate, bounceRate; orderBy screenPageViews desc; limit 50.

Response rows: `row.dimensionValues[0].value` (pagePath), `dimensionValues[1].value` (pageTitle), `metricValues[0].value` (views), etc., in order of metrics array.

### Anti-Patterns to Avoid

- **Skipping Config Loader:** Both workflows must start with Config Loader and gate on enable flag.
- **Hardcoding sheet ID or tab names:** Use config keys (GOOGLE_SHEET_ID, CONTENT_LOG_TAB, AB_TESTS_TAB, VIRAL_AMPLIFIER_TAB).
- **Storing variants or viral rows in Content Log:** Use dedicated A/B and Viral Amplifier tabs.
- **Implementing WP A/B injection or 6 PM results in Phase 3:** Deferred; Sheets-only and winner manual/future.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Config at runtime | Custom config node or hardcoded values | Execute Workflow → Config Loader (existing) | Same pattern as Phase 2; single source. |
| GA4 auth and runReport | Custom OAuth or report builder | HTTP Request to GA4 Data API with Bearer token from config/secrets | Official API; template already exists. |
| “Yesterday” in timezone | Ad-hoc date math | CONTENT_DAY_TIMEZONE + same date-key detection as Phase 2 | Consistency; avoids timezone bugs. |
| LLM JSON parsing | Ad-hoc regex only | Regex + try/catch + fallback object with success/data/error envelope | n8n-json-contracts; deterministic fallback. |
| Social queue input | New queue format | Same Social Queue tab and Blotato/Queue Processor as today’s post | Reuse existing queue and processor. |

**Key insight:** This phase is integration and simplification of existing templates, not new infrastructure. Reuse Config Loader, GA4 request shape, and social queue patterns.

## Common Pitfalls

### Pitfall 1: GA4 response shape

**What goes wrong:** Assuming a different response structure; metric order doesn’t match code.

**Why:** runReport returns `rows[].dimensionValues` and `rows[].metricValues` in the same order as the request’s dimensions and metrics.

**How to avoid:** In the Code node, map by index to named fields (e.g. views_7d = metricValues[0], avg_duration = metricValues[1], engagementRate = metricValues[2], bounceRate = metricValues[3]) and add a one-line comment matching the request metrics array.

**Warning signs:** Viral score or threshold logic uses wrong metric (e.g. bounce instead of engagement).

### Pitfall 2: No post yesterday

**What goes wrong:** A/B workflow runs at 6 AM and tries to fetch WP post when Content Log has no row for yesterday or status is publish_failed.

**Why:** Schedule always fires; filtering must happen in Code and exit cleanly.

**How to avoid:** After reading Content Log, Code node filters by date === yesterday and status !== publish_failed; if no rows, return e.g. `[{ json: { noPostYesterday: true } }]`. IF node: noPostYesterday === true → false branch (exit); only true branch calls WP and LLM.

**Warning signs:** HTTP Request to WP or LLM runs with empty or wrong slug.

### Pitfall 3: Viral Amplifier update row

**What goes wrong:** After queueing a viral post to Social Queue, the Viral Amplifier row is not updated to promotion_status=sent, so the same row is picked again.

**Why:** Google Sheets update in n8n requires a matching column (e.g. row number or unique id). Append doesn’t return a stable row ID unless you add one.

**How to avoid:** When appending to Viral Amplifier tab, include a unique key (e.g. `detected_at` + `post_url`, or an auto-generated `id`). When reading pending rows, pass through row number or that id; when updating, use the same sheet’s update by that column (or by row number if n8n exposes it). Document the matching column in the plan.

### Pitfall 4: LLM output not JSON-only

**What goes wrong:** Parse node fails or fallback is always used because the model returns markdown or prose.

**How to avoid:** Per ollama-json-only: prompt must start with “Return only valid JSON. No text. No markdown. No explanations.” and include the schema. Parse node: strip ```json wrapper, try JSON.parse, catch → fallback object with success: false and error.message.

## Code Examples

### A/B: Filter yesterday’s post (conceptual)

```javascript
const config = $('⚙️ Load Config').item.json;
const tz = config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC';
const today = new Date().toLocaleDateString('en-CA', { timeZone: tz });
const yesterday = new Date(new Date(today).getTime() - 86400000).toISOString().slice(0, 10);
const rows = $input.all().map(i => i.json);
const dateKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'date') || 'date';
const statusKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'status') || 'status';
const urlKey = Object.keys(rows[0] || {}).find(k => k === 'WP URL' || k.toLowerCase().includes('url')) || 'wp_url';
const valid = rows.filter(r => {
  const d = (r[dateKey] || '').toString().slice(0, 10);
  const s = (r[statusKey] || '').toString().toLowerCase();
  return d === yesterday && s !== 'publish_failed';
});
if (valid.length === 0) return [{ json: { noPostYesterday: true } }];
const latest = valid[valid.length - 1];
const wpUrl = latest[urlKey] || '';
const slug = wpUrl.split('/').filter(Boolean).pop() || '';
return [{ json: { noPostYesterday: false, ...latest, slug, wpUrl } }];
```

### Viral: Threshold from config (conceptual)

```javascript
const config = $('⚙️ Load Config').item.json;
const viewsMin = Number(config.VIRAL_VIEWS_7D_MIN) || 5000;
const engagementMin = Number(config.VIRAL_ENGAGEMENT_MIN) || 0.08;
const ga4Rows = $input.first().json.rows || [];
const baseUrl = (config.WORDPRESS_URL || '').replace(/\/$/, '') || 'https://example.com';
const out = [];
for (const row of ga4Rows) {
  const pagePath = row.dimensionValues?.[0]?.value || '';
  const pageTitle = row.dimensionValues?.[1]?.value || '';
  const views_7d = Number(row.metricValues?.[0]?.value || 0);
  const avgSessionDuration = Number(row.metricValues?.[1]?.value || 0);
  const engagementRate = Number(row.metricValues?.[2]?.value || 0);
  const bounceRate = Number(row.metricValues?.[3]?.value || 0);
  if (views_7d >= viewsMin && engagementRate >= engagementMin && pagePath.length > 1) {
    out.push({
      date: new Date().toISOString().slice(0, 10),
      post_url: baseUrl + pagePath,
      post_title: pageTitle,
      viral_score: Math.round((views_7d / 1000) * 0.5 + engagementRate * 100 * 0.5),
      views_7d,
      engagement_rate: engagementRate,
      bounce_rate: bounceRate,
      avg_session_duration: avgSessionDuration,
      amplify: true,
      promotion_status: 'pending',
      detected_at: new Date().toISOString()
    });
  }
}
return out.map(o => ({ json: o }));
```

### Parse & Validate LLM (envelope)

Per n8n-json-contracts.mdc:

```javascript
const raw = $input.first().json.response || $input.first().json.message?.content || '';
let parsed;
try {
  const match = raw.match(/```json\n([\s\S]*?)\n```/) || raw.match(/(\{[\s\S]*\})/);
  parsed = JSON.parse(match ? (match[1] || match[0]) : raw);
  if (parsed.success === false) parsed = { success: false, data: null, error: parsed.error || { code: '', message: '' } };
  if (parsed.success !== true && parsed.success !== false) throw new Error('Missing success');
} catch (e) {
  parsed = { success: false, data: null, error: { code: 'PARSE_ERROR', message: e.message } };
}
return [{ json: parsed }];
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Hardcoded YOUR_GOOGLE_SHEET_ID in A/B/Viral | Config Loader → GOOGLE_SHEET_ID, tab names from config | Phase 2 | All growth workflows config-driven. |
| A/B: WP injection + 6 PM results | Sheets-only A/B log; winner manual/future | Phase 3 CONTEXT | No WP plugin or custom endpoints in scope. |
| Viral: GA4 + GSC + paid campaigns | GA4 only; organic re-promotion via social queue | Phase 3 CONTEXT | Simpler; GSC and paid campaigns deferred. |

**Deprecated/out of scope for Phase 3:** WordPress A/B injection node; custom WP ab-impression/ab-results endpoints; GSC node in Viral workflow; Facebook/Google Ads/Reddit campaign creation nodes (can remain in JSON but not activated).

## Open Questions

1. **Config storage:** Config Loader in repo uses n8n Data Tables (Get Config(s) / Get Secret(s)). PROJECT.md/STATE refer to htg_config.csv. Are config keys (A_B_TESTING_ENABLED, AB_TESTS_TAB, VIRAL_AMPLIFIER_ENABLED, VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN, VIRAL_AMPLIFIER_TAB, GA4_PROPERTY_ID, GOOGLE_ANALYTICS_TOKEN) expected to live in the same Data Tables or in a CSV imported elsewhere?  
   - **Recommendation:** Assume the same Config Loader output as Phase 2; add the new keys to whatever store backs that (Data Tables or CSV). Planner can add a task to document required config keys.

2. **Viral → Social wiring:** Should “read Viral Amplifier pending → Blotato → Social Queue → update status” be a separate workflow (e.g. “Viral Amplifier Queue”) or an extra branch/schedule in the existing Queue Processor?  
   - **Recommendation:** Separate small workflow (or a dedicated schedule in Viral workflow) that runs e.g. every 6h or daily, reads pending rows, loops and calls Execute Workflow (Blotato) then append to Social Queue then update Viral row. Keeps Queue Processor focused on “due” posts only.

## Validation Architecture

Phase 3 is implemented as n8n workflow JSON and config. The repo has no test runner or *test* / *spec* files. Verification is manual/smoke in n8n (run workflow, check Sheets and logs).

### Test Framework

| Property | Value |
|----------|--------|
| Framework | None (n8n workflow JSON) |
| Config file | N/A |
| Quick run command | Manual: execute workflow in n8n UI |
| Full suite command | Manual: run A/B at 6 AM and Viral at :00 every 6h; verify Sheets and promotion_status |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| GROW-03 | A/B workflow creates and logs variants for yesterday’s post when enabled | Manual / smoke | Run A/B workflow; check A/B tab has new row with original + variant fields | N/A |
| GROW-03 | A/B workflow exits without writing when A_B_TESTING_ENABLED is false | Manual | Set flag false; run; no append to A/B tab | N/A |
| GROW-03 | A/B workflow exits when no yesterday row or status publish_failed | Manual | Use Content Log with no yesterday / failed; run; no WP fetch | N/A |
| GROW-04 | Viral workflow appends rows to Viral Amplifier tab when above threshold and enabled | Manual / smoke | Run Viral workflow; check Viral Amplifier tab for new rows with promotion_status=pending | N/A |
| GROW-04 | Viral workflow exits without writing when VIRAL_AMPLIFIER_ENABLED is false | Manual | Set flag false; run; no append | N/A |
| GROW-04 | Pending viral rows are picked up, queued to social, and marked sent | Manual | Add pending row; run viral-queue flow; check Social Queue and promotion_status=sent | N/A |

### Sampling Rate

- **Per task commit:** Manual run of the modified workflow(s) in n8n.
- **Per wave merge:** Same; no automated test script.
- **Phase gate:** All manual checks above satisfied before `/gsd:verify-work`.

### Wave 0 Gaps

- No automated test framework for n8n workflows in this repo.
- 03-VALIDATION.md can list the manual verification steps and expected Sheets state so the orchestrator and user can run a consistent checklist.

## Sources

### Primary (HIGH confidence)

- Project files: `core/01_Config_Loader.json`, `growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json`, `growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json`, `growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json`, `growth/HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json`, `social/11_Queue_Processor_v2.json`, `social/06_Blotato_SubWorkflow.json`.
- `.cursor/rules/n8n-rule.mdc`, `n8n-json-contracts.mdc`, `ollama-json-only.mdc`.
- `.planning/phases/03-optimization-loops/03-CONTEXT.md` (user decisions).

### Secondary (MEDIUM confidence)

- GA4 Data API: runReport, dimensions/metrics (screenPageViews, engagementRate, bounceRate, averageSessionDuration) — WebSearch + official API schema reference.

### Tertiary (LOW confidence)

- None.

## Metadata

**Confidence breakdown:**

- Standard stack: HIGH — same n8n, Config Loader, Sheets, GA4, and LLM patterns as existing workflows.
- Architecture: HIGH — flows and gates match CONTEXT and Phase 2 patterns.
- Pitfalls: HIGH — derived from existing Code nodes and common n8n/Sheets/GA4 issues.

**Research date:** 2026-03-12  
**Valid until:** 2026-04-12 (stable domain; config key names may be refined during implementation).
