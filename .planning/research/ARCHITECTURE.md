# Architecture Research: v2.0 Feature Integration

**Domain:** HowTo-Genie automation — integration of Islamic content, competitor intelligence, voice/audio, content repurposing, docs, and archive/cleanup with existing n8n + Ollama + Sheets + Laravel stack.

**Researched:** 2026-03-13  
**Confidence:** HIGH (codebase and PROJECT.md verified)

## System Overview (Existing + New Integration)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        SCHEDULE LAYER (cron)                                      │
├─────────────────────────────────────────────────────────────────────────────────┤
│  5 AM Islamic │ 6 AM A/B │ 8 AM Orchestrator │ 10 AM WA/Video │ 12 Repurpose    │
│  2 PM Multi-Lang │ 4 PM Voice │ every 3h Competitor │ every 6h Viral │ Sun SEO   │
├─────────────────────────────────────────────────────────────────────────────────┤
│                        n8n WORKFLOWS (satellites + core)                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Islamic (5AM)     Competitor (3h)    Repurposing (12)    Voice (4PM)             │
│  NEW/MODIFIED      NEW/MODIFIED       MODIFIED            NEW/MODIFIED            │
│       │                  │                  │                  │                 │
│       ▼                  ▼                  ▼                  ▼                 │
│  Islamic Queue    Content Ideas      Repurposed Content   Multilingual Content   │
│  Calendar Events  Competitor Intel   Twitter/Podcast Q    (read) + Audio Log      │
├─────────────────────────────────────────────────────────────────────────────────┤
│                        CONFIG & DATA (single source of truth)                     │
├─────────────────────────────────────────────────────────────────────────────────┤
│  ⚙️ Config Loader (n8n Data Tables: htg_config + htg_secrets)                     │
│  htg_config.csv → keys for GOOGLE_SHEET_ID, tabs, VOICE_PROVIDER, etc.           │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Google Sheets: Content Log │ Social Queue │ Affiliate Registry │ Blog Idea      │
│  + NEW/MODIFIED TABS: Islamic Content Queue, Islamic Calendar Events,            │
│    Content Ideas Queue, Competitor Intelligence, Backlink Opportunities,        │
│    Repurposed Content, Twitter Queue, Podcast Queue, Multilingual Content         │
├─────────────────────────────────────────────────────────────────────────────────┤
│  Laravel: Mission Control + Revenue dashboards, n8n status/webhooks (unchanged)  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

## Integration Points

### 1. Config Loader (Critical Path for All New/Modified Workflows)

| Current State | Required Change |
|---------------|-----------------|
| Orchestrator and most v1.0 satellites call **⚙️ Config Loader** first and use `$('⚙️ Load Config').item.json` for sheet ID and tab names. | Islamic, Competitor, Voice, and Content Repurposing workflows use **hardcoded** `YOUR_GOOGLE_SHEET_ID` and literal sheet names. |
| **Action:** Add Config Loader as the first step in each of these four workflows (Execute Workflow → Config Loader), then replace every `documentId` / `sheetName` with config expressions. |

**Config keys to add to htg_config (or Data Table) for v2.0:**

| Key | Purpose | Default / Existing |
|-----|---------|--------------------|
| `GOOGLE_SHEET_ID` | Already exists | Used by orchestrator and most workflows |
| `CONTENT_LOG_TAB` | Already exists | Content Log |
| `ISLAMIC_QUEUE_TAB` | **NEW** | Islamic Content Queue |
| `ISLAMIC_CALENDAR_TAB` | **NEW** | Islamic Calendar Events |
| `CONTENT_IDEAS_QUEUE_TAB` | **NEW** | Content Ideas Queue |
| `COMPETITOR_INTEL_TAB` | **NEW** | Competitor Intelligence |
| `BACKLINK_OPPORTUNITIES_TAB` | **NEW** | Backlink Opportunities |
| `REPURPOSED_CONTENT_TAB` | **NEW** | Repurposed Content |
| `TWITTER_QUEUE_TAB` | **NEW** | Twitter Queue |
| `PODCAST_QUEUE_TAB` | **NEW** | Podcast Queue |
| `MULTILINGUAL_CONTENT_TAB` | **NEW** (or already in use) | Multilingual Content — Multi-Language Engine already uses `MULTILINGUAL_CONTENT_TAB` in code; ensure key exists in config. |
| `VOICE_PROVIDER` | Already in htg_config.csv | e.g. `local` / `elevenlabs` |
| `COMPETITOR_RSS_FEEDS` | Already in htg_config.csv | CSV of RSS URLs for competitor monitor |
| `WORDPRESS_URL` | Already exists | Required by Repurposing (fetch post by slug) |

