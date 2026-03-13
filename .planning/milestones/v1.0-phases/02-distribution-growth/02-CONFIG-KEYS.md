# Phase 2: Config Keys and Sheets Tabs

**Purpose:** Single reference for all Phase 2 config keys and required Google Sheets tabs. Add these keys to `htg_config` (or the Config Loader data source) and create the listed tabs so growth workflows run correctly.

---

## Config Keys (htg_config / Config Loader)

| Key | Used by | Description | Example / Notes |
|-----|---------|-------------|-----------------|
| `MULTI_LANGUAGE_ENABLED` | Multi-Language Expansion Engine | When `true` or `"true"`, workflow runs at 2 PM; when false or missing, exits without running. | `true` or `false` |
| `MESSAGING_DIGEST_ENABLED` | WhatsApp & Telegram Distribution Bot | When `true` or `"true"`, digest runs at 10 AM; when false or missing, exits without running. | `true` or `false` |
| `MESSAGING_SUBSCRIBERS_TAB` | Messaging workflow | Sheet tab name for subscriber list (Platform, Chat ID or Phone, Status). | `Messaging Subscribers` |
| `MULTILINGUAL_CONTENT_TAB` | Multi-Language Expansion Engine | Sheet tab where translated post rows are appended (language code, WP URL, etc.). | `Multilingual Content` |
| `CONTENT_DAY_TIMEZONE` | Multi-Language, Messaging | Optional. Timezone for "today" (YYYY-MM-DD). Fallback: `TIMEZONE` or server TZ. | `UTC`, `America/New_York` |
| `TIMEZONE` | Multi-Language, Messaging | Optional. Used when `CONTENT_DAY_TIMEZONE` is not set. | `UTC` |
| `CONTENT_LOG_TAB` | Multi-Language, Messaging, Orchestrator | Sheet tab name for Content Log (date, status, wp_url, etc.). | `Content Log` |
| `GOOGLE_SHEET_ID` | All Phase 2 workflows, Orchestrator | Google Sheets spreadsheet ID (from the sheet URL). | `1abc...` |
| `SPREADSHEET_ID` | All Phase 2 workflows, Orchestrator | Alias for `GOOGLE_SHEET_ID`; either key can be used. | `1abc...` |
| `WORDPRESS_URL` | Multi-Language, Orchestrator | Main site base URL (no trailing slash). Used to fetch today's English post and to derive language subdomain URLs. | `https://howtogenie.com` |
| `WORDPRESS_URL_ES` … `WORDPRESS_URL_JA` | Multi-Language (optional) | Per-language base URLs. If set, used instead of deriving from `WORDPRESS_URL` + subdomain. | `https://es.howtogenie.com` |

---

## Required Google Sheets Tabs

Create these tabs in the same spreadsheet as `GOOGLE_SHEET_ID` / `SPREADSHEET_ID`:

| Tab name (default) | Config key override | Used by | Purpose |
|--------------------|---------------------|---------|---------|
| Content Log | `CONTENT_LOG_TAB` | Orchestrator, Multi-Language, Messaging | Published post log (date, status, wp_url, …). Phase 1 shape. |
| Multilingual Content | `MULTILINGUAL_CONTENT_TAB` | Multi-Language Expansion Engine | One row per translated post (language, URL, title, …). |
| Messaging Subscribers | `MESSAGING_SUBSCRIBERS_TAB` | WhatsApp & Telegram Distribution Bot | Subscriber list: Platform, Chat ID or Phone, Language, Status. |
| Messaging Distribution Log | (fixed or config in plan 02-02) | Messaging workflow | Log of digest sends (Status, Recipients, date). |

---

## Quick checklist

- [ ] Add `MULTI_LANGUAGE_ENABLED` to htg_config (set to `true` to run multi-language at 2 PM).
- [ ] Add `MESSAGING_DIGEST_ENABLED` to htg_config when using the messaging workflow.
- [ ] Set `GOOGLE_SHEET_ID` or `SPREADSHEET_ID` to your sheet ID.
- [ ] Set `CONTENT_LOG_TAB` if not using "Content Log".
- [ ] Set `MULTILINGUAL_CONTENT_TAB` if not using "Multilingual Content".
- [ ] Set `WORDPRESS_URL` to your main site URL (no trailing slash).
- [ ] Create "Multilingual Content" tab (and optionally "Messaging Subscribers", "Messaging Distribution Log") in the sheet.
