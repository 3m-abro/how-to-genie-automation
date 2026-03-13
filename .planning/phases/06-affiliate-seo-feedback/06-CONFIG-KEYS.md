# Phase 6: Configuration Keys and Registry Shape

**Purpose:** Single source of truth for Phase 6 config keys, affiliate registry row shape, and required Google Sheets tabs. Add these keys to the `htg_config` data table (Key/Value); workflows read them via Config Loader. No `YOUR_*` or hardcoded sheet IDs in workflows.

---

## 1. Config Keys (htg_config Key/Value)

Add the following rows to the `htg_config` data table. Config Loader (`core/01_Config_Loader.json`) builds a key-value object from this table; workflows reference e.g. `$('⚙️ Load Config').item.json.<KEY>`.

| Key | Description | Example / Default |
|-----|-------------|-------------------|
| **AFFILIATE_REGISTRY_TAB** | Sheet tab name for the affiliate product registry | `Affiliate Registry` |
| **AFFILIATE_MANAGER_ENABLED** | Gate for Affiliate Link Manager workflow | `true` or `false` |
| **REFRESH_CANDIDATES_TAB** | Sheet tab for GA4 refresh-candidates list (topic selection input) | `Refresh Candidates` |
| **REFRESH_VIEWS_MIN** | (Optional) Minimum 7d views for a post to be a refresh candidate. Can reuse Viral Amplifier’s **VIRAL_VIEWS_7D_MIN** if desired | `100` (default) |
| **SEO_INTERLINKING_ENABLED** | Gate for SEO Interlinking workflow | `true` or `false` |
| **INTERNAL_LINKING_LOG_TAB** | Sheet tab for interlink recommendations log | `Internal Linking Log` |
| **CONTENT_LOG_TAB** | Sheet tab for published posts (already used elsewhere). SEO Interlinking reads from this for “Load All Published Posts” | e.g. `Content Log` |
| **NICHES** | (Optional) Comma-separated list for registry `niche` column | `productivity,finance,home,health,tech` (default five) |

**Note:** `GOOGLE_SHEET_ID` (or `SPREADSHEET_ID`) is already in config; Phase 6 workflows use it with the tab keys above. No workflow JSON changes are required in this plan—this document is the contract for what keys to add.

---

## 2. Affiliate Registry Row Shape

The **Affiliate Registry** tab stores one row per product. Columns:

| Column | Type | Description |
|--------|------|-------------|
| **product_name** | string | Display name of the product |
| **platform** | string | Source platform (e.g. `Muncheye`, `CBEngine`, `ClickBank`) |
| **commission** | string | Commission info (e.g. percentage or flat) |
| **url** | string | Affiliate or product URL |
| **niche** | string | Must be one of: `productivity`, `finance`, `home`, `health`, `tech` (or from **NICHES** config if overridden) |
| **score** | number/string | Relevance or quality score from Manager scoring |
| **date_found** | string | When the product was added (e.g. ISO date) |
| **status** | string | `active` \| `deprecated` (or equivalent) |

Workflows that write to the registry (e.g. Affiliate Link Manager, Plan 02) must output rows conforming to this shape. Agent 1 (Topic Research) and Agent 4 (SEO & Monetization) read from this tab to pick products per niche.

---

## 3. Required Google Sheets Tabs

The spreadsheet identified by **GOOGLE_SHEET_ID** / **SPREADSHEET_ID** must have these tabs for Phase 6:

| Tab (config key) | Purpose |
|------------------|--------|
| **Affiliate Registry** (`AFFILIATE_REGISTRY_TAB`) | Product registry; Manager writes here; orchestrator/agents read |
| **Refresh Candidates** (`REFRESH_CANDIDATES_TAB`) | GA4-derived list of high-traffic posts for topic selection (Agent 1) |
| **Internal Linking Log** (`INTERNAL_LINKING_LOG_TAB`) | SEO Interlinking workflow writes recommendations here |
| **Content Log** (`CONTENT_LOG_TAB`) | Already required by core pipeline; SEO Interlinking reads it for “Load All Published Posts” |

Create the first three if they do not exist. Content Log is typically already present from Phase 1.

---

## 4. Bootstrap / Seed

- **Preferred:** Run the **Affiliate Link Manager** workflow (Plan 02) to populate the registry from Muncheye/CBEngine RSS (and optionally ClickBank). One successful run can fill the registry with scored products across niches.
- **Alternative:** One-time manual seed: add at least one row per niche (productivity, finance, home, health, tech) to the Affiliate Registry tab with `status: active` so that Agent 1/4 have at least one product per niche before the Manager has run.

---

*Phase: 06-affiliate-seo-feedback*  
*Contract for Plan 01; Plan 02 (Affiliate Manager) and other Phase 6 workflows reference this doc for tab names and row structure.*
