# Phase 08: Archive & Cleanup — Research

**Researched:** 2026-03-13  
**Domain:** Repo housekeeping, n8n workflow references, documentation consistency  
**Confidence:** HIGH

## Summary

Phase 8 is about safely archiving unused/superseded workflows and files, ensuring no active workflow’s Execute Workflow node points at an archived workflow, and documenting “what lives where” in `archive/` and in the consolidated docs. The repo already has an `archive/` with 11 workflow JSONs and no `archive/README.md`. HOWTOGENIE.md already has an Archive section that points to “archive/README.md (added in Phase 8)”. Active workflows live under `core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, `email/` and reference other workflows by root-level `id` inside Execute Workflow nodes. Discovery of callers is done by extracting each workflow’s root `id` from its JSON and grepping for that id in non-archive workflow JSON (in `workflowId` parameters). Never delete workflow or UI source files—archive only; duplicates: keep one, archive the other.

**Primary recommendation:** Before moving any workflow to archive, run a caller audit (grep workflow root id in active dirs); update or remove every Execute Workflow node that pointed at it; then add/update `archive/README.md` and ensure HOWTOGENIE reflects the archive.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **What counts as unused/superseded:** Workflows: "In use" = listed in HOWTOGENIE workflow table OR called by an active workflow (via Execute Workflow). Everything else is an archive candidate. Deprecated docs, binary files in files/, UI unused: Planner's discretion — choose a clear, consistent rule and document it.
- **Archive vs delete:** Never delete workflow or UI source files — archive only. Duplicates: keep one copy, archive the other; do not delete. Placeholder/empty files: Planner's discretion.
- **Execute Workflow callers:** Before moving any workflow, list every caller and update so no active workflow's Execute Workflow node points at an archived workflow ID. Discovery method, update strategy (point to replacement vs remove node), and caller-audit timing: Planner's discretion.
- **Documentation of "what lives where":** archive/README.md content, HOWTOGENIE Archive section detail, flat vs grouped list, and whether doc updates are part of Phase 8: Planner's discretion. Phase 7 already reserved "See archive/README.md (added in Phase 8)" in HOWTOGENIE; Phase 8 must add that README and document where things live.

### Claude's Discretion
- Deprecated docs: move to archive vs leave in place with pointer.
- Binary/reference files in files/: archive vs leave.
- UI "unused": strict (doc + referenced) vs include old variants.
- Placeholder/empty files: archive vs delete.
- Whether to state explicit "prefer archive; no delete for workflow/UI" rule in CONTEXT.
- Execute Workflow: discovery method, update strategy, audit-before-move vs per-workflow.
- archive/README: depth (list + reason vs minimal); HOWTOGENIE summary; flat vs grouped; doc updates in same phase.

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID     | Description                                                                                  | Research Support                                                                 |
|--------|-----------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| ARCH-01 | Unused or superseded workflows, UI, and other files identified and moved to archive/ or deleted | Source of truth: HOWTOGENIE workflow table + grep for Execute Workflow callees; active dirs: core/, content/, growth/, social/, affiliate/, monitoring/, email/. Archive only (no delete for workflows/UI). |
| ARCH-02 | Before moving any workflow, Execute Workflow callers listed and updated so no broken workflow IDs remain | Discovery: extract root `id` from workflow JSON; grep that id in non-archive JSON under active dirs for `workflowId` (literal or in `"value": "..."`). Update or remove node before move. |
| ARCH-03 | Archive location and "what lives where" documented in consolidated docs                          | Add archive/README.md; HOWTOGENIE already has Archive section pointing to it; Phase 8 fills README and ensures doc reflects archive. |
</phase_requirements>

---

## Standard Stack

### Core
| Tool / approach | Purpose | Why standard |
|-----------------|----------|---------------|
| Grep (rg)       | Find Execute Workflow nodes and workflowId references | No new deps; repo already uses JSON workflow files; pattern is `"type": "n8n-nodes-base.executeWorkflow"` and `workflowId`. |
| Shell / Node one-off script | Extract root `id` from each workflow JSON, build caller map | Small script; run once per audit. No need for n8n API. |
| Markdown       | archive/README.md, doc updates | Single reference (HOWTOGENIE) already Markdown; archive README same. |

### Supporting
| Item | Purpose | When to use |
|------|---------|-------------|
| HOWTOGENIE workflow table | Canonical list of active workflows and paths | Decide "in use" vs archive candidate; avoid archiving anything in table or called by table. |
| htg_config.csv | CONTENT_CALENDAR_WF_ID, TOPIC_RESEARCH_WF_ID, etc. | Config may reference workflow IDs; if a moved workflow was referenced by config, update config. |

### Alternatives considered
| Instead of | Could use | Tradeoff |
|------------|-----------|----------|
| Grep over JSON | jq to walk nodes and find workflowId | jq is fine; grep is sufficient and already in use; both work. |
| Manual caller list | n8n API to list workflows and subworkflow calls | API needs running n8n and creds; repo is file-based; file grep is self-contained. |

**Installation:** None required. Use system grep (or ripgrep), and optional Node/JavaScript to parse JSON for root `id` and emit a caller matrix.

---

## Architecture Patterns

### Recommended flow
1. **List active workflows** — From docs/HOWTOGENIE.md Workflows table (Directory + Workflow file). Add any workflow that is called via Execute Workflow by that set (transitive closure).
2. **List archive candidates** — Every workflow file under `core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, `email/` not in the active set. Plus deprecated docs/UI per planner’s rule.
3. **Per archive candidate (workflow):**  
   a. Read workflow JSON; get root-level `"id": "..."` if present (n8n exports often put it at end of file).  
   b. Grep that id in all JSON under active dirs (exclude archive/). Match in `workflowId` or `"value": "<id>"` (for `__rl` style).  
   c. If callers found: update those nodes (point to replacement workflow id or remove node); then move workflow to archive/.  
   d. If no callers: move to archive/.
