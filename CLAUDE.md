# CLAUDE.md — HowTo-Genie Automation Repository

## Project Overview

HowTo-Genie is a fully automated AI-powered content creation and monetization platform. It operates as a set of n8n workflows that run locally on a scheduled basis, using Ollama (local LLM) to drive an 8-agent content pipeline. Every day at 8 AM the system produces a complete "how-to" blog post, publishes it to WordPress, distributes content across five social platforms, and logs everything to Google Sheets. Additional satellite workflows handle video creation, email newsletters, multilingual translation, content repurposing, and performance optimization — all without manual intervention.

The system is designed around a single design constraint: the owner should only need to look at it once per week. Everything else is automated.

**Current branch:** `claude/add-claude-documentation-tPTAR`
**Base branch:** `master` / `main` on remote

---

## Repository Structure

```
/how-to-genie-automation/
├── .cursor/
│   ├── settings.json                          # Cursor IDE config (Hugging Face plugin enabled)
│   └── rules/
│       ├── n8n-rule.mdc                       # Core n8n workflow engineering conventions
│       ├── n8n-json-contracts.mdc             # JSON schema contracts for all LLM outputs
│       ├── n8n-llm-prompts.mdc                # Prompt templates (extraction, content, social, code)
│       └── ollama-json-only.mdc               # Ollama model config and JSON-only enforcement
│
├── files/
│   ├── HowToGenie_Full_Audit.docx             # Full system audit document
│   └── HowToGenie_Deep_Gap_Analysis.docx      # Gap analysis for future improvements
│
├── files.zip                                  # Archive of above docs
│
├── howto-genie-setup-guide.md                 # PRIMARY setup reference (318 lines)
│                                              # Covers agents, monetization, image pipeline,
│                                              # social media, checklist, performance, costs
│
├── adhd-mission-control.tsx                   # React dashboard: ADHD-optimized single-view
│                                              # Shows system status, pomodoro timer, wins
│
├── revenue-dashboard.tsx                      # React dashboard: Revenue/traffic analytics
│                                              # Uses recharts; tabs: overview/revenue/traffic/
│                                              # content/agents with hardcoded sample data
│
├── laravel-admin-panel.php                    # Laravel backend for Mission Control
│                                              # Routes, controllers, models, Blade views
│                                              # Integrates with n8n webhooks via HTTP
│
├── HowToGenie_Manual_Build_Guide.pdf          # 20-page manual build reference
│
│── n8n Workflow Files (JSON exports):
│
├── HowTo-Genie_ Master Orchestrator.json      # Simplified 5-agent chain (v1-style)
│                                              # Trigger: 9 AM daily; llama3.2
│
├── HowTo-Genie v2.0 — Master Orchestrator.json  # Full 8-agent pipeline (CANONICAL)
│                                                 # Trigger: 8 AM daily; llama3.2
│                                                 # Agents 0–7 + image + WP publish + sheets
│
├── HowTo-Genie v3.0 — Optimized.json         # Optimized pipeline with Reddit trend scraping
│                                              # Reads Google Sheets to avoid topic duplication
│
├── HowTo-Genie v3.0 — Auto Video Creation.json      # Trigger: 10:30 AM daily
│                                                     # Reads today's post; sends to Pictory/InVideo
│
├── HowTo-Genie v3.0 — Content Repurposing Engine.json  # Trigger: Noon daily
│                                                        # Strips HTML; repurposes to 10 asset types
│
├── HowTo-Genie v3.0 — Email Newsletter Automation.json # Webhook-triggered on new subscriber
│                                                        # Tags, sequences, ConvertKit/MailerLite
│
├── HowTo-Genie v4.0 — A_B Testing & Optimization Engine.json  # Trigger: 6 AM daily
│                                                               # Creates variants of yesterday's post
│
├── HowTo-Genie v4.0 — Competitor Intelligence & Trend Monitor.json  # Trigger: every 3 hours
│                                                                     # Scrapes 10 competitors + Reddit
│
├── HowTo-Genie v4.0 — Islamic Content Specialization Engine.json  # Trigger: 5 AM daily
│                                                                   # AlAdhan API + Hijri calendar logic
│
├── HowTo-Genie v4.0 — Multi-Language Expansion Engine.json  # Trigger: 2 PM daily
│                                                             # 9-language translation pipeline
│
├── HowTo-Genie v4.0 — SEO Interlinking Intelligence Engine.json  # Trigger: Sunday 3 AM
│                                                                  # Rebuilds internal linking network
│
├── HowTo-Genie v4.0 — Viral Content Amplifier Engine.json  # Trigger: every 6 hours
│                                                            # GA4 API → amplifies viral posts
│
├── HowTo-Genie v4.0 — Voice & Audio Content Pipeline.json  # Trigger: 4 PM daily
│                                                            # TTS for 9-language content
│
├── HowTo-Genie v4.0 — WhatsApp & Telegram Distribution Bot.json  # Trigger: 10 AM daily
│                                                                  # Sends digest to subscribers
│
└── HowTo-Genie_ Affiliate Link Manager.json  # Standalone; fetches Muncheye + CBEngine feeds
                                              # AI-selects relevant affiliate products
```

