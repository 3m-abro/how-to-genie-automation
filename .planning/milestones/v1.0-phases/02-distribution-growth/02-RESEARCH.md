# Phase 2: Distribution Growth - Research

**Researched:** 2026-03-12
**Domain:** n8n growth workflows — config-gated multi-language translation/publish and messaging digest (Telegram/WhatsApp)
**Confidence:** HIGH (based on CONTEXT.md decisions, existing template workflows, and Phase 1 patterns)

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **WordPress topology (GROW-01):** One publish endpoint per language (subdomains: es., pt., de., fr., hi., id., ar., ja. howtogenie.com). Either 8 separate WordPress installs or one WordPress multisite with 8 sites. Keep 8 "Publish to X Site" nodes (or one parameterized publish); workflow calls each language's REST API (e.g. https://es.howtogenie.com/wp-json/wp/v2/posts). Multisite acceptable and often simpler.
- **Base URL for English article:** From config (e.g. `WORDPRESS_URL` or equivalent) — same pattern as rest of system.
- **Target languages:** Fixed in workflow for Phase 2: es, pt-BR, de, fr, hi, id, ar, ja (8 translations). Config-driven language list is a later improvement.
- **Which post to use:** Latest Content Log row where `date` = today and `status` ≠ `publish_failed`. If none, exit without translating. "Today" = timezone from config (e.g. `TIMEZONE` or `CONTENT_DAY_TIMEZONE`) with fallback to n8n server timezone.
- **When no valid post (multi-language):** Exit immediately after "Get today's post"; no Multilingual Content row, no error alert (clean log).
- **Content Log columns:** Use whatever Phase 1 logs (e.g. `wp_url`); workflow derives slug from URL. No new column required.
- **Messaging (GROW-02):** Both WhatsApp and Telegram wired in workflow; only Telegram used until config flag (e.g. `WHATSAPP_DIGEST_ENABLED=true`) when WhatsApp Business is ready.
- **Digest content:** Short message: post title + link + optional 1-line hook from "Adapt for Messaging" LLM; one variant per platform when both used.
- **Subscriber identifier column:** One column "Chat ID or Phone" — Telegram rows store `chat_id`, WhatsApp rows store E.164 phone; workflow sends to the right API based on Platform.
- **Zero active subscribers:** Exit and append one row to "Messaging Distribution Log" with Status = "Skipped", Recipients = 0 for weekly review.
- **Subscriber list:** Manual only for Phase 2 — user adds/edits rows; no subscribe webhook in this phase.
- **Subscriber sheet:** Same Google Sheet as Content Log; tab name from config (e.g. `MESSAGING_SUBSCRIBERS_TAB`). Sheet ID from existing config (`GOOGLE_SHEET_ID` / `SPREADSHEET_ID`).
- **Subscriber columns (min):** Platform (WhatsApp/Telegram), Chat ID or Phone, Language (optional, default en), Status (active/inactive). Timezone/Preferences optional.
- **When no valid post for digest:** Exit and append one row to "Messaging Distribution Log" with Status = "Skipped", reason = "no_post_today".
- **Enable/Disable from config:** Keys `MULTI_LANGUAGE_ENABLED` and `MESSAGING_DIGEST_ENABLED` (boolean or string "true"/"false"). Load config at workflow start; if disabled, exit cleanly.
- **Default when key missing:** Both off — neither workflow runs until the key is set in the n8n data table (or htg_config reference).
- **How to load config:** Call the same Config Loader sub-workflow (`01_Config_Loader.json`) at the start of each growth workflow via Execute Workflow; read e.g. `$('⚙️ Load Config').item.json.MULTI_LANGUAGE_ENABLED` (same pattern as orchestrator).
- **When disabled:** No log row — silent exit. Log only when the workflow actually runs (success, skipped, or error).

### Claude's Discretion
- Exact config key type (boolean vs "true"/"false") and parsing in Code node
- Subscriber sheet column header names (e.g. "Chat ID or Phone" vs "Identifier") as long as Platform + identifier + Status are clear
- Whether to add `CONTENT_DAY_TIMEZONE` to htg_config or reuse an existing timezone key

### Deferred Ideas (OUT OF SCOPE)
- Subscribe webhook to add subscribers to the sheet — manual list only in Phase 2
- Config-driven list of target languages for multi-language — fixed 8 for Phase 2
- WhatsApp Business API setup and approval — Telegram-only until user flips flag

