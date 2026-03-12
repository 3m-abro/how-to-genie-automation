# Requirements: HowTo-Genie Automation Platform

**Defined:** 2026-03-12
**Core Value:** The pipeline must produce and publish at least one monetized, SEO-optimized blog post every day with zero manual intervention.

## v1 Requirements

### Pipeline Reliability

- [x] **PIPE-01**: Orchestrator runs daily at 8 AM and completes without manual intervention
- [x] **PIPE-02**: Failed LLM nodes fall back to defaults and log the error to Google Sheets rather than stopping the pipeline
- [x] **PIPE-03**: WordPress publish step retries on transient failures and reports final status to Sheets
- [x] **PIPE-04**: QC agent rejection routes to Sheets log (not silent failure) and triggers next-day retry topic
- [x] **PIPE-05**: Config Loader reads htg_config.csv at runtime so parameter changes take effect without re-importing workflows

### Growth Workflows Activation

- [x] **GROW-01**: Multi-language expansion workflow translates and publishes today's post to 9 languages (enabled in config)
- [x] **GROW-02**: WhatsApp/Telegram bot sends daily digest to subscribers (enabled in config)
- [x] **GROW-03**: A/B testing engine creates and logs variant articles for yesterday's post
- [x] **GROW-04**: Viral content amplifier reads GA4 data and promotes high-performing posts
- [ ] **GROW-05**: Video production workflow generates TikTok/Shorts scripts and submits to Pictory/InVideo
- [x] **GROW-06**: Email newsletter automation sends welcome sequence to new subscribers via ConvertKit/MailerLite

### Dashboard & Monitoring

- [ ] **DASH-01**: Revenue dashboard fetches live data from Google Sheets (replaces hardcoded demo data)
- [ ] **DASH-02**: ADHD Mission Control dashboard shows real system status from n8n API
- [ ] **DASH-03**: System health monitor sends Discord/Slack alert when any scheduled workflow fails
- [ ] **DASH-04**: Weekly summary report is auto-generated and sent to owner (posts published, revenue, top performers)

### Affiliate & SEO

- [ ] **AFF-01**: Affiliate link registry is populated with current ClickBank/JVZoo products across all 5 niches
- [ ] **AFF-02**: Affiliate Link Manager workflow runs and updates registry from Muncheye/CBEngine RSS feeds
- [ ] **SEO-01**: GA4 integration feeds performance data back into topic selection (high-traffic topics get refreshed/amplified)
- [ ] **SEO-02**: SEO Interlinking engine runs Sunday 3 AM and updates internal links on published posts

## v2 Requirements

### Advanced Personalization

- **PERS-01**: Islamic content engine runs 5 AM and adjusts content strategy based on Hijri calendar
- **PERS-02**: Competitor intelligence monitor runs every 3h and surfaces trending topics from 10 competitors

### Scaling

- **SCAL-01**: Voice & audio pipeline generates TTS for all 9 language versions
- **SCAL-02**: Content repurposing engine converts each post to 10 asset types (infographics, checklists, etc.)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Mobile app | Web-first; automation platform not consumer app |
| Custom CMS | WordPress is the target; not replacing it |
| Real-time analytics | Google Sheets + GA4 sufficient for owner's weekly review |
| Multi-user/team features | Single-owner system by design |
| Cloud LLM APIs (OpenAI, Anthropic) | Cost constraint; local Ollama is the design choice |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| PIPE-01 | Phase 1 | Complete |
| PIPE-02 | Phase 1 | Complete |
| PIPE-03 | Phase 1 | Complete |
| PIPE-04 | Phase 1 | Complete |
| PIPE-05 | Phase 1 | Complete |
| GROW-01 | Phase 2 | Complete |
| GROW-02 | Phase 2 | Complete |
| GROW-03 | Phase 3 | Complete |
| GROW-04 | Phase 3 | Complete |
| GROW-05 | Phase 4 | Pending |
| GROW-06 | Phase 4 | Complete |
| DASH-01 | Phase 5 | Pending |
| DASH-02 | Phase 5 | Pending |
| DASH-03 | Phase 5 | Pending |
| DASH-04 | Phase 5 | Pending |
| AFF-01 | Phase 6 | Pending |
| AFF-02 | Phase 6 | Pending |
| SEO-01 | Phase 6 | Pending |
| SEO-02 | Phase 6 | Pending |

**Coverage:**
- v1 requirements: 19 total
- Mapped to phases: 19
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-12*
*Last updated: 2026-03-12 after initial definition*
