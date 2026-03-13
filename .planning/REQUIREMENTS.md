# Requirements: HowTo-Genie Automation Platform

**Defined:** 2026-03-13  
**Core Value:** The pipeline must produce and publish at least one monetized, SEO-optimized blog post every day with zero manual intervention.

## Current Milestone: v2.0 Content Expansion & Housekeeping

Requirements below are for v2.0. v1.0 requirements are archived in `milestones/v1.0-REQUIREMENTS.md`.

---

## v2.0 Requirements

### Competitor Intelligence

- [ ] **COMP-01**: Competitor workflow runs on schedule (e.g. every 3h) and reads RSS + Reddit from config (COMPETITOR_RSS_FEEDS)
- [ ] **COMP-02**: Competitor workflow writes deduplicated, recency-ordered trend list to config-driven Sheet tab
- [ ] **COMP-03**: Competitor workflow uses Config Loader first; delay/IF after each HTTP to avoid 429/blocking
- [ ] **COMP-04**: No hardcoded YOUR_GOOGLE_SHEET_ID or sheet names; config-gated

### Voice & Audio

- [ ] **VOICE-01**: Voice workflow reads today's post from Content Log and Multilingual Content tab (timezone-aware)
- [ ] **VOICE-02**: Voice workflow adapts content to TTS script and produces one audio output per language (VOICE_PROVIDER from config: local or cloud)
- [ ] **VOICE-03**: Voice workflow uses Config Loader first; handles empty Multilingual Content rows; documents column contract
- [ ] **VOICE-04**: Voice runs after Multi-Language (e.g. 4 PM); logs outputs to Audio Log / config-driven tab

### Content Repurposing

- [ ] **REP-01**: Repurposing workflow reads today's post from Content Log (timezone-aware) and produces 3–5 platform-native formats
- [ ] **REP-02**: Repurposing workflow strips HTML and uses LLM per format; logs to Repurposed Content (and queues) in config-driven tabs
- [ ] **REP-03**: Repurposing uses Config Loader and WORDPRESS_URL from config; idempotent (no duplicate append same post/date)
- [ ] **REP-04**: Repurposing runs after publish (e.g. Noon); no YOUR_* placeholders

### Docs Consolidation

- [x] **DOCS-01**: One detailed, centralized Markdown doc (e.g. docs/HOWTOGENIE.md) covers all workflows, UI, config keys, schedule, and archive
- [x] **DOCS-02**: Doc is the single authoritative reference; no parallel conflicting doc sets; updated when adding workflows or config

### Archive & Cleanup

- [x] **ARCH-01**: Unused or superseded workflows, UI, and other files are identified and either moved to archive/ (with README) or deleted
- [x] **ARCH-02**: Before moving any workflow, Execute Workflow callers are listed and updated so no broken workflow IDs remain
- [x] **ARCH-03**: Archive location and "what lives where" are documented in the consolidated docs

---

## Future Requirements (v2.x / v3)

Deferred; not in current roadmap.

### Islamic Content (deferred)

- **ISLAM-01–04**: Islamic workflow (Hijri, AlAdhan, config-driven sheets) — not in v2.0 scope; may add later if audience needs it.
- **ISLAM-05**: Orchestrator reads Islamic Content Queue in Build Research Context (topic bias)

### Competitor Enhancements

- **COMP-05**: Competitor trend list feeds into Topic Research / Orchestrator

### Voice / Repurposing Enhancements

- **VOICE-05**: Local TTS (e.g. Piper) with HTTP server when VOICE_PROVIDER=local; short clips
- **REP-05**: 1:10 repurposing (10 asset types); optional auto-publish to platforms

### Out of Scope (v2.0)

- Islamic always-on (must remain config-gated)
- Full competitor article scraping; Google Trends as sole source
- Full-length podcast for all 9 languages (cost/storage)
- Same repurposed text on every platform (anti-feature)
- Deleting assets without archive (safe move only)
- Cloud TTS as default (local-first option in scope)

---

## Out of Scope (Project)

| Feature | Reason |
|--------|--------|
| Mobile app | Web-first platform |
| Custom CMS | WordPress is publishing target |
| Real-time chat / community | Content automation only |
| Manual content editing | Automation-first design |
| Cloud LLM APIs (OpenAI, etc.) | Cost; Ollama only |

---

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DOCS-01 | Phase 7 | Complete |
| DOCS-02 | Phase 7 | Complete |
| ARCH-01 | Phase 8 | Complete |
| ARCH-02 | Phase 8 | Complete |
| ARCH-03 | Phase 8 | Complete |
| COMP-01 | Phase 9 | Pending |
| COMP-02 | Phase 9 | Pending |
| COMP-03 | Phase 9 | Pending |
| COMP-04 | Phase 9 | Pending |
| REP-01 | Phase 10 | Pending |
| REP-02 | Phase 10 | Pending |
| REP-03 | Phase 10 | Pending |
| REP-04 | Phase 10 | Pending |
| VOICE-01 | Phase 11 | Pending |
| VOICE-02 | Phase 11 | Pending |
| VOICE-03 | Phase 11 | Pending |
| VOICE-04 | Phase 11 | Pending |

**Coverage:**
- v2.0 requirements: 18 total
- Mapped to phases: 18
- Unmapped: 0

---
*Requirements defined: 2026-03-13*  
*Last updated: 2026-03-13 after v2.0 research*
