# Phase 4: Content Satellites - Research

**Researched:** 2026-03-12
**Domain:** n8n content satellites — config-gated video production (Blotato) and email newsletter (ConvertKit/MailerLite)
**Confidence:** HIGH (CONTEXT.md decisions, existing workflows, Phase 2/3 patterns, verified ESP/Blotato behavior)

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **Video (GROW-05):** Provider: Prefer Blotato or free/stock method. No Pictory/InVideo paid subscriptions. Script always from today's post (Content Log → fetch post → LLM scripts). One post → multiple scripts/videos (e.g. TikTok, YT Short, IG Reel). Config gate: VIDEO_PRODUCTION_ENABLED; when disabled, exit without reading Sheets or calling Blotato. When no valid post: exit cleanly after "Get today's post" (no log row or append skipped row — planner's choice for consistency).
- **Video Log:** Dedicated "Video Log" (or config-driven tab name) in same Google Sheet. One row per video asset. Columns at least: date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at. Optionally update Content Log with video_status — planner may choose append-only Video Log only.
- **Email (GROW-06):** Config-driven. One of ConvertKit or MailerLite per deployment (e.g. EMAIL_PROVIDER=convertkit|mailerlite). Only the selected provider's branch runs. Sequence: simplified (e.g. 3 emails); exact count and delay (e.g. 0, 2, 5 days) are Claude's discretion. Enable gate: EMAIL_NEWSLETTER_ENABLED; when disabled, webhook still responds (200 OK) but does not add subscriber or send email. First email: ESP sends it; workflow adds subscriber to sequence/tag and ESP sends first welcome on its own (target "within 5 minutes" = add to sequence promptly).

### Claude's Discretion
- Exact config key names (VIDEO_PRODUCTION_ENABLED, VIDEO_LOG_TAB, EMAIL_NEWSLETTER_ENABLED, EMAIL_PROVIDER).
- Whether to update Content Log with video_status in addition to Video Log tab.
- Number and platforms of videos per post (TikTok + YT Short only vs also IG Reel).
- How "scheduled to publish" is implemented (Blotato publish node vs writing to Reels/Shorts queue).
- Email sequence length (e.g. 3) and delay schedule.
- Free/stock video method if Blotato is not used (Blotato-only for Phase 4 is acceptable).

### Deferred Ideas (OUT OF SCOPE)
- Pictory/InVideo as primary video providers.
- Dual-write to both ConvertKit and MailerLite (single provider only in Phase 4).
- Long 5-email welcome sequence (simplified for Phase 4).
- Workflow-triggered "send first email now" API (rely on ESP automation instead).
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| GROW-05 | Video production workflow generates TikTok/Shorts scripts and submits to Pictory/InVideo | Use Blotato (not Pictory/InVideo) per CONTEXT. Scheduler 10:30 AM → Config Loader → VIDEO_PRODUCTION_ENABLED gate → Get today's post (config sheet + CONTENT_LOG_TAB) → Filter today + status ≠ publish_failed → Fetch post body (WP URL from config) → LLM generate multiple scripts → Parse & Validate → For each script call social/14_Video_Production (or equivalent) with script/title/blog_url/platform → Log each to Video Log (one row per video). Script source = today's post; scheduling via Blotato Publish or Reels/Shorts queue. |
| GROW-06 | Email newsletter automation sends welcome sequence to new subscribers via ConvertKit/MailerLite | Webhook → Config Loader → EMAIL_NEWSLETTER_ENABLED gate (when disabled: respond 200 OK, do not add to ESP). Validate & Tag → single-provider branch from EMAIL_PROVIDER → Add to ConvertKit or MailerLite (sequence/form/group); ESP sends first email. No workflow "send now" call. Credentials via n8n (no YOUR_* in JSON). Simplified sequence (e.g. 3 emails). |
</phase_requirements>

---

## Summary

