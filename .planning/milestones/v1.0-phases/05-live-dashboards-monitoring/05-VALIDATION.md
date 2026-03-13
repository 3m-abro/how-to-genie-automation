---
phase: 5
slug: live-dashboards-monitoring
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-12
---

# Phase 5 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit (Laravel default) |
| **Config file** | phpunit.xml (create if Laravel app is in repo) |
| **Quick run command** | `php artisan test --filter=Dashboard` or `./vendor/bin/phpunit --filter=Dashboard` |
| **Full suite command** | `php artisan test` or `./vendor/bin/phpunit` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=<relevant suite>`
- **After every plan wave:** Run `php artisan test`
- **Before `$gsd-verify-work`:** Full suite must be green + manual UAT (open dashboards; trigger failure; receive weekly email)
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| (fill by planner) | 01 | 1 | DASH-01 | Feature/Integration | `php artisan test --filter=RevenueDashboard` | ❌ W0 | ⬜ pending |
| (fill by planner) | 02 | 1 | DASH-02 | Feature/Integration | `php artisan test --filter=MissionControl` | ❌ W0 | ⬜ pending |
| (fill by planner) | 03 | 2 | DASH-03 | Feature/Integration | `php artisan test --filter=N8nFailureMonitor` | ❌ W0 | ⬜ pending |
| (fill by planner) | 04 | 2 | DASH-04 | Feature/Integration | `php artisan test --filter=WeeklySummary` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Dashboard/RevenueDashboardApiTest.php` — covers DASH-01 (mock Sheets or in-memory)
- [ ] `tests/Feature/Dashboard/MissionControlApiTest.php` — covers DASH-02 (mock n8n HTTP)
- [ ] `tests/Feature/Commands/N8nFailureMonitorCommandTest.php` — covers DASH-03 (mock n8n + Telegram)
- [ ] `tests/Feature/Commands/WeeklySummaryCommandTest.php` — covers DASH-04 (mock Sheets + Mail)
- [ ] Laravel test bootstrap and phpunit.xml — if app is in repo and not yet present
- [ ] Framework: Laravel’s default PHPUnit

*If Laravel app is external: "Existing infrastructure in external app; Phase 5 adds endpoints and tests there."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Dashboard UI shows live data in browser | DASH-01, DASH-02 | E2E / visual | Open Revenue and Mission Control; confirm no hardcoded sample data |
| Telegram alert received on workflow failure | DASH-03 | External delivery | Trigger failure or mock; check Telegram within 10 min |
| Weekly summary email in inbox | DASH-04 | External delivery | Run weekly command or wait for Sunday; check inbox |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
