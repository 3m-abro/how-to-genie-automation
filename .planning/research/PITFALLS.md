# Pitfalls Research

**Domain:** Adding Islamic content, competitor intelligence, voice/audio, content repurposing, docs consolidation, and archive/cleanup to existing HowTo-Genie n8n/Ollama/Sheets automation  
**Researched:** 2026-03-13  
**Confidence:** HIGH (codebase + docs); MEDIUM (external APIs)

Focus: **Common mistakes when ADDING these features to the existing system** — not greenfield. Integration pitfalls with Content Log, Config Loader, Execute Workflow, and schedule ordering.

---

## Critical Pitfalls

### Pitfall 1: Islamic workflow Code node references wrong node name (no emoji)

**What goes wrong:**  
The "Analyze Islamic Calendar Context" Code node uses `$('Fetch Hijri Date & Islamic Calendar')` and `$('Fetch Prayer Times & Special Days')`. The actual node names in the JSON are `📅 Fetch Hijri Date & Islamic Calendar` and `🕌 Fetch Prayer Times & Special Days`. n8n resolves nodes by exact name; the reference without the emoji prefix fails at runtime, so the Code node gets no input from those HTTP nodes.

**Why it happens:**  
Copy-paste or editing node names in the UI adds emoji; Code nodes were written with the logical name only. No automated check enforces that `$('X')` matches a real node name.

**How to avoid:**  
- In every Code node that uses `$('Node Name')`, verify the string matches the **exact** `name` of the source node (including emoji and spaces).  
- After adding or renaming nodes, run the workflow once and confirm the Code node receives the expected input.  
- Consider a small checklist in the phase: "All $('...') references in Islamic workflow match node names character-for-character."

**Warning signs:**  
- Code node output is empty or shows fallback/undefined for fields that should come from AlAdhan.  
- Error in execution: "Could not find node" or similar when Code runs.

**Phase to address:**  
Islamic content phase (first phase that adds/modifies the Islamic Content Specialization Engine).

---

### Pitfall 2: New workflows hardcode YOUR_GOOGLE_SHEET_ID and skip Config Loader

**What goes wrong:**  
Islamic, Voice, Repurposing, and Competitor workflows in the repo use `documentId: "YOUR_GOOGLE_SHEET_ID"` and fixed sheet names. The rest of the system uses Config Loader (core/01_Config_Loader.json) and reads `GOOGLE_SHEET_ID`, `CONTENT_LOG_TAB`, etc. from htg_config.csv. After adding these workflows, they either fail (invalid sheet ID) or write to the wrong spreadsheet if someone replaces the placeholder once globally but config later points elsewhere.

**Why it happens:**  
Templates and v4.0 growth workflows were built before the config-driven pattern was standard. Adding them "as is" keeps the old pattern.

**How to avoid:**  
- For every **new or integrated** workflow that touches Google Sheets: add Config Loader as the first node (after trigger), add a Code node to normalize config (e.g. enable flags, sheet ID), and use `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID` and `CONTENT_LOG_TAB` (or the appropriate tab key) in every Google Sheets node.  
- Replace all `YOUR_GOOGLE_SHEET_ID` and `your-blog.com` with config expressions.  
- Document in the phase plan: "All Sheets and WordPress URLs must come from config."

**Warning signs:**  
- Workflow JSON contains literal `YOUR_GOOGLE_SHEET_ID` or `your-blog.com`.  
- Different workflows use different sheet IDs in the same codebase.

**Phase to address:**  
Each phase that adds or wires a workflow (Islamic, Competitor, Voice, Repurposing). Docs/archive phase can include a verification checklist for "no YOUR_* in active workflows."

---

### Pitfall 3: "Today's post" and "yesterday's post" without timezone

**What goes wrong:**  
Content Log rows have a date column; satellites assume "today" or "yesterday" in server/local time. If the server is UTC but content is planned in America/New_York, the Repurposing or Voice workflow may pick the wrong row (or none) when run at noon UTC vs noon ET. Same for Islamic (5 AM) and Orchestrator (8 AM) — ordering depends on a shared notion of "content day."

