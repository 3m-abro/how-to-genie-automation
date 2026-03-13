# Phase 1: Pipeline Reliability - Research

**Researched:** 2026-03-12
**Domain:** n8n workflow hardening — error handling, retry, structured logging, QC routing
**Confidence:** HIGH (based on direct code inspection of all relevant workflow files)

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **LLM Fallback (PIPE-02):** When a Parse & Validate node cannot parse the LLM response, use fallback defaults and continue the pipeline — do not abort. The fallback post IS published to WordPress. Write `parse_error=true` and `parse_error_agents=['agent_2','agent_4']` (array, not boolean) to the Content Log row. All remaining downstream agents still run on fallback content.
- **Ollama Node Type (PIPE-02 prerequisite):** Switch all Ollama httpRequest nodes (Agents 1–5) in the orchestrator to the Ollama Agent (Central) sub-workflow (`core/Ollama Agent (Central).json`). Each agent call passes temperature as a parameter. Agent 0 already uses Ollama Agent (Central).
- **WordPress Node Type + Retry (PIPE-03):** Switch WordPress publish from httpRequest to n8n's native WordPress nodes. Retry policy: 3 attempts with 30-second delay. On final failure: write `status=publish_failed` to Content Log row + send Telegram alert. When `status=publish_failed`, block all satellite triggers (Blotato, Calendar Manager).
- **QC Rejection Routing (PIPE-04):** Write a full structured row to a dedicated 'Rejected Posts' Google Sheets tab (not Content Log). Fields: `date`, `topic`, `primary_keyword`, `qc_score`, `rejection_reasons` (array), `word_count`, `agent_fallbacks_used`. Mark topic as `status=rejected` in Blog Idea Backlog. Send Telegram alert on every rejection.
- **Config Runtime Source (PIPE-05):** Existing n8n data table + Config Loader sub-workflow already satisfies PIPE-05. Phase 1 action: verify and document that Config Loader is called before any agents. No new sync mechanism needed. `htg_config.csv` is documentation reference only.
- **Workflow Audit:** Audit all workflows EXCEPT `/archive`. Archive criterion: move to `/archive` if superseded by a newer version of the same workflow.

### Claude's Discretion
- Exact retry implementation pattern (Wait node + loop vs. n8n node-level retry settings)
- Which specific Code node fields to add/update for per-agent error flags
- How to detect "superseded" workflows during audit (by name pattern and version number)

### Deferred Ideas (OUT OF SCOPE)
- Adding satellite workflow node-type fixes (social, growth, monitoring workflows) — Phases 2–6
- Building a CSV → n8n data table sync mechanism
- Approval polling integration into QC rejection flow
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PIPE-01 | Orchestrator runs daily at 8 AM and completes without manual intervention | Config Loader confirmed first in chain; schedule trigger verified; key gaps in node robustness now mapped |
| PIPE-02 | Failed LLM nodes fall back to defaults and log the error to Google Sheets rather than stopping the pipeline | All 5 Code parse nodes audited; Agents 1–5 confirmed as httpRequest needing migration; Content Log assembly gap identified |
| PIPE-03 | WordPress publish step retries on transient failures and reports final status to Sheets | WP node confirmed as httpRequest with no timeout and no retry; n8n native WP node and retryOnFail fields documented |
| PIPE-04 | QC agent rejection routes to Sheets log (not silent failure) and triggers next-day retry topic | QC rejected dead-end confirmed; Alert Handler input gap confirmed; Rejected Posts tab missing from config |
| PIPE-05 | Config Loader reads htg_config.csv at runtime so parameter changes take effect without re-importing workflows | Config Loader call confirmed as first node after triggers; both data tables (htg_config + htg_secrets) verified present |
</phase_requirements>

---

## Summary

The orchestrator (`core/08_Orchestrator_v3.json`) has a solid structural foundation: the Config Loader fires first, the 8-agent chain is connected correctly, and all LLM parse nodes (Agents 0, 1, 4) have try/catch with fallback defaults. However, several reliability gaps prevent it from satisfying the PIPE-01 through PIPE-05 requirements.

The most critical gaps are: (1) the Content Log write path carries API response data instead of article data — no assembly Code node exists before `📊 Log to Google Sheets`; (2) the QC rejection path is a dead-end — `⚠️ Alert: QC Rejected` calls Alert Handler with empty `workflowInputs`, writes nothing to Sheets, and never updates the Blog Idea Backlog; (3) Agents 1–5 call Ollama directly via httpRequest while Agent 0 already uses the Ollama Agent (Central) sub-workflow — migration is incomplete; (4) the WordPress publish node has no timeout, no retry, and no status field written back; (5) satellite triggers fire unconditionally even when publish fails.

The QC Approved? IF node uses a fragile string-contains check on the raw LLM response rather than parsing JSON first. Two non-LLM Code nodes (`🔧 Build Research Context`, `🖼️ Build Image Queries`) have no try/catch. These are secondary gaps that should be fixed opportunistically during the agent migration work.

**Primary recommendation:** The core work is four JSON edits to `08_Orchestrator_v3.json`: (1) add a `📋 Assemble Content Log Row` Code node before the Log node; (2) replace the QC rejected dead-end with three new nodes (Sheets write, Backlog update, Telegram alert); (3) replace Agents 1–5 httpRequest nodes with executeWorkflow calls to Ollama Agent (Central); (4) replace the WP httpRequest node with a native WordPress node with `retryOnFail: true, maxTries: 3, waitBetweenTries: 5000` (n8n max 5s; for 30s delay use Wait+Loop). Additionally add `REJECTED_POSTS_TAB` to htg_config.csv and the n8n data table.

---

## Current State Audit

### Orchestrator Node Inventory (37 nodes)

