# Feature Research — v2.0 Content Expansion & Housekeeping

**Domain:** HowTo-Genie v2.0 — Islamic content, competitor intelligence, voice/audio, content repurposing, docs consolidation, archive/cleanup  
**Researched:** 2026-03-13  
**Confidence:** HIGH (codebase + ecosystem verified)

## Feature Landscape

### Table Stakes (Users Expect These)

Features that make each v2.0 capability feel complete. Missing these = feature feels broken or half-done.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **Islamic: Hijri + occasion detection** | Islamic content implies correct date and Ramadan/Eid/Jumua awareness | LOW | AlAdhan API free, no key; existing workflow already has gToH + calendar endpoints |
| **Islamic: Content strategy output** | Pipeline must use the signal (theme/boost/queue), not just compute and discard | MEDIUM | Either feed Orchestrator (topic bias) or write to queue sheet; requires config + optional sheet |
| **Competitor: RSS + Reddit trending list** | “Competitor intelligence” implies list of what competitors are publishing + what’s hot on Reddit | MEDIUM | Existing workflow has RSS + Reddit + merge; must write to sheet and optionally feed topic research |
| **Competitor: Deduplication and recency** | Same topic from 5 sources must appear once; entries should be recent | LOW | Code node: merge by normalized title/URL, filter by date |
| **Voice: Input = today’s post in N languages** | TTS pipeline must know which post and which language versions exist | MEDIUM | Depends on Content Log + Multilingual Content tab; Multi-Language workflow must run first (2 PM) |
| **Voice: Script adaptation for TTS** | Raw HTML/article text is not suitable for TTS; need cleaned, shortened script | MEDIUM | Existing “Adapt to Audio Script” LLM step; strip markup, cap length (e.g. 5000 chars) |
| **Voice: One audio output per language** | One blog post → one audio file per language version | MEDIUM | Current design: 9 languages → 9 files; storage/logging required |
| **Repurposing: 1 post → multiple formats** | “Content repurposing” means one source article becomes several distinct assets | HIGH | Multiple LLM branches (Twitter, LinkedIn, IG, etc.); strip HTML first, then per-format prompts |
| **Repurposing: Platform-native adaptation** | Each format must match platform (length, tone, structure), not copy-paste | MEDIUM | Per-format prompts and Parse & Validate; existing workflow has separate nodes per format |
| **Repurposing: Log outputs** | Operator must see what was generated (for review or downstream use) | LOW | Write to Repurposed Content (and optional queues); sheet names already in v3.0 workflow |
| **Docs: Single authoritative reference** | One place for workflows, UI, config, and setup — no “which doc is right?” | MEDIUM | Consolidate CLAUDE.md, howto-genie-setup-guide.md, and planning summary into one structured MD (or minimal set) |
| **Docs: Workflow list + schedule + config keys** | Operator needs: what runs when, what config keys exist, where credentials live | LOW | Extract from PROJECT.md + config loader + workflow JSONs |
| **Archive: Unused assets identified** | “Archive/cleanup” implies knowing what is superseded or unused | LOW | Compare workflow names/versions and Execute Workflow references; list in archive/ |
| **Archive: Safe move (no broken refs)** | Moving to archive must not break active workflows that call other workflows | MEDIUM | Grep for workflow name / file path before move; document in README or docs |

### Differentiators (Competitive Advantage)