**Why it happens:**  
Existing v1.0 patterns (e.g. Multi-Language, A/B Testing) use `CONTENT_DAY_TIMEZONE` or `TIMEZONE` from config and compute today as `toLocaleDateString('en-CA', { timeZone })`. New workflows (Repurposing, Voice) often just read "Content Log" and take the first or last row, or filter by `new Date()`.

**How to avoid:**  
- Any workflow that reads "today's post" or "yesterday's post" from Content Log must: (1) get timezone from config (CONTENT_DAY_TIMEZONE or TIMEZONE or 'UTC'), (2) compute today/yesterday as YYYY-MM-DD in that timezone, (3) filter rows by that date and by status (e.g. exclude publish_failed).  
- Use the same pattern as in 02-01-PLAN (Multi-Language) and 03-01-PLAN (A/B Testing): Code node "Filter today's post" / "Filter yesterday's post" with explicit timezone.

**Warning signs:**  
- No TIMEZONE or CONTENT_DAY_TIMEZONE in the workflow.  
- Filter uses `new Date().toISOString().split('T')[0]` without timezone.  
- Repurposing or Voice runs at 12:00 / 16:00 and logs "no post" when a post exists for that calendar day in the owner's timezone.

**Phase to address:**  
Content repurposing phase; Voice & audio phase. Optionally Islamic if it ever reads Content Log for "today."

---

### Pitfall 4: AlAdhan API response shape and HTTP errors unhandled

**What goes wrong:**  
Islamic workflow calls `https://api.aladhan.com/v1/gToH?date=...` and `.../v1/calendar/YYYY/M?...`. If the API returns 429 (rate limit), 5xx, or a changed JSON shape (e.g. `data` missing or nested differently), the next Code node assumes `$('...').item.json.data` and throws or produces wrong Hijri month/day. The calendar logic then misdetects Ramadan/Eid/Jumua.

**Why it happens:**  
No IF node after HTTP Request to check status code; no try/catch in the Code node with fallback for missing/malformed data. AlAdhan is free and generally stable but not contractually fixed.

**How to avoid:**  
- Add an IF node after each AlAdhan HTTP node: condition `{{ $json.statusCode === 200 }}` (or equivalent); false branch → log to Error Log tab and stop (no append to Islamic Content Queue).  
- In "Analyze Islamic Calendar Context," defensively read: `const hijriData = $('...').item?.json?.data ?? {}` and validate `hijriData.hijri` exists; if not, set a safe default (e.g. content_theme: 'general', content_boost_multiplier: 1) and optionally set a flag `islamic_api_failed: true` for logging.  
- Use DD-MM-YYYY for the date parameter (AlAdhan expects this format).

**Warning signs:**  
- No IF after "Fetch Hijri Date" or "Fetch Prayer Times."  
- Code node has no fallback when `data` or `hijri` is undefined.  
- Ramadan/Eid content never triggers or triggers on wrong days.

**Phase to address:**  
Islamic content phase.

---

### Pitfall 5: Competitor / RSS polling too aggressive → blocks or 429s

**What goes wrong:**  
Competitor workflow runs every 3 hours and fetches multiple RSS feeds (and Reddit) in parallel or quick succession. Some sites rate-limit or block aggressive User-Agents; Reddit's JSON API can throttle. Result: 403/429, failed runs, or IP blocking.

**Why it happens:**  
Default design is "fetch all competitors every run." No per-feed delay, no cache, no use of COMPETITOR_RSS_FEEDS from config (workflow has hardcoded competitor list). Running every 3h is reasonable, but 10+ feeds at once without backoff is not.

**How to avoid:**  
- Use COMPETITOR_RSS_FEEDS from config (and optionally a dedicated config key for Reddit subreddits) so the list is tunable without editing workflow JSON.  
- Add a short delay between feeds (e.g. 2–5 seconds) or process in small batches so the run doesn’t hammer 10 domains in one second.  
- After each HTTP Request, add IF for non-200; on failure, log and continue with other feeds (don’t fail the whole run).  
- Consider caching last successful fetch per feed in a sheet or in-memory to avoid re-fetching identical data on retries.

