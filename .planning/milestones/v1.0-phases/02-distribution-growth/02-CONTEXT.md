# Phase 2: Distribution Growth - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Today's published post automatically reaches more people via (1) multi-language: translate and publish in 9 languages at 2 PM as separate WordPress posts on language subdomains, and (2) messaging: daily digest to WhatsApp/Telegram subscribers at 10 AM. Both workflows read enable/disable and key config from the same Config Loader used by the orchestrator; toggling a config value turns them on or off without re-importing workflow JSON.

Requirements in scope: GROW-01, GROW-02.
A/B testing, viral amplifier, video, email, dashboards, affiliate, and SEO are out of scope.

</domain>

<decisions>
## Implementation Decisions

### Multi-Language (GROW-01)

- **WordPress topology:** One publish endpoint per language (subdomains: es., pt., de., fr., hi., id., ar., ja. howtogenie.com). Can be either (a) 8 separate WordPress installs or (b) one WordPress multisite with 8 sites (subdomain or subdirectory). Keep the current 8 "Publish to X Site" nodes (or one parameterized publish); workflow calls each language's REST API (e.g. https://es.howtogenie.com/wp-json/wp/v2/posts). Multisite is acceptable and often simpler to maintain than 8 separate installs. Recommended for promoting in other countries (per-country SEO, local monetization later).
- **Base URL for fetching today's English article:** From config (e.g. `WORDPRESS_URL` or equivalent) — same pattern as rest of system.
- **Target languages:** Fixed in workflow for Phase 2: es, pt-BR, de, fr, hi, id, ar, ja (8 translations). Config-driven language list is a later improvement.
- **Which post to use:** Latest Content Log row where `date` = today and `status` ≠ `publish_failed`. If none, exit without translating. "Today" = timezone from config (e.g. `TIMEZONE` or `CONTENT_DAY_TIMEZONE`) with fallback to n8n server timezone.
- **When no valid post:** Exit immediately after "Get today's post"; no Multilingual Content row, no error alert (clean log).
- **Content Log columns:** Use whatever Phase 1 logs (e.g. `wp_url`); workflow derives slug from URL. No new column required.

### Messaging Digest (GROW-02)

- **Channels:** Both WhatsApp and Telegram wired in the workflow; only Telegram used until config flag (e.g. `WHATSAPP_DIGEST_ENABLED=true`) is set when WhatsApp Business is ready.
- **Digest content:** Short message: post title + link + optional 1-line hook from "Adapt for Messaging" LLM; one variant per platform when both are used.
- **Subscriber identifier column:** One column "Chat ID or Phone" — Telegram rows store `chat_id`, WhatsApp rows store E.164 phone; workflow sends to the right API based on Platform.
- **Zero active subscribers:** Exit and append one row to "Messaging Distribution Log" with Status = "Skipped", Recipients = 0 so it appears in weekly review.
- **Subscriber list source:** Manual only for Phase 2 — user adds/edits rows; no subscribe webhook in this phase.
- **Subscriber sheet:** Same Google Sheet as Content Log; tab name from config (e.g. `MESSAGING_SUBSCRIBERS_TAB`). Sheet ID from existing config (`GOOGLE_SHEET_ID` / `SPREADSHEET_ID`).
- **Required columns (min):** Platform (WhatsApp/Telegram), Chat ID or Phone, Language (optional, default en), Status (active/inactive). Timezone/Preferences optional; workflow tolerates missing optional fields.
- **When no valid post for digest:** Exit and append one row to "Messaging Distribution Log" with Status = "Skipped", reason = "no_post_today".

### Enable/Disable from Config

- **Config keys:** `MULTI_LANGUAGE_ENABLED` and `MESSAGING_DIGEST_ENABLED` (boolean or string "true"/"false"). Load config at workflow start; if disabled, exit cleanly.
- **Default when key missing:** Both off — neither workflow runs until the key is set in the n8n data table (or htg_config reference).
- **How to load config:** Call the same Config Loader sub-workflow (`01_Config_Loader.json`) at the start of each growth workflow via Execute Workflow; read e.g. `$('⚙️ Load Config').item.json.MULTI_LANGUAGE_ENABLED` (same pattern as orchestrator).
- **When disabled:** No log row — silent exit. Log only when the workflow actually runs (success, skipped, or error).

