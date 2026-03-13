---
phase: 11-voice-audio
plan: 02
subsystem: voice
tags: n8n, TTS, ElevenLabs, Google TTS, voice, audio, idempotency

# Dependency graph
requires:
  - phase: 11-01
    provides: Config Loader first, Multilingual filter, Voice config keys in HOWTOGENIE
provides:
  - VOICE_PROVIDER three-way branch (local, elevenlabs, google); local TTS HTTP to TTS_SERVER_URL
  - Process Audio unified for local binary, ElevenLabs binary, Google base64
  - Write audio to AUDIO_OUTPUT_PATH; Audio Log append with idempotency (post+date+language)
  - No YOUR_* in workflow; n8n credentials for ElevenLabs and Google TTS
affects: voice-audio

# Tech tracking
tech-stack:
  added: []
  patterns: Config-driven sheet/tab, idempotency via Read-then-filter-then-append

key-files:
  created: []
  modified:
    - growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json
    - docs/HOWTOGENIE.md

key-decisions:
  - "Voice credentials: ElevenLabs API (Header Auth xi-api-key), Google Cloud TTS (Google API); documented in HOWTOGENIE"
  - "Audio Log idempotency key = post identifier (slug) + date (YYYY-MM-DD) + language"

patterns-established:
  - "Voice workflow: Read Audio Log once per run, build existingKeys, Merge with log rows, filter then append"

requirements-completed: [VOICE-02, VOICE-04]

# Metrics
duration: 12min
completed: "2026-03-13"
---

# Phase 11 Plan 02: Voice Provider Branching, Local Write, Audio Log Idempotency — Summary

**VOICE_PROVIDER three-way route (local/elevenlabs/google), local TTS HTTP branch, Process Audio for local binary, write to AUDIO_OUTPUT_PATH, Audio Log with idempotency and n8n credentials; GCS/Spotify/YouTube/RSS/Voice Note removed.**

## Performance

- **Duration:** ~12 min
- **Tasks:** 3
- **Files modified:** 2

## Accomplishments

- VOICE_PROVIDER from config drives three branches: local (HTTP POST to TTS_SERVER_URL), ElevenLabs, Google TTS; cloud nodes use n8n credentials (no YOUR_* in JSON).
- Process Audio accepts ElevenLabs binary, Google base64, and local HTTP binary; unified filename slug_lang_timestamp; audio written to AUDIO_OUTPUT_PATH via Code node (fs).
- Audio Log: config-driven GOOGLE_SHEET_ID and AUDIO_LOG_TAB; columns Date, Language, Podcast Title, Duration, File Path, Blog URL, Voice Provider, Status; idempotency by post+date+language (Read tab → Existing keys → Merge with log rows → filter → append).
- HOWTOGENIE documents Voice n8n credentials (xi-api-key, Google Cloud TTS), Audio Log row shape, idempotency key, and AUDIO_OUTPUT_PATH write behavior.

## Task Commits

1. **Task 1: VOICE_PROVIDER from config, three-way route, local TTS branch** — `06f753a` (feat)
2. **Task 2: Process Audio for local binary, write to path, Audio Log idempotency** — `c8a265c` (feat)
3. **Task 3: Document credentials, Audio Log row shape, idempotency in HOWTOGENIE** — `1379f88` (docs)

## Files Created/Modified

- `growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json` — Three-way route, Local TTS node, credential refs; Process Audio unified; GCS/RSS/Spotify/YouTube/Voice Note removed; Write audio to path, Read Audio Log, Existing keys, Prepare log row, Merge, Idempotency filter, Log Audio config-driven.
- `docs/HOWTOGENIE.md` — Voice credentials, Audio Log row shape and idempotency key, AUDIO_OUTPUT_PATH write note.

## Decisions Made

- ElevenLabs: n8n credential "ElevenLabs API" (Header Auth, header name xi-api-key). Google TTS: credential "Google Cloud TTS" (Google API). Documented in HOWTOGENIE.
- Idempotency key = post identifier (slug from Blog URL) + date (YYYY-MM-DD) + language; re-run same day skips duplicate append.
- Audio write: Code node with Node fs (self-hosted); path must be writable; HOWTOGENIE notes alternative Write Binary File or external step if fs unavailable.

## Deviations from Plan

None — plan executed as written.

## Issues Encountered

None.

## User Setup Required

- Create n8n credentials: "ElevenLabs API" (Header Auth, xi-api-key), "Google Cloud TTS" (Google API key). Set AUDIO_OUTPUT_PATH and AUDIO_LOG_TAB in htg_config.csv (or config). Ensure AUDIO_OUTPUT_PATH is writable by n8n.

## Next Phase Readiness

- Voice workflow is self-contained: config-first, three TTS branches, local file write, idempotent Audio Log. Manual verification: run with VOICE_PROVIDER=local (and TTS server) or elevenlabs/google; check one audio per language, file under AUDIO_OUTPUT_PATH, row in AUDIO_LOG_TAB.

## Self-Check: PASSED

- 11-02-SUMMARY.md present; commits 06f753a, c8a265c, 1379f88 present.

---
*Phase: 11-voice-audio*
*Completed: 2026-03-13*
