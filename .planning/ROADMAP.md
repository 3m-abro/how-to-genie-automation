# Roadmap: HowTo-Genie Automation Platform

## Overview

The core 8-agent content pipeline is production-ready. v1.0 MVP shipped. v2.0 adds competitor intelligence, voice/audio, content repurposing, docs consolidation, and archive/cleanup — no Islamic content in this milestone.

## Milestones

- ✅ **v1.0 MVP** — Phases 1–6 (shipped 2026-03-13) — [Full details](milestones/v1.0-ROADMAP.md)
- 📋 **v2.0 Content Expansion & Housekeeping** — Phases 7–11 (planned)

## Phases

<details>
<summary>✅ v1.0 MVP (Phases 1–6) — SHIPPED 2026-03-13</summary>

- [x] Phase 1: Pipeline Reliability (6/6 plans) — completed 2026-03-12
- [x] Phase 2: Distribution Growth (2/2 plans) — completed 2026-03-12
- [x] Phase 3: Optimization Loops (2/2 plans) — completed 2026-03-12
- [x] Phase 4: Content Satellites (2/2 plans) — completed 2026-03-12
- [x] Phase 5: Live Dashboards & Monitoring (4/4 plans) — completed 2026-03-12
- [x] Phase 6: Affiliate & SEO Feedback (4/4 plans) — completed 2026-03-13

</details>

### 📋 v2.0 Content Expansion & Housekeeping (Planned)

**Milestone Goal:** Competitor intelligence, voice/audio, content repurposing; single authoritative doc; archive/cleanup with no broken refs. Islamic content deferred.

- [x] **Phase 7: Docs Consolidation** — Single authoritative Markdown covering workflows, UI, config, schedule, archive (completed 2026-03-13)
- [ ] **Phase 8: Archive & Cleanup** — Unused/superseded assets in archive/; Execute Workflow callers updated; documented
- [x] **Phase 9: Competitor Intelligence** — Config-driven competitor workflow (RSS + Reddit → sheet); no hardcoding (09-01 complete)
- [ ] **Phase 10: Content Repurposing** — Today's post → 3–5 formats; config-driven; idempotent
- [ ] **Phase 11: Voice & Audio** — Content Log + Multilingual → TTS per language; runs after Multi-Language

## Phase Details

### Phase 7: Docs Consolidation
**Goal**: One detailed, centralized Markdown doc is the single authoritative reference for workflows, UI, config keys, schedule, and archive.
**Depends on**: Nothing (first v2.0 phase)
**Requirements**: DOCS-01, DOCS-02
**Success Criteria** (what must be TRUE):
  1. One consolidated Markdown doc exists (e.g. docs/HOWTOGENIE.md) covering all workflows, UI, config keys, schedule, and archive.
  2. Doc is the single authoritative reference; no parallel conflicting doc sets.
  3. Doc includes workflow list and config key reference so it can be updated when adding workflows or config.
**Plans:** 1/1 plans complete

Plans:
- [ ] 07-01-PLAN.md — Create docs/HOWTOGENIE.md; deprecate other setup docs with pointers; point CLAUDE.md to single reference; human-verify DOCS-02

### Phase 8: Archive & Cleanup
**Goal**: Unused or superseded workflows, UI, and files are safely archived or removed; no broken Execute Workflow references.
**Depends on**: Phase 7
**Requirements**: ARCH-01, ARCH-02, ARCH-03
**Success Criteria** (what must be TRUE):
  1. Unused/superseded workflows, UI, and files are identified and either in archive/ (with README) or deleted.
  2. Every Execute Workflow caller of any moved workflow is listed and updated so no broken workflow IDs remain.
  3. Archive location and "what lives where" are documented in the consolidated docs.
**Plans:** 1/2 plans executed

Plans:
- [ ] 08-01-PLAN.md — Add verify-archive-refs and caller-audit scripts; update 08-VALIDATION (ARCH-02)
- [ ] 08-02-PLAN.md — Identify candidates, update callers, move workflows; add archive/README.md; doc updates (ARCH-01, ARCH-02, ARCH-03)