**Warning signs:**  
- All competitor HTTP nodes run in one batch with no delay.  
- Repeated 403/429 in execution logs.  
- Competitor list is only in Code node, not in htg_config.csv.

**Phase to address:**  
Competitor intelligence phase.

---

### Pitfall 6: Voice pipeline assumes Multilingual Content exists and column names match

**What goes wrong:**  
Voice & Audio workflow reads "Multilingual Content" and maps rows to languages by a "Language" column (e.g. "Spanish", "Arabic"). If the Multi-Language Expansion workflow writes different column names or the tab is missing/empty, "Configure Voice Settings" returns wrong langCode or no items; TTS is skipped or generated in the wrong voice.

**Why it happens:**  
Downstream workflow was designed against an assumed schema; no contract check or fallback when the tab is empty or columns differ (e.g. "Language" vs "language_code").

**How to avoid:**  
- Document the contract: Multilingual Content tab must have columns (e.g. Language, Translated Title, URL, ...) and Voice workflow must use the same names (or a shared doc with column names).  
- In Voice workflow: if "Get All Language Versions" returns no rows, output a single item e.g. `{ noMultilingualContent: true }` and branch so no TTS nodes run (avoid empty loop or errors).  
- Normalize language to a canonical code (en, es, ar, ...) in one place and reuse (e.g. same mapping as in Multi-Language workflow).

**Warning signs:**  
- Voice workflow has no "no rows" handling after "Get All Language Versions."  
- Column names in Code ("Language", "Translated Title") don’t match what Multi-Language writes.  
- TRANSLATION_ENABLED is false but Voice still runs and expects 9 rows.

**Phase to address:**  
Voice & audio phase; optionally Multi-Language phase if schema is formalized.

---

### Pitfall 7: TTS provider (local vs cloud) and language codes

**What goes wrong:**  
htg_config.csv has `VOICE_PROVIDER,local` but the Voice workflow JSON uses ElevenLabs and Google Cloud TTS (voice_id, provider, language_code). If the phase enforces "local only" (no cloud cost), the current Voice workflow will fail or need a different branch (e.g. Piper or local TTS). Conversely, if cloud is allowed, language_code must match the provider (e.g. ElevenLabs Turbo v2.5 / Flash v2.5 support language_code; other models may not). Mismatched locale codes (e.g. "pt-BR" vs "pt_BR") can cause wrong pronunciation or API errors.

**Why it happens:**  
Config says one thing (local), workflow implements another (ElevenLabs/Google). Adding Voice "as is" without aligning to config and to a single locale standard causes runtime failures or unexpected cost.

**How to avoid:**  
- Decide in the phase: support only local TTS, only cloud, or both gated by VOICE_PROVIDER.  
- If both: after Config Loader, branch on VOICE_PROVIDER (e.g. local vs elevenlabs vs google); each branch uses the correct nodes and credentials.  
- Use one canonical set of language codes (e.g. ISO 639-1 or provider-specific) in config and in the workflow; map from Sheet "Language" to that set in one Code node.  
- Document which TTS model supports language_code (e.g. ElevenLabs Turbo/Flash v2.5) and use that model when passing language.

**Warning signs:**  
- VOICE_PROVIDER=local but workflow calls ElevenLabs/Google.  
- Language names from Sheets mapped ad hoc (e.g. "Spanish" → "es" in one place, "es-ES" elsewhere).  
- No IF/branch on VOICE_PROVIDER.

**Phase to address:**  
Voice & audio phase.

---

### Pitfall 8: Content Repurposing uses wrong WordPress URL and no idempotency

**What goes wrong:**  
Repurposing Engine uses `https://your-blog.com/wp-json/wp/v2/posts?slug=...` and reads "Content Log" for "today's post" without timezone. So: (1) wrong blog URL unless replaced; (2) "today" may be wrong in UTC vs owner TZ; (3) re-running at noon twice the same day can append duplicate repurposed assets to Sheets or re-publish the same thread.

