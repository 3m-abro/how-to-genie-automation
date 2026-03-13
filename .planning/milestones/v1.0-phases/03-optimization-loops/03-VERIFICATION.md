---
phase: 03-optimization-loops
verified: "2026-03-12T00:00:00Z"
status: human_needed
score: 6/6 must-haves verified
gaps: []
human_verification:
  - test: "Replace REPLACE_WITH_CONFIG_LOADER_ID in Viral workflows"
    expected: "Viral Content Amplifier Engine and Viral Amplifier Queue use actual Config Loader workflow ID from n8n so they run correctly"
    why_human: "Workflow ID is instance-specific; cannot verify without n8n runtime"
  - test: "Run A/B workflow with A_B_TESTING_ENABLED=true and a valid yesterday row in Content Log"
    expected: "One row appended to A/B tab with test_id, post_url, original_*, variant_*, winner column; no write when disabled or noPostYesterday"
    why_human: "Requires live n8n, Sheets, and WP; behavior verified in code only"
  - test: "Run Viral workflow with VIRAL_AMPLIFIER_ENABLED=true and GA4 returning rows above threshold"
    expected: "Qualifying rows appended to Viral Amplifier tab with promotion_status=pending"
    why_human: "Requires GA4 credentials and live API"
  - test: "Run Viral Amplifier Queue with at least one pending row in Viral Amplifier tab"
    expected: "New rows in Social Queue (Status=Queued, Scheduled_Time); Viral Amplifier rows updated to promotion_status=sent"
    why_human: "Sheets update by row_id and Queue Processor consumption require live run"
---

# Phase 3: Optimization Loops Verification Report

**Phase Goal:** The system automatically tests content variants and amplifies posts that GA4 confirms are gaining traction, creating a self-improving content engine.

**Verified:** 2026-03-12  
**Status:** human_needed  
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | A/B workflow runs at 6 AM when A_B_TESTING_ENABLED is true | ✓ VERIFIED | Schedule `0 6 * * *`; Trigger → ⚙️ Load Config → Normalize → IF A_B_TESTING_ENABLED; true branch → Read Content Log → Filter yesterday |
| 2 | When disabled or no valid post yesterday (or status = publish_failed), workflow exits without writing to A/B tab | ✓ VERIFIED | IF A_B_TESTING_ENABLED false branch empty; Filter yesterday returns noPostYesterday; IF Has Post Yesterday false branch empty; no path to Log Active Test |
| 3 | When yesterday's post exists, variants (headline + intro + CTA) are generated, parsed with success/data/error envelope, and one row appended to A/B tab with original + variant fields and winner column | ✓ VERIFIED | Build variant prompt → Ollama HTTP → Parse & Validate (success/data/error, fallback); Log Active Test columns test_id, post_url, original_*, variant_*, created_at, status, winner; config AB_TESTS_TAB |
| 4 | Viral workflow runs every 6 hours when VIRAL_AMPLIFIER_ENABLED is true and appends qualifying rows to Viral Amplifier tab with promotion_status=pending | ✓ VERIFIED | Schedule `0 */6 * * *`; Load Config → IF enabled → Fetch GA4 (config GA4_PROPERTY_ID, GOOGLE_ANALYTICS_TOKEN) → Detect Viral (VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN) → Append to VIRAL_AMPLIFIER_TAB with promotion_status, row_id |
| 5 | When disabled or no rows above threshold, Viral workflow exits without writing | ✓ VERIFIED | IF VIRAL_AMPLIFIER_ENABLED false branch empty; Detect Viral filters by views_7d and engagement_rate; only qualifying rows passed to append |
| 6 | Viral Amplifier Queue reads pending rows, appends to Social Queue for re-promotion, and updates Viral Amplifier row to promotion_status=sent | ✓ VERIFIED | Read Viral Amplifier → Filter pending (promotion_status=pending) → Any pending? false branch → Prepare Social Queue rows → Append to Social Queue (SOCIAL_QUEUE_TAB, Status=Queued) → Prepare update sent → Update Viral row to sent (matchingColumns row_id) |

