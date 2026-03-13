---
phase: 6
slug: affiliate-seo-feedback
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-03-13"
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Laravel PHPUnit (API/dashboard); no n8n workflow test runner in repo |
| **Config file** | laravel/phpunit.xml |
| **Quick run command** | `cd laravel && php artisan test --filter=MissionControlApi` |
| **Full suite command** | `cd laravel && php artisan test` |
| **Estimated runtime** | ~30–60 seconds |

---

## Sampling Rate

- **After every task commit:** Manual: run affected n8n workflow(s), inspect Sheets (and optionally WP).
- **After every plan wave:** Run Laravel full suite if Phase 6 adds/ touches API; otherwise manual UAT per requirement.
- **Before `$gsd-verify-work`:** All four requirements (AFF-01, AFF-02, SEO-01, SEO-02) verified via UAT.
- **Max feedback latency:** Manual; document run + sheet check per requirement.

---

## Per-Requirement Verification Map

| Req ID | Behavior | Test Type | How to Verify | Status |
|--------|----------|-----------|---------------|--------|
| AFF-01 | Registry populated with products across 5 niches | Manual / UAT | Run Affiliate Manager (or bootstrap); inspect registry tab; ≥1 product per niche. | ⬜ pending |
| AFF-02 | Manager runs and updates registry from Muncheye/CBEngine RSS | Manual / UAT | Trigger workflow on schedule or manually; confirm new/updated rows in registry tab. | ⬜ pending |
| SEO-01 | GA4 data feeds into topic selection | Manual / UAT | Run refresh-candidates writer; run orchestrator; confirm Agent 1 prompt or context includes refresh list. | ⬜ pending |
| SEO-02 | SEO Interlinking runs Sunday 3 AM and updates internal links | Manual / UAT | Run workflow (trigger or manual); confirm Interlink/Internal Linking log rows and optional WP post content updated. | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- No n8n workflow unit tests in repo; verification is manual and document-based.
- Optional: add 06-CONFIG-KEYS.md listing Phase 6 config keys and required tabs for htg_config.
- If Laravel exposes any Phase 6 API (e.g. refresh candidates read), add PHPUnit test for that endpoint.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Registry has ≥1 product per niche | AFF-01 | n8n + Sheets; no automated test | Run manager/bootstrap; open registry tab; check niche column. |
| Manager updates registry from RSS | AFF-02 | n8n + external RSS | Trigger manager; compare registry rows before/after. |
| Refresh candidates reach Agent 1 | SEO-01 | Orchestrator + Agent 1 context | Run refresh writer → orchestrator; inspect Agent 1 input for refresh_candidates. |
| SEO Interlinking writes recommendations / WP | SEO-02 | n8n + Sheets + optional WP | Run workflow; check Internal Linking Log tab and/or WP post content. |

---

## Validation Sign-Off

- [ ] All requirements have verify path (manual UAT)
- [ ] Sampling continuity: manual run + sheet inspection per requirement
- [ ] Wave 0: optional 06-CONFIG-KEYS.md; PHPUnit for any new Laravel Phase 6 API
- [ ] No watch-mode flags
- [ ] `nyquist_compliant: true` set in frontmatter when UAT steps are documented and run

**Approval:** pending