**Why it happens:**  
Workflow was written as a template; WordPress URL and sheet ID were never wired to config; no "content day" filter; no idempotency key (e.g. post_id + date) to skip if already repurposed.

**How to avoid:**  
- Source WordPress base URL from config (e.g. WORDPRESS_URL from Config Loader).  
- Source "today's post" with timezone-aware filter (see Pitfall 3).  
- Before writing repurposed outputs to Sheets (or posting elsewhere), check if this post_id/date already has a row; if yes, skip or update instead of append.  
- Use Config Loader and GOOGLE_SHEET_ID / CONTENT_LOG_TAB for all Sheets nodes.

**Warning signs:**  
- Literal `your-blog.com` in the workflow.  
- No timezone in "today" filter.  
- No check for "already repurposed today" before appending.

**Phase to address:**  
Content repurposing phase.

---

### Pitfall 9: Archive/cleanup breaks Execute Workflow and webhook references

**What goes wrong:**  
When moving workflows to an archive folder or deleting them, any workflow that calls them via Execute Workflow (by workflow ID or name) will fail at runtime. Example: Orchestrator or Video Production Engine calls a sub-workflow by ID; if that workflow is archived or re-imported with a new ID, the caller still has the old ID. Same for webhooks that trigger a specific workflow.

**Why it happens:**  
n8n stores workflow IDs in the JSON of the caller; archiving/deleting changes which workflows exist and their IDs. Export/import can create new IDs. There is no automatic "update references" when a workflow is moved.

**How to avoid:**  
- Before archiving or deleting any workflow: grep (or search) the repo for its workflow ID and for "Execute Workflow" nodes that reference it; update those nodes to the new workflow ID if the target workflow was re-imported, or remove/redirect the call if the workflow is retired.  
- Prefer referencing sub-workflows by name where n8n supports it, or document "after import, set workflowId for X to the new ID" in a single place (e.g. setup doc).  
- In the archive phase: produce a list "Workflows that call other workflows" and for each archived workflow, list "Callers that must be updated."

**Warning signs:**  
- Execute Workflow node with a hardcoded workflow ID that no longer exists after cleanup.  
- Import fails with foreign key or "workflow not found" when running a parent workflow.

**Phase to address:**  
Archive/cleanup phase (and any phase that moves or renames workflows).

---

### Pitfall 10: Docs consolidation creates a single source that goes stale

**What goes wrong:**  
Consolidating into one authoritative MD (workflows, UI, reference) improves discoverability, but when workflows or config keys change in later phases, the doc is not updated. New contributors or the owner follow the doc and use wrong sheet names, wrong workflow order, or outdated placeholders.

**Why it happens:**  
Docs are updated manually; there is no automated link between workflow JSON (e.g. node names, config keys) and the doc. Phase work focuses on code, not "update the doc."

**How to avoid:**  
- In the docs phase: add an explicit task "Update [DOC] when adding/removing workflows or config keys."  
- In every subsequent phase that adds a workflow or config key: add a verification step "DOC.md reflects new workflow and config keys."  
- Keep a short "Config keys reference" section in the doc that lists every key used by the system (from htg_config.csv and workflow JSON); one place to update when keys change.  
- Optionally, list workflow file names and their schedule (cron) in the doc so schedule ordering is visible.

**Warning signs:**  
- Single DOC exists but mentions removed workflows or old tab names.  
- New config keys (e.g. ISLAMIC_ENABLED, COMPETITOR_FEEDS) not documented.

