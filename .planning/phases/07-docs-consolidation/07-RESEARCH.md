# Phase 7: Docs Consolidation — Research

**Researched:** 2026-03-13  
**Domain:** Documentation consolidation, single source of truth, Markdown reference  
**Confidence:** HIGH

## Summary

Phase 7 delivers one detailed, centralized Markdown document that is the single authoritative reference for workflows, UI, config keys, schedule, and archive. The domain is content and structure, not tooling: no new libraries or frameworks are required. The planner must choose doc location/name, workflow list format, config key reference shape, and depth (reference-only vs how-to) within the constraints below. Existing sources to merge or point from: `docs/howto-genie-setup-guide.md` (~331 lines, strong candidate to expand), `docs/ORCHESTRATOR-README.md` (short), CLAUDE.md (keep separate per decision), and `htg_config.csv` as config source of truth (80+ keys). Repo workflow layout is directory-based: core/, content/, growth/, social/, affiliate/, monitoring/, email/, ui/, laravel/.

**Primary recommendation:** Expand or replace `docs/howto-genie-setup-guide.md` into the single doc (e.g. `docs/HOWTOGENIE.md`); add a workflow list (by directory or schedule), a config key reference derived from `htg_config.csv`, and an Archive section or pointer; deprecate other human-facing setup docs in place with short pointers to the single doc.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **CLAUDE.md stays separate.** Consolidated doc is the human-facing reference; CLAUDE.md remains the AI context file. No merging of full content; avoid duplication/conflict by design (e.g. single source for facts, or clear pointer from CLAUDE to the doc).

### Claude's Discretion
- **Doc location and name:** Planner chooses path (e.g. docs/HOWTOGENIE.md vs root), new file vs expanding docs/howto-genie-setup-guide.md, filename, and how to handle other existing MD (deprecate in place, short pointer, etc.).
- **Workflow list format:** Planner chooses grouping (by directory vs by schedule vs flat table), fields per workflow (name/schedule/purpose only vs + config keys vs + trigger type), hand-maintained vs generated (or hand now with optional generator deferred), and whether to list active-only vs everything that exists today.
- **Config key reference:** Planner chooses single table vs grouped by domain vs by workflow; source of truth (htg_config.csv vs doc); placeholder/secret guidance (none vs short note vs per-key); and whether to link to phase CONFIG-KEYS or keep all in the single doc.
- **Depth and how-to:** Planner chooses reference-only vs including "how to add a workflow" / "how to add a config key"; amount of setup/tutorial content; whether to include an Archive section (or pointer to archive/README); and schedule presentation (one section vs per-workflow vs both).

### Deferred Ideas (OUT OF SCOPE)
- Optional: script-generated workflow list or "CLAUDE derived from doc" could be future phases; not requested.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DOCS-01 | One detailed, centralized Markdown doc (e.g. docs/HOWTOGENIE.md) covers all workflows, UI, config keys, schedule, and archive | Standard Stack (Markdown); Architecture (single-doc pattern); existing sources listed in Code Context |
| DOCS-02 | Doc is the single authoritative reference; no parallel conflicting doc sets; updated when adding workflows or config | Architecture (deprecation strategy); Don't Hand-Roll (no duplicate doc sets); workflow list + config key reference as update surface |
</phase_requirements>

## Standard Stack

### Core
| Tool | Version | Purpose | Why Standard |
|------|---------|---------|--------------|
| Markdown | — | Single consolidated doc format | Universal, versionable, readable in repo and on GitHub |
| htg_config.csv | (existing) | Config key source of truth | Already used by Config Loader; doc references or embeds table |

### Supporting
| Item | Purpose | When to Use |
|------|---------|-------------|
| docs/ directory | Human-facing docs | Keep consolidated doc under docs/ for consistency with existing howto-genie-setup-guide.md, ORCHESTRATOR-README.md |
| Phase CONFIG-KEYS (e.g. 06-CONFIG-KEYS.md) | Phase-scoped key contracts | Optional link from consolidated doc; or fold key list into single doc |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Single Markdown file | Multi-file with index | Single file = one place to search; multi-file = easier partial edits; CONTEXT favors single authoritative reference |
| Hand-maintained workflow list | Generated from JSON | Hand now is in scope; generator deferred per CONTEXT |

**Installation:** None. No new dependencies for this phase.

## Architecture Patterns

### Recommended Doc Structure (minimal required sections)
```
# HowTo-Genie — [Title]
## Overview
## Workflows          ← workflow list (updatable when adding workflows)
## Config Keys        ← reference (updatable when adding config)
## Schedule           ← cron/schedule (one section and/or per-workflow)
## UI                 ← dashboards, Laravel Mission Control
## Archive            ← or pointer to archive/README
[Optional: How to add a workflow / How to add a config key]
```

