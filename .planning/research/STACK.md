# Stack Research

**Domain:** HowTo-Genie v2.0 — Islamic content, competitor intelligence, voice/TTS, content repurposing, docs consolidation, archive/cleanup  
**Researched:** 2026-03-13  
**Confidence:** HIGH (Islamic, repurposing, docs, archive); MEDIUM (competitor); MEDIUM (local TTS — ecosystem in flux)

## Scope

Only **additions or changes** for the six v2.0 target areas. Existing stack (n8n, Ollama, Google Sheets, htg_config.csv, Laravel APIs, Master Orchestrator v3) is unchanged and not re-researched.

---

## Recommended Stack

### 1. Islamic Content (Prayer Times, Hijri)

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|------------------|
| AlAdhan API | v1 (REST, no version in path) | Hijri date (Gregorian→Hijri), prayer times calendar | Free, no API key; already used in `growth/HowTo-Genie v4.0 — Islamic Content Specialization Engine.json`. Stable REST; verified live 2026-03. |
| (No new runtime) | — | Hijri logic, occasion detection | Existing Code node parses API response; no extra libs (n8n Code = plain JS only). |

**Endpoints in use:**
- `GET https://api.aladhan.com/v1/gToH?date=DD-MM-YYYY` — Gregorian → Hijri.
- `GET https://api.aladhan.com/v1/calendar/{year}/{month}?latitude=...&longitude=...&method=3` — Prayer times for location.

**htg_config.csv additions (optional):**
- `PRAYER_LATITUDE`, `PRAYER_LONGITUDE` — Default 3.1390, 101.6869 (Kuala Lumpur); make configurable.
- `PRAYER_METHOD` — Calculation method (e.g. 3 = Muslim World League); keep in config so workflow reads from config loader.

**Integration:** Existing Islamic workflow already uses two HTTP Request nodes + Code. Ensure Config Loader (or equivalent) injects coordinates/method from htg_config so the workflow stays config-driven.

---

### 2. Competitor Intelligence & Trend Monitoring

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|------------------|
| RSS over HTTP | — | Competitor blog feeds | No new stack. n8n HTTP Request + Code node regex (or simple string split) to parse XML. Already in `growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json`. |
| htg_config.csv | Existing | `COMPETITOR_RSS_FEEDS` | Already present; comma-separated list of feed URLs. Use it; no new deps. |

**What NOT to add:**
- **Google Trends “API”** — Current workflow uses `https://trends.google.com/trends/api/dailytrends?geo=US`. This is **unofficial**, not documented, and can break anytime. Prefer RSS-only for reliability, or treat Trends as best-effort and document the risk. No official Google Trends API for this use case without cloud.
- **Puppeteer/Playwright** — Adds heavy runtime and maintenance. Prefer RSS; if a site has no feed, consider a single lightweight scraper only if required later.

**Integration:** Competitor workflow reads RSS URLs (from config or hardcoded list); write parsed items to Google Sheets. No new libraries; keep Code node parsing as plain JS.

---

### 3. Voice & Audio (Local TTS for Multilingual)