### Phase 9: Competitor Intelligence
**Goal**: Competitor workflow runs on schedule, reads RSS + Reddit from config, writes deduplicated trend list to a config-driven Sheet tab with no hardcoding.
**Depends on**: Phase 8
**Requirements**: COMP-01, COMP-02, COMP-03, COMP-04
**Success Criteria** (what must be TRUE):
  1. Competitor workflow runs on schedule (e.g. every 3h) and reads RSS + Reddit from config (COMPETITOR_RSS_FEEDS).
  2. Workflow writes deduplicated, recency-ordered trend list to config-driven Sheet tab.
  3. Workflow uses Config Loader first; delay/IF after each HTTP to avoid 429/blocking.
  4. No hardcoded YOUR_GOOGLE_SHEET_ID or sheet names; config-gated.
**Plans:** 1/1 plans complete

Plans:
- [x] 09-01-PLAN.md — Config Loader first; config-driven RSS/Reddit and COMPETITOR_INTEL_TAB; IF+Wait after HTTP; one deduplicated trend list to sheet (COMP-01–COMP-04)

### Phase 10: Content Repurposing
**Goal**: Repurposing workflow reads today's post (timezone-aware), produces 3–5 platform-native formats, logs to config-driven tabs, and is idempotent.
**Depends on**: Phase 8
**Requirements**: REP-01, REP-02, REP-03, REP-04
**Success Criteria** (what must be TRUE):
  1. Repurposing workflow reads today's post from Content Log (timezone-aware) and produces 3–5 platform-native formats.
  2. Workflow strips HTML and uses LLM per format; logs to Repurposed Content (and queues) in config-driven tabs.
  3. Uses Config Loader and WORDPRESS_URL from config; idempotent (no duplicate append same post/date).
  4. No YOUR_* placeholders in workflow.
**Plans:** 1/2 plans executed

Plans:
- [x] 10-01-PLAN.md — Config Loader first; timezone-aware today filter; idempotency check; no YOUR_* (REP-01, REP-03, REP-04)
- [ ] 10-02-PLAN.md — Config-driven formats, Parse & Validate per LLM, Assemble and log; config keys and docs (REP-01, REP-02)

### Phase 11: Voice & Audio
**Goal**: Voice workflow reads Content Log and Multilingual Content (timezone-aware), adapts to TTS script, produces one audio per language, runs after Multi-Language, logs to config-driven tab.
**Depends on**: Phase 8
**Requirements**: VOICE-01, VOICE-02, VOICE-03, VOICE-04
**Success Criteria** (what must be TRUE):
  1. Voice workflow reads today's post from Content Log and Multilingual Content tab (timezone-aware).
  2. Workflow adapts content to TTS script and produces one audio output per language (VOICE_PROVIDER from config).
  3. Uses Config Loader first; handles empty Multilingual Content rows; column contract documented.
  4. Runs after Multi-Language (e.g. 4 PM); logs outputs to Audio Log / config-driven tab.
**Plans**: TBD

## Progress

| Phase | Milestone | Plans | Status | Completed |
|-------|-----------|-------|--------|-----------|
| 1. Pipeline Reliability | v1.0 | 6/6 | Complete | 2026-03-12 |
| 2. Distribution Growth | v1.0 | 2/2 | Complete | 2026-03-12 |
| 3. Optimization Loops | v1.0 | 2/2 | Complete | 2026-03-12 |
| 4. Content Satellites | v1.0 | 2/2 | Complete | 2026-03-12 |
| 5. Live Dashboards & Monitoring | v1.0 | 4/4 | Complete | 2026-03-12 |
| 6. Affiliate & SEO Feedback | v1.0 | 4/4 | Complete | 2026-03-13 |
| 7. Docs Consolidation | 1/1 | Complete   | 2026-03-13 | - |
| 8. Archive & Cleanup | 1/2 | In Progress|  | - |
| 9. Competitor Intelligence | v2.0 | 0/? | Not started | - |
| 10. Content Repurposing | 1/2 | In Progress|  | - |
| 11. Voice & Audio | v2.0 | 0/? | Not started | - |

---
*v2.0 roadmap created 2026-03-13. Next: `/gsd:plan-phase 7`*
