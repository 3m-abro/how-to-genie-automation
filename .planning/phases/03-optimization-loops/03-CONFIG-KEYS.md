# Phase 3 — Config Keys and Sheets Tabs

Config is read by `core/01_Config_Loader.json` (Execute Workflow at workflow start). Keys live in htg_config (or equivalent Key/Value store). Sheet/tab names are config-driven so one document can serve multiple environments.

---

## A/B Testing (Plan 01 — GROW-03)

| Key | Purpose | Example |
|-----|---------|---------|
| `A_B_TESTING_ENABLED` | Gate: when false, workflow exits without reading Content Log or writing A/B tab | `true` / `false` |
| `AB_TESTS_TAB` | Google Sheets tab name for A/B variant log | `AB Tests` or `AB Tests Active` |
| `CONTENT_LOG_TAB` | Tab for published post log (yesterday filter) | `Content Log` |
| `GOOGLE_SHEET_ID` | Spreadsheet ID (same doc for Content Log and A/B tab) | from sheet URL |
| `WORDPRESS_URL` | Base URL for WP REST (no trailing slash) | `https://your-blog.com` |
| `CONTENT_DAY_TIMEZONE` | Timezone for “yesterday” (date in Content Log) | `UTC` / `America/New_York` |
| `TIMEZONE` | Fallback if CONTENT_DAY_TIMEZONE not set | `UTC` |

**Required Sheets tabs:** Content Log (existing), **AB Tests** or **AB Tests Active** (Phase 3 A/B log).

---

## Viral Amplifier (Plan 02 — GROW-04)

| Key | Purpose | Example |
|-----|---------|---------|
| `VIRAL_AMPLIFIER_ENABLED` | Gate: when false, workflow exits without GA4 or Viral tab write | `true` / `false` |
| `VIRAL_AMPLIFIER_TAB` | Google Sheets tab for viral rows and promotion_status | `Viral Amplifier` |
| `VIRAL_VIEWS_7D_MIN` | Minimum 7-day views to consider viral | `5000` |
| `VIRAL_ENGAGEMENT_MIN` | Minimum engagement rate (e.g. 0.08 = 8%) | `0.08` |
| `GA4_PROPERTY_ID` | GA4 property ID for runReport | numeric property ID |
| `GOOGLE_ANALYTICS_TOKEN` | Bearer token for GA4 Data API | OAuth2 or service account token |

**Required Sheets tabs:** **Viral Amplifier** (Phase 3 viral log and promotion_status).

---

## Shared (both workflows)

- `GOOGLE_SHEET_ID` / `SPREADSHEET_ID`: same spreadsheet for Content Log, A/B tab, and Viral Amplifier tab.
- Config Loader runs first; all sheet IDs and tab names come from config (no hardcoded tab names in Phase 3).
