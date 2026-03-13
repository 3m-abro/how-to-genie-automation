# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 — MVP

**Shipped:** 2026-03-13
**Phases:** 6 | **Plans:** 20

### What Was Built
- Pipeline reliability: Config Loader, 8 AM schedule, QC rejection path, WordPress retry, Content Log
- Distribution: Multi-language (9 languages), WhatsApp/Telegram daily digest
- Optimization: A/B testing, Viral Amplifier (GA4 → Viral tab)
- Content satellites: Video Production, Email newsletter (ConvertKit/MailerLite)
- Live dashboards: Laravel Revenue + Mission Control APIs, failure monitor + Telegram alerts, weekly summary email
- Affiliate & SEO: Registry + config keys, Affiliate Link Manager (Muncheye RSS), Refresh Candidates → Agent 1, SEO Interlinking

### What Worked
- Config-first pattern (htg_config.csv) kept workflow JSON clean and toggleable
- Phase-by-phase execution with PLAN/SUMMARY kept scope clear
- Laravel + n8n split: dashboards and alerts in Laravel, content pipeline in n8n

### What Was Inefficient
- (To be filled from session notes; no audit run before completion)

### Patterns Established
- Config Loader as first node after triggers in every satellite workflow
- Parse & Validate Code node after every LLM node; fallback defaults in catch
- No YOUR_* in workflow JSON; credentials via n8n

### Key Lessons
1. Define config keys (e.g. 06-CONFIG-KEYS.md) before building workflows so tab names and row shapes are consistent.
2. Wave 0 / validation docs up front reduce rework in later plans.

### Cost Observations
- (Model mix and sessions TBD when tracked)

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Phases | Plans | Key Change |
|-----------|--------|-------|------------|
| v1.0 | 6 | 20 | First full milestone; GSD complete-milestone workflow used |

### Top Lessons (Verified Across Milestones)

1. (Single milestone so far; expand after v1.1+)