Phase 4 activates two content satellites: (1) **Video** — a 10:30 AM scheduled workflow that reads today's post from Content Log (same "today + status ≠ publish_failed" pattern as Phase 2/3), fetches post body from WordPress, uses one LLM call to generate multiple platform-specific scripts (TikTok, YT Short, optionally IG Reel), then invokes the existing `social/14_Video_Production` sub-workflow once per script; each run logs one row to a Video Log tab. (2) **Email** — the existing `email/v3.0 — Email Newsletter Automation.json` is refactored to start with Config Loader, gate on EMAIL_NEWSLETTER_ENABLED, branch on EMAIL_PROVIDER (ConvertKit or MailerLite only), and simplify to a 3-email sequence; the workflow only adds the subscriber to the ESP (sequence/form or group); the ESP automation sends the first welcome email. Both workflows depend on Phase 1 (Content Log shape and status semantics). The existing 14_Video_Production sub-workflow logs the *notify* payload (telegram_message, chat_id) to the Video Log sheet because the Log node is fed from the Telegram notify node; the planner must ensure Video Log receives the required columns (date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at) — either by adding a "Build Video Log Row" path in the sub-workflow or by having the caller write to Video Log.

**Primary recommendation:** Build a new scheduler workflow (or adapt archive "Auto Video Creation") that: Schedule 10:30 → Execute Workflow (Config Loader) → Code normalize VIDEO_PRODUCTION_ENABLED → IF enabled → Google Sheets read Content Log (config) → Code "Filter today's post" (timezone + status) → IF noPostToday exit → HTTP fetch post by slug → LLM generate scripts (JSON envelope) → Parse & Validate → Loop/Split per script → Execute Workflow (14_Video_Production) with script, title, platform, blog_url, keyword; ensure Video Log gets one row per video with the required columns (fix 14_Video_Production so Log is fed from a "Build Video Log Row" node, not from Notify). For email: insert Config Loader at start of webhook workflow, gate on EMAIL_NEWSLETTER_ENABLED (disabled → Respond to Webhook 200 OK only), single IF/Switch on EMAIL_PROVIDER to Add to ConvertKit or Add to MailerLite (sequence ID or form ID from config); remove parallel dual-write and AI-generated email nodes; use ESP-built sequence so first email is sent by ESP.

---

## Standard Stack

### Core
| Component | Source | Purpose | Why Standard |
|-----------|--------|---------|--------------|
| n8n Execute Workflow | built-in | Call Config Loader and 14_Video_Production | Same as Phase 2/3; sub-workflow output = config or video result |
| Config Loader | `core/01_Config_Loader.json` | Key/Value from data tables (htg_config + htg_secrets) | Single source; GOOGLE_SHEET_ID, CONTENT_LOG_TAB, VIDEO_*, EMAIL_* from config |
| Google Sheets | n8n-nodes-base.googleSheets | Read Content Log; append Video Log; optional Email Log | Project standard; sheet/tab from config |
| Blotato API | HTTPS v1 (existing) | Generate video from script; Publish to platform | Already in 14_Video_Production; no extra subscription |
| ConvertKit API | v3 or v4 | Add subscriber to sequence; sequence sends first email | Add to sequence → ESP sends emails in order (verified) |
| MailerLite API | REST (connect.mailerlite.com) | Add subscriber to group; automation "subscriber_joins_group" sends welcome | Add to group → automation triggers (verified) |
| Ollama / LLM | Existing (qwen2.5:7b or llama3.2) | Generate multiple video scripts from post body | JSON-only output; Parse & Validate after LLM per n8n-json-contracts |

### Supporting
| Component | Purpose | When to Use |
|-----------|---------|-------------|
| Code node | Normalize enable flags, filter today's post, parse LLM script JSON, build Video Log row | After Config Loader; after every LLM; before Video Log append |
| IF node | VIDEO_PRODUCTION_ENABLED; noPostToday; EMAIL_NEWSLETTER_ENABLED; EMAIL_PROVIDER | Gate and branch |
| HTTP Request | WordPress GET post by slug; Blotato (in sub-workflow); ConvertKit/MailerLite | Fetch post; ESP APIs |

**Installation:** No new npm packages. All capabilities in n8n and existing workflows.

---

## Architecture Patterns

### Video workflow (GROW-05)