</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| GROW-01 | Multi-language expansion workflow translates and publishes today's post to 9 languages (enabled in config) | Template `growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json` provides structure; needs Config Loader at start, enable gate, today+status filter, config-driven sheet ID/tab and WORDPRESS_URL; 8 languages (en + 8 translations = 9 total content outputs); slug from Content Log wp_url |
| GROW-02 | WhatsApp/Telegram bot sends daily digest to subscribers (enabled in config) | Template `growth/HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json` provides structure; needs Config Loader, enable gate, today+status filter, subscriber tab from config; skip paths (no post / zero subscribers) append one row to Messaging Distribution Log; Telegram-only until WHATSAPP_DIGEST_ENABLED; column "Chat ID or Phone" + Platform + Status |

</phase_requirements>

---

## Summary

Phase 2 activates two existing growth workflow templates by (1) inserting the same Config Loader sub-workflow at the start of each and gating on `MULTI_LANGUAGE_ENABLED` and `MESSAGING_DIGEST_ENABLED`, (2) replacing hardcoded sheet IDs and URLs with config keys, (3) defining "today's post" as the latest Content Log row where date = today (config timezone) and status ≠ publish_failed, and (4) implementing clean exit/skip behavior (multi-language: silent exit when no post; messaging: one "Skipped" row when no post or zero subscribers). The Config Loader is already used by the orchestrator via Execute Workflow; growth workflows must call it first and read the merged config object from the Execute Workflow node output (node name `⚙️ Load Config` for consistency). Multi-language publishes to 8 subdomains via WordPress REST API; messaging uses Telegram Bot API (chat_id) and optionally WhatsApp Business API (E.164 phone) when enabled.

**Primary recommendation:** Wire both growth workflows to `core/01_Config_Loader.json` at start (Execute Workflow → node name "⚙️ Load Config"); add IF nodes that check normalized boolean for the respective enable key and exit without logging when disabled. Add a "Get today's post" Code node after reading Content Log that filters by config timezone date and status ≠ publish_failed and exits with no further nodes when empty. Use config for GOOGLE_SHEET_ID, CONTENT_LOG_TAB, WORDPRESS_URL, MESSAGING_SUBSCRIBERS_TAB, and optional TIMEZONE/CONTENT_DAY_TIMEZONE. For messaging, when no post or zero active subscribers, append a single row to Messaging Distribution Log with Status = "Skipped" and Recipients = 0 or reason = "no_post_today", then exit.

---

## Standard Stack

### Core
| Component | Version / Source | Purpose | Why Standard |
|-----------|------------------|---------|--------------|
| n8n Execute Workflow | (built-in) | Call Config Loader sub-workflow | Same as orchestrator; output = final node of sub-workflow (config object) |
| n8n Config Loader | `core/01_Config_Loader.json` | Load Key/Value from htg_config + htg_secrets data tables | Single source of truth; no re-import needed when config changes |
| Google Sheets node | n8n-nodes-base.googleSheets | Read Content Log, Subscribers; append Multilingual Content, Messaging Distribution Log | Project standard; sheet ID and tab names from config |
| HTTP Request | n8n-nodes-base.httpRequest | WordPress REST API, Telegram sendMessage, WhatsApp Business API | Required for subdomain-specific WP and messaging APIs |
| Ollama / LLM | Existing (llama3.2 or qwen2.5:7b) | Translate (multi-language), Adapt for Messaging (digest) | Per CLAUDE.md and ollama-json-only rule; JSON-only output with Parse & Validate after each LLM |

### Supporting
| Component | Purpose | When to Use |
|-----------|---------|-------------|
| Code node | Filter "today" by timezone, parse config booleans, derive slug from wp_url, parse LLM JSON | After Config Loader (enable + today filter); after every LLM (envelope validation) |
| IF node | Branch on MULTI_LANGUAGE_ENABLED / MESSAGING_DIGEST_ENABLED; route Telegram vs WhatsApp | Right after config load; after personalization by platform |
| Switch/Route by Language | Send each translation to correct publish node | Multi-language only |

**Installation:** No new npm packages. All capabilities exist in n8n and existing workflows.

---

## Architecture Patterns

### Recommended Flow (both workflows)