**Constraint:** No cloud AI. `htg_config.csv` has `VOICE_PROVIDER,local`. Current Voice workflow is hardcoded to ElevenLabs + Google Cloud TTS; v2.0 needs a **local TTS service** callable via HTTP from n8n.

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|------------------|
| Piper TTS | 1.2.x (CLI/binary); voice models from Hugging Face / rhasspy | Local neural TTS, low CPU, many languages | Lightweight, runs on CPU, no GPU required. Voice models per language (e.g. en_US-ryan-high). rhasspy/piper repo archived Oct 2025 but binaries and models still usable. |
| HTTP wrapper for Piper | — | Expose Piper to n8n | Piper is CLI-only; n8n needs HTTP. Use a small server that accepts POST text + voice_id, runs Piper, returns WAV/MP3. |
| serve-piper-tts (Go) | Latest from repo | HTTP API: TTS inference + list voices | [arunk140/serve-piper-tts](https://github.com/arunk140/serve-piper-tts) — Go wrapper; suitable for self-hosted next to n8n. |
| (Alternative) Piper + Flask/FastAPI | — | Custom HTTP API | [MaximosMK/piper-tts-api-demo](https://github.com/MaximosMK/piper-tts-api-demo) — Python; install Piper + add minimal API. |

**Language coverage (9 languages: en, es, pt-BR, de, fr, hi, id, ar, ja):**
- **Piper:** Strong for en, es, pt, de, fr, ar (and more). Hindi, Indonesian, Japanese may need community models (Hugging Face OpenVoiceOS/pipertts-voices); verify model availability per language before committing.
- **Fallback for missing languages:** espeak-ng (e.g. via [RESTfulSpeak](https://github.com/boltomli/RESTfulSpeak)) — lower quality but very broad language support; use only for languages where no Piper model exists.

**What NOT to use:**
- **ElevenLabs / Google Cloud TTS** — Cloud; contradicts “no cloud AI” and VOICE_PROVIDER=local.
- **Coqui TTS (unmodified)** — Coqui shut down Jan 2024; code is MPL-2.0. Community forks (e.g. AllTalk, Auralis/XTTS) exist; only consider if Piper + espeak-ng cannot cover all 9 languages and you accept maintaining a fork.

**htg_config.csv additions:**
- `TTS_SERVER_URL` — e.g. `http://localhost:8500` (or host where Piper HTTP server runs).
- `TTS_DEFAULT_VOICE` — Optional default voice id for fallback.

**Integration:** Voice workflow: add branch on `VOICE_PROVIDER` (from config). When `local`, call `TTS_SERVER_URL` (e.g. POST /synthesize with text + voice_id/language); parse binary response and pass to existing “Extract audio” / “Log to Sheet” logic. No new deps inside n8n; TTS runs as a separate process/container.

---

### 4. Content Repurposing (1 Post → Many Assets)

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|------------------|
| (No new stack) | — | 1 post → Twitter, LinkedIn, carousel, podcast script, etc. | Already implemented in `content/v3.0 — Content Repurposing Engine.json`: Google Sheets → WordPress REST (fetch post) → Code (strip HTML) → multiple Ollama LLM nodes → Sheets. No new libraries or services. |

**Integration:** Keep using existing workflow. Optional: ensure article fetch URL uses `WORDPRESS_URL` (or equivalent) from htg_config; same for Sheet IDs. No stack additions.

---

### 5. Docs Consolidation (Single Authoritative MD)

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|------------------|
| Markdown in repo | — | Single detailed, centralized reference | PROJECT.md goal: “consolidate docs into one authoritative MD (workflows, UI, reference)”. No new runtime; just content and structure. |
| (Optional) Static site generator | — | If “consolidated” becomes a small site | MkDocs or VitePress only if you later decide one MD is not enough and want navigation. Not required for “single detailed markdown”. |

**Recommendation:** No new stack. Consolidate into one or a few files under `docs/` (e.g. `HOWTO-GENIE.md` or `docs/README.md` + `docs/workflows.md`). Version with git; no extra tooling unless you add a static site later.

---

### 6. Archive / Cleanup Tooling

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|------------------|
| Bash script(s) | — | Move/delete unused workflows, UI, files | No new runtime; run manually or via cron. Use `mv`/`cp` to `archive/` and optional `git` commands. |
| (Optional) n8n Execute Command | — | Run script from workflow | Only if you want a “cleanup” workflow; otherwise manual or cron is enough. |

**Recommendation:** Add e.g. `scripts/archive-unused.sh` that: (1) moves specified JSON/files to `archive/` (or a dated subfolder), (2) optionally updates `.cursorignore` or docs. No new languages or frameworks; bash + git only.

---

## Summary Table: New Stack Additions

| Feature | Additions | Integration Point |
|---------|-----------|-------------------|
| Islamic content | AlAdhan API (already used); optional config keys: PRAYER_LATITUDE, PRAYER_LONGITUDE, PRAYER_METHOD | n8n HTTP nodes; Config Loader → workflow params |
| Competitor / trends | None (RSS + existing HTTP); avoid relying on unofficial Google Trends | Keep RSS in config; document Trends risk |
| Voice/TTS | Piper TTS + HTTP server (e.g. serve-piper-tts or piper-tts-api-demo); optional espeak-ng for missing languages | New TTS_SERVER_URL (+ optional TTS_DEFAULT_VOICE); Voice workflow branches on VOICE_PROVIDER |
| Content repurposing | None | Existing repurposing workflow + config-driven URLs/Sheets |
| Docs consolidation | None | Repo Markdown only |
| Archive/cleanup | Bash script in e.g. `scripts/` | Manual or cron; optional n8n Execute Command |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Cloud TTS (ElevenLabs, Google Cloud TTS) for default path | Cost and “no cloud AI” constraint; VOICE_PROVIDER=local | Local Piper (or Piper + espeak-ng) behind HTTP API |
| Google Trends as sole trend source | Unofficial endpoint; can break without notice | RSS feeds; document Trends as best-effort if kept |
| New npm/pip deps inside n8n Code nodes | n8n Code = plain JS only, no require() | Keep logic in Code or move to HTTP-callable service (e.g. TTS server) |
| Heavy browser automation for competitor scraping | Complexity and maintenance | RSS; minimal scraping only if strictly needed |
| Coqui TTS (unmaintained) without evaluating forks | Upstream abandoned | Piper first; Coqui forks only if language gap forces it |

---

## Version and Compatibility Notes

| Component | Notes |
|-----------|--------|
| AlAdhan API | No version in URL; v1 stable. Response shape: `data.hijri`, `data.gregorian`; calendar endpoint returns array of daily timings. |
| Piper | rhasspy/piper archived 2025-10; use last release (e.g. 1.2.0) + voice models from Hugging Face. HTTP layer is separate (serve-piper-tts or custom). |
| n8n | Existing; no change. HTTP Request, Code, Google Sheets, Schedule Trigger sufficient for all v2.0 features. |
| htg_config.csv | Add only: PRAYER_* (optional), TTS_SERVER_URL, TTS_DEFAULT_VOICE (optional). COMPETITOR_RSS_FEEDS and VOICE_PROVIDER already exist. |

---

## Installation (Only for New Components)

**Local TTS (Piper + HTTP):**

```bash
# Option A: Piper binary + serve-piper-tts (Go)
# 1. Install Piper (see https://github.com/rhasspy/piper)
# 2. Download voice models (e.g. from Hugging Face: rhasspy/piper-voices or OpenVoiceOS)
# 3. Build and run serve-piper-tts
go build -o serve-piper-tts
./serve-piper-tts --piper-path /path/to/piper --models-dir /path/to/models

# Option B: Piper + Python API demo
pip install Flask gunicorn requests
# Clone piper-tts-api-demo, point to Piper binary and models, run server
```

**Islamic / Competitor / Repurposing / Docs / Archive:** No installation; config and existing n8n + Sheets + optional bash script only.

---

## Sources

- AlAdhan: live request to `api.aladhan.com/v1/gToH?date=13-03-2025` (2026-03-13).
- Piper: rhasspy/piper GitHub (releases, VOICES.md), Pipecat Piper docs, serve-piper-tts and piper-tts-api-demo repos.
- Coqui: Coqui shutdown (GitHub issue #3488); Local TTS guide (LocalClaw) for alternatives.
- espeak-ng: RESTfulSpeak, espeak-ng integration docs.
- Project: `.planning/PROJECT.md`, `htg_config.csv`, `growth/` and `content/` workflow JSONs.

---
*Stack research for: HowTo-Genie v2.0 new features only*  
*Researched: 2026-03-13*