Not required for “it works,” but add clear value.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Islamic: Orchestrator topic bias** | Main pipeline picks topics aligned with Ramadan/Eid/Jumua when enabled | MEDIUM | Pass Islamic context (content_theme, suggested_topics) into Orchestrator research context; config-gated |
| **Islamic: Priority languages by occasion** | e.g. Ramadan → ar, ur, en, ms, id first | LOW | Already in existing “Analyze Islamic Calendar Context” recommendations |
| **Competitor: Feed into Topic Research** | Trending titles from competitors + Reddit influence Agent 1 topic choice | MEDIUM | Orchestrator already has Reddit; add optional “Competitor Trends” sheet read or merged trend list |
| **Competitor: Configurable competitor list** | Operator can change which sites/subreddits are monitored without editing JSON | LOW | Config keys or sheet “Competitor List” |
| **Voice: Local TTS option** | Zero recurring cost; aligns with “no cloud AI” for inference | HIGH | Piper or similar in n8n (HTTP to local service or sub-workflow); 9 languages may need multiple models |
| **Voice: Short clips vs full podcast** | Summary/short audio per post instead of 30-min podcast reduces storage and cost | MEDIUM | LLM step to produce 1–3 min script; cap script length |
| **Repurposing: 1:10 asset types** | Industry “1:10” rule: one post → up to 10 formats (carousels, threads, newsletter, etc.) | HIGH | Current v3.0 has several; add infographic, checklist, email snippet, etc., as needed |
| **Repurposing: Optional auto-publish** | Push to social/email from repurposing output instead of manual copy | HIGH | Depends on existing social/email workflows; config-gated |
| **Docs: Versioned / generated where possible** | Doc reflects current workflow names and config; reduce drift | MEDIUM | Script or manual pass to pull workflow list and config keys from repo |
| **Archive: Superseded-by rules** | Explicit “X supersedes Y” so future cleanup is deterministic | LOW | Document in archive/README or FEATURES.md |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| **Islamic content always on** | “We have Islamic engine” | Niche may be non-Islamic; can confuse topic quality | Config gate (e.g. ISLAMIC_CONTENT_ENABLED); when off, skip or no-op |
| **Scraping full competitor articles** | Richer intelligence | Legal/ToS risk; heavy and brittle | Use RSS + Reddit + public APIs only; no full-page scrape |
| **Voice: Full podcast length for all 9 languages** | “Complete” audio offering | Storage and cost scale (e.g. 9 × 30 min); ElevenLabs/Google cost | Short summary clips or local TTS; cap duration/characters |
| **Repurposing: Same text everywhere** | Simpler pipeline | Audiences and platforms expect native format | Enforce platform-native adaptation in prompts and validation |
| **Two parallel doc sets** | “Dev doc” vs “user doc” | Drift and “which is right?” | Single consolidated doc with clear sections (reference vs setup vs operations) |
| **Delete without archive** | “Just remove unused” | Recovery or reference lost | Move to /archive first; delete only after explicit owner approval |
| **Competitor: Google Trends in n8n** | “Trends” in name | Trends API is not public REST; unofficial endpoints break | Use only RSS + Reddit for v2.0; document “Google Trends” as future/optional if API available |

## Feature Dependencies

```
[Content Log + Config]
    ├── Islamic Content Engine (5 AM) ──optional──> [Orchestrator topic bias] / [Islamic Content Queue sheet]
    ├── Competitor Monitor (every 3h) ──optional──> [Competitor Trends sheet] ──optional──> [Orchestrator / Topic Research]
    ├── Repurposing Engine (Noon) ──requires──> [Content Log + WP URL] ──writes──> [Repurposed Content, queues]
    └── Voice Pipeline (4 PM) ──requires──> [Content Log] + [Multilingual Content]
                                              └──requires──> [Multi-Language Expansion ran at 2 PM]

[Config Loader]
    └── All v2.0 workflows should use config for GOOGLE_SHEET_ID, tab names, feature flags (no YOUR_* placeholders)

[Docs consolidation]
    └── Consumes: repo structure, workflow list, config keys, CLAUDE.md, howto-genie-setup-guide.md
    └── No dependency on Sheets/config

[Archive/cleanup]
    └── Consumes: workflow JSONs, Execute Workflow references, archive/ contents
    └── No dependency on Sheets/config
```

### Dependency Notes

- **Voice requires Multilingual Content:** Voice pipeline reads “today’s post” in all languages from Multilingual Content tab; Multi-Language Expansion Engine must run at 2 PM before 4 PM Voice run.
- **Repurposing requires published post:** Needs Content Log row with WP URL and full article fetch; runs at Noon after 8 AM publish.
- **Islamic and Competitor can be standalone:** They can write only to sheets; Orchestrator integration is an enhancement (topic bias / trend list).
- **All growth workflows:** Should call Config Loader first and use `GOOGLE_SHEET_ID`, `CONTENT_LOG_TAB`, etc. from config; replace hardcoded `YOUR_GOOGLE_SHEET_ID` and `YOUR_GOOGLE_API_KEY` / `YOUR_ELEVENLABS_API_KEY` with config or credentials.

## Downstream Consumer Needs

| Consumer | Table Stakes | Differentiators | Anti-Features |
|----------|--------------|-----------------|---------------|
| **Orchestrator / Agent 1** | Topic list + existing keywords (already); optional Islamic/competitor context | Islamic topic bias; competitor trend list in research_context | Forcing Islamic topics when disabled; duplicate topics from competitor feed |
| **Multi-Language Engine** | Content Log (today’s post) | — | — |
| **Video / Social / Email** | Content Log or Repurposed queues as designed | Repurposing output as input to social/email | Same copy everywhere |
| **Operator (weekly review)** | One doc for setup + workflows + config; clear archive so nothing “disappears” | Versioned doc; archive README | Two conflicting docs; delete without archive |
| **Sheets** | Required tabs: Content Log, Multilingual Content; optional: Islamic Content Queue, Competitor Trends, Repurposed Content, Audio Log | — | Missing tabs when workflow expects them |