```
Schedule Trigger (10:30)
  → ⚙️ Load Config (Execute Workflow → 01_Config_Loader)
  → Code: Normalize VIDEO_PRODUCTION_ENABLED (true / "true" → true)
  → IF enabled? → false: STOP (no log)
  → true: 📄 Read Content Log (documentId + sheetName from config)
  → Code: Filter today's post (timezone, date === today, status !== publish_failed); if none → { noPostToday: true }
  → IF noPostToday? → true: STOP (no append or one Skipped row per CONTEXT)
  → HTTP: Fetch full post (WORDPRESS_URL + slug from Content Log row)
  → LLM: Generate video scripts (JSON: e.g. tiktok, yt_short, ig_reel with script + title per platform)
  → Code: Parse & Validate (envelope + required keys; fallback defaults)
  → Split/Loop per script
  → Execute Workflow (14_Video_Production) with script, title, platform, blog_url, keyword
  → (Each run: Blotato Generate → Publish → Log; caller or sub-workflow must write Video Log row with date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at)
```

### Email workflow (GROW-06)

```
Webhook: New Subscriber
  → ⚙️ Load Config (Execute Workflow → 01_Config_Loader)
  → Code: Normalize EMAIL_NEWSLETTER_ENABLED
  → IF enabled? → false: Respond to Webhook 200 OK (e.g. { success: true, message: "Thanks" }), STOP (no ESP add)
  → true: Validate & Tag Subscriber (email, name, tags)
  → IF valid email? → false: Respond 4xx + message
  → true: IF EMAIL_PROVIDER === "convertkit" → Add to ConvertKit (sequence/form); else → Add to MailerLite (group)
  → Respond to Webhook 200 OK
```

### Pattern: "Today's post" (same as Phase 2/3)

- Read Content Log from config: `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`, `CONTENT_LOG_TAB || 'Content Log'`.
- Code filter: timezone from `CONTENT_DAY_TIMEZONE` or `TIMEZONE` or `'UTC'`; `today = new Date().toLocaleDateString('en-CA', { timeZone })`; filter rows where date (first 10 chars) === today and status (lowercased) !== `publish_failed`.
- If no rows: return `[{ json: { noPostToday: true } }]` and route so no Sheets append to Video Log (per CONTEXT: exit cleanly).

### Pattern: Config Loader at start

- First node after trigger: Execute Workflow → Config Loader (by ID or name). Downstream read `$('⚙️ Load Config').item.json.KEY`.
- Normalize booleans: `enabled === true || String(enabled).toLowerCase() === 'true'`.

### Anti-patterns to avoid

- **Dual-write email:** Do not run both ConvertKit and MailerLite in parallel; one branch only from EMAIL_PROVIDER.
- **Webhook silent when disabled:** When EMAIL_NEWSLETTER_ENABLED is false, webhook must still respond 200 so the form doesn't break; only skip adding to ESP.
- **Video Log from Notify payload:** 14_Video_Production currently feeds "Log Video Production" from "Notify Video Status", which outputs telegram_message and chat_id. That does not satisfy "one row per video with date, post_url, platform, job_id, status". Do not rely on autoMapInputData from the notify path; add a dedicated Build Video Log Row and feed Log from that (or have caller append after each Execute Workflow).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Video script schema | Ad-hoc object shape | LLM JSON with explicit schema (success/data/error); Parse & Validate with fallback | n8n-json-contracts; one post → N scripts must be parseable |
| Email sequence content | Workflow-generated HTML per email | ESP-hosted sequence (ConvertKit sequence / MailerLite automation) | CONTEXT: ESP sends first email; no "send now" API from workflow |
| Config storage | New CSV or env per workflow | Existing Config Loader (data tables) | PIPE-05; one place for VIDEO_*, EMAIL_* |
| "Today's post" logic | Custom date column or full scan | Same filter as Phase 2: date slice(0,10) === today, status !== publish_failed | Consistency; Content Log shape from Phase 1 |

**Key insight:** Video generation (Blotato) and email (ESP) are already implemented or documented; Phase 4 wires config gates, single-provider email, and correct Video Log row shape.

---

## Common Pitfalls

### Pitfall 1: Video Log row shape in 14_Video_Production

**What goes wrong:** The "Log Video Production" node uses autoMapInputData and is connected to "Notify Video Status", so the appended row contains telegram_message and chat_id instead of date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at.

**Why it happens:** The sub-workflow was built so the success path ends with Notify → Log; Log never receives the Extract Video URL / Validate+Prepare payload.

