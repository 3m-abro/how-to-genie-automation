---
phase: 01-pipeline-reliability
plan: 02
subsystem: pipeline
tags: n8n, ollama, executeWorkflow, qc, json-contracts

# Dependency graph
requires: []
provides:
  - Agents 1–5 call Ollama Agent (Central) sub-workflow with per-agent temperature
  - Parse & Validate QC Code node with parse_error fallback
  - QC Approved? branches on parsed boolean $json.approved
affects: 01-03, 01-04, 01-05

# Tech tracking
tech-stack:
  added: []
  patterns: executeWorkflow → Central for all LLM; Parse node + boolean IF for QC

key-files:
  created: []
  modified: [core/08_Orchestrator_v3.json]

key-decisions:
  - "All Agents 1–5 use same Central workflow ID (18GE0djgSQJHhj8C) with workflowInputs: model, user_message, system_message, temperature, num_predict"
  - "QC path uses parsed JSON and boolean .approved; fallback object includes parse_error: true"

patterns-established:
  - "LLM agents: executeWorkflow to Ollama Agent (Central) with waitForSubWorkflow true"
  - "QC gate: Code node parses LLM output → IF on $json.approved === true"

requirements-completed: [PIPE-02]

# Metrics
duration: 15
completed: "2026-03-12"
---

# Phase 01 Plan 02: Agent migration & QC parse Summary

**Agents 1–5 migrated from direct Ollama httpRequest to executeWorkflow calling Ollama Agent (Central); QC Approved? branches on parsed boolean with Parse & Validate QC fallback.**

## Performance

- **Duration:** ~15 min
- **Tasks:** 2
- **Files modified:** 1 (core/08_Orchestrator_v3.json)

## Accomplishments

- Agents 1–5 replaced with executeWorkflow nodes calling Ollama Agent (Central); per-agent temperatures 0.7, 0.8, 0.9, 0.4, 0.3; num_predict 4096.
- Agent 0 fixed to use `model` (not `ollama_model`) and num_predict 4096.
- Parse & Validate QC Code node added after Agent 5; parses QC JSON, fallback `{ approved: false, parse_error: true }`.
- QC Approved? IF node now uses boolean condition `$json.approved === true` instead of string-contains on raw content.

## Task Commits

1. **Task 1: Replace Agents 1–5 httpRequest with executeWorkflow to Ollama Central** — `d0fc2c2` (feat)
2. **Task 2: Add Parse & Validate QC node and fix QC Approved? condition** — Implemented in this run; Parse & Validate QC and QC Approved? boolean condition are present in `core/08_Orchestrator_v3.json` (same file was later modified by 01-03; no separate Task 2 commit in history).

## Files Created/Modified

- `core/08_Orchestrator_v3.json` — Agents 1–5 → executeWorkflow (Central); Agent 0 → model + num_predict; Parse & Validate QC node; QC Approved? on $json.approved.

## Decisions Made

- Use workflow ID 18GE0djgSQJHhj8C (Ollama Agent Central) from existing Agent 0 reference.
- Central interface: model, user_message, system_message, temperature, num_predict (per RESEARCH.md).

## Deviations from Plan

None — plan executed as written. Task 2 edits were applied; Parse & Validate QC and boolean QC Approved? are in the workflow.

## Issues Encountered

None.

## Next Phase Readiness

- All LLM agents go through Central; QC path uses parsed JSON. Ready for 01-03 (WordPress retry, Publish Succeeded? gate).

## Self-Check: PASSED

- SUMMARY exists: .planning/phases/01-pipeline-reliability/01-02-SUMMARY.md
- Task 1 commit d0fc2c2 present in history
- Parse & Validate QC and QC Approved? boolean present in core/08_Orchestrator_v3.json

---
*Phase: 01-pipeline-reliability*
*Completed: 2026-03-12*
