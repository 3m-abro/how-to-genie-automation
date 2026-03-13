# HowTo-Genie Automation Platform

## What This Is

HowTo-Genie is a fully automated AI-powered content creation and monetization platform built on n8n workflows and Ollama (local LLM). It runs daily without manual intervention — generating how-to blog posts via an 8-agent pipeline, publishing to WordPress, distributing across 5 social platforms (Facebook, Instagram, TikTok, Pinterest, YouTube), and monetizing via affiliate links — all logged to Google Sheets. The system is designed so the owner only needs to review it once per week.

## Core Value

The pipeline must produce and publish at least one monetized, SEO-optimized blog post every day with zero manual intervention — everything else (social, video, translation, email) is secondary to this guarantee.

## Requirements

### Validated

- ✓ 8-agent content pipeline (topic research → write → humanize → SEO → QC → social → moderation) — existing
- ✓ Master Orchestrator v3 (8 AM daily schedule, idempotent) — existing
- ✓ Topic Research Engine with Reddit/ATP scraping and deduplication — existing
- ✓ Affiliate link research (ClickBank/JVZoo scoring) — existing
- ✓ Social media formatting and queue processor (Blotato API) — existing
- ✓ Comment moderation workflow — existing
- ✓ Content calendar manager (webhook-triggered, prevents duplication) — existing
- ✓ Internal linking optimization (weekly rebuild) — existing
- ✓ Content refresh automation (updates older posts) — existing
- ✓ System health monitoring and alert handler — existing
- ✓ Google Sheets as data backbone (Content Log, Social Queue, Affiliate Registry) — existing
- ✓ Config-driven architecture via htg_config.csv — existing
- ✓ Pipeline reliability (Config Loader, QC rejection path, WordPress retry, Content Log) — v1.0
- ✓ Growth workflows: multi-language, WhatsApp/Telegram, A/B testing, viral amplifier, video production, email newsletter — v1.0
- ✓ Revenue and ADHD dashboards connected to live data (Laravel APIs, n8n status) — v1.0
- ✓ Monitoring: failure alerts (Telegram), weekly summary email — v1.0
- ✓ Affiliate registry + Affiliate Link Manager (Muncheye RSS); GA4 → topic selection; SEO Interlinking — v1.0

### Active

- [ ] (Next milestone — define via `$gsd-new-milestone`) — e.g. Islamic content, competitor intelligence, voice/audio, content repurposing (v2 requirements)

### Out of Scope

- Mobile app — web-first platform, no native app planned
- Custom CMS — WordPress is the publishing target; not replacing it
- Real-time chat or community features — content creation platform only
- Manual content editing — automation-first design principle

## Context

- **Tech stack:** n8n (self-hosted, localhost:5678), Ollama (localhost:11434), WordPress, Google Sheets, Blotato API
- **LLM models:** llama3 (smart), mistral (fast) — configured in htg_config.csv
- **Operating cost:** ~$5–35/month (Ollama is free, n8n self-hosted is free)
- **Niche targets:** productivity, finance, home, health, tech (+ halal filtering enabled)
- **Revenue model:** AdSense + Adsterra + affiliate commissions (ClickBank, JVZoo, Digistore24)
- **Target:** $2K–10K+/month passive revenue by Month 12+
- **Current state:** v1.0 MVP shipped 2026-03-13. Core pipeline + 6 phases complete: reliability, distribution, optimization, satellites, dashboards/monitoring, affiliate/SEO. Laravel app with Revenue + Mission Control APIs; n8n workflows config-gated via htg_config.csv.
- **Schedule design:** Staggered — Islamic content (5 AM) → A/B test (6 AM) → Orchestrator (8 AM) → social/video/translation satellites (10 AM–4 PM) → competitor monitor (every 3h) → viral amplifier (every 6h) → SEO rebuild (Sunday 3 AM)

## Constraints

- **Tech stack:** n8n + Ollama only — no cloud AI APIs (cost constraint); all inference is local
- **Idempotency:** All workflows must be safe to re-run on the same day without duplicating posts
- **JSON contracts:** All LLM outputs must use `{success, data, error}` envelope pattern with Parse & Validate Code nodes
- **No secrets in JSON:** All credentials via n8n credential system; placeholders use `YOUR_XXX` pattern
- **JavaScript only:** Code nodes use plain JS — no `require()`, no external libraries
- **Single owner:** Designed for zero daily intervention; one weekly review session

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Local Ollama over cloud APIs | Zero inference cost; ~$0/month AI spend | ✓ Good — v1.0 |
| Google Sheets as database | Zero infrastructure; human-readable; all satellites chain off Content Log tab | ✓ Good — v1.0 |
| Separate satellite workflows | Each can be enabled/disabled/debugged independently without touching core pipeline | ✓ Good — v1.0 |
| Staggered schedule design | Satellites must read today's post from Sheets; all must run after 8 AM orchestrator | ✓ Good — v1.0 |
| htg_config.csv as config source | Single file to update for all system parameters; no hardcoded values in workflow JSON | ✓ Good — v1.0 |

---
*Last updated: 2026-03-13 after v1.0 milestone*