**How to avoid:** Add a Code node "Build Video Log Row" that builds `{ date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at }` from Validate+Prepare and Extract (and Blotato response). Feed the Google Sheets "Log Video Production" from this node (e.g. branch from "Extract Video URL" so one branch goes Publish → Notify, the other goes Build Video Log Row → Log). Alternatively have the *caller* workflow append to Video Log after each Execute Workflow using the script/platform/title/blog_url and the sub-workflow return value (job_id/video_id, status).

**Warning signs:** Video Log tab has columns like "telegram_message", "chat_id" or only 2 columns.

### Pitfall 2: Webhook does not respond when email is disabled

**What goes wrong:** When EMAIL_NEWSLETTER_ENABLED is false, if the workflow exits without responding to the webhook, the client gets a timeout or 5xx.

**How to avoid:** After the IF enabled node, false branch must go to "Respond to Webhook" with 200 and a generic success message, then stop. Only the true branch adds to ESP.

### Pitfall 3: ConvertKit/MailerLite require subscriber first, then sequence/group

**What goes wrong:** ConvertKit v4 adds existing subscriber to sequence by ID; v3 can create subscriber and add to sequence in one call. MailerLite: add subscriber with group IDs; automation triggers on "subscriber_joins_group".

**How to avoid:** Use ConvertKit v3 subscribe-to-sequence endpoint (create + add to sequence) or create subscriber then v4 add to sequence. MailerLite: add subscriber with groups array; ensure automation is tied to that group. Store sequence_id / form_id (ConvertKit) or group_id (MailerLite) in config so no YOUR_* in JSON.

### Pitfall 4: Content Log column names vary (date vs Date, wp_url vs WP URL)

**What goes wrong:** Phase 1 Content Log row uses lowercase keys in Assemble node (date, wp_url, status); Google Sheets may return headers with different casing.

**How to avoid:** Reuse the same pattern as Phase 2 "Filter today's post": detect dateKey, statusKey, urlKey from first row (e.g. find key where lowercase === 'date') then filter using those keys. See growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json "Filter today's post" Code node.

---

## Code Examples

### Filter today's post (from Phase 2 Multi-Language)

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

### Normalize enable flag (from Phase 2)

```javascript
const cfg = $('⚙️ Load Config').item.json;
const raw = cfg.VIDEO_PRODUCTION_ENABLED; // or EMAIL_NEWSLETTER_ENABLED
const enabled = raw === true || String(raw).toLowerCase() === 'true';
return [{ json: { VIDEO_PRODUCTION_ENABLED: enabled, config: cfg } }];
```

### Parse & Validate video scripts (LLM output)

