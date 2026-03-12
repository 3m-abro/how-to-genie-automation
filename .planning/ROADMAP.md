# Roadmap: HowTo-Genie Automation Platform

## Overview

The core 8-agent content pipeline already exists and is production-ready. This roadmap activates and hardens everything around that core — starting with bulletproof daily reliability, then systematically enabling growth satellites (multi-language, messaging, A/B testing, video, email), wiring live dashboards and monitoring, and closing the revenue loop with affiliate management and SEO feedback. Each phase delivers a verifiable capability that runs without daily intervention.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Pipeline Reliability** - Harden the core 8 AM pipeline so it never silently fails or stops mid-run (completed 2026-03-12)
- [x] **Phase 2: Distribution Growth** - Activate multi-language expansion and WhatsApp/Telegram digest so content reaches more people daily (completed 2026-03-12)
- [x] **Phase 3: Optimization Loops** - Enable A/B testing and viral amplifier so the system learns which content performs best (completed 2026-03-12)
- [x] **Phase 4: Content Satellites** - Activate video production and email newsletter so every post spawns video and subscriber sequences (completed 2026-03-12)
- [ ] **Phase 5: Live Dashboards & Monitoring** - Replace hardcoded demo data with live feeds and wire Discord/Slack alerts plus weekly reports
- [ ] **Phase 6: Affiliate & SEO Feedback** - Populate affiliate registry, automate its refresh, and close the GA4 feedback loop into content strategy

## Phase Details

### Phase 1: Pipeline Reliability
**Goal**: The core pipeline runs daily at 8 AM, recovers from transient errors automatically, and always writes a machine-readable result to Google Sheets — whether it succeeded or failed
**Depends on**: Nothing (first phase)
**Requirements**: PIPE-01, PIPE-02, PIPE-03, PIPE-04, PIPE-05
**Success Criteria** (what must be TRUE):
  1. Orchestrator fires at 8 AM daily and completes a full run without any manual trigger or intervention
  2. When any LLM agent produces unparseable output, the pipeline continues with fallback defaults and a `parse_error` flag appears in the Sheets row for that run
  3. WordPress publish step retries on failure and writes `published` or `publish_failed` status to the Content Log — never a blank status
  4. QC rejection writes a structured row to Sheets (reason, score, topic) and the next day's run picks a fresh topic rather than re-running the rejected one
  5. Changing a value in htg_config.csv takes effect on the next scheduled run without re-importing the workflow JSON
**Plans:** 6/6 plans complete

Plans:
- [ ] 01-01-PLAN.md — Config & schedule verification; REJECTED_POSTS_TAB and Wave 0 doc
- [ ] 01-02-PLAN.md — Agent migration to Ollama Central; Parse & Validate QC; QC Approved? boolean
- [ ] 01-03-PLAN.md — WordPress native node, retry, Publish Succeeded? gate for satellites
- [ ] 01-04-PLAN.md — QC rejection path: Rejected Posts sheet, Backlog update, Telegram alert
- [ ] 01-05-PLAN.md — Assemble Content Log Row; wire Log and publish_failed path
- [ ] 01-06-PLAN.md — (gap) Wire 📊 Log to Google Sheets to Load Config; add CONTENT_LOG_TAB

### Phase 2: Distribution Growth
**Goal**: Today's published post automatically reaches subscribers in their native language and via WhatsApp/Telegram every day, expanding audience without any manual content promotion
**Depends on**: Phase 1
**Requirements**: GROW-01, GROW-02
**Success Criteria** (what must be TRUE):
  1. After the 8 AM orchestrator completes, the multi-language workflow runs at 2 PM and publishes translations in all 9 configured languages as separate WordPress posts
  2. Translated posts appear in the Multilingual Content Google Sheets tab with language codes and WP URLs
  3. WhatsApp/Telegram bot sends a daily digest message to subscribers at 10 AM containing today's post title and link
  4. Both workflows read their enable/disable state from htg_config.csv — toggling a config value turns them on or off without touching workflow JSON
**Plans:** 2/2 plans complete

Plans:
- [x] 02-01-PLAN.md — Multi-language: Config Loader, enable gate, today's post filter, 8 subdomains, Multilingual Content log (GROW-01)
- [ ] 02-02-PLAN.md — Messaging: Config Loader, enable gate, today's post + skip paths, subscribers, Telegram/WhatsApp send and log (GROW-02)

### Phase 3: Optimization Loops
**Goal**: The system automatically tests content variants and amplifies posts that GA4 confirms are gaining traction, creating a self-improving content engine
**Depends on**: Phase 2
**Requirements**: GROW-03, GROW-04
**Success Criteria** (what must be TRUE):
  1. A/B testing engine runs at 6 AM and creates at least one variant (different headline or intro) for the previous day's post, logging both variants to Google Sheets
  2. Viral amplifier reads GA4 data every 6 hours and marks high-performing posts in Sheets with an `amplify` flag that triggers social re-promotion
  3. Owner can see which variant won for any given post by checking the Sheets A/B log tab