```
Schedule Trigger
    → ⚙️ Load Config (Execute Workflow → 01_Config_Loader)
    → Code: Normalize enable flag (e.g. MULTI_LANGUAGE_ENABLED === true || MULTI_LANGUAGE_ENABLED === 'true')
    → IF enabled? → false: STOP (no log)
    → true: Get today's post (Google Sheets: config sheet ID + CONTENT_LOG_TAB)
    → Code: Filter rows by date === today (config timezone) and status !== 'publish_failed'; if empty → exit (multi-language: silent; messaging: append Skipped row then exit)
    → [Workflow-specific steps...]
```

### Pattern 1: Config Loader at start
**What:** First node after trigger is Execute Workflow calling the Config Loader. Downstream nodes read `$('⚙️ Load Config').item.json.KEY`.
**When to use:** Every growth workflow that must respect enable flags and use config for sheet ID, tab names, and URLs.
**Example (conceptual):**
- Execute Workflow node: workflow = Config Loader (by ID or name), source = database (workflow ID from project).
- Next node (Code or IF): `const enabled = $('⚙️ Load Config').item.json.MULTI_LANGUAGE_ENABLED; const isOn = enabled === true || String(enabled).toLowerCase() === 'true'; return [{ json: { multiLanguageEnabled: isOn, config: $('⚙️ Load Config').item.json } }];`

### Pattern 2: "Today's post" filter
**What:** Content Log is read with sheet ID and tab from config. A Code node keeps only rows where `date` equals today (in config timezone) and `status` !== `publish_failed`. If no rows, return empty or a single "no post" sentinel so IF/Route can exit or append Skipped.
**When to use:** Multi-language and Messaging both need exactly today's successful post.
**Example (conceptual):**
```javascript
const config = $('⚙️ Load Config').item.json;
const tz = config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC';
const today = new Date().toLocaleDateString('en-CA', { timeZone: tz }); // YYYY-MM-DD
const rows = $input.all().map(i => i.json);
const valid = rows.filter(r => (r.date || '').toString().slice(0, 10) === today && (r.status || '').toLowerCase() !== 'publish_failed');
if (valid.length === 0) return [{ json: { noPostToday: true } }];
const latest = valid[valid.length - 1];
const slug = (latest.wp_url || '').split('/').filter(Boolean).pop() || '';
return [{ json: { ...latest, slug, noPostToday: false } }];
```

### Pattern 3: Slug from Content Log
**What:** Phase 1 logs `wp_url` (e.g. `https://howtogenie.com/your-article-slug/`). Slug = last path segment. No new column.
**When to use:** Multi-language fetch English post from main site: `WORDPRESS_URL + '/wp-json/wp/v2/posts?slug=' + slug`.