4. **archive/README.md** — Add file; list what’s in archive (grouped or flat per planner); brief reason (e.g. “Superseded by 08_Orchestrator_v3”).
5. **Docs** — Ensure HOWTOGENIE Archive section and any “what lives where” wording stay correct; no need to list every archive file in HOWTOGENIE (already says see archive/README.md).

### Pattern: Caller discovery
**What:** Find every workflow file that contains an Execute Workflow node whose `workflowId` equals a given workflow’s root id.  
**When:** Before moving that workflow to archive.  
**Example (conceptual):**
```bash
# 1) Get root id of workflow (e.g. from end of JSON)
ID=$(node -e "const w=require('./path/to/workflow.json'); console.log(w.id || '')")

# 2) Search in active dirs only (no archive)
grep -r --include='*.json' -l "$ID" core/ content/ growth/ social/ affiliate/ monitoring/ email/
```
n8n `workflowId` in JSON can be:
- Direct: `"workflowId": "GnSSiZ83onbD05TZ"`
- Reference: `"workflowId": { "__rl": true, "value": "UUID_OR_ID", "mode": "id" }`
So the id string appears as literal or inside `"value": "..."`. Grep for the raw id in those files is enough.

### Anti-patterns
- **Archiving first, updating callers later:** Breaks ARCH-02; always update callers (or remove node) before moving.
- **Deleting workflow/UI files:** User rule: archive only for workflows and UI.
- **Relying only on “in table” for “in use”:** Workflows not in the table but called by an in-table workflow are still in use; need transitive closure or at least one pass of “who calls whom.”

---

## Don't Hand-Roll

| Problem | Don't build | Use instead | Why |
|---------|-------------|-------------|-----|
| “Which workflows call X?” | Custom n8n API client | Grep for workflow root id in active-dir JSON | Repo is file-based; no runtime; grep is reliable and simple. |
| Full graph of who calls whom | Heavy parser | One-time script: for each workflow, extract id, grep; optional small JSON parse for workflowId only | Enough for “list callers of W” and to avoid broken refs. |
| Versioned archive with diffs | Custom tooling | Git history + archive/README with short reason | Git already versions; README explains why things are archived. |