| Node Name | Type | Role | Has try/catch | parse_error flag |
|-----------|------|------|--------------|-----------------|
| 🕗 Daily Trigger 8AM | scheduleTrigger | Entry point | n/a | n/a |
| ⚡ Entry Override | executeWorkflowTrigger | Alt entry from Approval Poller | n/a | n/a |
| ⚙️ Load Config | executeWorkflow | Calls Config Loader | n/a | n/a |
| 📡 Load Existing Topics | googleSheets | Reads Blog Idea Backlog | n/a | n/a |
| 📡 Fetch Reddit Trending | httpRequest | Reddit trending data | n/a | n/a |
| 🔧 Build Research Context | code | Assembles Reddit+Sheets context | **NO** | NO |
| 💉 Inject Approved Topic | code | Merges approved_topic if present | partial | NO |
| 🧠 Agent 0: Prompt Engineer | executeWorkflow | Calls Ollama Central | n/a | YES (in Central) |
| 📋 Parse Prompt Package | code | Parses Agent 0 JSON output | **YES** | YES (`parse_error` set) |
| 🔍 Agent 1: Research & Topic | **httpRequest** | Direct Ollama API call | n/a | n/a |
| ✅ Parse & Validate Topic | code | Parses Agent 1 JSON output | **YES** | NO (has `status: fallback_topic` but not `parse_error`) |
| ✍️ Agent 2: Content Writer | **httpRequest** | Direct Ollama API call | n/a | n/a |
| 🫂 Agent 3: Humanizer | **httpRequest** | Direct Ollama API call | n/a | n/a |
| 🎯 Agent 4: SEO & Monetization | **httpRequest** | Direct Ollama API call | n/a | n/a |
| 🔧 Assemble Final Article | code | Parses Agent 4 output + assembles | **YES** | YES (`parse_error` set in seo object) |
| 🛡️ Agent 5: Quality Control | **httpRequest** | Direct Ollama API call | n/a | n/a |
| ✅ QC Approved? | if | **Fragile string-contains check** | n/a | n/a |
| ⚠️ Alert: QC Rejected | executeWorkflow | Calls Alert Handler | n/a | n/a |
| 🖼️ Build Image Queries | code | Builds Pexels/Pixabay query | **NO** | NO |
| 📸 Search Pexels | httpRequest | Image search | n/a | n/a |
| 📸 Search Pixabay (Fallback) | httpRequest | Fallback image search | n/a | n/a |
| 🔀 Select Best Image | code | Picks best image URL | **YES** | NO |
| 🤔 Need AI Image? | if | Routes to SD if no image found | n/a | n/a |
| 🎨 Generate Image (SD) | httpRequest | Stable Diffusion image gen | n/a | n/a |
| 📤 Upload Image to WP Media | httpRequest | Upload image to WordPress | n/a | n/a |
| 🔗 Capture Media ID | code | Extracts media ID from WP response | **NO** | NO |
| 📝 Publish to WordPress | **httpRequest** | **No timeout, no retry** | n/a | n/a |
| 🔍 Request Google Indexing | httpRequest | Google indexing API | n/a | n/a |
| 🔍 Request Bing Indexing | httpRequest | Bing webmaster API | n/a | n/a |
| 📡 Ping Sitemap | httpRequest | Google sitemap ping | n/a | n/a |
| 🎨 Queue via Blotato | executeWorkflow | Social post queuing (async) | n/a | n/a |
| 📅 Write to Blog Calendar | googleSheets | Writes to Blog Content Calendar | n/a | n/a |
| 🔄 Update Calendar Status (PUBLISHED) | googleSheets | Updates calendar row status | n/a | n/a |
| 🚀 Trigger Content Calendar Manager | executeWorkflow | Triggers downstream workflow | n/a | n/a |
| 📱 Telegram: Article Published | telegram | Success Telegram alert (native node) | n/a | n/a |
| 📊 Log to Google Sheets | googleSheets | **Writes wrong data (API responses)** | n/a | n/a |
| ✅ Send Success Alert | executeWorkflow | Calls Alert Handler on success | n/a | n/a |

### Confirmed Orchestrator Flow

```
🕗 Daily Trigger 8AM ──────────────────────────┐
⚡ Entry Override (from Approval Poller) ────────┤
                                                 ↓
                                         ⚙️ Load Config
                                         (executeWorkflow → Config Loader)
                                                 ↓  (parallel)
                              ┌──────────────────┴──────────────────┐
                     📡 Load Existing Topics              📡 Fetch Reddit Trending
                              └──────────────────┬──────────────────┘
                                                 ↓
                                    🔧 Build Research Context  [NO try/catch]
                                                 ↓
                                    💉 Inject Approved Topic
                                                 ↓
                                    🧠 Agent 0: Prompt Engineer
                                    (executeWorkflow → Ollama Central ✓)
                                                 ↓
                                    📋 Parse Prompt Package [try/catch ✓ parse_error ✓]
                                                 ↓
                                    🔍 Agent 1: Research & Topic [httpRequest ❌]
                                                 ↓
                                    ✅ Parse & Validate Topic [try/catch ✓ parse_error ❌]
                                                 ↓
                                    ✍️ Agent 2: Content Writer [httpRequest ❌]
                                                 ↓
                                    🫂 Agent 3: Humanizer [httpRequest ❌]
                                                 ↓
                                    🎯 Agent 4: SEO & Monetization [httpRequest ❌]
                                                 ↓
                                    🔧 Assemble Final Article [try/catch ✓ parse_error ✓]
                                                 ↓
                                    🛡️ Agent 5: Quality Control [httpRequest ❌]
                                                 ↓
                                          ✅ QC Approved? [fragile string-contains]
                                         /                  \
                              [true branch]                [false branch]
                        🖼️ Build Image Queries          ⚠️ Alert: QC Rejected
                        [NO try/catch]                   (→ Alert Handler with empty inputs)
                              ↓                          [DEAD END - no Sheets write]
                        📸 Search Pexels (parallel with Pixabay)
                              ↓
                        🔀 Select Best Image
                              ↓
                        🤔 Need AI Image?
                         /           \
               [no image]         [has image]
               🎨 Generate SD    📤 Upload to WP Media
                    ↓                    ↑
                    └────────────────────┘
                                 ↓
                        🔗 Capture Media ID [NO try/catch]
                                 ↓
                        📝 Publish to WordPress [httpRequest, no timeout, no retry ❌]
                                 ↓  (all parallel, unconditional)
          ┌──────────────────────┼──────────────────────┬──────────────────┐
   🔍 Google Indexing    🔍 Bing Indexing     📡 Ping Sitemap    🎨 Queue via Blotato
          │                     │                     │           [async, no wait]
          └─────────────────────┴─────────────────────┘
                                 ↓  (all merge to Log — WRONG DATA ❌)
                        📊 Log to Google Sheets
                        [writes API response bodies, not article data]
                                 ↓
                        ✅ Send Success Alert → Alert Handler

   [SEPARATE BRANCH from WP Publish]:
   📅 Write to Blog Calendar → 🔄 Update Calendar Status → 🚀 Trigger Calendar Manager
   → 📱 Telegram: Article Published
```