### Pattern 4: Parse & Validate after LLM
**What:** After every LLM node (Translate, Adapt for Messaging), a Code node extracts JSON from response (regex for ```json or first `{...}`), validates required fields, and returns fallback defaults on catch. Follow n8n-json-contracts envelope (success/data/error).
**When to use:** All LLM outputs that downstream nodes depend on.

### Anti-Patterns to Avoid
- **Don't run growth logic when disabled:** Never call Sheets/APIs when the enable flag is false; exit immediately after the IF.
- **Don't assume Content Log has "today":** Always filter by date and status; handle empty result explicitly.
- **Don't log when disabled:** Messaging Distribution Log and Multilingual Content are written only when the workflow actually ran (enabled and proceeded past "today's post" check, or explicitly "Skipped" with one row).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|--------------|-----|
| Config key/value store | Custom CSV sync or hardcoded values | Config Loader sub-workflow (existing) | Single source; already used by orchestrator; data table or htg_config reference |
| Translation | Custom translation service or manual prompts | Ollama LLM with JSON schema (existing template) | Project standard; template already has "Translate + Culturally Adapt" and 8-language split |
| WordPress publish per language | One generic "publish" that discovers endpoints | 8 HTTP Request nodes (or one parameterized by language) to subdomain REST APIs | CONTEXT locks 8 endpoints; subdomain URLs are fixed |
| "Today" in user timezone | Server local date only | Config TIMEZONE or CONTENT_DAY_TIMEZONE in Code node with toLocaleDateString(..., { timeZone }) | Per CONTEXT; consistent with content day |
| Telegram/WhatsApp send | Custom SDK or hand-built auth | HTTP Request to Telegram Bot API and WhatsApp Business API with credentials from n8n credentials | Standard APIs; template already has URLs and body shape |

**Key insight:** The templates already implement translation, publish, and messaging; the phase work is config wiring, enable gates, and correct filtering/logging, not new algorithms.

---

## Common Pitfalls

### Pitfall 1: Execute Workflow not waiting for Config Loader
**What goes wrong:** Downstream node runs before Config Loader finishes; `$('⚙️ Load Config').item.json` is undefined or from a previous run.
**Why it happens:** Execute Workflow node set to "Do not wait for sub-workflow" (waitForSubWorkflow: false).
**How to avoid:** Use default "Wait for sub-workflow completion" so the parent receives the Config Loader's final output (single item with config object).
**Warning signs:** Random missing config keys in execution panel.

### Pitfall 2: "Today" in wrong timezone
**What goes wrong:** Multi-language or messaging runs for yesterday/tomorrow relative to the content day, or no row matches.
**Why it happens:** Using `new Date()` without timezone, or server TZ not matching owner's content day.
**How to avoid:** In the "Get today's post" Code node, get `CONTENT_DAY_TIMEZONE` or `TIMEZONE` from config and use `toLocaleDateString('en-CA', { timeZone: tz })` for YYYY-MM-DD comparison with Content Log `date`.
**Warning signs:** Empty valid rows when a post was actually published that day.

### Pitfall 3: Subscriber column mismatch
**What goes wrong:** Telegram needs `chat_id` (numeric or string), WhatsApp needs E.164 phone. Wrong column or wrong value type breaks send.
**Why it happens:** Template uses "Phone" for both; CONTEXT specifies one column "Chat ID or Phone" with Platform distinguishing.
**How to avoid:** Use one column (e.g. "Chat ID or Phone"); in Code, pass value as-is to Telegram `chat_id` or WhatsApp `to`; ensure WhatsApp rows have E.164 and Telegram rows have chat_id from Bot API.
**Warning signs:** Telegram/WhatsApp API errors "chat not found" or "invalid phone".

### Pitfall 4: Logging when disabled or when no post
**What goes wrong:** Messaging Distribution Log or Multilingual Content gets a row even when workflow was disabled or there was no post.
**Why it happens:** Log node runs on a path that should have exited earlier.
**How to avoid:** Per CONTEXT: when disabled, no log row; when no valid post (multi-language), exit without any log; when no post (messaging), append exactly one row with Status = "Skipped", reason = "no_post_today"; when zero subscribers, append one row Status = "Skipped", Recipients = 0.
**Warning signs:** Multiple "Skipped" rows per run or log rows on disabled runs.

### Pitfall 5: LLM output not validated
**What goes wrong:** "Adapt for Messaging" or translation returns prose or malformed JSON; downstream node throws or uses wrong data.
**Why it happens:** No Parse & Validate Code node after LLM, or prompt doesn't enforce JSON-only.
**How to avoid:** Follow n8n-json-contracts and ollama-json-only: prompt starts with "Return only valid JSON. No text. No markdown." and schema; Code node with try/catch and fallback (e.g. default message with post title + URL).
**Warning signs:** Execution error in Parse node or empty messaging content.

---

## Code Examples

Verified patterns from existing workflows and CONTEXT:

### Config boolean normalization (Code node after Load Config)
```javascript
const c = $('⚙️ Load Config').item.json;
const toBool = (v) => v === true || String(v).toLowerCase() === 'true';
return [{ json: {
  multiLanguageEnabled: toBool(c.MULTI_LANGUAGE_ENABLED),
  messagingDigestEnabled: toBool(c.MESSAGING_DIGEST_ENABLED),
  sheetId: c.GOOGLE_SHEET_ID || c.SPREADSHEET_ID,
  contentLogTab: c.CONTENT_LOG_TAB || 'Content Log',
  wordpressUrl: (c.WORDPRESS_URL || '').replace(/\/$/, ''),
  subscribersTab: c.MESSAGING_SUBSCRIBERS_TAB || 'Messaging Subscribers',
  timezone: c.CONTENT_DAY_TIMEZONE || c.TIMEZONE || 'UTC'
}}];
```

### Slug from wp_url (Content Log row)
```javascript
const row = $input.first().json;
const wpUrl = row.wp_url || row['WP URL'] || '';
const slug = wpUrl.split('/').filter(Boolean).pop() || '';
return [{ json: { ...row, slug } }];
```

### Telegram sendMessage (existing template pattern)
- URL: `https://api.telegram.org/bot{{ $credentials.token }}/sendMessage` or from config/credentials
- Body: `chat_id`, `text`, `parse_mode` (e.g. "Markdown"), optional `reply_markup` with inline_keyboard for link button

### WordPress REST API post create (per language)
- URL: `https://{subdomain}.howtogenie.com/wp-json/wp/v2/posts`
- Method: POST
- Auth: WordPress Application Password (n8n credential)
- Body: title, content, slug, status: "publish", excerpt, lang (or meta for Yoast)

---

## State of the Art

| Old Approach | Current Approach | Impact |
|--------------|------------------|--------|
| Hardcoded YOUR_GOOGLE_SHEET_ID, your-blog.com in workflow JSON | Config Loader + config keys (GOOGLE_SHEET_ID, CONTENT_LOG_TAB, WORDPRESS_URL) | Enable/disable and parameter changes without re-import |
| Growth workflows always run when triggered | IF after Config Loader on MULTI_LANGUAGE_ENABLED / MESSAGING_DIGEST_ENABLED | Owner can turn off multi-language or messaging from config only |
| "Get today's post" = read full sheet, use first row | Filter by date (config timezone) and status !== publish_failed; exit when empty | Only successful same-day post is used; no accidental reuse of old/failed post |

**Deprecated/outdated:**
- Relying on sheet name "Content Log" and document ID literal in growth workflows — use config.
- Running WhatsApp send when WhatsApp Business is not approved — use WHATSAPP_DIGEST_ENABLED and default off.

---

## Open Questions

1. **Config Loader workflow ID in growth JSON**
   - What we know: Orchestrator references Config Loader by workflowId (e.g. `CVc7gJbrt1baZLxG`). Growth workflows must call the same workflow; ID is instance-specific.
   - What's unclear: Whether to use workflowId (breaks on re-import to another n8n instance) or workflow name; n8n allows reference by name in some contexts.
   - Recommendation: Use Execute Workflow with workflow selected by name "⚙️ Config Loader" or document that workflowId must be set after import (same as orchestrator).

2. **Multilingual Content log: one row per language vs one row per run**
   - What we know: Template "📊 Log Translated Posts" runs per language (after each Publish to X Site); each row is one language.
   - What's unclear: CONTEXT says "no Multilingual Content row" when no valid post — so when we do run, we log 8 rows (one per language). No change needed.
   - Recommendation: Keep current template behavior: 8 append operations (or merge into one batch if n8n supports) after all 8 publishes.

3. **Messaging: Recipients count when mixed Telegram + WhatsApp**
   - What we know: CONTEXT says append one row with Status = "Skipped", Recipients = 0 when zero subscribers; when sent, log "Sent" with counts.
   - What's unclear: Template "📊 Log Distribution" uses expressions like `$('Personalize for Each Subscriber').all().length` — correct for total recipients.
   - Recommendation: Keep one log row per run with Platform breakdown (WhatsApp Sent / Telegram Sent) for visibility.

---

## Validation Architecture

> `workflow.nyquist_validation` is `true` in `.planning/config.json` — this section is included.

### Test Framework

n8n workflows are validated by JSON validity, manual execution in n8n, and inspection of Google Sheets and (where applicable) Telegram/WordPress. No automated test runner exists in this repo.

| Property | Value |
|----------|-------|
| Framework | n8n manual execution + JSON parse of workflow files |
| Config file | None — test via n8n UI execution panel and config data table |
| Quick run command | `node -e "JSON.parse(require('fs').readFileSync('growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json'))"` (and same for WhatsApp/Telegram workflow) |
| Full suite command | Trigger each growth workflow from its Schedule Trigger or Execute Node on "⚙️ Load Config"; verify enable gate, today filter, and log behavior |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | How to Verify | Currently Exists? |
|--------|----------|-----------|---------------|-------------------|
| GROW-01 | Multi-language enabled in config → workflow runs | Manual | Set MULTI_LANGUAGE_ENABLED=true in config; run workflow; verify translation and 8 publishes | No (template has no Config Loader or gate) |
| GROW-01 | Multi-language disabled → silent exit, no log | Manual | Set MULTI_LANGUAGE_ENABLED=false or missing; run; verify no Sheets append, no HTTP calls | No |
| GROW-01 | No today's post (or status=publish_failed) → exit, no Multilingual row | Manual | Empty Content Log or only publish_failed for today; run; verify no Multilingual Content append | No (template doesn't filter by date/status) |
| GROW-01 | Today's post exists → translate and publish to 8 subdomains, log 8 rows | Manual | Content Log has today's row with status published; run; verify 8 WP posts and Multilingual Content rows | Partial (template logic exists; config and filter missing) |
| GROW-02 | Messaging digest enabled → workflow runs | Manual | Set MESSAGING_DIGEST_ENABLED=true; run; verify subscribers loaded, Telegram (and WhatsApp if enabled) send | No (template has no Config Loader or gate) |
| GROW-02 | Messaging disabled → silent exit, no log | Manual | Set MESSAGING_DIGEST_ENABLED=false or missing; run; verify no Messaging Distribution Log row | No |
| GROW-02 | No today's post → append one Skipped row (reason no_post_today), exit | Manual | No valid Content Log row for today; run; verify exactly one row in Messaging Distribution Log: Status=Skipped, reason=no_post_today | No |
| GROW-02 | Zero active subscribers → append one Skipped row (Recipients=0), exit | Manual | Subscriber tab empty or all Status≠active; run; verify one Skipped row, Recipients=0 | No |
| GROW-02 | Post + subscribers → send digest, log Sent row | Manual | Valid post and at least one active subscriber; run; verify messages sent and one "Sent" row in log | Partial (template has send + log; config and skip logic missing) |

