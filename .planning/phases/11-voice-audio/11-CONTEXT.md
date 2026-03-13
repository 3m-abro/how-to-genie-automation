# Phase 11: Voice & Audio — Context

**Gathered:** 2026-03-13  
**Status:** Ready for planning

<domain>
## Phase Boundary

Voice workflow reads today's post from Content Log and Multilingual Content tab (timezone-aware), adapts content to TTS script, produces one audio output per language, runs after Multi-Language (e.g. 4 PM), and logs to a config-driven tab. Uses Config Loader first; handles empty Multilingual Content rows; documents column contract. VOICE_PROVIDER from config supports local and cloud. Requirements: VOICE-01, VOICE-02, VOICE-03, VOICE-04.
</domain>

<decisions>
## Implementation Decisions

### TTS provider and VOICE_PROVIDER
- **Both local and cloud** — Branch on VOICE_PROVIDER: local = Piper (or similar) behind HTTP; cloud = ElevenLabs/Google. Implement both paths in this phase.
- **Local TTS** — Same machine as n8n; config key e.g. TTS_SERVER_URL (localhost or configurable URL).
- **Cloud credentials** — Planner's discretion: credential strategy (n8n credentials only, which names) and document in plan.
- **Language codes** — Planner's discretion: where canonical mapping (Sheet "Language" → provider locale) lives and how it's documented; align with Multi-Language column contract.

### Empty Multilingual Content / no translations
- **Behavior when no rows for today** — Planner's discretion: skip run vs English-only from Content Log vs other; document choice.
- **Where to branch** — Planner's discretion: where in the flow to detect "no content" and how to represent it (e.g. single item with noMultilingualContent).
- **Column contract** — Planner's discretion: fixed doc vs config-driven column names vs hybrid; both Multi-Language and Voice must agree.
- **Timezone for "today"** — Planner's discretion: source (e.g. TIMEZONE from config) and filter pattern for Content Log and Multilingual; document.

### Audio output and Audio Log
- **Storage** — Local only: write to configurable path (e.g. AUDIO_OUTPUT_PATH); no cloud upload in this phase.
- **Audio Log row shape** — Planner's discretion: one row per language vs one row per post, column set; document in CONFIG-KEYS / HOWTOGENIE.
- **Idempotency** — Planner's discretion: key (e.g. post+date, post+date+language), behavior (skip vs append vs update); document.
- **Audio Log tab name** — Planner's discretion: config key and default (e.g. AUDIO_LOG_TAB); document in config reference.

### Script adaptation and length
- **Full vs short vs config-driven** — Planner's discretion: keep full podcast-style script, or short clips, or config-driven mode; document.
- **LLM schema and validation** — Planner's discretion: schema (e.g. podcast_title, podcast_script, estimated_duration), Parse & Validate + envelope per project rules; document if simplified.
- **TTS script cleaning** — Planner's discretion: strip stage directions / markdown before TTS; document if non-obvious.
- **Per-language failure** — Planner's discretion: continue with other languages and log failure vs fail whole run; document.

### Claude's Discretion
- All items above marked "Planner's discretion": researcher and planner choose concrete options and document them in the plan and in CONFIG-KEYS / HOWTOGENIE as appropriate.
</decisions>

<code_context>
## Existing Code Insights

### Reusable assets
- **core/01_Config_Loader.json** — Execute at start; read config from `$('⚙️ Load Config').item.json` (GOOGLE_SHEET_ID, CONTENT_LOG_TAB, TIMEZONE, etc.). Use for all Sheets and HTTP nodes.
- **growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json** — Existing Voice workflow: 4 PM trigger; hardcoded YOUR_GOOGLE_SHEET_ID, "Content Log", "Multilingual Content"; no Config Loader; no timezone filter; no "no rows" branch. Expects columns Language, Translated Title, URL. Routes by voice_provider (elevenlabs vs google) only — no local branch. Contains "Configure Voice Settings" (language → voice_config), "Adapt to Audio Script" LLM, "Parse Audio Script" (strip [PAUSE]/[EMPHASIS]), Route by Voice Provider → ElevenLabs HTTP / Google TTS, "Process Audio File", "Log Audio Content". Refactor: add Config Loader, config-driven sheet/tab names and AUDIO_OUTPUT_PATH, timezone-aware today filter, empty-Multilingual branch, VOICE_PROVIDER branch including local (Piper/TTS_SERVER_URL), local file write instead of GCS/Spotify/YouTube for this phase.
- **growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json** — Writes to MULTILINGUAL_CONTENT_TAB (or "Multilingual Content"); has Config Loader; append with columns. Voice must align with same column contract (document or config-driven).

### Established patterns
- **Content Log + timezone** — TIMEZONE from config; compute today as YYYY-MM-DD in that timezone; filter by date (see Phase 10, PITFALLS).
- **JSON contracts** — LLM outputs use success/data/error envelope; Parse & Validate Code node after each LLM; fallback defaults on parse error (n8n-json-contracts, ollama-json-only).
- **Config** — htg_config.csv has VOICE_PROVIDER (local). Add TTS_SERVER_URL, AUDIO_OUTPUT_PATH, AUDIO_LOG_TAB, MULTILINGUAL_CONTENT_TAB if not present; document in HOWTOGENIE.

### Integration points
- Workflow runs on schedule (4 PM, after Multi-Language at 2 PM); reads Content Log and Multilingual Content (today); writes audio files to local path and logs to Audio Log tab. Orchestrator already maps hour 16 to Voice workflow; caller/ID update per Phase 8 if workflow moves.
</code_context>

<specifics>
## Specific Ideas

- User chose both local and cloud TTS (branch on VOICE_PROVIDER); local TTS on same machine with TTS_SERVER_URL. Audio storage local only (configurable path). All other gray areas (empty Multilingual behavior, column contract, timezone, Audio Log shape, idempotency, tab name, script length, LLM schema, script cleaning, per-language failure) delegated to planner with "you decide."
</specifics>

<deferred>
## Deferred Ideas

- VOICE-05: Local TTS short clips; full-length podcast for all 9 languages (cost/storage) — future phase. This phase delivers one audio per language (full or short per planner choice) and local file output only.
- Cloud upload (GCS, Spotify, YouTube, podcast RSS) — not in Phase 11; local path only.
</deferred>

---
*Phase: 11-voice-audio*  
*Context gathered: 2026-03-13*
