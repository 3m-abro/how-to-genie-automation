# Phase 11: Voice & Audio — Research

**Researched:** 2026-03-13  
**Domain:** n8n Voice workflow refactor — config-driven, timezone-aware, local + cloud TTS, Audio Log  
**Confidence:** HIGH (codebase + Phase 10 patterns + CONTEXT decisions)

## Summary

Phase 11 refactors `growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json` into a config-first, timezone-aware workflow that: (1) reads today's post from Content Log and today's rows from Multilingual Content (same timezone as Phase 10 / Multi-Language); (2) branches on VOICE_PROVIDER (local | elevenlabs | google); (3) for local, calls a TTS HTTP server (e.g. Piper at TTS_SERVER_URL); (4) writes audio files to a configurable local path only (no GCS/Spotify/YouTube this phase); (5) logs to a config-driven Audio Log tab. The existing workflow already has ElevenLabs and Google TTS branches and an "Adapt to Audio Script" LLM + Parse & Validate; it lacks Config Loader, timezone filter, empty-Multilingual handling, local TTS branch, and config-driven sheet/tab/path. Reuse Config Loader, timezone "today" pattern from Phase 10 and Multi-Language, and Multilingual Content column contract (Date, Language, Translated Title, URL) so Voice and Multi-Language stay aligned.

**Primary recommendation:** Refactor in place: Execute Config Loader first; Read Content Log → Filter today's post (timezone); Read Multilingual Content (config tab) → Filter rows by same today; IF no rows → branch (planner chooses: skip / English-only from Content Log / noMultilingualContent); per row: Configure Voice Settings (language → provider/voice_id; drive from VOICE_PROVIDER + locale map); Fetch article by URL; LLM Adapt to Audio Script → Parse & Validate (envelope); Route by VOICE_PROVIDER (local / elevenlabs / google); local = HTTP POST to TTS_SERVER_URL with text, binary response; Process Audio File (handle binary or base64); Write binary to AUDIO_OUTPUT_PATH (Code + Write Binary File or n8n pattern); append to AUDIO_LOG_TAB. Remove GCS upload, RSS, Spotify, YouTube, Voice Note branch for Phase 11 scope.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **TTS provider:** Both local and cloud. Branch on VOICE_PROVIDER: local = Piper (or similar) behind HTTP (TTS_SERVER_URL); cloud = ElevenLabs or Google. Implement both paths this phase.
- **Local TTS:** Same machine as n8n; config key e.g. TTS_SERVER_URL (localhost or configurable URL).
- **Cloud credentials:** Planner's discretion: credential strategy (n8n credentials only, which names) and document in plan.
- **Language codes:** Planner's discretion: where canonical mapping (Sheet "Language" → provider locale) lives and how it's documented; align with Multi-Language column contract.
- **Empty Multilingual Content / no translations:** Behavior when no rows for today — Planner's discretion (skip run vs English-only from Content Log vs other); where to branch and how to represent (e.g. noMultilingualContent); column contract — Planner's discretion (fixed doc vs config-driven column names vs hybrid); timezone for "today" — Planner's discretion (e.g. TIMEZONE from config), document.
- **Audio output and Audio Log:** Storage local only — configurable path (e.g. AUDIO_OUTPUT_PATH); no cloud upload this phase. Audio Log row shape, idempotency key/behavior, tab name (e.g. AUDIO_LOG_TAB) — Planner's discretion; document in CONFIG-KEYS / HOWTOGENIE.
- **Script adaptation and length:** Full vs short vs config-driven — Planner's discretion; LLM schema and validation (envelope per project rules); TTS script cleaning (strip stage directions/markdown); per-language failure — Planner's discretion.

### Claude's Discretion
- All items above marked "Planner's discretion": researcher and planner choose concrete options and document in plan and CONFIG-KEYS / HOWTOGENIE as appropriate.