---

## Standard Stack

### Core n8n Node Types Required

| Node Type | n8n Type String | Purpose | Notes |
|-----------|----------------|---------|-------|
| Native WordPress node | `n8n-nodes-base.wordpress` | Publish post with auth | Replaces httpRequest for WP |
| Execute Workflow | `n8n-nodes-base.executeWorkflow` | Call Ollama Central sub-wf | Already used by Agent 0 |
| Code node | `n8n-nodes-base.code` | JavaScript parsing/assembly | Plain JS, no require() |
| Google Sheets append | `n8n-nodes-base.googleSheets` op: append | Write structured log rows | Already used |
| Google Sheets update | `n8n-nodes-base.googleSheets` op: update | Update Backlog row status | Already used in Approval Poller |
| Telegram native | `n8n-nodes-base.telegram` | Send alert messages | Already present for success |
| IF node | `n8n-nodes-base.if` | Gate on publish_failed | Already used for QC |

### Retry Implementation: n8n Node-Level Fields (Verified)

n8n supports node-level retry via top-level JSON fields on any node:

```json
{
  "name": "📝 Publish to WordPress",
  "type": "n8n-nodes-base.wordpress",
  "retryOnFail": true,
  "maxTries": 3,
  "waitBetweenTries": 5000,
  "parameters": { ... }
}
```