---

## Key Components

### 1. The 8-Agent Pipeline (v2.0 Master Orchestrator — Canonical)

The v2.0 orchestrator is the canonical workflow. All other workflows depend on the content it produces and logs to Google Sheets.

| Agent | n8n Node Name | Model | Temperature | Role |
|-------|--------------|-------|-------------|------|
| 0 | Prompt Engineer | llama3.2:latest | 0.4 | Generates all downstream prompts dynamically each run |
| 1 | Research & Topic | llama3.2:latest | 0.7 | Finds trending topics + identifies affiliate products |
| 2 | Content Writer | llama3.2:latest | 0.8 | Writes 2800+ word structured article |
| 3 | Humanizer | llama3.2:latest | 0.9 | Rewrites to sound like expert human blogger |
| 4 | SEO & Monetization | llama3.2:latest | 0.4 | Meta tags, slug, affiliate CTAs, schema |
| 5 | Quality Control | llama3.2:latest | 0.3 | Scores and approves/rejects content |
| 6 | Social + Reels | llama3.2:latest | 0.9 | Creates posts for FB, IG, TikTok, Pinterest, YT |
| 7 | Comment Moderator | llama3.2:latest | 0.7 | Classifies, approves, replies to comments |

Between each LLM agent is a Code node that parses the LLM response, validates required fields, and provides fallback defaults when parsing fails.

**Schedule:** `0 8 * * *` (8 AM daily, configurable in the Schedule Trigger node)

### 2. Satellite Workflows and Their Schedules

The satellite workflows run after the main pipeline and read from Google Sheets' "Content Log" tab to pick up the day's published post.

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| Auto Video Creation | `30 10 * * *` (10:30 AM) | Script → Pictory/InVideo API |
| Content Repurposing | `0 12 * * *` (Noon) | 1 post → 10 asset formats |
| Multi-Language | `0 14 * * *` (2 PM) | Translates to 9 languages |
| Voice & Audio | `0 16 * * *` (4 PM) | TTS for all 9 language versions |
| WhatsApp/Telegram | `0 10 * * *` (10 AM) | Sends digest to subscribers |
| A/B Testing | `0 6 * * *` (6 AM) | Tests variants of prior day's post |
| Competitor Monitor | `0 */3 * * *` (every 3h) | Scans 10 competitors + Reddit |
| Viral Amplifier | `0 */6 * * *` (every 6h) | GA4 API → boosts viral posts |
| SEO Interlinking | `0 3 * * 0` (Sun 3 AM) | Rebuilds internal link graph |
| Islamic Content | `0 5 * * *` (5 AM) | Hijri calendar-aware content |
| Email Newsletter | Webhook | New subscriber welcome + tagging |
| Affiliate Manager | Manual/Trigger | Fetches Muncheye + CBEngine feeds |

### 3. Google Sheets as the Data Backbone

