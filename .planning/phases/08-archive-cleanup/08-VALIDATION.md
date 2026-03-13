---
phase: 08
slug: archive-cleanup
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-13
---

# Phase 08 — Validation Strategy

> Per-phase validation contract for archive/cleanup: script + checklist (no unit-test framework).

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Script + checklist (no Jest/pytest for this phase) |
| **Config file** | None |
| **Quick run command** | `scripts/verify-archive-refs.sh` (or equivalent Node script) |
| **Full gate** | Script + checklist: archive/README exists; HOWTOGENIE Archive section present and correct |
| **Estimated runtime** | ~5–15 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick script (no active-dir JSON references archived workflow ids)
- **After every plan wave:** Run script + checklist (README, HOWTOGENIE Archive)
- **Before `$gsd-verify-work`:** Full gate must pass
- **Max feedback latency:** 15 seconds

---

## Verification Commands (from RESEARCH)

1. **Archive contents and README**
   - Assert `archive/README.md` exists.
   - Assert every `.json` (and any other archived asset) under `archive/` is listed or described in `archive/README.md`.

2. **No broken Execute Workflow references**
   - Collect root `id` from every workflow JSON in `archive/`.
   - For each such id, grep in `core/`, `content/`, `growth/`, `social/`, `affiliate/`, `monitoring/`, `email/` for that id (in any `.json`).
   - Assert no matches.

3. **Docs reflect archive**
   - Assert `docs/HOWTOGENIE.md` contains an "Archive" section that points to `archive/README.md`.
   - Optional: HOWTOGENIE Workflows table does not list any archived workflow.

---

## Per-Requirement Verification Map

| Req ID   | Behavior | Verification |
|----------|----------|---------------|
| ARCH-01  | Unused/superseded items in archive/ with README | Script/checklist: archive/ exists; README exists and lists/describes contents. |
| ARCH-02  | No broken Execute Workflow refs | Script: no active-dir JSON contains root id of any workflow in archive/. |
| ARCH-03  | Archive and "what lives where" in consolidated docs | Checklist: HOWTOGENIE Archive section present and points to archive/README.md. |

---

## Wave 0 Requirements

- [ ] Add script (e.g. `scripts/verify-archive-refs.sh` or Node) that: collect archive workflow ids; grep in active dirs; exit 1 if any match.
- [ ] Checklist or one-line grep for README existence and HOWTOGENIE Archive section.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|--------------------|
| archive/README lists/describes every archived file | ARCH-01 | Content check | Confirm each file under archive/ appears in README (group or flat). |
| HOWTOGENIE Archive section wording | ARCH-03 | Doc wording | Confirm section points to archive/README.md and is accurate. |

---

## Validation Sign-Off

- [ ] Script covers ARCH-02 (no broken refs)
- [ ] Checklist covers ARCH-01 (README) and ARCH-03 (HOWTOGENIE)
- [ ] Wave 0 script and checklist created
- [ ] `nyquist_compliant: true` set in frontmatter when Wave 0 complete

**Approval:** pending