### 2. Islamic Content Specialization Engine

| Aspect | Detail |
|--------|--------|
| **Schedule** | 5 AM daily (before Orchestrator). |
| **Existing workflow** | `growth/HowTo-Genie v4.0 — Islamic Content Specialization Engine.json` — present but **not config-integrated**. |
| **Data flow** | AlAdhan API (Hijri + prayer times) → Code (calendar context, special occasions) → Ollama (Islamic content ideas) → Parse → **Append to Islamic Content Queue**; optional branch → **Append to Islamic Calendar Events**. |
| **New components** | None (workflow exists). **Modified:** Add Execute Workflow “⚙️ Load Config” at start; replace `YOUR_GOOGLE_SHEET_ID` and sheet names with `$('⚙️ Load Config').item.json.GOOGLE_SHEET_ID`, `$('⚙️ Load Config').item.json.ISLAMIC_QUEUE_TAB`, etc. Fix Code node references: use exact n8n node names (e.g. `📅 Fetch Hijri Date & Islamic Calendar` not `Fetch Hijri Date & Islamic Calendar`) where `$('Node Name')` is used. |
| **Optional downstream** | Orchestrator’s “🔧 Build Research Context” could **read** Islamic Content Queue (e.g. suggested_topics or priority_languages) and pass into Agent 1 context. This is an **orchestrator modification**, not Islamic workflow change. |

### 3. Competitor Intelligence & Trend Monitor

| Aspect | Detail |
|--------|--------|
| **Schedule** | Every 3 hours. |
| **Existing workflow** | `growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json` — present but **not config-integrated**. |
| **Data flow** | Code (competitor list + subreddits) → Switch (RSS vs Reddit) → Fetch RSS / Reddit / (Google Trends) → Parse → **Append to Content Ideas Queue**, **Competitor Intelligence**, **Backlink Opportunities**. |
| **New components** | None. **Modified:** Add Config Loader at start; replace all `YOUR_GOOGLE_SHEET_ID` and sheet names with config. Optionally source competitor list or RSS URLs from config (e.g. `COMPETITOR_RSS_FEEDS` already in htg_config.csv — wire it into the “Load Monitoring Targets” Code node instead of hardcoded list). |
| **Optional downstream** | Orchestrator could **read** Content Ideas Queue or Competitor Intelligence in “🔧 Build Research Context” (alongside Reddit + existing topics + refresh candidates) so Agent 1 gets competitor-derived ideas. |

### 4. Voice & Audio Content Pipeline

| Aspect | Detail |
|--------|--------|
| **Schedule** | 4 PM daily (after Multi-Language at 2 PM). |
| **Existing workflow** | `growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json` — present but **not config-integrated**. |
| **Data flow** | **Read Content Log** (today’s post) → **Read Multilingual Content** (all language versions) → Code (voice config per language) → Fetch article content → Ollama (adapt to audio script) → Parse → branch by `VOICE_PROVIDER` (e.g. ElevenLabs vs local) → TTS → store/append results (workflow-specific; may write to a “Voice Log” or similar tab). |
| **Dependency** | **Multilingual Content** tab is **written by** Multi-Language Expansion Engine (2 PM). Voice pipeline must run after 2 PM. |
| **New components** | None. **Modified:** Add Config Loader at start; replace `YOUR_GOOGLE_SHEET_ID`, `Content Log`, `Multilingual Content` with config (`GOOGLE_SHEET_ID`, `CONTENT_LOG_TAB`, `MULTILINGUAL_CONTENT_TAB`). Use `VOICE_PROVIDER` from config for branching. |

### 5. Content Repurposing Engine

| Aspect | Detail |
|--------|--------|
| **Schedule** | Noon daily (after Orchestrator 8 AM; today’s post is in Content Log). |
| **Existing workflow** | `content/v3.0 — Content Repurposing Engine.json` — present but **not config-integrated**. |
| **Data flow** | **Read Content Log** (today’s post) → HTTP Request to **WordPress REST API** (post by slug from Content Log) → Code (strip HTML, extract headings/excerpt) → parallel LLM branches (Twitter thread, LinkedIn, IG carousel, etc.) → **Append to Repurposed Content**, **Twitter Queue**, **Podcast Queue**, etc. |
| **New components** | None. **Modified:** Add Config Loader at start; replace `YOUR_GOOGLE_SHEET_ID`, `Content Log`, and sheet names with config. Replace `https://your-blog.com` in WordPress fetch URL with `$('⚙️ Load Config').item.json.WORDPRESS_URL`. |

