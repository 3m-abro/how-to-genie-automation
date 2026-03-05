# HowTo-Genie — Agentic Team Orchestrator

Single workflow that runs your HowTo-Genie "agents" (sub-workflows) on a schedule. One cron, one router, 12 Execute Workflow nodes + optional logging.

## After import

1. **Replace every `REPLACE_WITH_*_ID`** in the Execute Workflow nodes with your real n8n workflow IDs (from each workflow’s URL: `/workflow/<ID>`).
2. **Switch node:** If your n8n version doesn’t use `outputIndexExpression`, set:
  - Mode: **Expression**
  - Number of outputs: **13**
  - Output index expression:
3. **Log to Google Sheets:** Set `documentId` and `sheetName`, or disable the node if you don’t want logging.
4. **Sub-workflows:** Either keep their own Schedule Triggers (orchestrator is an extra caller) or remove those triggers and add an **Execute Workflow Trigger** so only the orchestrator starts them.

## Schedule (default cron)

Runs at: **3, 5, 6, 8, 9, 10, 12, 14, 15, 16, 18, 21** (hour, server time).


| Hour         | Agent (runType)                       |
| ------------ | ------------------------------------- |
| 3 (Sun only) | SEO Interlinking                      |
| 5            | Islamic Content                       |
| 6            | A/B Testing                           |
| 8            | Master Orchestrator (main blog)       |
| 9            | Email (Tue) / Competitor (other days) |
| 10           | WhatsApp & Telegram                   |
| 12           | Content Repurposing                   |
| 14           | Multi-Language                        |
| 15, 21       | Competitor & Trend                    |
| 16           | Voice & Audio                         |
| 18           | A/B Testing                           |


Edit the **Set Context** Code node to change this mapping or add more agents.

## Workflow IDs to replace

- `REPLACE_WITH_ISLAMIC_ENGINE_ID` → HowTo-Genie v4.0 — Islamic Content Specialization Engine
- `REPLACE_WITH_AB_TESTING_ID` → HowTo-Genie v4.0 — A_B Testing & Optimization Engine
- `REPLACE_WITH_MASTER_ORCHESTRATOR_ID` → HowTo-Genie_ Master Orchestrator (or v3 Optimized)
- `REPLACE_WITH_EMAIL_NEWSLETTER_ID` → HowTo-Genie v3.0 — Email Newsletter Automation
- `REPLACE_WITH_WHATSAPP_TELEGRAM_ID` → HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot
- `REPLACE_WITH_REPURPOSING_ID` → HowTo-Genie v3.0 — Content Repurposing Engine
- `REPLACE_WITH_AUTO_VIDEO_ID` → HowTo-Genie v3.0 — Auto Video Creation
- `REPLACE_WITH_MULTILANG_ID` → HowTo-Genie v4.0 — Multi-Language Expansion Engine
- `REPLACE_WITH_COMPETITOR_ID` → HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor
- `REPLACE_WITH_VIRAL_AMPLIFIER_ID` → HowTo-Genie v4.0 — Viral Content Amplifier Engine
- `REPLACE_WITH_VOICE_AUDIO_ID` → HowTo-Genie v4.0 — Voice & Audio Content Pipeline
- `REPLACE_WITH_SEO_INTERLINKING_ID` → HowTo-Genie v4.0 — SEO Interlinking Intelligence Engine