**Phase to address:**  
Docs consolidation phase; then every phase that adds/changes workflows or config.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Add new workflow without Config Loader | Faster integration | Multiple sources of truth for sheet ID/URL; breaks when config changes | Never for v2.0 |
| Hardcode "today" in Content Log filter | Quick prototype | Wrong row in another TZ; flaky at midnight | Never |
| Skip IF after AlAdhan HTTP | Fewer nodes | Silent wrong Hijri data; wrong Ramadan/Eid | Never |
| Competitor list in Code only | No config change needed | Can't tune feeds without editing workflow | Only for MVP; move to config in same phase |
| Voice workflow without "no Multilingual rows" branch | Simpler flow | Errors or no-op when translation disabled | Never for production |
| Archive workflow without updating callers | Fast cleanup | Broken Execute Workflow at next run | Never |
| Document once, never touch | Single doc shipped | Stale references, wrong setup | Never; tie doc updates to phase tasks |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Google Sheets | Use YOUR_GOOGLE_SHEET_ID in new workflows | Use Config Loader; all documentId/sheetName from config |
| WordPress | Use your-blog.com in Repurposing/Video | Use WORDPRESS_URL from config (or equivalent) |
| AlAdhan API | Assume 200 and fixed JSON shape | IF statusCode === 200; defensive parsing with fallbacks |
| Competitor RSS | Fetch all feeds in parallel, no delay | Per-feed delay or batching; IF on non-200; use COMPETITOR_RSS_FEEDS from config |
| Reddit (competitor) | No User-Agent or aggressive polling | Set User-Agent (e.g. HowToGenie-Bot/1.0); keep 3h interval; handle 429 |
| Multilingual Content → Voice | Assume tab and columns exist | Check for empty rows; document column contract; handle noMultilingualContent |
| TTS (ElevenLabs/Google) | Use language names instead of codes | Map to provider’s locale codes (e.g. en, es, pt-BR); use model that supports language_code |
| Execute Workflow | Leave workflowId as placeholder after import | Document "set workflowId after import"; after archive, update all callers |
| n8n import | Import archived workflows without re-mapping IDs | Re-import into same instance or update all Execute Workflow nodes with new IDs |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Competitor run fetches 10+ feeds at once | 429/403, failed runs | Stagger requests or batch with delay | Every 3h run with many feeds |
| Voice runs for 9 languages in one go | Timeouts, memory | Process in batches or limit concurrent TTS calls | 9+ long articles same run |
| Repurposing runs multiple LLM branches in parallel | Ollama overload, slow responses | Reuse existing pattern: sequential or limited concurrency per Orchestrator | When many branches run simultaneously |
| Content Log read without limit | Slow Sheets API for large tab | Filter by date (today/yesterday); read only needed columns if possible | 1000+ rows in Content Log |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|-------------|
| Commit workflow JSON with filled-in GOOGLE_SHEET_ID or API keys | Leak of sheet data or TTS quota | Use placeholders or config references only; secrets in n8n credentials |
| AlAdhan or competitor URLs with API key in query | Key in logs or export | AlAdhan is key-free; any future keyed API must use n8n credentials |
| TTS API key in workflow body | Key in export/share | Store in n8n credential; reference by name in HTTP node |

---

## "Looks Done But Isn't" Checklist