Every workflow reads from and writes to a single Google Sheets spreadsheet. The sheet ID is the single most important configuration value in this project.

Required tabs:
- **Content Log** — Published post title, keyword, WP URL, date, tags, categories (read by ALL satellite workflows)
- **Reels Scripts** — TikTok/Shorts script queue
- **YT Shorts Queue** — YouTube Shorts scripts
- **Affiliate Products** — Active affiliate product database
- **Revenue Tracker** — Monthly income tracking
- **Multilingual Content** — Translated versions (read by Voice & Audio pipeline)

### 4. Image Sourcing Fallback Chain

```
Article keyword → Pexels API (200 req/hr free)
                     ↓ Not found
                  Pixabay API (100 req/min free)
                     ↓ Not found
                  Ollama BakLLaVA (image description generation)
                     ↓
                  Stable Diffusion WebUI API (local, port 7860)
                     ↓
                  Upload to WordPress Media Library
```

### 5. React Dashboards (adhd-mission-control.tsx, revenue-dashboard.tsx)

Both files are standalone React components. They are not part of a build system in this repository — they must be embedded into an existing React application or CRA/Vite project.

- `adhd-mission-control.tsx`: Uses `lucide-react` icons. Displays system status, Pomodoro timer, weekly wins, module grid. Has hardcoded `systemStatus` and `priorities` data — these must be wired to real API calls to be functional.
- `revenue-dashboard.tsx`: Uses `recharts` (LineChart, BarChart, PieChart, AreaChart). All data (`revenueData`, `trafficData`, `agentActivity`) is currently hardcoded for demonstration.

### 6. Laravel Backend (laravel-admin-panel.php)

This file contains multiple Laravel files concatenated (routes, controller, models, views). It is not a standalone file. To use it:

1. Create a Laravel project with Breeze
2. Extract each section (marked by `FILE:` comments) into the correct directory
3. The `MissionControlController` connects to n8n at `http://localhost:5678`
4. Uses `Cache::remember` with 5-minute TTL to reduce n8n API calls

---

## n8n Workflow Conventions

These rules are sourced from `.cursor/rules/` and must be followed when creating or modifying any workflow JSON.

### Core Rules

- Workflows must be deterministic. No randomness beyond LLM temperature.
- Prefer simple linear node chains. Avoid deep nesting or complex branching.
- All Code nodes use plain JavaScript only. No external libraries. No `console.log`.
- Code nodes return an object or array directly — never use `return` with undefined.
- Workflows must be idempotent and restart-safe (re-running the same day should not duplicate posts).

### LLM / Ollama Configuration

Default model for new nodes: `qwen2.5:7b` (per Cursor rules). Existing workflows use `llama3.2:latest`.

```javascript
// Standard Ollama node settings
{
  "model": "llama3.2:latest",  // or qwen2.5:7b for new work
  "options": {
    "temperature": 0.2,   // use per-agent values from the table above
    "top_p": 0.9,
    "num_ctx": 4096
  }
}
```

**Every prompt to an LLM must begin with:**
```
Return only valid JSON.
No text.
No markdown.
No explanations.

Schema:
{ ... exact schema ... }
```

### JSON Contract Pattern

Every LLM output must conform to this envelope:

```json
// Success
{
  "success": true,
  "data": { ... },
  "error": null
}

// Failure
{
  "success": false,
  "data": null,
  "error": {
    "code": "",
    "message": ""
  }
}
```

Rules:
- `success` is always boolean
- `data` is `null` when `success` is `false`
- `error` is `null` when `success` is `true`
- No missing fields. Use `""` for empty strings, `[]` for empty arrays
- Never use partial objects or implicit defaults

### Validation Pattern After Every LLM Node

```javascript
// Code node immediately after every LLM node:
const raw = $input.first().json.response || '';
let parsed;
try {
  const match = raw.match(/```json\n([\s\S]*?)\n```/) || raw.match(/(\{[\s\S]*\})/);
  parsed = JSON.parse(match ? (match[1] || match[0]) : raw);
  if (!parsed.requiredField) throw new Error('Missing required field');
} catch(e) {
  parsed = { /* fallback defaults */ };
}
return [{ json: parsed }];
```