## MVP Definition (v2.0 Scope)

### Launch With (v2.0)

- [ ] **Islamic:** AlAdhan Hijri + occasion detection; write to Islamic Content Queue (or in-memory context); config-gated; no Orchestrator integration required for MVP.
- [ ] **Competitor:** RSS + Reddit → merged trend list → write to Competitor Trends sheet; config-gated; optional read into Orchestrator in a later phase.
- [ ] **Voice:** Config Loader at start; read Content Log + Multilingual Content; script adaptation; one TTS path (Google TTS free tier or single local provider); write to Audio Log; no GCS required for MVP.
- [ ] **Repurposing:** Config Loader; Content Log → fetch WP post → strip HTML → 3–5 formats (e.g. Twitter, LinkedIn, IG carousel, email snippet); write to Repurposed Content; no auto-publish.
- [ ] **Docs:** One consolidated markdown (or two: “reference” + “setup”) covering workflows, UI, config keys, and archive; replace or subsume conflicting scattered docs.
- [ ] **Archive:** List superseded/unused workflows and files; move to /archive; document in consolidated doc and optional archive/README; no broken Execute Workflow refs.

### Add After Validation (v2.x)

- [ ] Islamic: Orchestrator topic bias using Islamic context.
- [ ] Competitor: Feed trend list into Topic Research / Agent 1.
- [ ] Voice: Local TTS option; short-clip mode.
- [ ] Repurposing: More asset types (1:10); optional auto-publish to social/email.
- [ ] Docs: Automated extraction of workflow list and config keys.

### Future Consideration (v3+)

- [ ] Google Trends (if official API available).
- [ ] Full podcast length + distribution (e.g. Spotify).
- [ ] Competitor sentiment or pricing tracking.

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Islamic (Hijri + queue, config-gated) | HIGH (niche alignment) | LOW | P1 |
| Competitor (RSS + Reddit → sheet) | HIGH (topic ideas) | MEDIUM | P1 |
| Voice (config + one TTS path + log) | HIGH (accessibility) | MEDIUM | P1 |
| Repurposing (3–5 formats + log) | HIGH (reach) | HIGH | P1 |
| Docs consolidation | HIGH (operator sanity) | MEDIUM | P1 |
| Archive/cleanup | MEDIUM (repo hygiene) | LOW | P2 |
| Islamic → Orchestrator bias | MEDIUM | MEDIUM | P2 |
| Competitor → Topic Research | MEDIUM | MEDIUM | P2 |
| Voice local TTS / short clips | MEDIUM | HIGH | P3 |
| Repurposing 1:10 + auto-publish | MEDIUM | HIGH | P3 |

**Priority key:** P1 = must have for v2.0; P2 = should have in v2.0 or v2.x; P3 = nice to have later.

## Complexity Summary

| Area | Complexity | Main Drivers |
|------|------------|--------------|
| Islamic | LOW–MEDIUM | AlAdhan is simple; integration with Orchestrator is optional |
| Competitor | MEDIUM | Multiple sources, merge, dedupe, sheet + optional Orchestrator |
| Voice | MEDIUM | Content Log + Multilingual dependency; TTS provider and script length |
| Repurposing | HIGH | Multiple LLM branches, platform-native prompts, logging |
| Docs | MEDIUM | Consolidation and structure; optional automation |
| Archive | LOW | Naming rules + grep for refs before move |

## Sources

- Project: `.planning/PROJECT.md`, `CLAUDE.md`, v1.0-REQUIREMENTS.md
- Codebase: `growth/HowTo-Genie v4.0 — Islamic Content Specialization Engine.json`, `Competitor Intelligence & Trend Monitor.json`, `Voice & Audio Content Pipeline.json`, `content/v3.0 — Content Repurposing Engine.json`, `core/01_Config_Loader.json`
- AlAdhan: aladhan.com, api.aladhan.com — free, no auth
- Content repurposing: 1:10 rule, platform-native adaptation (Digital Applied, Spinfluence, SchedulePress)
- Competitor intelligence: RSS + Reddit monitoring (RSS.app, RedditMentions)
- TTS: ElevenLabs (paid), Google TTS (free tier), local (Piper, Orpheus) — LocalClaw, ElevenLabs docs
- Docs: docs-as-code, single source of truth (Unmarkdown, Antora, OneUptime aggregation)

---
*Feature research for: HowTo-Genie v2.0 Content Expansion & Housekeeping*  
*Researched: 2026-03-13*