### Claude's Discretion

- Exact config key type (boolean vs "true"/"false") and parsing in Code node
- Subscriber sheet column header names (e.g. "Chat ID or Phone" vs "Identifier") as long as Platform + identifier + Status are clear
- Whether to add `CONTENT_DAY_TIMEZONE` to htg_config.csv or reuse an existing timezone key

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets

- `core/01_Config_Loader.json` — Config Loader sub-workflow; both growth workflows must call it at start (Execute Workflow) and read `GOOGLE_SHEET_ID`, `CONTENT_LOG_TAB`, `MULTI_LANGUAGE_ENABLED`, `MESSAGING_DIGEST_ENABLED`, `MESSAGING_SUBSCRIBERS_TAB`, `WORDPRESS_URL` (or equivalent), and optional `TIMEZONE` / `CONTENT_DAY_TIMEZONE`.
- `growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json` — Template: 2 PM trigger, reads Content Log, fetches English post, splits into 8 languages, Ollama translate, Route by Language, 8 httpRequest publish nodes, Log to Multilingual Content. Uses `YOUR_GOOGLE_SHEET_ID`, hardcoded `your-blog.com`; needs config wiring and enable gate.
- `growth/HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json` — Template: 10 AM trigger, Get Today's Post, Load Messaging Subscribers, AI Adapt for Messaging, Personalize, Route by Platform, Send WhatsApp/Telegram, Log Distribution. Uses `YOUR_GOOGLE_SHEET_ID`, hardcoded credentials; needs Config Loader at start, enable gate, and subscriber tab/sheet from config.

### Established Patterns

- **Execute Workflow for Config:** Orchestrator calls `⚙️ Load Config` at start; growth workflows should do the same and branch on config before any Sheets/API calls.
- **Content Log row shape:** Phase 1 logs `date`, `status`, `wp_url` (or equivalent); multi-language filters by date + status and derives slug from URL.
- **Parse & Validate after LLM:** Same regex + try/catch + fallback pattern; "Adapt for Messaging" and translation outputs should follow JSON envelope where applicable.
- **Google Sheets append** for Multilingual Content and Messaging Distribution Log — same operation as Content Log.

### Integration Points

- Multi-language: First node after trigger must be Config Loader; then IF `MULTI_LANGUAGE_ENABLED` → Get Today's Post (from config sheet ID + CONTENT_LOG_TAB), filter by today + status ≠ publish_failed, fetch from WORDPRESS_URL + slug.
- Messaging: Config Loader → IF `MESSAGING_DIGEST_ENABLED` → Get Today's Post (same), Load Subscribers (sheet ID + MESSAGING_SUBSCRIBERS_TAB); if no post → append skipped row and exit; if no subscribers → append skipped row and exit.
- Both workflows must not run when orchestrator wrote `publish_failed` for today (multi-language by not selecting that row; messaging by checking same Content Log and skipping when no valid post).

</code_context>

<specifics>
## Specific Ideas

- User asked for recommendation on multi-language approach: subdomains recommended for promoting in other countries; accepted.
- User delegated most "you decide" choices to Claude (base URL, language list, today's post rule, timezone, digest content shape, subscriber column design, zero-subscriber/disabled behavior, config key names and defaults).

</specifics>

<deferred>
## Deferred Ideas

- Subscribe webhook to add subscribers to the sheet — manual list only in Phase 2
- Config-driven list of target languages for multi-language — fixed 8 for Phase 2
- WhatsApp Business API setup and approval — Telegram-only until user flips flag

</deferred>

---

*Phase: 02-distribution-growth*
*Context gathered: 2026-03-12*