### Error Handling Rules

- Add IF nodes after every HTTP Request node to check for non-200 responses
- Capture error messages in structured fields: `{ code: "", message: "" }`
- Never silently fail — always return a machine-readable error object
- Use fallback defaults in catch blocks; log errors to Google Sheets

### HTTP Request Node Requirements

All HTTP Request nodes must set: Method, URL, Headers, and Timeout. Validate the response before any downstream node uses it.

### Node Naming Convention

- Use verb-noun format for workflow names: `generate-blog-draft`, `sync-crm-contacts`
- Node names in this project use emoji prefixes (e.g., `🔍 Agent 1: Research & Topic`) — maintain this style for visual clarity
- Code nodes that parse LLM output are named `Parse & Validate [Stage]`

### Versioning

- Each workflow JSON has a `versionId` field — update it on every logical change
- The workflow `name` field includes the version: `HowTo-Genie v2.0 — Description`
- Never overwrite a workflow without bumping the version

### Security

- Never hardcode API keys or secrets in workflow JSON
- Reference credentials by name using n8n's credential system
- All `YOUR_XXX` placeholders in workflow JSON must be replaced with n8n credential references, not raw values

---

## Development Workflow

### Making Changes to Workflows

1. Export the existing workflow from n8n as JSON (via n8n UI: Workflow → Export)
2. Make edits to the JSON in this repository
3. Import the modified JSON back into n8n (Settings → Import)
4. Test manually before re-enabling the schedule trigger
5. Commit the updated JSON file with a clear commit message

### Editing React Dashboards

The `.tsx` files require a React environment with these dependencies:
- `recharts` (revenue-dashboard.tsx)
- `lucide-react` (adhd-mission-control.tsx)

To embed in a project:
```bash
npm install recharts lucide-react
# Copy the TSX file into your src/ directory
# Import and render as a default export component
```

The hardcoded data in both dashboards must eventually be replaced with API calls to n8n webhooks or the Laravel backend.

### Editing the Laravel Backend

The `laravel-admin-panel.php` file is a reference implementation with multiple files concatenated. Each section is marked with a `FILE:` comment. Extract each section into the corresponding Laravel directory:

- `routes/web.php` — route definitions
- `app/Http/Controllers/MissionControlController.php` — dashboard logic
- `app/Http/Controllers/N8nWebhookController.php` — n8n trigger/status
- Models and views follow accordingly

### Commit Convention

Follow standard imperative commit messages:
```
Add: [description of new workflow or feature]
Update: [description of change]
Fix: [description of bug fix]
```

Examples:
- `Add: v4.0 Email drip sequence workflow`
- `Update: Master Orchestrator v2.0 — increase QC agent temperature to 0.35`
- `Fix: Content Repurposing Engine — handle missing WP URL slug`

---

## Configuration and Environment

### Placeholder Values in Workflow JSON

Every workflow JSON file contains placeholder strings that must be replaced before activation. Search for `YOUR_` across all JSON files.

| Placeholder | Where Used | How to Configure |
|-------------|-----------|-----------------|
| `YOUR_GOOGLE_SHEET_ID` | All workflows | Google Sheets spreadsheet ID from the URL |
| `your-blog.com` | All workflows | Your WordPress site domain |
| `YOUR_CONVERTKIT_API_SECRET` | Email Newsletter | ConvertKit API credentials in n8n |
| `YOUR_MAILERLITE_API_KEY` | Email Newsletter (alt) | MailerLite API key in n8n |
| `YOUR_FACEBOOK_PAGE_TOKEN` | Social workflows | Facebook Graph API page token |
| `YOUR_INSTAGRAM_GRAPH_TOKEN` | Social workflows | Instagram Graph API token |
| `YOUR_PINTEREST_OAUTH_TOKEN` | Social workflows | Pinterest OAuth2 token |
| `YOUR_TIKTOK_ACCESS_TOKEN` | Social workflows | TikTok Content Posting API v2 |
| `YOUR_PEXELS_API_KEY` | Image pipeline | Pexels API (free, 200 req/hr) |
| `YOUR_PIXABAY_API_KEY` | Image pipeline | Pixabay API (free, 100 req/min) |
| `YOUR_CLICKBANK_ID` | Affiliate Manager | ClickBank affiliate username |
| `YOUR_JVZOO_ID` | Affiliate Manager | JVZoo affiliate ID |
| `YOUR_GA4_PROPERTY_ID` | Viral Amplifier | Google Analytics 4 property ID |
| `YOUR_GOOGLE_ANALYTICS_TOKEN` | Viral Amplifier | GA4 Data API Bearer token |
| `YOUR_PICTORY_ACCESS_TOKEN` | Video Creation | Pictory.ai API token |
| `YOUR_N8N_WEBHOOK_URL` | Laravel backend | n8n instance webhook base URL |

