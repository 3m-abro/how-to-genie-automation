# Phase 7: Docs Consolidation — Context

**Gathered:** 2026-03-13  
**Status:** Ready for planning

<domain>
## Phase Boundary

Deliver one detailed, centralized Markdown doc that is the single authoritative reference for workflows, UI, config keys, schedule, and archive. No parallel conflicting doc sets. Doc must support being updated when adding workflows or config (workflow list + config key reference). Requirements: DOCS-01, DOCS-02.
</domain>

<decisions>
## Implementation Decisions

### Relationship to CLAUDE.md
- **CLAUDE.md stays separate.** Consolidated doc is the human-facing reference; CLAUDE.md remains the AI context file. No merging of full content; avoid duplication/conflict by design (e.g. single source for facts, or clear pointer from CLAUDE to the doc).

### Doc location and name
- **Claude's discretion.** Planner chooses: path (e.g. docs/HOWTOGENIE.md vs root), new file vs expanding docs/howto-genie-setup-guide.md, filename, and how to handle other existing MD (deprecate in place, short pointer, etc.).

### Workflow list format
- **Claude's discretion.** Planner chooses: grouping (by directory vs by schedule vs flat table), fields per workflow (name/schedule/purpose only vs + config keys vs + trigger type), hand-maintained vs generated (or hand now with optional generator deferred), and whether to list active-only vs everything that exists today.

### Config key reference
- **Claude's discretion.** Planner chooses: single table vs grouped by domain vs by workflow; source of truth (htg_config.csv vs doc); placeholder/secret guidance (none vs short note vs per-key); and whether to link to phase CONFIG-KEYS or keep all in the single doc.

### Depth and how-to
- **Claude's discretion.** Planner chooses: reference-only vs including "how to add a workflow" / "how to add a config key"; amount of setup/tutorial content; whether to include an Archive section (or pointer to archive/README); and schedule presentation (one section vs per-workflow vs both).
</decisions>

<specifics>
## Specific Ideas

No specific requirements — user delegated all choices to planner. Standard approaches acceptable.
</specifics>

<code_context>
## Existing Code Insights

### Existing docs (sources to merge or reference)
- **CLAUDE.md** (root) — Long; AI-oriented; repo structure, conventions, workflow list (partly outdated paths). Keep separate per decision; do not duplicate full content into consolidated doc.
- **docs/howto-genie-setup-guide.md** — Setup, 8-agent pipeline, central Ollama, architecture; ~13k chars. Strong candidate to expand or merge into the single doc.
- **docs/ORCHESTRATOR-README.md** — Short orchestrator-focused readme.
- **docs/** also contains .docx and .pdf (audit, gap analysis, manual build guide); no need to merge binary; can reference or list.

### Repo layout (workflow locations)
- **core/** — Orchestrator, Config Loader, Approval Poller, Ollama Agent Central.
- **content/** — Topic Research, Content Calendar, Internal Linking, Content Refresh, SEO Interlinking, Content Repurposing.
- **growth/** — A/B Testing, Viral Amplifier, Multi-Language, WhatsApp/Telegram, Refresh Candidates, Islamic/Voice/Competitor (v4 templates).
- **social/** — Social Formatter, Blotato, Queue Processor, Video Production.
- **affiliate/** — Affiliate Link Manager, Affiliate Research, Affiliate Link Registry.
- **monitoring/** — System Health, Alert Handler, Comment Moderation.
- **email/** — Email Newsletter Automation.
- **ui/** — adhd-mission-control.tsx, revenue-dashboard.tsx (React; Laravel APIs).
- **laravel/** — Mission Control + Revenue APIs, N8nApiService, GoogleSheetsService, etc.
- **htg_config.csv** — Single config source; all keys for pipelines, sheets, gates, etc.

### Integration points
- Consolidated doc will be the human entrypoint for "how does this system work" and "where do I add/change things." Phase 8 (Archive) will document "what lives where" and may add archive/README; Phase 7 doc can reserve an Archive section or pointer.
</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope. (Optional: script-generated workflow list or "CLAUDE derived from doc" could be future phases; not requested.)
</deferred>

---
*Phase: 07-docs-consolidation*  
*Context gathered: 2026-03-13*