**CRITICAL — n8n retry limits (verified):** As of n8n 1.41+ the **UI enforces** a maximum of **5** for max tries and **5000 ms (5 seconds)** for wait between tries. See [GitHub #9458](https://github.com/n8n-io/n8n/issues/9458) (closed as feature request). Setting `waitBetweenTries: 30000` in JSON may be accepted on import but can be clamped on re-open in the UI. **Recommendation:** Use built-in retry with `maxTries: 3` and `waitBetweenTries: 5000` (max). If the locked decision requires a 30-second delay between attempts, use a **custom pattern:** Wait node (30s) + Loop/retry branch instead of node-level retry. [Confidence: HIGH — official issue and docs]

**Why node-level retry when sufficient:** Simpler (no loop node, no counter), idiomatic n8n, UI-visible. Queue Processor v2 uses a Code-node counter for multi-day retries — overkill for same-run WP publish.

**After retry exhaustion:** The native WordPress node throws when all retries fail. Gate the publish result via a Code node that sets `status=publish_failed` in try/catch, then route through an IF node before satellite triggers.

### Ollama Agent (Central) Sub-Workflow Interface

**File:** `core/Ollama Agent (Central).json`

**Expected inputs (Start node `workflowInputs`):**

| Field | Type | Notes |
|-------|------|-------|
| `user_message` | string | The full prompt text |
| `system_message` | string | Optional system prompt (defaults to "You are a helpful assistant") |
| `model` | string | Ollama model name (defaults to `llama3.2:latest` in Ollama Chat Model node) |
| `temperature` | number | Temperature value (defaults to 0.7 in Ollama Chat Model node) |
| `num_predict` | number | Context window length (used as numCtx, defaults to 4096) |

**Output:** Single item with `json.message.content` string (from Normalize Output Code node).

**CRITICAL: Parameter name mismatch (Agent 0 bug):** Agent 0 currently passes `ollama_model` but the Ollama Central node uses `$json.model`. Because `$json.model` is undefined when `ollama_model` is passed, the Ollama Chat Model node falls back to the hardcoded `llama3.2:latest`. This means Agent 0 never actually uses the `OLLAMA_MODEL_SMART` config value at runtime. This must be fixed when migrating agents 1–5: use `model` not `ollama_model` in all executeWorkflow inputs.

**Per-agent temperature values to preserve:**

| Agent | Node Name | Temperature |
|-------|-----------|-------------|
| 0 | Prompt Engineer | 0.4 |
| 1 | Research & Topic | 0.7 |
| 2 | Content Writer | 0.8 |
| 3 | Humanizer | 0.9 |
| 4 | SEO & Monetization | 0.4 |
| 5 | Quality Control | 0.3 |

---

## Architecture Patterns

### Pattern 1: Agent Migration (httpRequest → executeWorkflow Ollama Central)

**What:** Replace each `🔍 Agent N` httpRequest node with an `n8n-nodes-base.executeWorkflow` node pointing to the Ollama Agent (Central) sub-workflow.

**Current httpRequest pattern (to remove):**
```javascript
// Current Agent 1 httpRequest
{
  "type": "n8n-nodes-base.httpRequest",
  "parameters": {
    "url": "http://{{ $('⚙️ Load Config').item.json.OLLAMA_URL || ... }}/api/chat",
    "jsonBody": "={ \"model\": \"llama3.2:latest\", \"messages\": [...], \"stream\": false, \"options\": { \"temperature\": 0.7 } }",
    "timeout": 300000
  }
}
```

**Replacement executeWorkflow pattern:**
```javascript
// New Agent 1 executeWorkflow
{
  "name": "🔍 Agent 1: Research & Topic",
  "type": "n8n-nodes-base.executeWorkflow",
  "typeVersion": 1.2,
  "parameters": {
    "workflowId": {
      "__rl": true,
      "value": "18GE0djgSQJHhj8C",
      "mode": "list",
      "cachedResultName": "Ollama Agent (Central)"
    },
    "workflowInputs": {
      "mappingMode": "defineBelow",
      "value": {
        "model": "={{ $('⚙️ Load Config').item.json.OLLAMA_MODEL_SMART || 'llama3.2:latest' }}",
        "user_message": "={{ ... (existing prompt text) }}",
        "system_message": "={{ $('⚙️ Load Config').item.json.PERSONA }}",
        "temperature": 0.7,
        "num_predict": 4096
      }
    },
    "options": { "waitForSubWorkflow": true }
  }
}
```

**Critical:** `waitForSubWorkflow: true` is required for all agent calls so the next node receives the LLM output.

### Pattern 2: Content Log Row Assembly (pre-Log Code Node)

**Problem:** `📊 Log to Google Sheets` uses `autoMapInputData` and receives items from parallel paths (Google Indexing response, Bing Indexing response, Sitemap ping response, Blotato async output). None of these carry article data.

**Solution:** Insert a `📋 Assemble Content Log Row` Code node that all paths funnel through before the Log node. This node explicitly reads from upstream named nodes:

```javascript
// 📋 Assemble Content Log Row
const article  = $('🔧 Assemble Final Article').item.json;
const topic    = $('✅ Parse & Validate Topic').item.json;
const qc       = $('✅ Parse & Validate QC').item.json;  // new node
const wpResult = $('📝 Publish to WordPress').item.json;
const media    = $('🔗 Capture Media ID').item.json;
const config   = $('⚙️ Load Config').item.json;

// Collect parse_error_agents list
const errorAgents = [];
if ($('📋 Parse Prompt Package').item.json.parse_error)         errorAgents.push('agent_0');
if (topic.status === 'fallback_topic')                          errorAgents.push('agent_1');
// Agents 2 and 3 return prose (no JSON parse), track via Assemble node
if (article.parse_error)                                        errorAgents.push('agent_4');
if (qc.parse_error)                                             errorAgents.push('agent_5');

const publishFailed = !wpResult?.id && !wpResult?.link;

return [{ json: {
  date:               new Date().toISOString().split('T')[0],
  title:              article.title,
  primary_keyword:    topic.primary_keyword,
  wp_url:             wpResult?.link || '',
  wp_post_id:         wpResult?.id || '',
  word_count:         article.word_count,
  status:             publishFailed ? 'publish_failed' : 'published',
  parse_error:        errorAgents.length > 0,
  parse_error_agents: JSON.stringify(errorAgents),
  qc_score:           qc.average_score || '',
  affiliate_ctas:     article.affiliate_ctas,
  media_id:           media.media_id || '',
  run_timestamp:      new Date().toISOString()
}}];
```

### Pattern 3: QC Rejection Path (Replace Dead-End)

**Current state:** `⚠️ Alert: QC Rejected` calls Alert Handler with empty `workflowInputs: {}`. Alert Handler receives no data so `$input.first().json` has no fields — all alert message fields (`level`, `workflow_name`, `error_message`) resolve to `undefined`.

**Required replacement:** Remove the Alert Handler call from QC rejection path. Replace with three new dedicated nodes:

```
✅ QC Approved? [false] → 📋 Build QC Rejection Row → 📊 Write to Rejected Posts Sheet
                        ↓ (also)
                        📝 Update Backlog Status to Rejected → (Sheets update node)
                        ↓ (also)
                        📱 Send QC Rejection Alert (Telegram native node)
```

QC Rejection Row Code node pattern:
```javascript
// 📋 Build QC Rejection Row
const qc      = $('✅ Parse & Validate QC').item.json;
const topic   = $('✅ Parse & Validate Topic').item.json;
const article = $('🔧 Assemble Final Article').item.json;
const config  = $('⚙️ Load Config').item.json;

const errorAgents = [];
if ($('📋 Parse Prompt Package').item.json.parse_error) errorAgents.push('agent_0');
if (topic.status === 'fallback_topic')                  errorAgents.push('agent_1');
if (article.parse_error)                               errorAgents.push('agent_4');
if (qc.parse_error)                                    errorAgents.push('agent_5');

return [{ json: {
  date:                new Date().toISOString().split('T')[0],
  topic:               topic.topic,
  primary_keyword:     topic.primary_keyword,
  qc_score:            qc.average_score || 0,
  rejection_reasons:   JSON.stringify(qc.scores || {}),
  word_count:          article.word_count,
  agent_fallbacks_used: JSON.stringify(errorAgents),
  run_timestamp:       new Date().toISOString()
}}];
```

Telegram alert for QC rejection (native Telegram node):
```
QC rejected: [topic] — score [X]/10, reasons: [reasons]. Fresh topic tomorrow.
```

Backlog update: use a Code node that returns `{ Row_Number: topic._row, status: 'rejected' }` then a Google Sheets update node using `Row_Number` as match column. Note: `_row` must be stored in Parse & Validate Topic node output.

### Pattern 4: WordPress Publish with Retry + Status Gate

**Replace httpRequest WP publish with native WordPress node + retry:**

```json
{
  "name": "📝 Publish to WordPress",
  "type": "n8n-nodes-base.wordpress",
  "typeVersion": 1,
  "retryOnFail": true,
  "maxTries": 3,
  "waitBetweenTries": 5000,
  "parameters": {
    "resource": "post",
    "operation": "create",
    "additionalFields": {
      "title": "={{ $('🔧 Assemble Final Article').item.json.title }}",
      "content": "={{ $('🔧 Assemble Final Article').item.json.content }}",
      "slug": "={{ $('🔧 Assemble Final Article').item.json.slug }}",
      "status": "publish",
      "featuredMediaId": "={{ $('🔗 Capture Media ID').item.json.media_id || 0 }}",
      "excerpt": "={{ $('🔧 Assemble Final Article').item.json.excerpt }}"
    }
  },
  "credentials": {
    "wordpressApi": {
      "id": "...",
      "name": "..."
    }
  }
}
```

**After WP publish:** Add `🔀 Publish Result?` IF node checking `$json.id` existence. True branch → satellite triggers. False branch → write `publish_failed` to Sheets + Telegram alert → end.

### Pattern 5: QC Parse Node (New)

**Current state:** `🛡️ Agent 5: Quality Control` (httpRequest) → `✅ QC Approved?` (IF with raw string check `message.content contains '"approved": true'`)

**Problem:** The string check is fragile — if Ollama returns `approved:true` (no quotes), or puts the JSON inside markdown, the IF routes to false (treating valid approvals as rejections).

**Required:** After Agent 5 migration to executeWorkflow, insert `✅ Parse & Validate QC` Code node:

```javascript
// ✅ Parse & Validate QC
const raw = $input.first().json.message?.content || '';
let qc;
try {
  const match = raw.match(/```json\n([\s\S]*?)\n```/) || raw.match(/(\{[\s\S]*\})/s);
  qc = JSON.parse(match ? (match[1] || match[0]) : raw);
  if (typeof qc.approved !== 'boolean') throw new Error('Missing approved field');
} catch(e) {
  qc = {
    approved: false,
    scores: { word_count: 0, readability: 0, seo_compliance: 0, affiliate_disclosure: 0, factual_accuracy: 0, reader_value: 0 },
    average_score: 0,
    rejection_reason: 'QC parse failure: ' + e.message,
    suggestions: [],
    parse_error: true
  };
}
return [{ json: qc }];
```

Update `✅ QC Approved?` IF condition to check `{{ $json.approved }}` boolean (not string-contains).

### Pattern 6: Parse & Validate Topic — Add parse_error Flag

The existing `✅ Parse & Validate Topic` fallback uses `status: 'fallback_topic'` but PIPE-02 requires `parse_error` flag in the standard field. Add `parse_error: true` to the fallback object and `parse_error: false` to the success path.

### Pattern 7: Satellite Trigger Gate (publish_failed check)

Insert `🔀 Publish Succeeded?` IF node between WP publish result and satellite triggers:

```
📝 Publish to WordPress → 🔗 Capture WP Post Data (Code node that handles try/catch)
                                    ↓
                          🔀 Publish Succeeded? (IF: $json.status !== 'publish_failed')
                         /                                \
              [true] satellite triggers             [false] 📊 Log publish_failed to Sheets
              🎨 Queue via Blotato                          📱 Telegram: Publish Failed
              📅 Write to Blog Calendar                     (end)
```

### Anti-Patterns to Avoid

- **Parallel Log writes:** Do not let 4 parallel paths (Google Indexing, Bing Indexing, Ping Sitemap, Blotato) all feed into the same Google Sheets append node. They write different data and produce 4 rows per run. Use a single `📋 Assemble Content Log Row` Code node that all paths converge on, pulling data from named nodes.
- **Empty workflowInputs on executeWorkflow:** When calling a sub-workflow that needs structured data, always populate `workflowInputs.value` explicitly. Empty `{}` means the sub-workflow receives no data.
- **String-contains QC check:** Never check `message.content contains '"approved": true'` — parse JSON first, then check the boolean field.
- **waitForSubWorkflow: false for agents:** Only use async (`waitForSubWorkflow: false`) for fire-and-forget satellites (Blotato social queuing). LLM agents must use `waitForSubWorkflow: true`.
- **ollama_model vs model field mismatch:** When building executeWorkflow inputs for Ollama Central, use `model` (matches Central's Start node input definition), not `ollama_model`.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| WP publish retry | Manual loop with Wait node + counter Code node (when 5s delay is enough) | `retryOnFail: true, maxTries: 3, waitBetweenTries: 5000` on the WP node | n8n native retry is atomic, UI-visible; max wait is 5000 ms per n8n limits. For 30s delay use Wait + loop. |
| Telegram alerts | Custom httpRequest to Telegram Bot API URL | Native n8n Telegram node (`n8n-nodes-base.telegram`) | Already used by the success alert; handles token/chat_id from credentials or config cleanly |
| JSON parsing from LLM | Custom regex-only parser | Standard pattern: `raw.match(/```json\n...\n```/) \|\| raw.match(/(\{[\s\S]*\})/s)` with try/catch + fallback object | Already established pattern in orchestrator — extend, don't replace |
| Config at runtime | Re-importing workflow JSON | n8n dataTable nodes in Config Loader (`Get Config(s)` + `Get Secret(s)`) | Already implemented; reads at execution time |
| Backlog row updates | Rebuilding entire Sheets row | `operation: update` with `matchingColumns: ['Row_Number']` | Already used in Approval Poller (`📊 Update Backlog Status`) |

---

## Common Pitfalls

### Pitfall 1: autoMapInputData writes the wrong fields to Sheets
**What goes wrong:** `📊 Log to Google Sheets` with `mappingMode: autoMapInputData` writes whatever fields are in the current item. If the item comes from the Google Indexing API response, the row gets indexing API fields (not article fields).
**Why it happens:** The Log node sits at the end of 4 parallel execution paths. n8n picks one (or all) of those items. None carry article data.
**How to avoid:** Always insert a dedicated `📋 Assemble Content Log Row` Code node that explicitly reads from named nodes before the Log node. Use `mappingMode: defineBelow` or ensure the assembly node produces exactly the fields you want.
**Warning signs:** Check the actual Google Sheets Content Log tab — if it has fields like `urlNotificationMetadata`, `latestUpdate`, or empty rows, the assembly step is missing.

### Pitfall 2: executeWorkflow with waitForSubWorkflow: false does not propagate sub-workflow output
**What goes wrong:** When `options.waitForSubWorkflow` is false, the main workflow does not wait for the sub-workflow to complete. The executeWorkflow node does not receive the sub-workflow's output (verified: option path `options.waitForSubWorkflow`, default true — n8n MCP get_node). So Blotato's executeWorkflow node yields no useful item for downstream Log.
**Why it happens:** Async execution by design; output is not passed back to the caller.
**How to avoid:** Use `waitForSubWorkflow: false` only for fire-and-forget satellites. Gate the Content Log write on the article assembly node, not on any async executeWorkflow output.

### Pitfall 3: QC rejection Backlog update needs _row number
**What goes wrong:** To update a specific row in Blog Idea Backlog to `status=rejected`, you need the row number of the topic that was selected. If `✅ Parse & Validate Topic` doesn't preserve `_row`, the update is impossible.
**Why it happens:** `_row` is set by the Approval Poller when it finds an approved topic, but when Agent 1 selects a topic from Reddit (normal daily run), there is no `_row`.
**How to avoid:** For QC rejection path, only update the Backlog row when `use_approved_topic: true` (topic came from backlog). When Agent 1 selected a Reddit topic (no `_row`), skip the Backlog update. The Approval Poller path already marks topics as "In Progress" before the orchestrator runs — a QC rejection should change that status back to `rejected`.

### Pitfall 4: Native WP node vs httpRequest WP API — different field names
**What goes wrong:** The native `n8n-nodes-base.wordpress` node uses `additionalFields.title`, `additionalFields.content`, etc. The current httpRequest sends `"title"`, `"content"` in raw JSON body. They achieve the same WP REST API call but the node parameter paths differ.
**Why it happens:** Different node types, different parameter schemas.
**How to avoid:** When migrating WP publish, use the node's documented `additionalFields` schema. **Verified:** Featured image is **`featuredMediaId`** (camelCase, type number) in the native node. In the current node schema it is present for **page** create/update; for **post** create, verify in the n8n UI that your node version exposes it (some versions add it for posts). If not, keep passing featured image via httpRequest or a follow-up update.

### Pitfall 5: Alert Handler expects config from its own Load Config call, not passed data
**What goes wrong:** Alert Handler runs `⚙️ Config` (its own Config Loader call) before formatting the message. It references `$('⚙️ Config').item.json.TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID`. If the Alert Handler's Config Loader call fails, the alert silently fails.
**Why it happens:** Alert Handler is designed as standalone — it loads its own config. It does NOT receive credentials from the calling workflow.
**How to avoid:** When adding new calls to Alert Handler (for QC rejection), pass the structured alert fields in `workflowInputs.value`: `{ level: 'WARNING', workflow_name: 'Orchestrator v3', node_name: 'QC Approved?', error_message: '...' }`. The Alert Handler already expects `level`, `workflow_name`, `node_name`, `error_message`.

### Pitfall 6: `SPREADSHEET_ID` vs `GOOGLE_SHEET_ID` config key inconsistency
**What goes wrong:** `📅 Write to Blog Calendar` and `🔄 Update Calendar Status` reference `$('⚙️ Load Config').item.json.SPREADSHEET_ID` but `htg_config.csv` defines `GOOGLE_SHEET_ID` (not `SPREADSHEET_ID`). These Calendar nodes currently resolve to `undefined` and fail.
**Why it happens:** Different config key names used in different nodes — a naming inconsistency.
**How to avoid:** During the orchestrator audit, standardize all Sheet ID references to `GOOGLE_SHEET_ID`. Either fix the Calendar nodes or add `SPREADSHEET_ID` as an alias in the n8n data table.

### Pitfall 7: Agents 2 and 3 return prose, not JSON
**What goes wrong:** Agent 2 (Content Writer) and Agent 3 (Humanizer) return full prose HTML, not JSON. There is no Parse & Validate node between them — they feed directly into the next agent. If they return empty or malformed content, the downstream agents receive blank input.
**Why it happens:** These agents are prose generators, not JSON generators. Fallback detection must check content length, not JSON parsing.
**How to avoid:** After migrating Agent 2 and 3 to executeWorkflow, add a lightweight guard Code node that checks `message.content.length > 500`. If shorter, use the previous agent's output as fallback and log `parse_error_agents`.

---

## Config Changes Required

### htg_config.csv (documentation reference) + n8n data table (canonical runtime)

Add these new keys:

| Key | Value | Purpose |
|-----|-------|---------|
| `REJECTED_POSTS_TAB` | `Rejected Posts` | QC rejection log tab name (PIPE-04) |

Both the CSV and the n8n `htg_config` data table must be updated. The orchestrator will read `REJECTED_POSTS_TAB` from `$('⚙️ Load Config').item.json.REJECTED_POSTS_TAB`.

### Google Sheets — New Tab Required

**Tab name:** `Rejected Posts`

**Columns:**
| Column | Type | Source |
|--------|------|--------|
| date | string (YYYY-MM-DD) | Code node |
| topic | string | Parse & Validate Topic output |
| primary_keyword | string | Parse & Validate Topic output |
| qc_score | number | Parse & Validate QC output |
| rejection_reasons | string (JSON array) | QC scores object |
| word_count | number | Assemble Final Article output |
| agent_fallbacks_used | string (JSON array) | Collected across parse nodes |

---

## Workflow Audit Findings

### Currently in /archive (already moved — no action needed)
- `Master Orchestrator.json` — v1 orchestrator, superseded by v3
- `Master Orchestrator v2.0.json` — superseded by v3
- `Master Orchestrator v2.2.json` — superseded by v3
- `Master Orchestrator v3.0.json` — superseded by `08_Orchestrator_v3.json`
- `02_Topic_Research_Engine.json` — superseded by `content/02_Topic_Research_Engine_v2.json`
- `03_Affiliate_Research.json` — superseded by `affiliate/10_Affiliate_Research_v2.json`
- `04_Social_Formatter_PartA.json` — superseded by `social/04_Social_Formatter_v2.json`
- `05_Queue_Processor_PartB.json` — superseded by `social/11_Queue_Processor_v2.json`
- `Agentic Team Orchestrator.json` — experimental, superseded by `08_Orchestrator_v3.json`
- `Auto Video Creation.json` — v3.0, superseded by `social/14_Video_Production.json`
- `Content Writer (Ollama Agent).json` — standalone agent, functionality now in orchestrator

### Active workflows — no archive candidates identified

All workflows in `core/`, `content/`, `social/`, `affiliate/`, `monitoring/`, `growth/`, `email/` appear to be either:
- The canonical current version (no newer replacement exists), or
- Phase 2–6 activation targets (out of scope for Phase 1 archiving)

The `content/v3.0 — Content Repurposing Engine.json` and `content/v4.0 — SEO Interlinking Intelligence Engine.json` have no superseding version in the active dirs and should not be archived.

**Phase 1 audit action:** Verify that `core/`, `content/`, `social/`, `affiliate/`, `monitoring/`, `growth/`, `email/` contain no JSON files that duplicate functionality of a newer file in the same directory. Based on current inspection, no additional files need archiving.

---

## Code Examples

### Content Log Assembly Node (full working pattern)
```javascript
// Source: synthesized from existing Assemble Final Article + Parse & Validate patterns
// 📋 Assemble Content Log Row — insert before 📊 Log to Google Sheets

const article  = $('🔧 Assemble Final Article').item.json;
const topic    = $('✅ Parse & Validate Topic').item.json;
const qc       = $('✅ Parse & Validate QC').item.json;
const config   = $('⚙️ Load Config').item.json;

// Detect WP publish result - native WP node returns post object with 'id' and 'link'
let wpUrl = '', wpId = '';
try {
  const wp = $('📝 Publish to WordPress').item.json;
  wpUrl = wp.link || wp.url || '';
  wpId  = wp.id   || '';
} catch(e) {}

// Collect parse_error_agents
const errorAgents = [];
try { if ($('📋 Parse Prompt Package').item.json.parse_error)  errorAgents.push('agent_0'); } catch(e) {}
try { if (topic.parse_error || topic.status === 'fallback_topic') errorAgents.push('agent_1'); } catch(e) {}
try { if (article.parse_error) errorAgents.push('agent_4'); } catch(e) {}
try { if (qc.parse_error)      errorAgents.push('agent_5'); } catch(e) {}

const publishFailed = !wpUrl;

return [{ json: {
  date:               new Date().toISOString().split('T')[0],
  title:              article.title || '',
  primary_keyword:    topic.primary_keyword || '',
  wp_url:             wpUrl,
  wp_post_id:         String(wpId),
  word_count:         article.word_count || 0,
  status:             publishFailed ? 'publish_failed' : 'published',
  parse_error:        errorAgents.length > 0,
  parse_error_agents: JSON.stringify(errorAgents),
  qc_score:           qc.average_score || '',
  affiliate_ctas:     article.affiliate_ctas || 0,
  run_timestamp:      new Date().toISOString()
}}];
```

### Alert Handler Call Pattern (with populated inputs)
```javascript
// When calling Alert Handler from QC rejection path
// workflowInputs.value must be populated:
{
  "level": "WARNING",
  "workflow_name": "HowTo-Genie Orchestrator v3",
  "node_name": "QC Approved?",
  "error_message": "={{ 'QC rejected: ' + $('✅ Parse & Validate Topic').item.json.topic + ' — score: ' + $('✅ Parse & Validate QC').item.json.average_score }}"
}
```

### Backlog Rejection Update (for Approval Poller path)
```javascript
// 📋 Build Backlog Rejection Update
// Only run when topic came from backlog (has _row number)
const topic       = $('✅ Parse & Validate Topic').item.json;
const useApproved = $('💉 Inject Approved Topic').item.json.use_approved_topic;

if (!useApproved || !topic._row) {
  // Reddit-sourced topic — no backlog row to update
  return [{ json: { skip_backlog_update: true } }];
}

return [{ json: {
  Row_Number: topic._row,
  status:     'rejected',
  Status:     'rejected'
}}];
```

---

## State of the Art

| Old Approach | Current Approach | Impact |
|--------------|------------------|--------|
| All agents as httpRequest to Ollama | Agent 0 uses executeWorkflow (Central sub-wf); Agents 1–5 still httpRequest | Agents 1–5 need migration |
| QC check: raw string contains | Should be: parse JSON, check boolean field | Current check is fragile |
| No Content Log assembly node | 4 parallel API responses write to Sheets | Log currently has wrong/incomplete data |
| WP publish as httpRequest, no retry | Should use native WP node with retryOnFail | Current publish has no fault tolerance |
| QC rejection: dead-end executeWorkflow | Should route to Sheets + Backlog update + Telegram | Rejections are currently silent |

---

## Open Questions

1. **_row tracking for Reddit-sourced topics**
   - What we know: `_row` exists on Approval Poller path. Reddit path never sets `_row`.
   - What's unclear: Should Agent 1 write the selected topic back to a Blog Idea Backlog row when picking from Reddit, so it can be marked rejected if QC fails?
   - Recommendation: Keep it simple — only update the Backlog row on QC rejection when `use_approved_topic: true`. Reddit-sourced rejections are logged to Rejected Posts tab only (no Backlog update). Agent 1 already avoids duplicate topics via the `existing_keywords` list.

2. **native WordPress node field names** — RESOLVED
   - Verified: The native WordPress node uses **`featuredMediaId`** (camelCase, number) under `additionalFields`. Confirmed via n8n MCP get_node (nodes-base.wordpress). In the schema inspected, it appears for **page** create/update; for **post** create, the planner should verify in the n8n UI at implementation time (node version may add it for posts).

3. **Blotato and Calendar trigger sequencing post-WP**
   - What we know: Currently both fire unconditionally in parallel from WP publish result.
   - What's unclear: After adding `Publish Succeeded?` IF gate, should Calendar write still happen on `publish_failed`? (Calendar row would have no WP URL.)
   - Recommendation: Only write to Blog Calendar on successful publish. On `publish_failed`, the calendar gets no entry — the owner sees a gap in the weekly view, which is actually a useful signal.

---

## Validation Architecture

> `workflow.nyquist_validation` is `true` in `.planning/config.json` — this section is included.

### Test Framework

n8n workflows cannot be unit-tested with a traditional test framework. Validation for this phase is execution-based: import the modified JSON, trigger manually, inspect node outputs in the n8n execution panel and Google Sheets.

| Property | Value |
|----------|-------|
| Framework | n8n manual execution (no automated test runner) |
| Config file | None — test via n8n UI execution panel |
| Quick run command | Trigger "Execute Workflow" on `⚙️ Load Config` node directly |
| Full suite command | Enable schedule trigger, wait for 8 AM run, inspect execution log |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | How to Verify | Currently Exists? |
|--------|----------|-----------|---------------|------------------|
| PIPE-01 | Orchestrator completes daily without intervention | Smoke | Check n8n execution history shows success at 8 AM | Partial (schedule exists, but Content Log row is wrong) |
| PIPE-02 | LLM parse failure → fallback + continue + log | Manual | In n8n, pin a Code node to return `{}` (empty/invalid JSON), run execution, verify `parse_error: true` appears in Content Log row | Not yet (Log assembly node missing) |
| PIPE-02 | `parse_error_agents` array identifies which agents failed | Manual | Same as above — confirm the array contains the expected agent name | Not yet |
| PIPE-03 | WP publish retries on transient failure | Manual | Temporarily set WP credentials to wrong password, run, verify 3 retry attempts in execution log and `publish_failed` in Sheets | Not yet (no retry configured) |
| PIPE-03 | `publish_failed` status blocks satellite triggers | Manual | After forced WP failure, confirm Blotato and Calendar nodes did NOT execute | Not yet (no gate exists) |
| PIPE-04 | QC rejection writes to Rejected Posts tab | Manual | Pin Agent 5 response to `{"approved": false, ...}`, run, verify row appears in Rejected Posts sheet | Not yet (tab missing, path dead-ends) |
| PIPE-04 | QC rejection updates Blog Idea Backlog (for backlog topics) | Manual | Use Approval Poller to feed an approved topic, force QC rejection, verify Backlog row status becomes `rejected` | Not yet |
| PIPE-04 | QC rejection sends Telegram alert | Manual | Same scenario, verify Telegram message received with topic name and score | Not yet (dead-end) |
| PIPE-05 | Config change takes effect without re-import | Manual | Change `QC_MIN_SCORE` in n8n data table, run orchestrator, verify QC rubric in Agent 5 prompt uses new value | Likely already working (Config Loader confirmed first) |

### Per-Failure-Mode Test Scenarios

**Scenario A — Agent 1 LLM returns malformed JSON:**
1. In n8n, open `✅ Parse & Validate Topic` node
2. Pin the `🔍 Agent 1: Research & Topic` output to `{ "message": { "content": "not valid json" } }`
3. Run execution
4. Expected: Parse & Validate Topic falls back to hardcoded defaults; `parse_error: true` in fallback object; Content Log row shows `parse_error: true`, `parse_error_agents: ["agent_1"]`; article publishes with fallback topic

**Scenario B — WordPress publish fails all 3 retries:**
1. Temporarily set WordPress credential to invalid password
2. Run execution
3. Expected: 3 retry attempts visible in execution log (each failing); `status: publish_failed` in Content Log row; Telegram alert received; Blotato and Calendar nodes NOT executed

**Scenario C — QC rejects the article:**
1. Pin `🛡️ Agent 5: Quality Control` output to `{ "message": { "content": "{\"approved\": false, \"average_score\": 4.5, \"rejection_reason\": \"word count too low\", \"scores\": {}}" } }`
2. Run execution
3. Expected: `✅ Parse & Validate QC` parses JSON correctly; `✅ QC Approved?` routes to false branch; row appears in Rejected Posts sheet with score 4.5 and reason; Telegram alert sent; NO WordPress publish

**Scenario D — Normal successful run (regression):**
1. Run orchestrator with all real credentials active
2. Expected: Full 8-agent chain completes; WordPress post published; Content Log row has `status: published`, correct title/keyword/WP URL; Telegram success alert sent; Blotato queued; Calendar row written

### Wave 0 Gaps

- [ ] `Rejected Posts` tab must exist in Google Sheets before testing PIPE-04
- [ ] `REJECTED_POSTS_TAB` key must be added to n8n `htg_config` data table before the orchestrator can reference it
- [ ] Ollama Agent (Central) credential `YOUR_OLLAMA_CREDENTIAL_ID` must be replaced with real Ollama credential ID before Agent migration is testable

---

## Sources

### Primary (HIGH confidence)
- Direct inspection of `core/08_Orchestrator_v3.json` — all 37 nodes, all connections, all Code node contents
- Direct inspection of `core/Ollama Agent (Central).json` — input field definitions, Ollama Chat Model parameters
- Direct inspection of `core/01_Config_Loader.json` — data table IDs, merge logic, output structure
- Direct inspection of `core/07_Approval_Poller.json` — Backlog update pattern, row number tracking
- Direct inspection of `monitoring/Alert_Handler.json` — expected input fields, Config Loader call pattern
- Direct inspection of `htg_config.csv` — all config keys, no REJECTED_POSTS_TAB present
- Direct inspection of `.planning/config.json` — nyquist_validation: true confirmed
- n8n retry limits: GitHub n8n-io/n8n#9458 (maxTries 5, waitBetweenTries 5000 ms); docs.n8n.io rate-limits
- n8n WordPress node: user-n8n-mcp get_node (nodes-base.wordpress) — additionalFields.featuredMediaId for page; post create verify in UI
- n8n Execute Workflow: user-n8n-mcp get_node (nodes-base.executeWorkflow) — options.waitForSubWorkflow boolean, default true

### Secondary (MEDIUM confidence)
- None remaining after verification pass

### Tertiary (LOW confidence)
- None. executeWorkflow `waitForSubWorkflow: false` behavior confirmed via n8n MCP get_node (option exists, description: main workflow does not wait for sub-workflow completion); downstream implication (no output propagated) is standard async behavior.

---

## Metadata

**Confidence breakdown:**
- Current state audit: HIGH — based on direct JSON inspection of all files
- Standard stack: HIGH — all node types confirmed from existing orchestrator
- Architecture patterns: HIGH — patterns derived from existing working nodes in same orchestrator
- Retry mechanism: HIGH — n8n limits (maxTries ≤5, waitBetweenTries ≤5000 ms) verified via GitHub #9458 and docs; 30s delay requires custom Wait+Loop if mandated
- WordPress node: HIGH — featuredMediaId (camelCase) verified via n8n MCP get_node; post create support for featuredMediaId may vary by node version — verify in UI
- Pitfalls: HIGH — all confirmed by direct code inspection; executeWorkflow async behavior confirmed via MCP

**Research date:** 2026-03-12
**Verified/extended:** 2026-03-12 (retry limits, WP field name, executeWorkflow option)
**Valid until:** 2026-06-12 (90 days — n8n node API is stable; orchestrator unlikely to change independently)