### n8n Credential Names to Create

Before importing workflows, create these named credentials in n8n:
- `wordpressApi` — WordPress Application Password
- `googleSheetsOAuth2Api` — Google OAuth2 for Sheets
- `facebookGraphApi` — Facebook Graph API
- `pexelsApi` — Pexels HTTP Header Auth
- `pixabayApi` — Pixabay HTTP Header Auth
- `convertKitApi` — ConvertKit API Secret
- `tiktokApi` — TikTok Content Posting API

### Ollama Setup

```bash
# Install Ollama
curl -fsSL https://ollama.ai/install.sh | sh

# Pull the primary model
ollama pull llama3.2:latest

# Pull the default model per Cursor rules (for new work)
ollama pull qwen2.5:7b

# Pull lightweight fallback
ollama pull mistral:7b-instruct

# Check running models
ollama ps
```

Ollama must be accessible at `http://localhost:11434` for n8n's Ollama nodes.

### n8n Instance

The workflows assume n8n is running at `http://localhost:5678`. The Laravel backend (`laravel-admin-panel.php`) hardcodes this URL in `MissionControlController::$n8nBaseUrl`.

For self-hosted Docker:
```bash
docker run -it --rm \
  --name n8n \
  -p 5678:5678 \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n
```

### Stable Diffusion (Optional, Image Fallback)

```bash
git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui
cd stable-diffusion-webui
./webui.sh --api --listen
# API available at http://localhost:7860/sdapi/v1/txt2img
```

---

## Common Tasks

### Adding a New Workflow

1. Create a new JSON file following the naming pattern: `HowTo-Genie vX.0 — [Descriptive Name].json`
2. Use the verb-noun workflow name format inside the JSON: `{ "name": "HowTo-Genie vX.0 — verb-noun-description" }`
3. Always include a Schedule Trigger node — document the timing rationale in node `notes`
4. Read from Google Sheets "Content Log" at the start if dependent on today's post
5. Write results back to an appropriate Google Sheets tab at the end
6. All LLM nodes must have a paired Code node for validation immediately after
7. End with error logging to Google Sheets on failure path

### Modifying an Existing Workflow

1. Export current version from n8n as JSON backup
2. Increment the `versionId` in the JSON (generate a new UUID or increment a counter in the `name` field)
3. Make the change
4. Test with manual trigger in n8n with test data
5. Re-import and re-enable the schedule

### Adding a New Agent to the Pipeline

1. Insert the LLM node between existing nodes in the chain
2. Add a paired Code node immediately after for parsing/validation
3. Follow the temperature guide: higher temp (0.8–0.9) for creative agents, lower (0.3–0.4) for analytical agents
4. Update the data contract: document what fields the new agent adds to the payload
5. Update the Google Sheets log to capture the new agent's output if it produces standalone data

### Changing the LLM Model

To switch from `llama3.2:latest` to `qwen2.5:7b` (the Cursor-rule default for new work):
- Update the `model` field in the LLM node parameters
- Adjust `num_ctx` if needed (default: 4096)
- Test JSON output compliance — different models may require stricter prompt wording