**Key insight:** The requirement is “no broken workflow IDs,” not “full dependency graph.” So per-workflow caller list + update-before-move is sufficient.

---

## Common Pitfalls

### Pitfall 1: workflowId in multiple forms
**What goes wrong:** Grep only for `"workflowId": "id"` and miss `"value": "id"` in `__rl` objects.  
**Why:** n8n exports can use `__rl` with value for credential/workflow references.  
**How to avoid:** Search for the raw id string (e.g. `GnSSiZ83onbD05TZ`) in the file; both forms contain it.  
**Warning signs:** “No callers found” but orchestrator (or another workflow) actually calls it.

### Pitfall 2: Root id missing in some exports
**What goes wrong:** Some workflow JSONs don’t have a root-level `"id"`.  
**Why:** Older or manually edited exports may omit it.  
**How to avoid:** If no root `id`, treat “caller discovery by id” as N/A for that file; identify by name/path in README and ensure no active workflow references it by name in a way that could break (n8n uses id at runtime). For repo-level verification, “no active file references an id that exists only in archive” still applies when ids are present.  
**Warning signs:** Script fails or skips workflows; double-check workflows that are called (e.g. Config Loader) have their id in callers.

### Pitfall 3: archive/README out of sync
**What goes wrong:** New items moved to archive but README not updated.  
**Why:** Phase 8 adds README once; later phases might add more archived items.  
**How to avoid:** Phase 8 defines the rule: “Any new or moved file under archive/ must be reflected in archive/README.md” (from CONTEXT). Plan a short checklist or single place that lists archive contents so README stays the source.  
**Warning signs:** README missing entries that exist in archive/.

### Pitfall 4: Config-driven workflow IDs
**What goes wrong:** htg_config.csv (or similar) has keys like CONTENT_CALENDAR_WF_ID, TOPIC_RESEARCH_WF_ID. If a workflow we archive was that target, config points at a non-existent workflow.  
**How to avoid:** When updating callers, include “config keys that hold workflow IDs” and update them if they pointed at the archived workflow.  
**Warning signs:** HOWTOGENIE mentions CONTENT_CALENDAR_WF_ID, TOPIC_RESEARCH_WF_ID; any archived workflow might have been that target.

---

## Code Examples

### Extract root workflow id from n8n JSON (Node)
```javascript
// workflow JSON has optional root "id" (short or UUID)
const fs = require('fs');
const w = JSON.parse(fs.readFileSync('path/to/workflow.json', 'utf8'));
const id = w.id || null;  // e.g. "GnSSiZ83onbD05TZ"
```

### Find files that reference a workflow id (shell)
```bash
# Active dirs only; exclude archive
for dir in core content growth social affiliate monitoring email; do
  grep -r --include='*.json' -l "YOUR_WORKFLOW_ID" "$dir/" 2>/dev/null || true
done
```

### Check workflowId shape in repo
Execute Workflow nodes use:
- `"parameters": { "workflowId": { "__rl": true, "value": "<id>", "mode": "id" }, ... }`  
- or `"workflowId": "<id>"` (string).  
In both cases the id string appears in the file; grep for the id is sufficient.

---

## State of the Art

| Old approach | Current approach | Impact |
|--------------|------------------|--------|
| Ad-hoc “some old files in root” | Structured archive/ + README + single doc reference | Clear “what lives where”; HOWTOGENIE stays the single reference. |
| No caller check | Caller audit before move (grep by id) | ARCH-02 satisfied; no broken Execute Workflow refs. |

**Deprecated/outdated:** N/A for this phase (housekeeping, not library versions).

---

## Open Questions

1. **Config workflow ID keys**  
   - Known: HOWTOGENIE lists CONTENT_CALENDAR_WF_ID, TOPIC_RESEARCH_WF_ID.  
   - Unclear: Whether any archived workflow is the current value of those keys in htg_config.csv.  
   - Recommendation: When building “callers,” include a step to check config keys that store workflow IDs; if they point at a workflow being archived, update config (or document that config is instance-specific and out of repo scope).

