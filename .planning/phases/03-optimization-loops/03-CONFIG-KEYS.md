# Phase 3: Config Keys

Keys consumed by Phase 3 workflows. Same config source as Phase 2 (Config Loader / htg_config).

## Viral Content Amplifier Engine & Viral Amplifier Queue

| Key | Purpose | Example / Default |
|-----|---------|-------------------|
| `VIRAL_AMPLIFIER_ENABLED` | Gate: when false, workflow exits without writing | `true` / `false` |
| `VIRAL_VIEWS_7D_MIN` | Minimum 7-day views for viral detection | `5000` |
| `VIRAL_ENGAGEMENT_MIN` | Minimum engagement rate (0–1) for viral detection | `0.08` |
| `VIRAL_AMPLIFIER_TAB` | Google Sheets tab for viral rows (append/read/update) | `Viral Amplifier` |
| `GOOGLE_SHEET_ID` | Spreadsheet ID (or `SPREADSHEET_ID`) | (sheet ID) |
| `GA4_PROPERTY_ID` | GA4 property for runReport URL | (property ID) |
| `GOOGLE_ANALYTICS_TOKEN` | Bearer token for GA4 Data API | (secret) |
| `WORDPRESS_URL` | Base URL for post_url (no trailing slash) | `https://howtogenie.com` |
| `SOCIAL_QUEUE_TAB` | Tab for Social Queue (Viral Amplifier Queue appends here) | `Social Queue` |

## A/B Testing (Plan 01)

| Key | Purpose |
|-----|---------|
| `A_B_TESTING_ENABLED` | Gate for A/B workflow |
| `AB_TESTS_TAB` | Sheet tab for A/B test log |
| `CONTENT_LOG_TAB` | Content Log tab for yesterday's post |
