# Project Research Summary

**Project:** HowTo-Genie v2.0 — Content Expansion & Housekeeping  
**Domain:** n8n/Ollama/Sheets content automation — Islamic content, competitor intelligence, voice/audio, content repurposing, docs consolidation, archive/cleanup  
**Researched:** 2026-03-13  
**Confidence:** HIGH (with MEDIUM on local TTS language coverage and competitor/trends API risk)

## Executive Summary

HowTo-Genie v2.0 adds six target areas to the existing pipeline: Islamic content (Hijri + occasions), competitor intelligence (RSS + Reddit), voice/audio (local TTS path), content repurposing (1 post → many assets), a single authoritative doc, and archive/cleanup. Experts build this by keeping a **config-first** pattern: every satellite starts with Config Loader; sheet IDs, tab names, and feature flags come from `htg_config.csv` (or n8n Data Tables). No new runtimes are required except for local TTS: Piper behind an HTTP server (e.g. serve-piper-tts) when `VOICE_PROVIDER=local`. Islamic and competitor workflows already exist in the repo but are not config-integrated; the main work is wiring them to Config Loader, adding new Sheet tabs, and fixing known pitfalls.

The recommended approach is to **do docs and archive first** (no runtime dependency), then **add config keys and create all new Sheet tabs**, then wire the four feature workflows in dependency order: Islamic and Competitor (parallel), then Repurposing (depends only on Content Log), then Voice last (depends on Multilingual Content from the 2 PM Multi-Language run). Key risks: (1) Islamic workflow Code nodes reference node names without emoji — n8n requires exact names, so runtime fails silently; (2) new workflows hardcode `YOUR_GOOGLE_SHEET_ID` and skip Config Loader, creating multiple sources of truth; (3) "Today's post" filters that ignore timezone pick the wrong row for Repurposing/Voice; (4) archiving workflows without updating Execute Workflow callers breaks at runtime. Mitigation: phase checklists that enforce Config Loader, exact `$('Node Name')` matches, timezone-aware Content Log filters, and a "callers of archived workflows" list before any move.

## Key Findings

### Recommended Stack

Existing stack (n8n, Ollama, Google Sheets, htg_config, Laravel, Master Orchestrator v3) is unchanged. Additions are minimal and mostly config + integration.

**Core technologies (new or explicit):**
- **AlAdhan API** (REST, no key) — Hijri date and prayer calendar; already used in Islamic workflow. Add optional config: PRAYER_LATITUDE, PRAYER_LONGITUDE, PRAYER_METHOD.
- **RSS + HTTP** — Competitor feeds; no new stack. Use COMPETITOR_RSS_FEEDS from config; avoid relying on unofficial Google Trends as sole source.
- **Piper TTS + HTTP wrapper** — Local TTS when VOICE_PROVIDER=local. Piper is CLI-only; use serve-piper-tts (Go) or piper-tts-api-demo (Python). Config: TTS_SERVER_URL, optional TTS_DEFAULT_VOICE. Fallback for missing languages: espeak-ng (e.g. RESTfulSpeak).
- **Content repurposing** — No new stack; existing v3.0 workflow + Config Loader + WORDPRESS_URL from config.
- **Docs** — Single Markdown in repo (e.g. docs/HOWTOGENIE.md); no static site required for v2.0.
- **Archive** — Bash script(s) in scripts/; move to archive/; no new runtime. Optional n8n Execute Command only if cleanup is workflow-triggered.

**Do not use:** Cloud TTS (ElevenLabs/Google) for default path; Google Trends as sole trend source; new npm/pip deps inside n8n Code nodes; heavy browser automation for competitor scraping; unmaintained Coqui TTS without evaluating forks.

### Expected Features