After LLM node, Code node: extract JSON from response (regex for ```json or first `{...}`), parse, validate required keys (e.g. scripts per platform), fallback to single default script on parse_error. Return envelope `{ success, data: { scripts: [...] }, error }` so downstream can branch.

### 14_Video_Production input (caller passes)

From Validate+Prepare in 14_Video_Production: script (required), title (required), platform (default 'tiktok'), blog_url, keyword, duration (default 60). Caller from Content Log: post title, wp_url, slug; from LLM parse: script and platform per item. So Execute Workflow input: `{ script, title, platform, blog_url, keyword }`.

---

## State of the Art

| Old / Template | Current for Phase 4 | Impact |
|----------------|---------------------|--------|
| Pictory/InVideo in archive Auto Video Creation | Blotato (14_Video_Production) or free/stock only | No paid video API; use existing Blotato sub-workflow |
| Email: parallel ConvertKit + MailerLite, 5 AI emails | Single provider from config; 3-email ESP sequence; ESP sends first email | Simpler; no YOUR_*; config-driven |
| Video: single script per post | One post → multiple scripts (TikTok, YT Short, etc.) | LLM returns structured scripts; loop over platforms |
| Config hardcoded in workflow | Config Loader at start; all keys from config | Same as Phase 2/3 |

**Deprecated/out of scope for Phase 4:** Pictory/InVideo as primary; dual-write email; workflow "send first email now" API; long 5-email workflow-generated sequence.

---

## Open Questions

1. **Blotato API base URL**  
   Existing 14_Video_Production uses `https://api.blotato.com/v1/video/generate` and `v1/publish`. Some docs reference `https://backend.blotato.com` and v2. Recommendation: keep v1 for Phase 4 unless v1 is deprecated; planner can verify Blotato docs at implementation time.

2. **ConvertKit: form vs sequence**  
   Adding to a "form" can trigger a sequence in ConvertKit. V3 has subscribe-to-sequence. Recommendation: use sequence (or form that adds to sequence) so first email is sent by ESP; store sequence_id or form_id in config.

3. **Video Log written by caller vs sub-workflow**  
   CONTEXT allows "planner may choose append-only Video Log only". Option A: 14_Video_Production appends one row per run (requires fixing Log input to Build Video Log Row). Option B: Caller appends to Video Log after each Execute Workflow using script/title/platform and sub-workflow output (job_id, status). Both satisfy "one row per video asset".

---

## Validation Architecture

### Test framework

| Property | Value |
|----------|--------|
| Framework | None (n8n workflow JSON; no Jest/pytest in repo) |
| Config file | N/A |
| Quick run | Manual: import JSON into n8n, run with test data |
| Full suite | UAT: run video workflow with enabled config + today's post; run webhook with enabled/disabled and convertkit/mailerlite |

### Phase requirements → verification map

| Req ID | Behavior | Verification Type | How to Verify |
|--------|----------|-------------------|---------------|
| GROW-05 | Video workflow runs 10:30, config-gated, today's post → multiple scripts → Blotato → Video Log | UAT | Enable VIDEO_PRODUCTION_ENABLED; ensure Content Log has today's row with status ≠ publish_failed; run workflow; check Video Log has one row per platform (e.g. tiktok, yt_short) with date, post_url, platform, job_id_or_video_id, status |
| GROW-05 | When no valid post, exit without writing to Video Log (or one Skipped row) | UAT | Run with no today row or status = publish_failed; confirm no Video Log append (or single Skipped row) |
| GROW-05 | When VIDEO_PRODUCTION_ENABLED false, exit without reading Sheets or Blotato | UAT | Set enabled false; run; confirm no Content Log read, no Blotato call |
| GROW-06 | Email webhook adds subscriber to ConvertKit or MailerLite per config; ESP sends first email | UAT | Set EMAIL_PROVIDER=convertkit; POST webhook with email/name; confirm subscriber in ConvertKit and first sequence email sent within 5 min; repeat with mailerlite |
| GROW-06 | When EMAIL_NEWSLETTER_ENABLED false, webhook responds 200 but does not add to ESP | UAT | Set enabled false; POST webhook; confirm 200 response and no subscriber in ESP |

### Sampling rate

- **Per task:** Validate workflow JSON parses: `node -e "JSON.parse(require('fs').readFileSync('path/to/workflow.json'))"`.
- **Phase gate:** UAT checklist above; no automated test suite in repo.

### Wave 0 gaps

- No test directory or test runner exists. Verification is manual/UAT and JSON schema validation of workflow files. Planner may add a small script to assert required nodes and keys in JSON (e.g. "Load Config", "VIDEO_PRODUCTION_ENABLED", "Filter today's post") as a smoke check.

---

## Sources

### Primary (HIGH confidence)
- `.planning/phases/04-content-satellites/04-CONTEXT.md` — locked decisions and code context
- `social/14_Video_Production.json` — Blotato generate/publish, Log node input source
- `core/01_Config_Loader.json` — config merge from data tables
- `email/v3.0 — Email Newsletter Automation.json` — webhook, Validate & Tag, ConvertKit/MailerLite nodes
- `growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json` — Load Config, Filter today's post pattern
- `.planning/phases/02-distribution-growth/02-RESEARCH.md` — config gate and today's post patterns

### Secondary (MEDIUM confidence)
- Web search: ConvertKit add to sequence → first email sent by ESP; MailerLite add to group → automation trigger
- Web search: Blotato API (v1 vs v2 endpoints); existing workflow uses api.blotato.com v1

### Tertiary (LOW confidence)
- Blotato v2 migration (backend.blotato.com, /v2/videos/creations) — not verified for current account; keep v1 for Phase 4

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — existing workflows and Phase 2/3 patterns define stack
- Architecture: HIGH — CONTEXT and code context spell out flows; only Video Log row source needs fix
- Pitfalls: HIGH — 14_Video_Production Log input and webhook response when disabled are from code inspection

**Research date:** 2026-03-12  
**Valid until:** ~30 days (stable n8n/Blotato/ESP APIs)