### Pattern 1: Single authoritative doc + pointers
**What:** One doc holds all reference content; other MD files (e.g. ORCHESTRATOR-README.md, root howto-genie-setup-guide.md if moved) contain 1–2 lines pointing to the single doc.  
**When to use:** To satisfy "no parallel conflicting doc sets."  
**Example:**
```markdown
<!-- In docs/ORCHESTRATOR-README.md or similar -->
# Orchestrator
See **[HowTo-Genie reference](HOWTOGENIE.md#workflows)** for workflow list and schedule.
```

### Pattern 2: Config key reference from CSV
**What:** Config key reference in the doc is either (a) a table derived from `htg_config.csv` (copy or script) or (b) a short note that "all keys live in htg_config.csv" plus a grouped/summary table. Source of truth remains the CSV; doc is the human reference.  
**When to use:** DOCS-02 "updated when adding workflows or config" — adding a key = add row to CSV + optional doc table update.

### Anti-Patterns to Avoid
- **Duplicate long-form content in CLAUDE.md and the consolidated doc:** Decision locks CLAUDE.md separate; use a single source for facts or a clear pointer from CLAUDE to the doc (e.g. "Workflow list: see docs/HOWTOGENIE.md#workflows").
- **Leaving multiple competing "main" setup docs:** Results in parallel conflicting doc sets; deprecate in place with pointers or replace by redirecting all links to the single doc.
- **Documenting workflow paths that don't match repo layout:** Repo uses core/, content/, growth/, social/, affiliate/, monitoring/, email/, ui/, laravel/ — doc workflow list should reflect actual paths (e.g. core/08_Orchestrator_v3.json) not old root-level JSON names from CLAUDE.md.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|--------------|-----|
| "Single source" for prose | Duplicate CLAUDE.md into the doc | One human doc + CLAUDE points to it or summarizes | Locked decision: CLAUDE stays separate |
| Config key list | New config format or DB | htg_config.csv + table in doc (or link) | CSV is already source of truth; doc is reference |
| Workflow discovery | Custom doc generator (this phase) | Hand-maintained workflow list in the doc | Deferred; hand-maintained is acceptable |

**Key insight:** The phase is consolidation and structure, not tooling. Avoid building generators or new config systems; use existing CSV and Markdown.

## Common Pitfalls

### Pitfall 1: Outdated workflow paths in doc
**What goes wrong:** Doc lists workflow filenames or locations that don't match the repo (e.g. root-level "HowTo-Genie v2.0 — Master Orchestrator.json" when actual file is core/08_Orchestrator_v3.json).  
**Why it happens:** CLAUDE.md and setup guide were written for an older layout.  
**How to avoid:** Audit current repo: list workflow JSON files under core/, content/, growth/, social/, affiliate/, monitoring/, email/ and optionally archive/; build workflow list from that.  
**Warning signs:** Any path in the doc that doesn't exist under the repo tree.

### Pitfall 2: Two "main" setup docs
**What goes wrong:** howto-genie-setup-guide.md and the new consolidated doc both claim to be the setup reference; they drift and conflict.  
**Why it happens:** Expanding in place without deprecating, or creating new file without redirecting.  
**How to avoid:** Either (a) make the consolidated doc the only setup doc (rename/expand howto-genie-setup-guide.md into it and remove or shorten the old one) or (b) keep one file as the single doc and put a 1-line pointer in the other.  
**Warning signs:** Same topics covered in two different files with different wording.

### Pitfall 3: Config key reference drifts from htg_config.csv
**What goes wrong:** Doc lists keys that were removed or omits new keys; readers add keys to the doc but not to the CSV (or vice versa).  
**Why it happens:** Doc and CSV maintained separately without a single source of truth.  
**How to avoid:** Treat htg_config.csv as source of truth; doc table is either generated from it (if a small script is added later) or explicitly "reference only — add new keys to htg_config.csv and optionally add a row here."  
**Warning signs:** Keys in doc that aren't in CSV; keys in CSV missing from doc.

### Pitfall 4: CLAUDE.md and consolidated doc contradict
**What goes wrong:** Workflow list or schedule in CLAUDE.md differs from the consolidated doc; AI and humans get different answers.  
**Why it happens:** Copy-paste or independent updates.  
**How to avoid:** Per decision: single source for facts or clear pointer from CLAUDE to the doc. Prefer CLAUDE.md pointing to docs/HOWTOGENIE.md for workflow list and config reference so one place (the doc) is updated.  
**Warning signs:** Different workflow counts or schedule tables in the two files.

## Code Examples

### Required sections checklist (for verification)
The consolidated doc must include at least these conceptual sections to satisfy DOCS-01:
- Workflows (list or table)
- UI (dashboards, Laravel)
- Config keys (reference table or pointer to CSV)
- Schedule (global and/or per-workflow)
- Archive (section or pointer to archive/README)