**Must have (table stakes):**
- Islamic: Hijri + occasion detection; content strategy output (queue or in-memory context) used by pipeline; config-gated.
- Competitor: RSS + Reddit → merged trend list → sheet; deduplication and recency; config-gated.
- Voice: Input = today's post in N languages (Content Log + Multilingual Content); script adaptation for TTS; one audio per language; VOICE_PROVIDER branch (local or cloud).
- Repurposing: 1 post → multiple formats (3–5 for MVP); platform-native adaptation; log to Repurposed Content (and queues).
- Docs: Single authoritative reference (workflows, UI, config keys); workflow list + schedule + config keys.
- Archive: Unused/superseded assets identified; safe move (no broken Execute Workflow refs).

**Should have (differentiators / v2.x):**
- Islamic → Orchestrator topic bias; competitor trend list → Topic Research; local TTS option; short clips; repurposing 1:10 + optional auto-publish; versioned/generated doc sections.

**Defer (v2+ or v3+):**
- Islamic always on (config gate); full competitor article scrape; full podcast length for all languages; same repurposed text everywhere; delete without archive; Google Trends until official API.

### Architecture Approach

All v2.0 satellites must follow the **Config-First Satellite** pattern: trigger → Execute Workflow (Config Loader) → use `$('⚙️ Load Config').item.json` for GOOGLE_SHEET_ID, tab names, VOICE_PROVIDER, WORDPRESS_URL, etc. New Sheet tabs to create: Islamic Content Queue, Islamic Calendar Events, Content Ideas Queue, Competitor Intelligence, Backlink Opportunities, Repurposed Content, Twitter Queue, Podcast Queue; Multilingual Content may already exist. Data flow: Islamic (5 AM) and Competitor (every 3h) write to sheets; Orchestrator (8 AM) writes Content Log; Repurposing (Noon) reads Content Log, writes repurposed queues; Multi-Language (2 PM) writes Multilingual Content; Voice (4 PM) reads Content Log + Multilingual Content, writes audio log. Optional: Orchestrator "Build Research Context" can read Islamic Queue and/or Content Ideas/Competitor Intel.

**Major components:**
1. **Config Loader** — Single source for sheet ID, tabs, feature flags; every new/modified workflow calls it first.
2. **Google Sheets** — Content Log (canonical); new tabs for Islamic, competitor, repurposed, multilingual, audio.
3. **Four feature workflows** — Islamic, Competitor, Repurposing, Voice; all modified (no new workflows), config-driven.
4. **Docs + archive** — Repo-only; one consolidated MD; archive/ with safe move and updated Execute Workflow refs.

### Critical Pitfalls

1. **Islamic Code node references wrong node name** — `$('Fetch Hijri Date & Islamic Calendar')` fails; actual names include emoji (e.g. `📅 Fetch Hijri Date & Islamic Calendar`). Match exact `name` in JSON. Add IF after each AlAdhan HTTP; defensive parse with fallback when `data`/`hijri` missing.
2. **Hardcoded YOUR_GOOGLE_SHEET_ID and skip Config Loader** — Every workflow that touches Sheets must start with Config Loader and use config for documentId/sheetName and WORDPRESS_URL. Grep for YOUR_* and your-blog.com in active workflows.
3. **"Today's post" / "yesterday's post" without timezone** — Use CONTENT_DAY_TIMEZONE (or TIMEZONE) from config; compute today as YYYY-MM-DD in that timezone; filter Content Log by that date. Required for Repurposing and Voice.
4. **Competitor RSS too aggressive** — Use COMPETITOR_RSS_FEEDS from config; add delay between feeds; IF after each HTTP for non-200; avoid 429/blocking.
5. **Voice: Multilingual Content assumed present and schema fixed** — Handle empty rows; document column contract; branch on VOICE_PROVIDER (local vs cloud); use canonical language codes for TTS provider.
6. **Repurposing: wrong WordPress URL and no idempotency** — WORDPRESS_URL from config; timezone-aware "today" filter; check "already repurposed this post/date" before append.
7. **Archive breaks Execute Workflow** — Before moving workflows, list all callers; update workflow IDs or remove calls; verify after archive.
8. **Docs go stale** — Tie doc update to every phase that adds workflows or config keys; keep a config-keys reference section.

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Docs consolidation + Archive/cleanup
**Rationale:** No runtime dependency; reduces confusion and clutter before wiring config and tabs.  
**Delivers:** Single authoritative MD (workflows, schedule, config keys, archive); superseded/unused workflows and files moved to archive/ with README; Execute Workflow callers updated so no broken IDs.  
**Addresses:** Docs table stakes; archive table stakes.  
**Avoids:** Doc goes stale (by adding "update DOC" to subsequent phases); archive breaks callers (by listing and updating callers in this phase).