### Deferred Ideas (OUT OF SCOPE)
- VOICE-05: Local TTS short clips; full-length podcast for all 9 languages (cost/storage) — future phase. This phase delivers one audio per language (full or short per planner choice) and local file output only.
- Cloud upload (GCS, Spotify, YouTube, podcast RSS) — not in Phase 11; local path only.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| VOICE-01 | Voice workflow reads today's post from Content Log and Multilingual Content tab (timezone-aware) | Config Loader first; Read Content Log with CONTENT_LOG_TAB; Code "Filter today's post" using CONTENT_DAY_TIMEZONE \|\| TIMEZONE, toLocaleDateString('en-CA', { timeZone }). Read Multilingual Content with MULTILINGUAL_CONTENT_TAB; Code "Filter Multilingual by today" same timezone and Date column. Align with Phase 10 and Multi-Language pattern. |
| VOICE-02 | Voice workflow adapts content to TTS script and produces one audio per language (VOICE_PROVIDER from config: local or cloud) | Route by VOICE_PROVIDER: local → HTTP to TTS_SERVER_URL (Piper-style POST text, binary wav/mp3); elevenlabs → existing ElevenLabs node (credential); google → existing Google TTS node (credential). Configure Voice Settings: language → voice_id/locale per provider; document mapping. One audio output per language row. |
| VOICE-03 | Voice workflow uses Config Loader first; handles empty Multilingual Content rows; documents column contract | Execute Workflow Config Loader at start. IF no rows for today in Multilingual Content → planner-defined branch (skip / English-only / noMultilingualContent). Column contract: Multilingual = Date, Language, Translated Title, URL (and optional columns); document in plan and HOWTOGENIE; Voice reads same columns. |
| VOICE-04 | Voice runs after Multi-Language (e.g. 4 PM); logs outputs to Audio Log / config-driven tab | Schedule 16:00 (or orchestrator hour 16). Log to tab from config (AUDIO_LOG_TAB). Write audio files to AUDIO_OUTPUT_PATH only (no cloud). Document row shape and idempotency in CONFIG-KEYS / HOWTOGENIE. |
</phase_requirements>

## Standard Stack

### Core
| Component | Version / reference | Purpose | Why standard |
|-----------|--------------------|---------|--------------|
| n8n | (instance) | Workflow runtime | Existing; Voice runs in n8n |
| core/01_Config_Loader.json | — | Load config into execution context | GOOGLE_SHEET_ID, CONTENT_LOG_TAB, MULTILINGUAL_CONTENT_TAB, TIMEZONE, VOICE_PROVIDER, TTS_SERVER_URL, AUDIO_OUTPUT_PATH, AUDIO_LOG_TAB |
| Ollama (lmChatOllama) | llama3.2:latest / qwen2.5:7b | Adapt to Audio Script LLM | Same as existing Voice node and project rules |
| Google Sheets | — | Content Log (read), Multilingual Content (read), Audio Log (write) | Data backbone; tab names from config |
| HTTP Request | — | TTS: Piper/local URL, ElevenLabs, Google TTS | Existing for cloud; add local branch |

### Supporting
| Component | Purpose | When to use |
|-----------|---------|-------------|
| Code (plain JS) | Timezone today, filter Multilingual rows, Parse & Validate LLM, voice config map, build file path, handle binary/base64 | After Config Loader, after each Sheet read, after LLM, before/after TTS |
| IF / Switch | No post today; no Multilingual rows; VOICE_PROVIDER (local / elevenlabs / google) | Early exit and provider branching |
| Write Binary File / Code | Write audio to AUDIO_OUTPUT_PATH | After Process Audio File; n8n may use "Write Binary File" node or Code + external write; project uses local path only this phase |

### TTS providers
| Provider | Config | Request pattern | Response |
|----------|--------|-----------------|----------|
| local | VOICE_PROVIDER=local, TTS_SERVER_URL | HTTP POST, body = plain text (tts_script), URL e.g. http://localhost:5000 | Binary audio (wav typical for Piper) |
| elevenlabs | VOICE_PROVIDER=elevenlabs, n8n credential | POST https://api.elevenlabs.io/v1/text-to-speech/{{voice_id}}, JSON body, response format file | Binary mp3 |
| google | VOICE_PROVIDER=google, n8n credential | POST texttospeech.googleapis.com/v1/text:synthesize, JSON body with audioContent base64 | JSON with audioContent; decode to binary in Code |

### Alternatives considered
| Instead of | Could use | Tradeoff |
|-----------|-----------|----------|
| Config Loader sub-workflow | Inline config | Config Loader is project standard |
| Single TTS node with provider param | Separate branches per provider | Current workflow already routes by provider; local adds one more branch |
| Writing to GCS | Local path only | Phase 11 scope: local only; cloud upload deferred |

