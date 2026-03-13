# Phase 4: Content Satellites - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Every published post automatically spawns (1) multiple video scripts and videos generated from today's post and scheduled for publish, and (2) a subscriber welcome sequence triggered by the new-subscriber webhook — extending each post's reach into video and email without manual work. Video workflow runs at 10:30 AM; email is webhook-triggered. Both are config-gated.

Requirements in scope: GROW-05, GROW-06.
Dashboards, affiliate, and SEO are out of scope.

</domain>

<decisions>
## Implementation Decisions

### Video Production (GROW-05)

- **Provider:** Prefer Blotato (no extra subscriptions) or a free method using stock images/videos. No Pictory/InVideo paid subscriptions required for Phase 4.
- **Script source:** Script is always generated from today's post. Workflow reads Content Log (today, status ≠ publish_failed), fetches post content, then generates scripts via LLM.
- **One post → multiple assets:** One post produces several scripts and videos (e.g. TikTok, YT Short, IG Reel — exact set is implementation choice). Each is generated and then scheduled for publish accordingly (scheduling mechanism can be Blotato publish, queue to social workflow, or write to Reels Scripts/YT Shorts Queue for downstream — planner decides).
- **Config gate:** Video workflow loads config at start; e.g. `VIDEO_PRODUCTION_ENABLED`. When disabled, exit without reading Sheets or calling Blotato.
- **When no valid post:** Same pattern as Phase 2/3: exit cleanly after "Get today's post" (no log row or append skipped row — planner's choice for consistency with other satellites).

### Video Logging (GROW-05)

- **Best approach (Claude's discretion):** Dedicated "Video Log" (or config-driven tab name) in the same Google Sheet. One row per video asset (e.g. one row for TikTok, one for YT Short) so each has a clear confirmation ID and status. Columns at least: date, post_url, post_title, platform, script_type, job_id_or_video_id, status, created_at. Optionally update Content Log with a single "video_status" or "video_job_ids" for today's row — planner may choose append-only Video Log only for simplicity, or both.

### Email Newsletter (GROW-06)

- **Provider:** Config-driven. One of ConvertKit or MailerLite per deployment; config key e.g. `EMAIL_PROVIDER=convertkit|mailerlite`. Only the selected provider's branch runs; no parallel dual-write in Phase 4.
- **Sequence:** Simplify. Fewer than the template's 5 emails (e.g. 3 emails); exact count and delay schedule (e.g. 0, 2, 5 days) are Claude's discretion.
- **Enable gate:** Workflow gated by config (e.g. `EMAIL_NEWSLETTER_ENABLED`). When disabled, webhook still responds (e.g. 200 OK) but does not add subscriber to ESP or send any email.
- **First email timing:** Rely on ESP's automation. Workflow adds the subscriber to the ESP (ConvertKit/MailerLite) with the correct sequence/tag; the ESP sends the first welcome email on its own. Target "within 5 minutes" is satisfied by adding to sequence promptly; no requirement for the workflow to call an explicit "send now" API.

### Claude's Discretion

- Exact config key names (VIDEO_PRODUCTION_ENABLED, VIDEO_LOG_TAB, EMAIL_NEWSLETTER_ENABLED, EMAIL_PROVIDER).
- Whether to update Content Log with video_status in addition to Video Log tab.
- Number and platforms of videos per post (e.g. TikTok + YT Short only vs also IG Reel).
- How "scheduled to publish" is implemented (Blotato publish node vs writing to existing Reels/Shorts queue for another workflow).
- Email sequence length (e.g. 3) and delay schedule.
- Free/stock video method if Blotato is not used (e.g. Ollama + stock footage API, or Blotato-only for Phase 4).

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets

- `social/14_Video_Production.json` — Sub-workflow: expects script, title (and optional platform, blog_url, keyword, duration). Calls Config Loader, Blotato generate + publish, logs to VIDEO_LOG_TAB. Triggered via Execute Workflow; caller must supply script/title. Can be invoked once per script (e.g. once for TikTok, once for YT Short) from a 10:30 scheduler workflow that gets today's post and generates multiple scripts.
- `core/01_Config_Loader.json` — Both video and email workflows call Config Loader at start; read GOOGLE_SHEET_ID, CONTENT_LOG_TAB, VIDEO_PRODUCTION_ENABLED, VIDEO_LOG_TAB, VIDEO_VOICE, ATP_LANGUAGE; for email: EMAIL_NEWSLETTER_ENABLED, EMAIL_PROVIDER, and provider-specific keys (or credentials only).
- `email/v3.0 — Email Newsletter Automation.json` — Webhook, Validate & Tag, ConvertKit + MailerLite nodes (parallel); AI welcome emails 1–3 and 4+5; sequence builder. Needs: Config Loader at start, enable gate, single-provider branch from config, simplified sequence, credentials via n8n (no YOUR_* in JSON).

### Established Patterns

- Execute Workflow for Config at start; IF enabled then continue else exit (video: exit when disabled; email: webhook responds but no ESP add when disabled).
- "Today's post": latest Content Log row where date = today, status ≠ publish_failed; sheet and tab from config.
- Parse & Validate after every LLM (regex + try/catch + fallback); JSON envelope where applicable.
- Google Sheets: append for Video Log; tab name from config. Content Log shape from Phase 1 (date, status, wp_url, …).

### Integration Points

- Video: Schedule 10:30 → Config Loader → IF VIDEO_PRODUCTION_ENABLED → Get today's post (config sheet + CONTENT_LOG_TAB) → Fetch post body (WP URL from config) → LLM generate multiple scripts (Parse & Validate) → For each script: call 14_Video_Production (or equivalent) with script/title/blog_url/platform → Log each result to Video Log (one row per video). Scheduling: either 14_Video_Production's Blotato Publish or write to Reels Scripts/YT Shorts Queue for existing social pipeline.
- Email: Webhook → Config Loader (or load config once at start of execution) → IF EMAIL_NEWSLETTER_ENABLED → Validate & Tag → IF EMAIL_PROVIDER = convertkit → Add to ConvertKit (else MailerLite) → trigger ESP sequence; first email sent by ESP. Optional: log subscription to a "Newsletter Subscribers" or "Email Log" sheet.

</code_context>

<specifics>
## Specific Ideas

- Prefer no extra paid subscriptions for video — Blotato or free/stock method.
- One post → several scripts and videos, then scheduled for publish (multi-asset per post).
- Email: config-driven single provider; simplified sequence; gate and ESP-driven first email.

</specifics>

<deferred>
## Deferred Ideas

- Pictory/InVideo as primary video providers (user preferred Blotato/free).
- Dual-write to both ConvertKit and MailerLite (config-driven one provider only in Phase 4).
- Long 5-email welcome sequence (simplified for Phase 4).
- Workflow-triggered "send first email now" API (rely on ESP automation instead).

</deferred>

---

*Phase: 04-content-satellites*
*Context gathered: 2026-03-12*
