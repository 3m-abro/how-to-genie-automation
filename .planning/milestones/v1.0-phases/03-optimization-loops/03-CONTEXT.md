# Phase 3: Optimization Loops - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

The system automatically (1) runs the A/B testing engine at 6 AM to create headline + intro + CTA variants for yesterday's published post and log them to Google Sheets, and (2) runs the viral amplifier every 6 hours to read GA4 data, identify high-performing posts, write them to a dedicated Sheets tab with an amplify flag and full metrics, and trigger organic re-promotion via the existing social queue. The owner can see which variant was generated (and, when filled in, which variant won) in the A/B log tab. No WordPress plugin, no custom WP endpoints, no paid ad campaigns in this phase.

Requirements in scope: GROW-03, GROW-04.
Video, email, dashboards, affiliate, and SEO are out of scope.

</domain>

<decisions>
## Implementation Decisions

### A/B Testing (GROW-03)

- **Variant types:** Headline + intro + CTA. One alternate variant per element (A vs B); number of variants is Claude's discretion — implementation may choose one alternate for minimal scope.
- **Where variants live:** Sheets only. Log original + variant(s) to an A/B tab (e.g. "AB Tests" or "AB Tests Active"). No WordPress injection, no draft posts, no custom WP endpoints in Phase 3.
- **Yesterday's post source:** Latest Content Log row where date = yesterday. If no row or status = publish_failed, skip run (exit cleanly).
- **A/B log tab columns (conceptual):** test_id, post_url, original_title, original_intro, original_cta, variant_title, variant_intro, variant_cta, created_at, status, winner. Winner column exists but is manual or future-auto in Phase 3 — no automatic winner detection (no WP tracking).
- **Config gate:** A/B workflow reads config at start (Config Loader); e.g. `A_B_TESTING_ENABLED`. When disabled, exit without writing to Sheets.

### Viral Amplifier (GROW-04)

- **Where amplify flag lives:** Dedicated Google Sheets tab (e.g. "Viral Amplifier"), not new columns on Content Log. Columns: date, post_url, post_title, viral_score, views_7d, engagement_rate, bounce_rate, avg_session_duration, amplify (boolean), promotion_status (pending|sent|skipped), detected_at.
- **What "triggers social re-promotion" means:** Organic re-promotion via existing social queue. Viral workflow writes rows with amplify=true, promotion_status=pending. Phase 3 wires the existing Blotato/social queue workflow to read pending rows, generate social posts for those URLs, queue them, and set promotion_status=sent. No Facebook Ads, Google Ads, or Reddit campaign creation in Phase 3.
- **Viral threshold:** Config-driven. New config keys e.g. VIRAL_VIEWS_7D_MIN (default 5000), VIRAL_ENGAGEMENT_MIN (default 0.08). Condition: views_7d >= VIEWS_MIN and engagement_rate >= ENGAGEMENT_MIN. Config Loader at workflow start.
- **What we log when marking viral:** Full metrics (views_7d, engagement_rate, bounce_rate, avg_session_duration, viral_score, detected_at, promotion_status).
- **Analytics source:** GA4 only for Phase 3. No Search Console integration yet (defer to later phase).
- **Config gate:** Viral workflow reads config at start; e.g. `VIRAL_AMPLIFIER_ENABLED`. When disabled, exit without writing.

### Claude's Discretion

- Exact A/B tab name and column header names (AB Tests vs AB Tests Active; Winner vs Winning Variant).
- Number of variants (one vs two alternates) if not specified above.
- Exact viral_score formula and config key names (e.g. VIRAL_SCORE_MIN vs VIRAL_VIEWS_7D_MIN + VIRAL_ENGAGEMENT_MIN).
- How social queue workflow identifies "amplify" rows (e.g. by tab name from config, or by promotion_status in a shared tab).
- Parse & Validate schema for "Generate Test Variants" and "Design Amplification Campaign" LLM outputs (success/data/error envelope per project rules).

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets

- `core/01_Config_Loader.json` — Both A/B and Viral workflows call Config Loader at start; read GOOGLE_SHEET_ID, CONTENT_LOG_TAB, A_B_TESTING_ENABLED, VIRAL_AMPLIFIER_ENABLED, and for Viral: VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN, VIRAL_AMPLIFIER_TAB (or equivalent). Same pattern as Phase 2 growth workflows.
- `growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json` — Template: 6 AM trigger, Get Yesterday's Post (Content Log), Fetch Post Content, LLM Generate Test Variants, Parse Test Suite, Inject A/B Test Code (omit for Phase 3), Log to "AB Tests Active". Second trigger 6 PM for results (omit or simplify: no WP endpoint). Reuse: schedule, "yesterday" filter, LLM + Parse pattern, Sheets append. Remove: WP injection, custom WP endpoints, 6 PM results flow.
- `growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json` — Template: every 6h, Fetch GA4, Fetch GSC (remove for Phase 3), Detect Viral Content (Code node), Viral Score ≥ 50 (IF), LLM Design Amplification Campaign, then Facebook/Google Ads + Reddit queue (remove for Phase 3). Reuse: schedule, GA4 HTTP request, viral detection logic (GA4-only), threshold from config, Sheets append to dedicated tab. Add: write promotion_status=pending; wire downstream to social queue. Remove: GSC, paid campaigns, Reddit queue node.
- Social/Blotato queue workflow — Integration point: read "Viral Amplifier" tab (or config-driven name) for promotion_status=pending, generate posts, update status to sent. Exact node names and sheet tab from config.

### Established Patterns

- Execute Workflow for Config at start; IF enabled then continue else exit.
- Content Log row shape: date, status, wp_url (or equivalent); "yesterday" = date = yesterday in configured timezone.
- Parse & Validate after every LLM (regex + try/catch + fallback); JSON envelope where applicable.
- Google Sheets: append for logs; readRange for "yesterday" or "pending" rows. Tab names from config.

### Integration Points

- A/B: Config Loader → IF A_B_TESTING_ENABLED → Read Content Log (sheet from config), filter by date=yesterday, status≠publish_failed → Fetch post from WP URL + slug → LLM variants → Parse → Append to A/B tab.
- Viral: Config Loader → IF VIRAL_AMPLIFIER_ENABLED → GA4 API (property + token from config/credentials) → Code node (viral criteria from config) → IF above threshold → Append to Viral Amplifier tab with promotion_status=pending. Separate branch or scheduled run: social queue reads pending rows, queues posts, updates status.
- Both workflows use same Google Sheet (GOOGLE_SHEET_ID); tab names (e.g. AB_TESTS_TAB, VIRAL_AMPLIFIER_TAB) from config.

</code_context>

<specifics>
## Specific Ideas

- User chose headline + intro + CTA variants; yesterday's post = latest Content Log row where date = yesterday; "you decide" on variant count and viral/amplify details — captured above as Claude's discretion or explicit decisions.
- Recommendation accepted: variants in Sheets only (no WP injection or draft posts) for Phase 3.
- No specific product references or "I want it like X" — standard automation approach.

</specifics>

<deferred>
## Deferred Ideas

- WordPress plugin / custom endpoints for A/B impression tracking and automated winner detection — future phase.
- Search Console integration for viral score — later phase or Phase 6 SEO feedback.
- Paid amplification (Facebook Ads, Google Ads, Reddit queue) — out of Phase 3; template nodes remain but are not activated.
- Automatic A/B winner computation (from GA4 or WP) — Phase 3 winner column is manual or future-auto.
- Draft WordPress posts for variants — not in scope; Sheets-only for Phase 3.

</deferred>

---

*Phase: 03-optimization-loops*
*Context gathered: 2026-03-12*