**Installation:** None for n8n. For local TTS: Piper (or similar) HTTP server must be running and reachable at TTS_SERVER_URL; e.g. `python3 -m piper.http_server --model <path>` (GET/POST with text, returns wav). Add config keys to htg_config.csv / Data Table: TTS_SERVER_URL, AUDIO_OUTPUT_PATH, AUDIO_LOG_TAB, MULTILINGUAL_CONTENT_TAB (if not present).

## Architecture Patterns

### Recommended flow (high level)
1. Schedule Trigger (16:00) → Execute Workflow (Config Loader).
2. Read Content Log (documentId/tab from config) → Code "Filter today's post" (timezone-aware, same as Phase 10 / Multi-Language).
3. IF no post today → end (no error; optional log).
4. Read Multilingual Content (config MULTILINGUAL_CONTENT_TAB) → Code "Filter Multilingual by today" (Date column = today in same timezone).
5. IF no Multilingual rows → planner-defined branch (skip / English-only from Content Log / noMultilingualContent item).
6. Per language row: Configure Voice Settings (language → voice_id, provider from VOICE_PROVIDER + locale map).
7. Fetch article content by URL (HTTP Request to WordPress or URL from row).
8. LLM "Adapt to Audio Script" → Parse & Validate (success/data/error envelope; podcast_title, podcast_script, tts_script, etc.).
9. Route by VOICE_PROVIDER: local → HTTP POST to TTS_SERVER_URL; elevenlabs → ElevenLabs HTTP; google → Google TTS HTTP.
10. Process Audio File (unify binary: ElevenLabs = binary; Google = base64 decode; local = binary response).
11. Write audio to AUDIO_OUTPUT_PATH (file path from config + filename e.g. slug_lang_timestamp.mp3).
12. Append to Audio Log tab (config AUDIO_LOG_TAB); idempotency per planner (e.g. post+date+language key).

### Pattern 1: Timezone-aware "today" (Content Log and Multilingual)
**What:** Compute today as YYYY-MM-DD in owner timezone; filter Content Log and Multilingual Content rows by that date.  
**When:** Any satellite that reads "today's post" or "today's translations."  
**Source:** Phase 10 RESEARCH; growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json "Filter today's post."

```javascript
const config = $('⚙️ Load Config').item.json;
const tz = config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC';
const today = new Date().toLocaleDateString('en-CA', { timeZone: tz });
const rows = $input.all().map(i => i.json);
const dateKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'date') || 'date';
const valid = rows.filter(r => (r[dateKey] || r.date || '').toString().slice(0, 10) === today);
if (valid.length === 0) return [{ json: { noMultilingualToday: true, rows: [] } }];
return [{ json: { noMultilingualToday: false, rows: valid } }];
```

Use same `today` and tz for both Content Log filter and Multilingual filter so "today" is consistent.

### Pattern 2: Multilingual Content column contract (align with Multi-Language)
**What:** Multi-Language writes: Date, English Post ID, Language, Country, Translated Title, URL, Translation Quality, Local Keywords, Affiliate Networks, Cultural Adaptations, Status. Voice minimally needs: Date, Language, Translated Title, URL for filtering and TTS.  
**When:** Reading Multilingual Content and mapping to voice config.  
**Source:** growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json "📊 Log Translated Posts" columns.

Use case-insensitive key lookup for robustness (e.g. `Language` vs `language`). Language column may be full name (e.g. "Spanish"); map to locale code (es, pt-BR, de, fr, hi, id, ar, ja, en) for provider voice_id / languageCode. Document the contract in HOWTOGENIE and plan.

### Pattern 3: Local TTS HTTP (Piper-style)
**What:** Local TTS server exposes HTTP; POST body = raw text; response = binary audio.  
**When:** VOICE_PROVIDER=local; URL from TTS_SERVER_URL.  
**Source:** Web search — Piper Python HTTP server (README_http.md): POST with Content-Type text/plain, body = text; response wav.

```text
URL: {{ $('⚙️ Load Config').item.json.TTS_SERVER_URL || 'http://localhost:5000' }}
Method: POST
Headers: Content-Type: text/plain (or application/x-www-form-urlencoded with text=)
Body: {{ $json.tts_script }} (or truncated for length limit)
Options: response format = file (binary)
```