### Updating Affiliate Links

The `HowTo-Genie_ Affiliate Link Manager.json` workflow contains an `Affiliate Link Database` Code node. Edit the `affiliateDB` and `productCategories` objects in that Code node directly within n8n to add or update links.

For inline affiliate links in articles, the SEO & Monetization agent (Agent 4) reads from the topic research output. The fallback affiliate links are hardcoded in the "Parse & Validate Topic" Code node — update these defaults when you change your primary affiliate programs.

### Updating the Dashboard Data

Both React dashboards currently use hardcoded data. To connect them to live data:

1. Expose an n8n webhook that aggregates Google Sheets data
2. Replace the static data arrays in the TSX files with `useEffect` + `fetch` calls
3. The Laravel backend's `MissionControlController` already provides endpoints at `/api/n8n/status` and `/mission-control` that can serve dashboard data

---

## Architecture Decisions

### Why n8n for Orchestration

n8n provides a visual workflow editor, built-in credential management, native Ollama/LLM nodes, and schedule triggers — all without requiring custom infrastructure code. Workflows are exportable as JSON, making them version-controllable in this repository.

### Why Ollama/llama3.2 (Local LLM)

Zero API cost. The entire content pipeline runs at $0/month for AI inference. The tradeoff is speed: CPU-only setups take 25–40 minutes per full pipeline run; GPU setups take 3–5 minutes. The Cursor rules default to `qwen2.5:7b` for new nodes because it is faster and still produces reliable JSON output.

### Why Google Sheets as the Database

Google Sheets provides a zero-infrastructure database that is human-readable and editable without SQL. All satellite workflows chain off of the "Content Log" tab, creating a simple dependency graph: main pipeline writes → satellites read.

### Why JSON-Only LLM Outputs

Prose LLM outputs are unpredictable in structure and break downstream node parsing. By enforcing strict JSON schemas with envelope validation (`success/data/error`), each node's output becomes a reliable data contract. Fallback defaults in every catch block ensure the pipeline continues even if one agent fails.

### Why Separate Satellite Workflows

Separating concerns into independent workflows allows each to be enabled, disabled, debugged, or scheduled independently without touching the core pipeline. If video creation fails, the blog still publishes.

### The Staggered Schedule Design

The schedule timing is intentional and sequential:
```
5:00 AM  — Islamic Content Engine (adjusts today's content strategy)
6:00 AM  — A/B Testing Engine (tests yesterday's post variants)
8:00 AM  — Master Orchestrator (main content pipeline)
10:00 AM — WhatsApp/Telegram Distribution
10:30 AM — Auto Video Creation (picks up 8 AM post)
12:00 PM — Content Repurposing Engine
2:00 PM  — Multi-Language Expansion
4:00 PM  — Voice & Audio Pipeline
Every 3h — Competitor Intelligence Monitor
Every 6h — Viral Content Amplifier
Sunday   — SEO Interlinking Engine
```

Each satellite reads "today's post" from Google Sheets, so it must run after 8 AM.

---

## Testing and Validation

### Before Importing a Workflow into n8n

1. Validate the JSON is parseable: `node -e "JSON.parse(require('fs').readFileSync('file.json'))"`
2. Confirm all `YOUR_XXX` placeholders have been replaced with n8n credential references
3. Confirm the Google Sheet ID is correct
4. Confirm the WordPress domain is correct

### Manual Test Run

1. Open the workflow in n8n
2. Disable the Schedule Trigger (toggle off)
3. Click the Schedule Trigger node → "Execute Node" to fire a single test run
4. Watch each node execute; check the output panel for valid JSON at each step
5. Verify the Google Sheets "Content Log" tab received a new row
6. Check WordPress for a new draft or published post

### Validating LLM Output Quality

The Quality Control agent (Agent 5, temperature 0.3) must return an `approved: true` field for the pipeline to proceed to publication. Check the IF node named "Content Approved?" — the true branch publishes, the false branch logs rejection to Google Sheets.