### 6. Centralized Docs (Non-Runtime)

| Aspect | Detail |
|--------|--------|
| **Goal** | Single authoritative markdown for workflows, UI, and reference. |
| **New components** | One consolidated doc (e.g. `docs/HOWTOGENIE.md` or root `HOWTOGENIE.md`) that replaces or subsumes scattered references. |
| **Modified** | Consolidate from: `howto-genie-setup-guide.md`, `CLAUDE.md`, and any other ad-hoc .md that describe setup, workflows, schedules, config keys, and UI. No n8n or Laravel code change; repo-only. |

### 7. Archive / Cleanup (Non-Runtime)

| Aspect | Detail |
|--------|--------|
| **Goal** | Move unused workflows/UI/files to `archive/` or delete if not needed. |
| **New components** | None. **Modified:** Identify workflows and assets that are superseded (e.g. older orchestrator versions, deprecated social/formatter parts). Move to `archive/` (already exists with e.g. `Master Orchestrator v2.0.json`, `Content Writer (Ollama Agent).json`). Optionally remove duplicate or obsolete UI files; keep one source of truth for dashboards. No change to active workflows. |

---

## New vs Modified Components (Summary)

| Feature | New Components | Modified Components |
|---------|----------------|--------------------|
| **Islamic content** | None | Islamic Content workflow (add Config Loader, config-driven sheet/tabs). Optional: Orchestrator (read Islamic queue in research context). |
| **Competitor intelligence** | None | Competitor Monitor workflow (add Config Loader, config-driven sheet/tabs; optionally use COMPETITOR_RSS_FEEDS). Optional: Orchestrator (read Content Ideas / Competitor Intel in research context). |
| **Voice/audio** | None | Voice & Audio workflow (add Config Loader, config-driven sheet/tabs and VOICE_PROVIDER). |
| **Content repurposing** | None | Content Repurposing workflow (add Config Loader, config-driven sheet/tabs and WORDPRESS_URL). |
| **Centralized docs** | One consolidated MD file | Possibly retire or redirect from existing setup/CLAUDE docs. |
| **Archive/cleanup** | None | Repo layout: move selected workflows/files into `archive/`. |

**New Google Sheets tabs (create in same spreadsheet as Content Log):**  
Islamic Content Queue, Islamic Calendar Events, Content Ideas Queue, Competitor Intelligence, Backlink Opportunities, Repurposed Content, Twitter Queue, Podcast Queue.  
Multilingual Content may already exist if Multi-Language Engine is in use.

---

## Data Flow (v2.0 Additions)

1. **Islamic (5 AM) → Sheets**  
   Writes: Islamic Content Queue, Islamic Calendar Events.  
   Optional read by Orchestrator (8 AM): Islamic Content Queue for topic hints / theme.

2. **Competitor (every 3h) → Sheets**  
   Writes: Content Ideas Queue, Competitor Intelligence, Backlink Opportunities.  
   Optional read by Orchestrator: Content Ideas Queue or Competitor Intelligence in research context.

3. **Orchestrator (8 AM) → Content Log**  
   Unchanged. Writes today’s post row. Downstream satellites read Content Log.

4. **Repurposing (12 PM) → Content Log (read) → Sheets (write)**  
   Reads Content Log (today’s post). Writes: Repurposed Content, Twitter Queue, Podcast Queue.

5. **Multi-Language (2 PM) → Content Log (read) → Multilingual Content (write)**  
   Unchanged. Writes translated rows. Voice reads Multilingual Content.

6. **Voice (4 PM) → Content Log + Multilingual Content (read)**  
   Reads today’s post and all language versions. Writes: audio assets / log as defined in workflow.

---

## Suggested Build Order (Dependencies Respected)

1. **Docs consolidation + Archive/cleanup**  
   No runtime dependency. Reduces confusion and clutter before adding config/tabs.

2. **Config + Sheet tabs**  
   Add new keys to htg_config (or n8n Data Table) and create the new tabs in Google Sheets. Required for all four workflows.