**Plans:** 2/2 plans complete

Plans:
- [ ] 03-01-PLAN.md — A/B Testing: Config Loader, enable gate, yesterday filter, variants to Sheets only (GROW-03)
- [ ] 03-02-PLAN.md — Viral Amplifier: Config + GA4-only + Viral tab; Viral Amplifier Queue workflow (GROW-04)

### Phase 4: Content Satellites
**Goal**: Every published post automatically spawns a TikTok/Shorts video script and triggers a subscriber welcome sequence — extending each post's reach into video and email channels without manual work
**Depends on**: Phase 1
**Requirements**: GROW-05, GROW-06
**Success Criteria** (what must be TRUE):
  1. Video production workflow runs at 10:30 AM, reads today's post from Sheets, and submits a script to Pictory or InVideo — a confirmation ID appears in the Sheets row
  2. New subscriber webhook fires, tags the subscriber in ConvertKit or MailerLite, and sends the first welcome email within 5 minutes of signup
  3. Email sequence sends subsequent messages on the configured schedule without any manual intervention
**Plans:** 2/2 plans complete

Plans:
- [ ] 04-01-PLAN.md — Video Production: Config Loader, VIDEO_PRODUCTION_ENABLED gate, today's post, multiple scripts → Blotato, Video Log one row per video (GROW-05)
- [ ] 04-02-PLAN.md — Email Newsletter: Config Loader, EMAIL_NEWSLETTER_ENABLED gate (200 when disabled), single provider (EMAIL_PROVIDER), ESP sends first email (GROW-06)

### Phase 5: Live Dashboards & Monitoring
**Goal**: The owner can open either dashboard and see real system data, and gets a Discord/Slack alert within minutes of any scheduled workflow failure plus an automated weekly summary in their inbox
**Depends on**: Phase 1
**Requirements**: DASH-01, DASH-02, DASH-03, DASH-04
**Success Criteria** (what must be TRUE):
  1. Revenue dashboard shows real post counts, traffic estimates, and affiliate click data sourced from Google Sheets — not hardcoded sample arrays
  2. ADHD Mission Control dashboard shows actual n8n workflow run statuses fetched from the n8n API, not hardcoded `systemStatus` values
  3. When any scheduled workflow fails, a Discord or Slack alert arrives within 10 minutes containing the workflow name, error message, and timestamp
  4. Every Sunday (or configured day), a weekly summary message is sent to the owner listing posts published, top performer, and revenue estimate for the week
**Plans:** 1/4 plans executed

Plans:
- [ ] 05-01-PLAN.md — Bootstrap Laravel, config, Wave 0 tests; GoogleSheetsService + Revenue API; wire Revenue dashboard (DASH-01)
- [ ] 05-02-PLAN.md — N8nApiService + mission-control API; wire ADHD Mission Control UI (DASH-02)
- [ ] 05-03-PLAN.md — N8nFailureMonitorCommand + TelegramAlertService; schedule every 5–10 min (DASH-03)
- [ ] 05-04-PLAN.md — WeeklySummaryCommand + Mailable; schedule Sunday (config) (DASH-04)

### Phase 6: Affiliate & SEO Feedback
**Goal**: The affiliate link registry is current and niche-relevant, the Affiliate Link Manager refreshes it automatically from RSS feeds, GA4 performance data routes back into topic selection, and the weekly SEO interlinking rebuild runs on schedule
**Depends on**: Phase 1
**Requirements**: AFF-01, AFF-02, SEO-01, SEO-02
**Success Criteria** (what must be TRUE):
  1. Affiliate registry in Google Sheets contains at least one active product per niche (productivity, finance, home, health, tech) with valid tracking links
  2. Affiliate Link Manager workflow runs on schedule, fetches Muncheye and CBEngine RSS feeds, and updates the registry with newly scored products without manual intervention
  3. GA4 integration is active: posts above a configurable traffic threshold appear in a "refresh candidates" list that Agent 1 (Topic Research) can read on the next run
  4. SEO Interlinking engine runs every Sunday at 3 AM and writes updated internal link recommendations to a Sheets tab or directly updates WordPress post content
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6

Note: Phase 4 depends on Phase 1 (not Phase 3) — it can be worked in parallel with Phases 2 and 3 if needed.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Pipeline Reliability | 6/6 | Complete    | 2026-03-12 |
| 2. Distribution Growth | 2/2 | Complete    | 2026-03-12 |
| 3. Optimization Loops | 2/2 | Complete    | 2026-03-12 |
| 4. Content Satellites | 2/2 | Complete   | 2026-03-12 |
| 5. Live Dashboards & Monitoring | 1/4 | In Progress|  |
| 6. Affiliate & SEO Feedback | 0/TBD | Not started | - |