### Phase 2: Config + Sheet tabs
**Rationale:** All four feature workflows need the same config keys and tabs; do once so every satellite uses one pattern.  
**Delivers:** New keys in htg_config (or n8n Data Table): ISLAMIC_QUEUE_TAB, ISLAMIC_CALENDAR_TAB, CONTENT_IDEAS_QUEUE_TAB, COMPETITOR_INTEL_TAB, BACKLINK_OPPORTUNITIES_TAB, REPURPOSED_CONTENT_TAB, TWITTER_QUEUE_TAB, PODCAST_QUEUE_TAB, MULTILINGUAL_CONTENT_TAB; optional PRAYER_*, TTS_SERVER_URL, TTS_DEFAULT_VOICE. Create corresponding tabs in Google Sheets.  
**Uses:** Existing Config Loader; STACK and ARCHITECTURE config tables.  
**Avoids:** Hardcoded sheet IDs and tab names in later phases.

### Phase 3: Islamic Content workflow
**Rationale:** No dependency on other v2.0 features; can run in parallel with Phase 4.  
**Delivers:** Config Loader as first step; all documentId/sheetName from config; fix Code node `$('...')` to exact node names (including emoji); IF after both AlAdhan HTTP nodes; defensive parsing with fallbacks. Optional: Orchestrator reads Islamic Queue in Build Research Context (can be later phase).  
**Addresses:** Islamic table stakes (Hijri + occasion, content strategy output).  
**Avoids:** Islamic node name mismatch; AlAdhan response/HTTP unhandled (PITFALLS 1, 4).

### Phase 4: Competitor Intelligence workflow
**Rationale:** Independent of Islamic, Repurposing, Voice; can run in parallel with Phase 3.  
**Delivers:** Config Loader first; COMPETITOR_RSS_FEEDS (and optional Reddit list) from config; replace hardcoded sheet IDs and tab names; delay or batching between feeds; IF after each HTTP for non-200.  
**Addresses:** Competitor table stakes (RSS + Reddit list, dedupe, recency).  
**Avoids:** Hardcoded YOUR_*; aggressive RSS polling and 429/block (PITFALLS 2, 5).

### Phase 5: Content Repurposing workflow
**Rationale:** Depends only on Content Log (already produced by Orchestrator) and config/tabs from Phase 2.  
**Delivers:** Config Loader first; WORDPRESS_URL and all sheet refs from config; timezone-aware "today's post" filter; idempotency (no duplicate append same post/date).  
**Addresses:** Repurposing table stakes (1 post → 3–5 formats, platform-native, log outputs).  
**Avoids:** Wrong blog URL; wrong day; duplicate output (PITFALLS 2, 3, 8).

### Phase 6: Voice & Audio workflow
**Rationale:** Depends on Multilingual Content tab (written by Multi-Language at 2 PM); must run after that pipeline. Build after Repurposing so config and Content Log patterns are consistent.  
**Delivers:** Config Loader first; CONTENT_LOG_TAB, MULTILINGUAL_CONTENT_TAB, VOICE_PROVIDER from config; branch on VOICE_PROVIDER (local vs cloud); "no Multilingual Content" path; canonical language codes for TTS; document column contract with Multi-Language.  
**Addresses:** Voice table stakes (input from Content Log + Multilingual, script adaptation, one audio per language).  
**Avoids:** Wrong/missing Multilingual schema; TTS provider mismatch (PITFALLS 6, 7).

### Phase Ordering Rationale