Process Audio File must accept binary from this node (same as ElevenLabs path). If Piper returns wav and downstream expects mp3, planner can document "local outputs wav" or add conversion (out of scope if not required).

### Pattern 4: Parse & Validate after LLM (envelope)
**What:** LLM "Adapt to Audio Script" must return JSON with success/data/error; Code node parses and returns fallback on error.  
**When:** After every LLM node.  
**Source:** n8n-json-contracts.mdc; Phase 10 RESEARCH.

Existing Voice workflow already has "Parse Audio Script" with podcast_title, podcast_script, tts_script (cleaned), estimated_duration, word_count, etc. Align with envelope: either wrap in success/data/error or keep current shape and ensure fallback defaults; document schema in plan.

### Anti-patterns to avoid
- **Hardcoded sheet/URL/keys:** No YOUR_GOOGLE_SHEET_ID, no YOUR_ELEVENLABS_API_KEY, no your-blog.com; use config and n8n credentials.
- **"Today" without timezone:** Do not use server date; use CONTENT_DAY_TIMEZONE || TIMEZONE for both Content Log and Multilingual filters.
- **Reading Multilingual without filtering by today:** Voice must only process today's translations; filter by Date column in same timezone.
- **Skipping empty Multilingual handling:** If Multi-Language didn't run or wrote no rows, workflow must branch explicitly (not assume 9 rows).
- **Cloud upload in Phase 11:** Do not implement GCS, Spotify, YouTube, RSS in this phase; local file write only.

## Don't Hand-Roll

| Problem | Don't build | Use instead | Why |
|---------|-------------|-------------|-----|
| Config | Per-workflow env or hardcoded IDs | Config Loader + $('⚙️ Load Config').item.json | Single source; all satellites use it |
| "Today" in timezone | Server date or UTC | toLocaleDateString('en-CA', { timeZone: tz }) with config TIMEZONE | Phase 10 PITFALLS; Multi-Language pattern |
| TTS API for local | Custom binary protocol | HTTP POST to TTS_SERVER_URL (Piper or compatible) | Piper and wrappers use simple HTTP; no custom client |
| LLM JSON parsing | Ad hoc regex only | Parse & Validate Code node with success/data/error and fallback | n8n-json-contracts; ollama-json-only |
| Credentials for cloud TTS | Hardcoded API keys in JSON | n8n credential references (xi-api-key, Google API key) | Security; document credential names in plan |

**Key insight:** Same as Phase 10: config-first, timezone-explicit. Voice adds: provider branching (local vs cloud) and local file write; do not hand-roll "today" or config.

## Common Pitfalls

### Pitfall 1: Hardcoded YOUR_* and wrong sheet/URL
**What goes wrong:** Workflow still has YOUR_GOOGLE_SHEET_ID, YOUR_ELEVENLABS_API_KEY, YOUR_GOOGLE_API_KEY; runs fail or leak placeholders.  
**How to avoid:** Config Loader first; all Sheets nodes use config for documentId and sheetName; cloud TTS use n8n credentials; grep for YOUR_ before sign-off.  
**Warning signs:** Literal YOUR_ in workflow JSON.

### Pitfall 2: "Today" without timezone for Content Log or Multilingual
**What goes wrong:** Voice picks wrong day's post or wrong Multilingual rows when server TZ ≠ owner TZ.  
**How to avoid:** Same pattern as Phase 10 and Multi-Language: CONTENT_DAY_TIMEZONE || TIMEZONE, toLocaleDateString('en-CA', { timeZone }). Apply to both Content Log filter and "Filter Multilingual by today."  
**Warning signs:** Filter uses new Date().toISOString().slice(0,10) or no timezone variable.

### Pitfall 3: No branch for empty Multilingual Content
**What goes wrong:** Workflow assumes 9 rows; when Multi-Language disabled or failed, Voice errors or produces no output.  
**How to avoid:** After reading Multilingual Content and filtering by today, IF rows.length === 0 → explicit branch (skip run / English-only from Content Log / single item noMultilingualContent); document in plan.  
**Warning signs:** No IF after "Filter Multilingual by today"; direct loop over rows without empty check.