### Sampling Rate
- **Per task commit:** Validate modified workflow JSON parses: `node -e "JSON.parse(require('fs').readFileSync('<path>'))"`.
- **Per wave merge:** Run both growth workflows manually with config enabled and with disabled/no-post scenarios; spot-check Sheets and (if available) Telegram.
- **Phase gate:** All GROW-01 and GROW-02 behaviors above verified before `/gsd:verify-work`.

### Wave 0 Gaps
- [ ] Config Loader must be invoked as first step in both growth workflows (Execute Workflow node + IF on enable flag).
- [ ] "Get today's post" Code node (or equivalent) must filter Content Log by config timezone date and status !== publish_failed in both workflows.
- [ ] Multi-language: When no valid post, workflow must stop without writing to Multilingual Content.
- [ ] Messaging: When no post or zero subscribers, workflow must append exactly one row to Messaging Distribution Log then stop.
- [ ] Config keys MULTI_LANGUAGE_ENABLED, MESSAGING_DIGEST_ENABLED, MESSAGING_SUBSCRIBERS_TAB (and optionally CONTENT_DAY_TIMEZONE) must exist in htg_config/data table for testing.
- [ ] Subscriber sheet tab with columns: Platform, Chat ID or Phone, Language (optional), Status (active/inactive).
- [ ] Messaging Distribution Log tab exists for append (Status, Recipients, reason, etc.).