3. **Islamic Content workflow**  
   Wire Config Loader; config-driven documentId/sheetName. Optional: add “Load Islamic Queue” in Orchestrator and merge into “🔧 Build Research Context”.

4. **Competitor Intelligence workflow**  
   Wire Config Loader; config-driven documentId/sheetName; optionally use COMPETITOR_RSS_FEEDS. Optional: add “Load Content Ideas” or “Load Competitor Intel” in Orchestrator and merge into research context.

5. **Content Repurposing workflow**  
   Wire Config Loader; config-driven documentId/sheetName and WORDPRESS_URL. Depends only on Content Log (already produced by Orchestrator).

6. **Voice & Audio workflow**  
   Wire Config Loader; config-driven documentId/sheetName and MULTILINGUAL_CONTENT_TAB / VOICE_PROVIDER. Depends on Multilingual Content tab (Multi-Language Engine); build after Multi-Language is confirmed and tab exists.

**Rationale:** Docs and archive first to avoid mixing refactors with new wiring. Config and tabs next so every satellite can be updated to a single pattern. Islamic and Competitor can run in parallel after config. Repurposing and Voice both depend on config and existing pipelines (Content Log; Multilingual Content for Voice), so they follow.

---

## Architectural Patterns

### Pattern: Config-First Satellite

**What:** Every satellite that reads or writes Google Sheets starts with Execute Workflow → Config Loader; all sheet IDs and tab names come from `$('⚙️ Load Config').item.json`.

**When:** Any workflow that uses GOOGLE_SHEET_ID or tab names.

**Trade-offs:** One extra workflow call per run; consistent, single source of truth and no hardcoded IDs.

### Pattern: Optional Orchestrator Enrichment

**What:** Orchestrator’s “🔧 Build Research Context” already merges Reddit + existing topics + refresh candidates. It can be extended to merge one or more of: Islamic Content Queue, Content Ideas Queue, Competitor Intelligence.

**When:** When topic quality should be influenced by Islamic calendar or competitor trends.

**Trade-offs:** Richer context for Agent 1; slightly more complexity and prompt size. Implement as optional (config flag or extra sheet read with fallback).

---

## Anti-Patterns to Avoid

- **Hardcoding sheet ID or tab names in v2.0 workflows** — Use Config Loader and config keys so one change in htg_config applies everywhere.
- **Calling another workflow’s node by wrong name** — Code nodes that use `$('Node Name')` must use the **exact** node name (including emoji) as in the workflow JSON; otherwise n8n throws at runtime.
- **Running Voice before Multilingual** — Voice depends on Multilingual Content tab; schedule must stay after 2 PM Multi-Language run.
- **Repurposing without WORDPRESS_URL** — Fetching post by slug requires correct WordPress base URL from config.

---

## Laravel / Dashboards

No mandatory Laravel or dashboard changes for v2.0. Optional later: Mission Control or Revenue dashboard widgets that surface Islamic queue depth, competitor intel summary, repurposed asset counts, or voice pipeline status (e.g. by calling n8n webhooks or reading from Sheets).

---

## Sources

- `.planning/PROJECT.md` — v2.0 scope, schedule design, constraints.
- `core/08_Orchestrator_v3.json` — Trigger → Config Loader → sheet reads, Build Research Context, Content Log write.
- `core/01_Config_Loader.json` — Data Table (htg_config + htg_secrets), Build Config Object.
- `htg_config.csv` — Existing keys (GOOGLE_SHEET_ID, CONTENT_LOG_TAB, VOICE_PROVIDER, COMPETITOR_RSS_FEEDS, etc.).
- `growth/HowTo-Genie v4.0 — Islamic Content Specialization Engine.json` — AlAdhan, Islamic Queue, Calendar Events.
- `growth/HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json` — RSS/Reddit, Content Ideas Queue, Competitor Intelligence, Backlink Opportunities.
- `growth/HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json` — Content Log + Multilingual Content read, TTS branch.
- `content/v3.0 — Content Repurposing Engine.json` — Content Log read, WP fetch, Repurposed Content / Twitter / Podcast queues.
- `growth/HowTo-Genie v4.0 — Multi-Language Expansion Engine.json` — MULTILINGUAL_CONTENT_TAB usage.

---
*Architecture research for: v2.0 Content Expansion & Housekeeping integration*
*Researched: 2026-03-13*