Signs of LLM output problems:
- Code node fallback defaults activate (check for `parse_error` field in node output)
- IF node routes to false branch unexpectedly
- WordPress API receives malformed payload

### Validating New Code Nodes

Code node requirements checklist:
- [ ] Plain JavaScript only — no `require()`, no `import`, no external libraries
- [ ] Returns `[{ json: ... }]` format (array of objects with `json` key)
- [ ] Has try/catch with fallback defaults
- [ ] Does not call `console.log`
- [ ] Input is accessed via `$input.first().json` or `$input.all()`
- [ ] References to prior nodes use `$('Node Name').item.json` syntax

### Monitoring in Production

The system logs all runs to Google Sheets. A healthy daily log row contains:
- Title, Keyword, WP URL, Date, Tags, Categories
- Agent status flags (approved/rejected, fallback used)
- Social post IDs for each platform
- Affiliate links injected

Check the Content Log weekly (the ADHD Mission Control dashboard surfaces this as "weekly review").

---

## Supported Languages (Multi-Language Engine)

The Multi-Language Expansion Engine translates each post into 9 languages:
`en`, `es`, `pt-BR`, `de`, `fr`, `hi`, `id`, `ar`, `ja`

Each translation is published as a separate WordPress post and logged to the "Multilingual Content" Google Sheets tab. The Voice & Audio Pipeline then generates TTS for all 9 versions.

---

## Revenue Architecture

### Ad Injection

The `howto-genie-setup-guide.md` contains a WordPress `functions.php` snippet for automatic ad injection. Add it to your WordPress theme's `functions.php`. Replace `ca-pub-XXXXXXXX` and `SLOT1`/`SLOT2` with your actual AdSense publisher ID and slot IDs.

Ad placement zones per article:
```
[Intro paragraph]
[AD_ZONE_TOP]      ← after paragraph 3
[Table of Contents]
[Sections 1–4]
[AD_ZONE_MID]      ← at 50% of article + Adsterra native
[Sections 5–8]
[AFFILIATE_CTA]    ← product recommendation block
[AD_ZONE_BOTTOM]   ← AdSense display
[Conclusion + FAQ]
```

### Affiliate Network Configuration

| Network | Base URL Pattern | Configure In |
|---------|-----------------|-------------|
| ClickBank | `https://[your-id].vendor.hop.clickbank.net` | Affiliate Link Manager Code node |
| JVZoo | `https://jvzoo.com/b/[product-id]` | Affiliate Link Manager Code node |
| Digistore24 | `https://www.digistore24.com/redir/[PRODUCT_ID]/[YOUR_ID]` | Agent 4 prompt + Code node |
| Muncheye | RSS feed `https://muncheye.com/feed` | Affiliate Link Manager HTTP node |
| CBEngine | RSS feed `https://www.cbengine.com/feeds/cbengine.xml` | Affiliate Link Manager HTTP node |

### Expected Revenue Timeline

| Month | Posts | Est. Traffic | Est. Revenue |
|-------|-------|-------------|-------------|
| 1–2 | 60 | 500–1K | $10–50 (Adsterra only) |
| 3–4 | 120 | 2K–5K | $100–500 (+ Affiliates) |
| 5–6 | 180 | 8K–15K | $500–2K (+ AdSense) |
| 12+ | 365 | 30K+ | $2K–10K+ |

Monthly operating cost: ~$5–35 (Ollama is $0; n8n self-hosted is $0).

---

## Critical Reference Files

| File | Purpose |
|------|---------|
| `HowTo-Genie v2.0 — Master Orchestrator.json` | Canonical 8-agent pipeline; start here for any core changes |
| `howto-genie-setup-guide.md` | Human-authored ground truth for setup, monetization, and platform configuration |
| `.cursor/rules/n8n-rule.mdc` | Binding engineering conventions for all n8n workflow work |
| `.cursor/rules/n8n-json-contracts.mdc` | Mandatory success/data/error JSON envelope pattern |
| `HowTo-Genie v3.0 — Optimized.json` | Best reference for new workflow architecture patterns (deduplication, Reddit trend scraping) |
