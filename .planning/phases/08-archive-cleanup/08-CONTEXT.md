# Phase 8: Archive & Cleanup — Context

**Gathered:** 2026-03-13  
**Status:** Ready for planning

<domain>
## Phase Boundary

Unused or superseded workflows, UI, and files are identified and either moved to `archive/` (with README) or deleted. Every Execute Workflow caller of any moved workflow is listed and updated so no broken workflow IDs remain. Archive location and "what lives where" are documented in the consolidated docs (ARCH-01, ARCH-02, ARCH-03).
</domain>

<decisions>
## Implementation Decisions

### What counts as unused/superseded
- **Workflows:** "In use" = listed in the HOWTOGENIE workflow table OR called by an active workflow (via Execute Workflow). Everything else is an archive candidate.
- **Deprecated docs, binary files in files/, UI unused definition:** Planner's discretion — choose a clear, consistent rule and document it (e.g. deprecated docs: leave in place vs move to archive/docs; binaries: leave in files/ vs archive; UI: only archive if not in doc and not referenced).

### Archive vs delete
- **Never delete** workflow or UI source files — archive only.
- **Duplicates:** Keep one copy, archive the other; do not delete.
- **Placeholder/empty files and whether to state "prefer archive; delete only when X" in CONTEXT:** Planner's discretion.

### Execute Workflow callers when moving a workflow
- **Discovery, update strategy (point to replacement vs remove node), and caller-audit timing:** Planner's discretion. Requirement: before moving any workflow, list every caller and update so no active workflow's Execute Workflow node points at an archived workflow ID. How to discover (grep workflowId), whether to produce a full caller audit first, and how to update (replace ID vs remove node when no replacement) are left to the plan.

### Documentation of "what lives where"
- **archive/README.md content, HOWTOGENIE Archive section detail, flat vs grouped list, and whether doc updates are part of Phase 8:** Planner's discretion. Phase 7 already reserved "See archive/README.md (added in Phase 8)" in HOWTOGENIE; Phase 8 must add that README and document where things live.

### Claude's Discretion
- Deprecated docs: move to archive vs leave in place with pointer.
- Binary/reference files in files/: archive vs leave.
- UI "unused": strict (doc + referenced) vs include old variants.
- Placeholder/empty files: archive vs delete.
- Whether to state explicit "prefer archive; no delete for workflow/UI" rule in CONTEXT.
- Execute Workflow: discovery method, update strategy, audit-before-move vs per-workflow.
- archive/README: depth (list + reason vs minimal); HOWTOGENIE summary; flat vs grouped; doc updates in same phase.
</decisions>

<code_context>
## Existing Code Insights

### Current state
- **archive/** already exists with 11 workflow JSONs: old orchestrators (Master v2.0, v2.2, v3.0, Agentic Team, Content Writer, etc.), Topic Research v1, Affiliate Research v1, Social Formatter PartA, Queue Processor PartB, Auto Video Creation. No archive/README.md yet.
- **Execute Workflow:** Used in many active workflows; nodes reference other workflows by `workflowId` in JSON. Callers live in core/, content/, growth/, social/, affiliate/, monitoring/, email/. Archive workflows only reference each other or are uncalled.
- **docs/HOWTOGENIE.md:** Has an Archive section: "Superseded or unused workflows and assets are in archive/. See archive/README.md (added in Phase 8) for what lives where."

### Reusable patterns
- Grep for `"type": "n8n-nodes-base.executeWorkflow"` and `workflowId` in non-archive JSON to find caller → callee edges.
- Active workflow list and directory layout are in HOWTOGENIE; use as source of truth for "in use."

### Integration points
- Any new or moved file under archive/ must be reflected in archive/README.md. HOWTOGENIE remains the single reference; archive/README is the detailed "what lives where" for archive only.
</code_context>

<specifics>
## Specific Ideas

- User chose strict "in use" for workflows (table or called); archive-only for workflow/UI; duplicate handling = keep one, archive the other. All other choices delegated to planner.
</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.
</deferred>

---
*Phase: 08-archive-cleanup*  
*Context gathered: 2026-03-13*