---

## Sources

### Primary (HIGH confidence)
- `.planning/phases/02-distribution-growth/02-CONTEXT.md` — locked decisions, discretion, deferred
- `core/01_Config_Loader.json` — structure (dataTable → Merge → Build Config Object); output is single item with config object
- `core/08_Orchestrator_v3.json` — Execute Workflow to Config Loader (node "⚙️ Load Config"), downstream reference `$('⚙️ Load Config').item.json`
- `growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json` — trigger 2 PM, Get Today's English Post, Fetch, Split 8 languages, Translate, Build Localized Post, Route by Language, 8× Publish, Log Translated Posts
- `growth/HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json` — trigger 10 AM, Get Today's Post, Load Subscribers, Adapt for Messaging, Personalize, Route by Platform, Send Telegram/WhatsApp, Log Distribution
- `.planning/REQUIREMENTS.md` — GROW-01, GROW-02 wording
- `.planning/config.json` — nyquist_validation: true
- `.cursor/rules/n8n-rule.mdc`, `n8n-json-contracts.mdc`, `ollama-json-only.mdc` — JSON envelope, no console.log, prompt header

### Secondary (MEDIUM confidence)
- Web search: n8n Execute Workflow output from sub-workflow (wait for completion); Telegram sendMessage chat_id; WhatsApp Business API to = E.164

### Tertiary (LOW confidence)
- None

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all components exist in repo and orchestrator
- Architecture patterns: HIGH — patterns taken from CONTEXT and existing templates
- Pitfalls: HIGH — derived from CONTEXT rules and common n8n/API mistakes

**Research date:** 2026-03-12
**Valid until:** 2026-06-12 (90 days — config and template structure stable)