**Score:** 6/6 truths verified (code and structure)

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json` | Config-gated A/B variant generation and log to Sheets only | ✓ VERIFIED | Contains Execute Workflow (Load Config), IF A_B_TESTING_ENABLED, Read Content Log, Filter yesterday's post, IF Has Post Yesterday, Fetch Post Content, Build variant prompt, AI Generate Test Variants, Parse & Validate, Log Active Test; no Inject A/B; no 6 PM branch |
| `.planning/phases/03-optimization-loops/03-CONFIG-KEYS.md` | Phase 3 config keys (A/B and Viral) for htg_config and Sheets tabs | ✓ VERIFIED | Documents A_B_TESTING_ENABLED, AB_TESTS_TAB, CONTENT_LOG_TAB, GOOGLE_SHEET_ID, WORDPRESS_URL, CONTENT_DAY_TIMEZONE; VIRAL_AMPLIFIER_ENABLED, VIRAL_AMPLIFIER_TAB, VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN, GA4_PROPERTY_ID, GOOGLE_ANALYTICS_TOKEN; required tabs AB Tests / AB Tests Active, Viral Amplifier |
| `growth/HowTo-Genie v4.0 — Viral Content Amplifier Engine.json` | Config-gated GA4 viral detection and append to Viral Amplifier tab | ✓ VERIFIED | Trigger → Load Config → IF VIRAL_AMPLIFIER_ENABLED → Fetch GA4 Performance Data → Detect Viral Content → Append to Viral Amplifier Tab; GA4-only (no Search Console); config-driven thresholds and sheet/tab |
| `growth/HowTo-Genie v4.0 — Viral Amplifier Queue.json` | Read pending viral rows, queue to social, mark sent | ✓ VERIFIED | Trigger (0 */6 * * *) → Load Config → Read Viral Amplifier → Filter pending → Any pending? → Prepare Social Queue rows → Append to Social Queue → Prepare update sent → Update Viral row to sent |

### Key Link Verification

| From | To | Via | Status | Details |
|------|-----|-----|--------|---------|
| Schedule Trigger (6 AM) | Execute Workflow (Config Loader) | first node after trigger | ✓ WIRED | connections["🧪 A/B Test Setup (Daily 6AM)"].main → ⚙️ Load Config |
| Filter yesterday Code | Content Log | date === yesterday, status !== publish_failed | ✓ WIRED | Read Content Log (config doc/tab) → Filter yesterday's post; jsCode uses CONTENT_DAY_TIMEZONE, yesterday, noPostYesterday |
| Parse & Validate | LLM output | success/data/error envelope, fallback on parse error | ✓ WIRED | Parse & Validate jsCode parses response, validates success/data/error, returns test_id, original_*, variant_*, winner |
| Schedule (every 6h) | Execute Workflow (Config Loader) | first node after trigger | ✓ WIRED | Viral Engine and Queue: Trigger → ⚙️ Load Config |
| Detect Viral Code | GA4 response | VIRAL_VIEWS_7D_MIN, VIRAL_ENGAGEMENT_MIN; views_7d, engagement_rate; promotion_status pending | ✓ WIRED | Detect Viral Content reads config, iterates GA4 rows, pushes objects with promotion_status: 'pending', row_id |
| Viral Amplifier Queue | Viral Amplifier tab | read promotion_status=pending; update same row to sent | ✓ WIRED | Read Viral Amplifier → Filter pending; Update Viral row to sent with matchingColumns ["row_id"], promotion_status: 'sent' |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| GROW-03 | 03-01-PLAN.md | A/B testing engine creates and logs variant articles for yesterday's post | ✓ SATISFIED | A/B workflow: 6 AM trigger, config gate, yesterday filter, WP fetch by slug, LLM variants (headline/intro/CTA), Parse & Validate envelope, append to AB_TESTS_TAB with winner column |
| GROW-04 | 03-02-PLAN.md | Viral content amplifier reads GA4 data and promotes high-performing posts | ✓ SATISFIED | Viral Engine: 6h trigger, config gate, GA4 runReport, config thresholds, append to Viral Amplifier tab with promotion_status=pending; Viral Queue: read pending, append to Social Queue, update row to sent |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| Viral Content Amplifier Engine.json | workflowId value | REPLACE_WITH_CONFIG_LOADER_ID | ℹ️ Info | Intentional placeholder; user must set Config Loader workflow ID in n8n (documented in 03-02-SUMMARY) |
| Viral Amplifier Queue.json | workflowId value | REPLACE_WITH_CONFIG_LOADER_ID | ℹ️ Info | Same as above |

### Human Verification Required

1. **Replace REPLACE_WITH_CONFIG_LOADER_ID in Viral workflows**  
   **Test:** In n8n, set Execute Workflow node "⚙️ Load Config" to the actual Config Loader workflow ID in both Viral Content Amplifier Engine and Viral Amplifier Queue.  
   **Expected:** Workflows load config at runtime.  
   **Why human:** Workflow ID is instance-specific; cannot verify without n8n.

2. **A/B run with enabled and valid yesterday post**  
   **Test:** Set A_B_TESTING_ENABLED=true in config; ensure Content Log has a row for yesterday with status ≠ publish_failed. Run A/B workflow.  
   **Expected:** One row in A/B tab with test_id, original_*, variant_*, winner; when disabled or no yesterday row, no append.  
   **Why human:** Requires live n8n, Google Sheets, and WordPress.

3. **Viral run with enabled and GA4 above threshold**  
   **Test:** Set VIRAL_AMPLIFIER_ENABLED=true, GA4 credentials and property ID; run Viral Content Amplifier Engine when GA4 has pages above VIRAL_VIEWS_7D_MIN and VIRAL_ENGAGEMENT_MIN.  
   **Expected:** Rows in Viral Amplifier tab with promotion_status=pending.  
   **Why human:** Requires GA4 API and credentials.

4. **Viral Amplifier Queue with pending rows**  
   **Test:** With at least one row in Viral Amplifier tab with promotion_status=pending, run Viral Amplifier Queue.  
   **Expected:** New rows in Social Queue (Status=Queued, Scheduled_Time); those Viral Amplifier rows updated to promotion_status=sent.  
   **Why human:** Sheets update by row_id and Queue Processor behavior need live run.

### Gaps Summary

None. All must-haves from 03-01-PLAN.md and 03-02-PLAN.md are present and wired in the codebase. Phase 3 goal (automatic testing of content variants and amplification of GA4-confirmed traction) is achieved in code; status is **human_needed** for runtime and deployment checks (Config Loader ID, live A/B run, live Viral run, live Queue run).

**Note:** 03-CONFIG-KEYS.md does not list SOCIAL_QUEUE_TAB; it is used by Viral Amplifier Queue (and htg_config.csv). Consider adding SOCIAL_QUEUE_TAB to the doc for completeness; not required for phase goal.

---

_Verified: 2026-03-12_  
_Verifier: Claude (gsd-verifier)_