- [ ] **Islamic:** All `$('...')` in Code nodes match node names exactly (including emoji); IF after both AlAdhan HTTP nodes; fallback when `data`/`hijri` missing; Islamic Content Queue tab exists in Sheet.
- [ ] **Competitor:** COMPETITOR_RSS_FEEDS (and Reddit list if desired) from config; delay or batching between feeds; IF after HTTP to handle non-200; no hardcoded competitor list in Code only.
- [ ] **Voice:** Config Loader present; VOICE_PROVIDER branch (local vs cloud); "no Multilingual Content" path; language codes aligned with TTS provider; Multilingual Content column names match.
- [ ] **Repurposing:** Config Loader; WORDPRESS_URL and GOOGLE_SHEET_ID from config; timezone-aware "today" filter; idempotency (no duplicate append same post/date).
- [ ] **Docs:** Single DOC lists all active workflows, schedules, and config keys; no references to archived workflows without "archived" label; config key list updated when keys added.
- [ ] **Archive:** List of workflows that call others; all Execute Workflow nodes updated or removed for any archived workflow; no broken workflow IDs in active JSON.

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Wrong node name in Islamic Code | LOW | Fix string in Code node to match exact node name (with emoji); re-run. |
| Hardcoded sheet ID / blog URL | MEDIUM | Add Config Loader to workflow; replace placeholders with config expressions; re-test. |
| Wrong "today" row | LOW | Add timezone from config and filter by YYYY-MM-DD in that TZ; re-run. |
| AlAdhan failure unhandled | LOW | Add IF after HTTP; add fallbacks in Code; re-run. Next run will handle failures. |
| Competitor 429/block | MEDIUM | Add delays and IF nodes; optionally reduce frequency or feeds; clear any block (time or IP). |
| Voice wrong language / no rows | MEDIUM | Fix column mapping and no-rows branch; re-run Voice; ensure Multi-Language ran and tab populated. |
| TTS provider mismatch | MEDIUM | Add VOICE_PROVIDER branch; configure credentials; or switch to local TTS and implement that path. |
| Execute Workflow broken after archive | HIGH | Find all callers; update workflow IDs or re-import archived workflow and use new ID; test each caller. |
| Doc stale | LOW | Update DOC with current workflows and config keys; add phase task for future updates. |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|---------------|
| Islamic node name + AlAdhan handling | Islamic content phase | Run Islamic workflow; confirm Hijri and occasion detected; no runtime error on missing node. |
| Config Loader + no YOUR_* in new workflows | Each feature phase (Islamic, Competitor, Voice, Repurposing) | Grep for YOUR_GOOGLE_SHEET_ID, your-blog.com in active workflows; all use config. |
| Timezone for "today"/"yesterday" | Repurposing, Voice | Filter uses TIMEZONE/CONTENT_DAY_TIMEZONE; correct row chosen in tests. |
| Competitor rate limit / config | Competitor intelligence phase | COMPETITOR_RSS_FEEDS from config; delay or batch; IF after HTTP; no 429 in normal run. |
| Voice schema + provider + no-rows | Voice & audio phase | Run with Multilingual empty and with data; correct language codes; VOICE_PROVIDER respected. |
| Repurposing idempotency + URL | Content repurposing phase | Run twice same day; no duplicate rows; WordPress URL from config. |
| Archive and Execute Workflow IDs | Archive/cleanup phase | After archive, run every workflow that has Execute Workflow; all succeed. |
| Doc and config key list | Docs consolidation; every phase | DOC lists current workflows and config keys; new keys added in same phase. |

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|----------------|------------|
| Islamic content | Node name mismatch; AlAdhan 5xx/429 | Fix $() names; IF + fallbacks; test on Ramadan/Eid edge dates |
| Competitor intelligence | Blocking; duplicate with Reddit in Orchestrator | Config-driven feeds; delay; dedupe with existing Topic Research if needed |
| Voice & audio | Local vs cloud; 9 languages; empty Multilingual | VOICE_PROVIDER branch; canonical locale codes; no-rows path |
| Content repurposing | Wrong blog; wrong day; duplicate output | Config URL; timezone filter; idempotency check |
| Docs consolidation | Single doc goes stale | Tie doc update to every phase that adds workflows/config |
| Archive/cleanup | Broken callers | List callers before archive; update workflow IDs; verify |

---

## Sources

- Project: `.planning/PROJECT.md`, `CLAUDE.md`, `htg_config.csv`
- Workflows: `growth/HowTo-Genie v4.0 — Islamic Content Specialization Engine.json`, `Voice & Audio Content Pipeline.json`, `Competitor Intelligence & Trend Monitor.json`, `content/v3.0 — Content Repurposing Engine.json`
- v1.0 patterns: `.planning/milestones/v1.0-phases/02-distribution-growth/02-01-PLAN.md`, `03-01-PLAN.md`, `04-01-PLAN.md`
- AlAdhan: API expects DD-MM-YYYY; location required for calendar; rate limits (client-side handling in some SDKs)
- n8n: Export/import — credential names and workflow IDs; foreign key issues on publish history (n8n 2.3.4+)
- RSS/competitor: Polling frequency and caching to avoid 403/429 (Scrapy, Apify docs; general practice)
- ElevenLabs: language_code for Turbo/Flash v2.5; model-specific behavior

---
*Pitfalls research for: v2.0 Content Expansion & Housekeeping — adding Islamic content, competitor intelligence, voice/audio, content repurposing, docs consolidation, archive/cleanup to existing HowTo-Genie automation*  
*Researched: 2026-03-13*