### Example workflow list snippet (by directory)
```markdown
## Workflows
| Directory   | Workflow / File | Schedule / Trigger | Purpose |
|------------|-----------------|--------------------|---------|
| core/      | 08_Orchestrator_v3.json | 8 AM daily | Main 8-agent pipeline |
| core/      | 01_Config_Loader.json  | (sub-workflow) | Load htg_config.csv |
| content/   | ... | ... | ... |
```
Planner may choose by schedule, flat table, or add columns (e.g. config keys used).

### Example config key reference snippet
```markdown
## Config Keys
Source of truth: `htg_config.csv`. Key reference (summary):
| Key | Description |
|-----|-------------|
| GOOGLE_SHEET_ID | Spreadsheet ID for Content Log, etc. |
| WORDPRESS_URL | Blog base URL |
| CONTENT_LOG_TAB | Sheet tab name for published posts |
| ... | (see htg_config.csv for full list) |
```

## State of the Art

| Old Approach | Current Approach | Impact |
|--------------|------------------|--------|
| Multiple setup/readme files | Single authoritative doc | DOCS-02: no parallel conflicting doc sets |
| Config in workflow JSON / env | htg_config.csv + Config Loader | Doc references CSV; no hand-roll of config system |
| Workflow list in CLAUDE.md only | Consolidated doc as human reference; CLAUDE can point to it | Single place to update when adding workflows |

**Deprecated/outdated:** Root-level "HowTo-Genie vX.X — Name.json" paths in CLAUDE.md — repo has moved to directory-based layout (core/, content/, growth/, etc.). Consolidated doc should use current paths.

## Open Questions

1. **Expand howto-genie-setup-guide.md vs new file**
   - What we know: CONTEXT says planner chooses (expand vs new).
   - What's unclear: Whether renaming to HOWTOGENIE.md and expanding causes broken external links.
   - Recommendation: Prefer expanding and renaming to docs/HOWTOGENIE.md; add a 1-line redirect at the top of the old filename if it's kept for compatibility, or replace in place.

2. **Archive section depth**
   - What we know: Phase 8 will document "what lives where" and may add archive/README.
   - What's unclear: Whether Phase 7 doc should reserve a short "Archive" section with placeholder or only a pointer.
   - Recommendation: Include an "Archive" section (or subsection) with one sentence + pointer to archive/ and note that archive/README is added in Phase 8.

## Validation Architecture

`workflow.nyquist_validation` is true in `.planning/config.json`. Phase 7 is documentation-only; no application test framework applies. Verification is structural and manual.

### Test Framework
| Property | Value |
|----------|--------|
| Framework | Structural check (required sections) + manual UAT |
| Config file | None |
| Quick run command | `grep -E '^## (Workflows|Config Keys|Schedule|UI|Archive)' docs/HOWTOGENIE.md` (or actual doc path) |
| Full suite command | Same + manual: "Doc is single authoritative reference; no conflicting setup docs" |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| DOCS-01 | One consolidated doc covers workflows, UI, config, schedule, archive | structural | `test -f docs/HOWTOGENIE.md && grep -qE '^## (Workflows|Config Keys|Schedule|UI|Archive)' docs/HOWTOGENIE.md` (adjust path/section names to plan) | ❌ Wave 0 |
| DOCS-02 | Single authoritative reference; no parallel conflicting doc sets | manual | N/A — verify no other "main" setup doc competes; workflow list + config reference present for updates | — |

### Sampling Rate
- **Per task commit:** Quick structural grep (or script) if planner adds a small verification script.
- **Per wave merge:** Same + manual check for DOCS-02.
- **Phase gate:** Doc exists, required sections present, pointer/deprecation strategy applied to other docs; UAT confirms single reference.

### Wave 0 Gaps
- [ ] Verification script or documented grep commands for required sections (path and section names depend on planner choice).
- [ ] No pytest/jest needed; structural + manual verification only.

## Sources

### Primary (HIGH confidence)
- .planning/phases/07-docs-consolidation/07-CONTEXT.md — decisions, discretion, code context (existing docs, repo layout)
- .planning/REQUIREMENTS.md — DOCS-01, DOCS-02
- Repo layout and docs/ content — howto-genie-setup-guide.md, ORCHESTRATOR-README.md, htg_config.csv

### Secondary (MEDIUM confidence)
- CLAUDE.md — repo structure, workflow list (partly outdated paths); used to identify anti-patterns and drift risk
- Phase 06 CONFIG-KEYS pattern — reference for config key table format

### Tertiary
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Markdown + existing CSV; no new stack.
- Architecture: HIGH — Single-doc pattern and deprecation strategy are standard; CONTEXT and repo layout are known.
- Pitfalls: HIGH — Identified from CONTEXT (conflicting docs, CLAUDE separation) and repo state (path drift, CSV as source of truth).

**Research date:** 2026-03-13  
**Valid until:** 30 days (stable doc process)
