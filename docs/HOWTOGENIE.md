# HowTo-Genie — Single Reference

Single authoritative reference for workflows, UI, config keys, schedule, and archive. When adding workflows or config, update this doc (and `htg_config.csv` for keys).

---

## Overview

HowTo-Genie is an automated AI-powered content and monetization pipeline. n8n workflows run on a schedule (locally or self-hosted), using Ollama as the LLM. The main flow runs at 8 AM daily: an 8-agent pipeline produces a full how-to article, finds images, publishes to WordPress, logs to Google Sheets, and drives social and comment workflows. Satellite workflows handle video, repurposing, multi-language, voice, A/B testing, competitor monitoring, viral amplification, SEO interlinking, Islamic content, WhatsApp/Telegram, and email — all triggered by the orchestrator or their own schedules. Design constraint: the owner looks at the system about once per week; everything else is automated.

**High-level flow:**

- **8 AM:** Orchestrator runs → Config Loader → 8 agents (Prompt Engineer → Research → Content Writer → Humanizer → SEO & Monetization → Quality Control → Image → WordPress publish → Social + Comment Moderator) → Google Sheets log.
- **Other times:** Orchestrator (or cron) triggers growth/content/social/affiliate/monitoring/email workflows per schedule below.
- **Data backbone:** One Google Sheet (tabs defined in config); WordPress for posts; n8n for execution.

---

## Workflows