2. **Root id coverage**  
   - Known: Many workflow JSONs have root `"id"` at end of file.  
   - Unclear: Whether every active workflow has it (e.g. core/01_Config_Loader.json appears to have no root id in snippet).  
   - Recommendation: Script that extracts id: if missing, log and treat as “no id to search for”; rely on name/path in README and manual check that nothing in active dirs references that workflow by id.

---

## Validation Architecture

Phase 8 is repo housekeeping (moves, README, doc updates). Verification is by scripts and checklist rather than unit tests. Nyquist validation should assert: (1) archive contents and README, (2) no broken Execute Workflow references, (3) docs reflect archive.

### Test framework
| Property | Value |
|----------|--------|
| Framework | Script + checklist (no Jest/pytest for this phase) |
| Config file | None |
| Quick run | Script: verify no active workflow references an archived workflow id |
| Full gate | Run script + checklist (archive/README exists, HOWTOGENIE Archive section present and correct) |

### Verification commands (implement in 08-VALIDATION or script)

1. **Archive contents and README**
   - Assert `archive/README.md` exists.
   - Assert every `.json` (and any other archived asset) under `archive/` is listed or described in `archive/README.md` (e.g. by group or flat list).
   - Optional: Assert README mentions purpose (e.g. “Superseded by …”, “Unused”).

2. **No broken Execute Workflow references**
   - Collect root `id` from every workflow JSON in `archive/`.
   - For each such id, grep in `core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, `email/` for that id (in any `.json`).
   - Assert no matches: no active workflow file may reference an archived workflow’s id.
   - Edge case: if an archived workflow has no root `id`, skip it for this check; rely on README and manual review.

3. **Docs reflect archive**
   - Assert `docs/HOWTOGENIE.md` contains an “Archive” section that points to `archive/README.md` (e.g. “See archive/README.md (added in Phase 8)” or equivalent).
   - Assert HOWTOGENIE does not list any archived workflow in the main Workflows table (optional strict check: workflow table rows reference only files under active dirs, not under archive/).

### Phase requirements → verification map
| Req ID  | Behavior | Verification |
|---------|----------|----------------|
| ARCH-01 | Unused/superseded items in archive/ (or deleted per rule) with README | Script/checklist: archive/ exists; README exists and lists/describes contents. |
| ARCH-02 | No broken Execute Workflow refs | Script: no active-dir JSON contains root id of any workflow in archive/. |
| ARCH-03 | Archive and “what lives where” in consolidated docs | Checklist: HOWTOGENIE Archive section present and points to archive/README.md. |

### Wave 0 gaps
- Add a small script (e.g. `scripts/verify-archive-refs.sh` or Node) that implements “collect archive ids + grep in active dirs; exit 1 if any match.”
- Add checklist or automated checks for README existence and HOWTOGENIE Archive section (can be a one-line grep).

---

## Sources

### Primary (HIGH confidence)
- CONTEXT.md (08-archive-cleanup) — decisions, discretion, code context.
- REQUIREMENTS.md — ARCH-01, ARCH-02, ARCH-03.
- docs/HOWTOGENIE.md — workflow table, Archive section, active dirs.
- Repo grep for `executeWorkflow`, `workflowId`, root `"id"` in workflow JSON — patterns and file set.

### Secondary (MEDIUM confidence)
- CLAUDE.md — repo structure, workflow list, archive mention; some paths differ from current HOWTOGENIE (e.g. root-level JSON vs core/content/…).

### Tertiary
- None.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — grep + script + Markdown are standard and already used in context.
- Architecture: HIGH — caller discovery and update-before-move are defined; workflowId shapes verified in repo.
- Pitfalls: HIGH — workflowId variants and missing root id observed in repo.

**Research date:** 2026-03-13  
**Valid until:** 30 days (housekeeping process is stable).