### Pitfall 4: Local TTS branch missing or wrong URL
**What goes wrong:** VOICE_PROVIDER=local but workflow only has elevenlabs/google; or TTS_SERVER_URL not used.  
**How to avoid:** Route by VOICE_PROVIDER with three branches: local (HTTP to TTS_SERVER_URL), elevenlabs, google. Document TTS_SERVER_URL default (e.g. http://localhost:5000).  
**Warning signs:** Only two branches in "Route by Voice Provider."

### Pitfall 5: Audio Log or file path hardcoded
**What goes wrong:** Tab name "Audio Content" or path /tmp/audio hardcoded; cannot override per env.  
**How to avoid:** AUDIO_LOG_TAB and AUDIO_OUTPUT_PATH from config; document in HOWTOGENIE.  
**Warning signs:** Literal "Audio Content" or path in JSON.

### Pitfall 6: Process Audio File assumes only ElevenLabs/Google
**What goes wrong:** Local TTS returns binary in different shape (e.g. response body directly); Process Audio File fails.  
**How to avoid:** In Process Audio File (or equivalent), branch on provider: ElevenLabs = binary from response; Google = Buffer.from(audioContent, 'base64'); local = binary from HTTP response. Unify to one shape (e.g. binary + fileName + mimeType) for write and log.  
**Warning signs:** Code only handles audioData.data and audioData.audioContent.

## Code Examples

### Filter Multilingual by today (after Read Multilingual Content)
```javascript
const config = $('⚙️ Load Config').item.json;
const tz = config.CONTENT_DAY_TIMEZONE || config.TIMEZONE || 'UTC';
const today = new Date().toLocaleDateString('en-CA', { timeZone: tz });
const rows = $input.all().map(i => i.json);
const dateKey = Object.keys(rows[0] || {}).find(k => k.toLowerCase() === 'date') || 'Date';
const filtered = rows.filter(r => (r[dateKey] || r.Date || '').toString().slice(0, 10) === today);
if (filtered.length === 0) return [{ json: { noMultilingualToday: true } }];
return filtered.map(r => ({ json: r }));
```

### Voice config map (language column → locale + provider voice)
Existing workflow uses voiceConfig with keys en, es, pt-BR, de, fr, hi, id, ar, ja. For local provider, voice_id can be model name or voice name (Piper uses one model per run; planner can document "one voice per language" or config-driven). Use VOICE_PROVIDER from config to set provider per row (or global); language column from Sheet → same locale codes so Multi-Language and Voice agree.

### Local TTS HTTP Request (n8n)
- URL: `={{ ($('⚙️ Load Config').item.json.TTS_SERVER_URL || 'http://localhost:5000').replace(/\/$/, '') }}`
- Method: POST
- Body Content Type: Raw / Text (or form with text=)
- Send Body: Yes; body = `{{ $json.tts_script.substring(0, 5000) }}` (or configurable limit)
- Response: File (binary) so n8n passes binary to next node

### Write audio to local path (concept)
n8n may not have a built-in "Write Binary to Path" node; options: (1) Code node that uses Node.js fs (if available in n8n Code node), (2) "Write Binary File" node if present in instance, (3) external webhook that receives binary and writes. Planner to choose and document. Requirement: files end up at configurable AUDIO_OUTPUT_PATH.

## State of the Art

| Current (v4.0 Voice) | Target (Phase 11) | Impact |
|---------------------|-------------------|--------|
| YOUR_GOOGLE_SHEET_ID, "Content Log", "Multilingual Content" | Config Loader; CONTENT_LOG_TAB, MULTILINGUAL_CONTENT_TAB, GOOGLE_SHEET_ID | Correct sheet/tabs |
| No timezone; no "today" filter for Content Log or Multilingual | Filter today's post + Filter Multilingual by today (same timezone) | Correct day's content only |
| No empty-Multilingual branch | IF no rows → skip / English-only / noMultilingualContent (planner) | No assumption of 9 rows |
| voice_provider from hardcoded map (elevenlabs/google only) | VOICE_PROVIDER from config; branch local / elevenlabs / google | Local TTS supported |
| No local TTS | HTTP to TTS_SERVER_URL; Process Audio handles binary response | Local Piper (or similar) |
| GCS upload, Spotify, YouTube, RSS | Remove; write to AUDIO_OUTPUT_PATH only; log to AUDIO_LOG_TAB | Phase 11 scope: local only |
| "Audio Content" hardcoded | AUDIO_LOG_TAB from config | Config-driven log tab |

**Deprecated / avoid:** Keeping YOUR_*; using server date for "today"; only two TTS branches; cloud upload in this phase.

## Open Questions

1. **n8n Write Binary to local path**  
   - What we know: Requirement is local file at AUDIO_OUTPUT_PATH.  
   - Unclear: Whether n8n has native "Write Binary File" to filesystem or only to cloud/storage; Code node may have restricted fs.  
   - Recommendation: Planner to verify on n8n version; if no native node, document "binary in output for downstream" or use Execute Command / external service; or keep binary in item and document that user must add a node that writes to path.

2. **Idempotency key for Audio Log**  
   - What we know: Re-running same day should not duplicate rows per VOICE-04.  
   - Unclear: Key shape (post+date, post+date+language); behavior (skip append vs update row).  
   - Recommendation: Planner define; document in plan and HOWTOGENIE.

3. **English row when Multilingual empty**  
   - What we know: CONTEXT allows "English-only from Content Log" as one option.  
   - Unclear: Whether to add one synthetic row from Content Log (title, URL, language=en) or skip run.  
   - Recommendation: Planner choose; document in plan.

## Validation Architecture

workflow.nyquist_validation is true in .planning/config.json. This phase is n8n workflow JSON refactor; the repo has no n8n-specific test framework (no pytest.ini, no jest for workflows; only laravel/tests). Validation is manual/smoke.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual / smoke (n8n workflow run) |
| Config file | — |
| Quick run command | Manual: trigger workflow in n8n with test config; check one language path |
| Full suite command | Manual: run full workflow; verify Content Log + Multilingual read, all three provider branches (or two if local not set), file write and Audio Log append |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| VOICE-01 | Reads today from Content Log + Multilingual (timezone) | manual | — | N/A |
| VOICE-02 | Adapts to TTS script; one audio per language; VOICE_PROVIDER branch | manual | — | N/A |
| VOICE-03 | Config Loader first; empty Multilingual branch; column contract doc | manual | — | N/A |
| VOICE-04 | Runs after Multi-Language; logs to config-driven tab; local path only | manual | — | N/A |

### Sampling Rate
- **Per task commit:** Grep workflow for YOUR_*, verify config keys used.
- **Per wave merge:** Manual run in n8n (config with test sheet, optional local TTS server).
- **Phase gate:** Manual verification checklist before /gsd:verify-work (run workflow, check Sheet + files).

### Wave 0 Gaps
- No automated tests for n8n workflow JSON in repo. Recommendation: Planner add verification checklist in PLAN.md (steps to run workflow, expected Sheet rows, expected files under AUDIO_OUTPUT_PATH). Optional: add a small script that validates workflow JSON has no YOUR_* and has expected node names (e.g. "⚙️ Load Config", "Filter today's post") — LOW priority.

## Sources

### Primary (HIGH confidence)
- .planning/phases/11-voice-audio/11-CONTEXT.md — decisions and code context
- growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json — current nodes and flow
- growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json — Config Loader, Filter today's post, MULTILINGUAL_CONTENT_TAB, column contract
- .planning/phases/10-content-repurposing/10-RESEARCH.md — timezone, idempotency, config patterns
- core/01_Config_Loader.json — config output shape
- docs/HOWTOGENIE.md — config keys and schedule

### Secondary (MEDIUM confidence)
- Web search: Piper TTS HTTP server (GET/POST text → wav); Python piper.http_server, port 5000
- htg_config.csv — VOICE_PROVIDER=local present

### Tertiary (LOW confidence)
- Exact Piper HTTP API (Piper project has multiple forks; README_http.md referenced in search). Planner to confirm TTS_SERVER_URL contract (POST body = text, response = binary) for chosen local TTS.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — same as Phase 10 + existing Voice/Multi-Language codebase
- Architecture: HIGH — patterns from Phase 10 and Multi-Language apply; local TTS = HTTP to URL
- Pitfalls: HIGH — same classes as Phase 10 (config, timezone, empty branch) plus provider branch and local path

**Research date:** 2026-03-13  
**Valid until:** ~30 days (stable n8n/config patterns)