Workflows live under `core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, and `email/`. Superseded or unused workflows are in `archive/` (see [Archive](#archive)).


| Directory       | Workflow file                                                     | Schedule/Trigger                           | Purpose                                       |
| --------------- | ----------------------------------------------------------------- | ------------------------------------------ | --------------------------------------------- |
| **core/**       | `01_Config_Loader.json`                                           | On demand (called by others)               | Loads `htg_config.csv` into execution context |
| **core/**       | `07_Approval_Poller.json`                                         | On demand / polling                        | Waits for human approval when gated           |
| **core/**       | `08_Orchestrator_v3.json`                                         | Cron (e.g. 3,5,6,8,9,10,12,14,15,16,18,21) | Main router; runs agents by hour/day          |
| **core/**       | `Ollama Agent (Central).json`                                     | Sub-workflow (called by orchestrator)      | Single Ollama LLM endpoint for all 8 agents   |
| **content/**    | `02_Topic_Research_Engine_v2.json`                                | Orchestrator / schedule                    | Topic research and backlog                    |
| **content/**    | `10_Content_Calendar_Manager.json`                                | Schedule / manual                          | Manages content calendar in Sheets            |
| **content/**    | `12_Internal_Linking.json`                                        | Orchestrator / schedule                    | Internal link suggestions                     |
| **content/**    | `13_Content_Refresh.json`                                         | Schedule                                   | Refreshes older posts                         |
| **content/**    | `v3.0 — Content Repurposing Engine.json`                          | 12:00 (noon)                               | Repurposes post into multiple assets          |
| **content/**    | `v4.0 — SEO Interlinking Intelligence Engine.json`                | Sun 03:00                                  | Rebuilds interlinking network                 |
| **growth/**     | `06_Refresh_Candidates_Writer.json`                               | Schedule                                   | Writes refresh candidates                     |
| **growth/**     | `HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json`       | 06:00, 18:00                               | A/B tests post variants                       |
| **growth/**     | `HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json` | 09:00 (non-Tue), 15:00, 21:00              | Competitor + trend scraping                   |
| **growth/**     | `HowTo-Genie v4.0 — Islamic Content Specialization Engine.json`   | 05:00                                      | Hijri/Islamic-aware content                   |
| **growth/**     | `HowTo-Genie v4.0 — Multi-Language Expansion Engine.json`         | 14:00                                      | Multi-language translation                    |
| **growth/**     | `HowTo-Genie v4.0 — Viral Amplifier Queue.json`                   | —                                          | Queue for viral amplification                 |
| **growth/**     | `HowTo-Genie v4.0 — Viral Content Amplifier Engine.json`          | 15:00, 21:00                               | GA4-driven viral amplification                |
| **growth/**     | `HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json`          | 16:00                                      | TTS for translated content                    |
| **growth/**     | `HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json`    | 10:00                                      | Digest to WhatsApp/Telegram                   |
| **growth/**     | `HowTo-Genie v4.0 — Video Production Engine.json`                 | —                                          | Video production (orchestrator/called)        |
| **social/**     | `04_Social_Formatter_v2.json`                                     | After publish                              | Formats social posts from article             |
| **social/**     | `06_Blotato_SubWorkflow.json`                                     | Sub-workflow                               | Blotato-based social posting                  |
| **social/**     | `11_Queue_Processor_v2.json`                                      | Schedule                                   | Processes social queue                        |
| **social/**     | `14_Video_Production.json`                                        | Schedule / called                          | Video creation pipeline                       |
| **affiliate/**  | `06_Affiliate_Link_Manager.json`                                  | Schedule / manual                          | Fetches and selects affiliate links           |
| **affiliate/**  | `10_Affiliate_Research_v2.json`                                   | Schedule                                   | Affiliate product research                    |
| **affiliate/**  | `15_Affiliate_Link_Registry.json`                                 | On demand                                  | Registry of affiliate links                   |
| **monitoring/** | `09_Comment_Moderation.json`                                      | Schedule / 2h                              | Comment classification and replies            |
| **monitoring/** | `Alert_Handler.json`                                              | Webhook / on error                         | Handles alerts from workflows                 |
| **monitoring/** | `System_Health_Monitor.json`                                      | Schedule                                   | System health checks                          |
| **email/**      | `v3.0 — Email Newsletter Automation.json`                         | 09:00 Tue / webhook                        | Newsletter and sequences                      |


---

## Config Keys

**Source of truth:** `htg_config.csv` (repo root). All keys are loaded by `core/01_Config_Loader.json`. When adding a new key, add a row to `htg_config.csv` and optionally add a row below for discoverability.


| Key                                                                                                                                                                                                                                                                                                                               | Purpose                           |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------- |
| **Blog & WordPress**                                                                                                                                                                                                                                                                                                              |                                   |
| `BLOG_NAME`, `BLOG_NICHE`                                                                                                                                                                                                                                                                                                         | Site identity and niche           |
| `WORDPRESS_URL`                                                                                                                                                                                                                                                                                                                   | WordPress base URL                |
| **Google Sheets**                                                                                                                                                                                                                                                                                                                 |                                   |
| `GOOGLE_SHEET_ID`                                                                                                                                                                                                                                                                                                                 | Single spreadsheet ID             |
| `BLOG_IDEA_TAB`, `CONTENT_LOG_TAB`, `AFFILIATE_TAB`, `AFFILIATE_LINKS_TAB`, `SOCIAL_QUEUE_TAB`, `INTERNAL_LINKS_TAB`, `MODERATION_LOG_TAB`, `VIDEO_LOG_TAB`, `SEED_KEYWORDS_TAB`, `ERROR_LOG_TAB`, `REDDIT_POST_CACHE_TAB`, `ATP_CACHE_TAB`, `BLOG_CALENDAR_TAB`, `SOCIAL_CALENDAR_TAB`, `MANUAL_QUEUE_TAB`, `REJECTED_POSTS_TAB` | Sheet tab names                   |
| **Ollama**                                                                                                                                                                                                                                                                                                                        |                                   |
| `OLLAMA_URL`, `OLLAMA_MODEL_FAST`, `OLLAMA_MODEL_SMART`                                                                                                                                                                                                                                                                           | Ollama endpoint and models        |
| **Content & QC**                                                                                                                                                                                                                                                                                                                  |                                   |
| `MIN_WORD_COUNT`, `QC_MIN_SCORE`, `CONTENT_REFRESH_DAYS`, `CONTENT_REFRESH_MAX_WEEKLY`, `ARTICLES_PER_WEEK`, `MIN_PLANNED_ARTICLES`                                                                                                                                                                                               | Content and quality thresholds    |
| **Affiliate**                                                                                                                                                                                                                                                                                                                     |                                   |
| `MIN_CLICKBANK_COMMISSION`, `MIN_CLICKBANK_GRAVITY`, `MIN_AI_AFFILIATE_SCORE`, `HALAL_FILTER_KEYWORDS`, `HALAL_FILTER_ENABLED`                                                                                                                                                                                                    | Affiliate and halal filter        |
| **Topics & research**                                                                                                                                                                                                                                                                                                             |                                   |
| `NICHES`, `REDDIT_SUBREDDITS`, `TOPIC_BACKLOG_MAX`, `TOPIC_SCORE_MIN`                                                                                                                                                                                                                                                             | Topic and research config         |
| **Social**                                                                                                                                                                                                                                                                                                                        |                                   |
| `SOCIAL_PLATFORMS`, `VIDEO_PLATFORMS`, `VIDEO_VOICE`                                                                                                                                                                                                                                                                              | Platforms and voice               |
| `FACEBOOK_POST_DAYS`, `FACEBOOK_POST_HOUR`, `INSTAGRAM_POST_HOUR`, `PINTEREST_POST_HOUR`, `TIKTOK_POST_HOUR`, `YOUTUBE_POST_HOUR`, `TWITTER_POST_HOUR`                                                                                                                                                                            | Per-platform schedule             |
| `SOCIAL_POST_DELAY_`*, `SOCIAL_POST_TIME_*`, `BLOTATO_PROFILE_*`                                                                                                                                                                                                                                                                  | Delays and Blotato profiles       |
| **Feature flags & providers**                                                                                                                                                                                                                                                                                                     |                                   |
| `USE_BLOTATO_FOR_INLINE_IMAGES`, `AD_CAMPAIGNS_ENABLED`, `TRANSLATION_ENABLED`, `WHATSAPP_ENABLED`, `VOICE_PROVIDER`, `EMAIL_PROVIDER`                                                                                                                                                                                            | Feature toggles and providers     |
| **APIs & external**                                                                                                                                                                                                                                                                                                               |                                   |
| `COMPETITOR_RSS_FEEDS`, `COMPETITOR_INTEL_TAB`, `RAPIDAPI_REDDIT_`*, `RAPIDAPI_ATP_*`, `REDDIT_CACHE_TTL_DAYS`, `ATP_CACHE_TTL_DAYS`                                                                                                                                                                                                | Competitor feeds, trend list tab, API config |
| **Repurposing**                                                                                                                                                                                                                                                                                                                   |                                   |
| `REPURPOSE_FORMATS`, `REPURPOSED_CONTENT_TAB`, `TWITTER_QUEUE_TAB`, `PODCAST_QUEUE_TAB`                                                                                                                                                                                                                                           | Repurposing formats and sheet tabs (3–5 formats; optional queue tabs) |
| *Repurposed Content tab column contract*                                                                                                                                                                                                                                                                                          | Source URL, Date (content day), twitter_text, linkedin_text, ig_carousel_json, podcast_script, community_text (per enabled format), Timestamp. One row per post/date. |
| **Voice & Audio**                                                                                                                                                                                                                                                                                                                |                                   |
| `VOICE_PROVIDER`, `TTS_SERVER_URL`, `AUDIO_OUTPUT_PATH`, `AUDIO_LOG_TAB`, `MULTILINGUAL_CONTENT_TAB`                                                                                                                                                                                                                            | TTS provider (local \| elevenlabs \| google), local TTS HTTP URL, local path for audio files, Sheet tab for audio log, Sheet tab for Multilingual Content (default "Multilingual Content") |
| *Multilingual Content tab column contract (Voice reads)*                                                                                                                                                                                                                                                                          | Tab from `MULTILINGUAL_CONTENT_TAB`. Columns: Date, Language, Translated Title, URL (case-insensitive). Source: Multi-Language workflow writes; Voice reads. |
| *Audio Log tab column contract*                                                                                                                                                                                                                                                                                                  | One row per language. Columns: Date, Language, Podcast Title, Duration, File Path (local full path), Blog URL, Voice Provider, Status. Tab name from `AUDIO_LOG_TAB`. **Idempotency:** key = post identifier (slug from Blog URL) + date (YYYY-MM-DD) + language — re-run same day skips duplicate append. |
| *Voice n8n credentials*                                                                                                                                                                                                                                                                                                           | **ElevenLabs:** create credential type Header Auth, name e.g. "ElevenLabs API"; set header name to `xi-api-key` and value to your API key. **Google TTS:** create credential type Google API, name e.g. "Google Cloud TTS", add API key. Workflow references these by name; no keys in JSON. |
| *Audio file write*                                                                                                                                                                                                                                                                                                               | Audio files are written to `AUDIO_OUTPUT_PATH` (local only). The workflow uses a Code node with Node `fs` (self-hosted n8n); ensure the path exists and is writable. If using n8n without fs, use a Write Binary File node or external step. |
| **System**                                                                                                                                                                                                                                                                                                                        |                                   |
| `TIMEZONE`, `CONTENT_DAY_TIMEZONE`, `YOUR_N8N_URL`, `PERSONA`                                                                                                                                                                                                                                                                     | Timezone; optional override for "today" in Content Log / Multilingual filters (defaults to TIMEZONE) |
| `CONTENT_CALENDAR_WF_ID`, `TOPIC_RESEARCH_WF_ID`                                                                                                                                                                                                                                                                                  | Workflow IDs for Execute Workflow |


---

## Schedule

Default cron hours (server time): **3, 5, 6, 8, 9, 10, 12, 14, 15, 16, 18, 21**. The orchestrator (`core/08_Orchestrator_v3.json`) maps hour (and day) to runType and calls the corresponding workflow.


| Hour         | Typical run                      | Workflow (path)                                                                                                    |
| ------------ | -------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| 3 (Sun only) | SEO Interlinking                 | content/v4.0 — SEO Interlinking Intelligence Engine.json                                                           |
| 5            | Islamic Content                  | growth/HowTo-Genie v4.0 — Islamic Content Specialization Engine.json                                               |
| 6            | A/B Testing                      | growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json                                                   |
| 8            | Master pipeline (blog)           | core/08_Orchestrator_v3 (agents + content + social)                                                                |
| 9            | Email (Tue) / Competitor (other) | email/v3.0 — Email Newsletter Automation or growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json |
| 10           | WhatsApp & Telegram              | growth/HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json                                                |
| 12           | Content Repurposing              | content/v3.0 — Content Repurposing Engine.json                                                                     |
| 14           | Multi-Language                   | growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json                                                     |
| 15, 21       | Competitor & Viral               | growth/HowTo-Genie v4.0 — Competitor… / Viral Content Amplifier Engine.json                                        |
| 16           | Voice & Audio                    | growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json                                                      |
| 18           | A/B Testing                      | growth/HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json                                                   |


Edit the **Set Context** Code node in the orchestrator to change this mapping.

---

## UI

- **adhd-mission-control.tsx** (repo root): React dashboard — system status, pomodoro, wins. Uses `lucide-react`. Data is currently hardcoded; intended to be wired to n8n webhooks or Laravel APIs.
- **revenue-dashboard.tsx** (repo root): React dashboard — revenue/traffic/content/agents. Uses `recharts`. Data is currently hardcoded; intended to be wired to Laravel or n8n.
- **Laravel Mission Control** (`laravel/`): Backend for Mission Control — routes, controllers, models, Blade views. Integrates with n8n (e.g. `N8nApiService`) and Google Sheets. Serves dashboard data; replace hardcoded TSX data with API calls to this backend when ready.

---

## Archive

Superseded or unused workflows and assets are in `archive/`. See **archive/README.md** (added in Phase 8) for what lives where. Do not list every archive file in this doc.

---

## How to add a workflow

1. Add the workflow JSON under the appropriate directory (`core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, `email/`).
2. Add a row to the [Workflows](#workflows) table above (Directory, Workflow file, Schedule/Trigger, Purpose).
3. If the orchestrator should call it, add the workflow ID to the orchestrator’s **Set Context** (or equivalent) and add an Execute Workflow node for it.

---

## How to add a config key

1. Add a row to **htg_config.csv** (key, value).
2. Optionally add a row to the [Config Keys](#config-keys) table above for discoverability.
3. Use the key in workflows via the context provided by `core/01_Config_Loader.json`.