- Docs and archive first so refactors and new wiring don’t mix with "which doc is right" and broken refs.
- Config + tabs second so every satellite is updated to one pattern and no workflow ships with YOUR_*.
- Islamic and Competitor can be parallel (no dependency on each other or on Repurposing/Voice).
- Repurposing and Voice both depend on config and existing pipelines (Content Log; Multilingual for Voice); Voice explicitly depends on Multi-Language run and tab, so Voice last.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 6 (Voice):** Local TTS (Piper) model availability for all 9 languages (hi, id, ja in particular); Piper HTTP server choice (serve-piper-tts vs piper-tts-api-demo) and deployment. Optional `/gsd:research-phase` if local TTS is in scope for v2.0 launch.
- **Phase 4 (Competitor):** If Google Trends is kept as best-effort, document risk and fallback; no extra research if RSS+Reddit only.

Phases with standard patterns (skip research-phase):
- **Phases 1, 2, 3, 5:** Docs/archive are repo-only; config and tabs follow existing Config Loader; Islamic uses existing AlAdhan and workflow; Repurposing uses existing v3.0 workflow and WP REST. Patterns are documented in ARCHITECTURE and PITFALLS.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Islamic, repurposing, docs, archive are no-new-stack or well-specified (AlAdhan, Piper + HTTP). Competitor: MEDIUM (RSS clear; Google Trends risk documented). Local TTS: MEDIUM (Piper ecosystem archived Oct 2025; model coverage for hi/id/ja may need verification). |
| Features | HIGH | Codebase and FEATURES.md align on table stakes, differentiators, and anti-features; dependency graph clear. |
| Architecture | HIGH | Config Loader pattern and data flow verified against core/ and growth/ workflows; build order and integration points explicit. |
| Pitfalls | HIGH | Codebase and v1.0 plans confirm node-name, config, timezone, AlAdhan, RSS, Voice schema, archive-caller issues; recovery and phase mapping documented. |

**Overall confidence:** HIGH. Gaps are narrow: (1) Piper voice model availability for hi, id, ja; (2) Google Trends if used at all (treat as best-effort and document).

### Gaps to Address

- **Local TTS language coverage:** Before committing to Piper-only for all 9 languages, verify Hugging Face / OpenVoiceOS models for Hindi, Indonesian, Japanese; plan espeak-ng fallback for any missing language (STACK.md).
- **Config key naming:** RESEARCH uses names like ISLAMIC_QUEUE_TAB; confirm against existing htg_config.csv and core/01_Config_Loader.json so keys match exactly during Phase 2.
- **Doc location and naming:** Template suggests docs/HOWTOGENIE.md or root; align with repo layout and CLAUDE.md so one consolidated doc is the single entry point.

## Sources

### Primary (HIGH confidence)
- `.planning/PROJECT.md` — v2.0 scope, schedule, constraints
- `core/01_Config_Loader.json`, `core/08_Orchestrator_v3.json` — Config Loader and Orchestrator patterns
- `htg_config.csv` — Existing keys (GOOGLE_SHEET_ID, CONTENT_LOG_TAB, VOICE_PROVIDER, COMPETITOR_RSS_FEEDS, etc.)
- `growth/` and `content/` workflow JSONs — Islamic, Competitor, Voice, Repurposing (current state and node names)
- AlAdhan API — Live check 2026-03-13; DD-MM-YYYY, gToH and calendar endpoints

### Secondary (MEDIUM confidence)
- Piper / rhasspy/piper — Archived 2025-10; serve-piper-tts, piper-tts-api-demo for HTTP wrapper
- v1.0 phase plans (02-01, 03-01, 04-01) — Timezone and Content Log filter patterns
- n8n — Execute Workflow IDs, export/import and caller updates

### Tertiary (LOW confidence / document risk only)
- Google Trends unofficial endpoint — Not for sole reliance; document if used as best-effort
- Coqui TTS — Upstream abandoned; use only if Piper + espeak-ng cannot cover languages

---
*Research completed: 2026-03-13*  
*Ready for roadmap: yes*
