# HowTo-Genie Automation

Automated AI-powered content creation and monetization: n8n workflows run on a schedule (locally or self-hosted), using **Ollama** as the LLM. The main pipeline runs at **8 AM daily**: an 8-agent flow produces a full how-to article, finds images, publishes to WordPress, logs to Google Sheets, and drives social and comment workflows. Satellite workflows handle video, repurposing, multi-language, voice, A/B testing, competitor monitoring, viral amplification, SEO interlinking, Islamic content, WhatsApp/Telegram, and email. Design constraint: the owner looks at the system about once per week; everything else is automated.

---

## What's in this repo

| Area | Contents |
|------|----------|
| **core/** | Config loader, orchestrator, Ollama agent (central LLM), approval poller |
| **content/** | Topic research, content calendar, repurposing, internal linking, SEO interlinking |
| **growth/** | A/B testing, competitor/trend monitor, Islamic content, multi-language, voice, viral amplifier, WhatsApp/Telegram, video |
| **social/** | Social formatter, queue processor, Blotato sub-workflow, video production |
| **affiliate/** | Affiliate link manager, research, registry |
| **monitoring/** | Comment moderation, alert handler, system health |
| **email/** | Newsletter automation (ConvertKit/MailerLite) |
| **docs/** | [HOWTOGENIE.md](docs/HOWTOGENIE.md) (canonical workflow list, schedule, config keys), setup guide, audit/gap docs |
| **laravel/** | Mission Control backend (n8n status, webhooks) |
| **ui/** | Dashboards (e.g. ADHD Mission Control, revenue) |
| **archive/** | Superseded or unused workflows |
| **htg_config.csv** | Config key source of truth (loaded by `core/01_Config_Loader.json`) |

For the **full workflow table, schedule, and config key reference**, see **[docs/HOWTOGENIE.md](docs/HOWTOGENIE.md)**.

---

## Prerequisites

- **n8n** (e.g. `http://localhost:5678`)
- **Ollama** at `http://localhost:11434` (e.g. `ollama pull llama3.2:latest`)
- **Google Sheets** — one spreadsheet; sheet ID is the main config value
- **WordPress** — site URL and Application Password for publishing
- Optional: Pexels/Pixabay (images), ConvertKit/MailerLite (email), Blotato/social APIs, Pictory/InVideo (video), GA4 (viral amplifier)

---

## Quick start

1. **Clone** and open the repo.
2. **Create credentials in n8n** (WordPress, Google Sheets OAuth2, Pexels, etc.). Never put secrets in workflow JSON.
3. **Set config:** ensure `htg_config.csv` (and/or keys in your deployment) has `GOOGLE_SHEET_ID`, `WORDPRESS_URL`, `OLLAMA_URL`, and any other keys your workflows need. See [docs/HOWTOGENIE.md](docs/HOWTOGENIE.md) for the full key list.
4. **Import workflows** from `core/`, `content/`, `growth/`, etc. into n8n (Import from File). Replace any `REPLACE_WITH_*_ID` placeholders in the orchestrator with real n8n workflow IDs.
5. **Run the Config Loader** then the **Orchestrator** (`core/08_Orchestrator_v3.json`) on a schedule (default cron: 3, 5, 6, 8, 9, 10, 12, 14, 15, 16, 18, 21). The 8 AM run is the main content pipeline.

Detailed setup and manual build steps: see **docs/** (e.g. setup guide, manual build guide PDF).

---

## Documentation

- **[docs/HOWTOGENIE.md](docs/HOWTOGENIE.md)** — Single reference: workflows, schedule, config keys, sheet tabs.
- **[docs/ORCHESTRATOR-README.md](docs/ORCHESTRATOR-README.md)** — Orchestrator import and workflow ID replacement.
- **CLAUDE.md** — Contributor and AI-agent reference: repo structure, n8n conventions, JSON contracts, development workflow.

---

## License

No license file in repo; treat as unlicensed unless stated elsewhere.
