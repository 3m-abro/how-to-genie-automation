# Archive — Superseded and Unused Workflows

Superseded or unused n8n workflows and assets live here. Active workflows are under `core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, and `email/`. See **docs/HOWTOGENIE.md** for the canonical workflow list and config.

---

## Orchestrators

| File | Reason |
|------|--------|
| **Master Orchestrator.json** | Superseded by `core/08_Orchestrator_v3.json`. |
| **Master Orchestrator v2.0.json** | Superseded by `core/08_Orchestrator_v3.json`. |
| **Master Orchestrator v2.2.json** | Superseded by `core/08_Orchestrator_v3.json`. |
| **Master Orchestrator v3.0.json** | Superseded by `core/08_Orchestrator_v3.json`. |
| **Agentic Team Orchestrator.json** | Superseded by `core/08_Orchestrator_v3.json`. |
| **Content Writer (Ollama Agent).json** | Standalone writer; superseded by pipeline in `core/08_Orchestrator_v3.json` + Ollama Agent Central. |

## Topic research

| File | Reason |
|------|--------|
| **02_Topic_Research_Engine.json** | Superseded by `content/02_Topic_Research_Engine_v2.json`. |

## Affiliate

| File | Reason |
|------|--------|
| **03_Affiliate_Research.json** | Superseded by `affiliate/10_Affiliate_Research_v2.json`. |

## Social

| File | Reason |
|------|--------|
| **04_Social_Formatter_PartA.json** | Superseded by `social/04_Social_Formatter_v2.json`. |
| **05_Queue_Processor_PartB.json** | Superseded by `social/11_Queue_Processor_v2.json`. |

## Video

| File | Reason |
|------|--------|
| **Auto Video Creation.json** | Superseded by `growth/HowTo-Genie v4.0 — Video Production Engine.json` and `social/14_Video_Production.json`. |

---

*Every file under `archive/` is listed above. Do not reference these workflow IDs from active workflows; see scripts/verify-archive-refs.sh.*
