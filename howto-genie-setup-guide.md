# 🧞‍♂️ HowTo-Genie v2.0 — Setup & Architecture Guide

## 🗺️ System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                    HOWTOGENIE v2.0 AGENT PIPELINE                  │
│                                                                     │
│  ⏰ Daily 8AM                                                        │
│       ↓                                                             │
│  🧠 Agent 0: PROMPT ENGINEER ← Designs all downstream prompts       │
│       ↓                                                             │
│  🔍 Agent 1: RESEARCH → Trending topics + affiliate products        │
│       ↓                                                             │
│  ✍️  Agent 2: CONTENT WRITER → 2800+ word article                   │
│       ↓                                                             │
│  🫂 Agent 3: HUMANIZER → Sounds like expert human blogger           │
│       ↓                                                             │
│  🎯 Agent 4: SEO + MONETIZATION → Meta, tags, affiliate CTAs        │
│       ↓                                                             │
│  🛡️  Agent 5: QUALITY CONTROL → Approves or rejects                 │
│       ↓ (Approved)                                                  │
│  🖼️  IMAGE FINDER → Pexels → Pixabay → Stable Diffusion fallback    │
│       ↓                                                             │
│  📝 WORDPRESS PUBLISHER → Publishes with full SEO meta              │
│       ↓                          ↓                                 │
│  📱 Agent 6: SOCIAL + REELS   💬 Agent 7: COMMENT MODERATOR        │
│       ↓                          ↓                                 │
│  FB / IG / TikTok /           Auto-replies to comments             │
│  Pinterest / YT Shorts                                              │
│       ↓                                                             │
│  📊 GOOGLE SHEETS LOG → Full content + performance tracking         │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🤖 The 8-Agent Team (Roles & Responsibilities)

| # | Agent | Model | Temp | Role |
|---|-------|-------|------|------|
| 0 | Prompt Engineer | llama3.2 | 0.4 | Designs all prompts dynamically each run |
| 1 | Research & Topic | llama3.2 | 0.7 | Finds trending topics + affiliate products |
| 2 | Content Writer | llama3.2 | 0.8 | Writes 2800+ word structured article |
| 3 | Humanizer | llama3.2 | 0.9 | Makes content sound like expert human |
| 4 | SEO & Monetization | llama3.2 | 0.4 | Meta, tags, affiliate CTAs, schema |
| 5 | Quality Control | llama3.2 | 0.3 | Approves/rejects with scored rubric |
| 6 | Social Media + Reels | llama3.2 | 0.9 | Creates content for all 5 platforms |
| 7 | Comment Moderator | llama3.2 | 0.7 | Reviews, classifies, replies to comments |

---

## 💰 Monetization Strategy

### Affiliate Networks Configured
| Network | Setup URL | Commission Range | Best For |
|---------|-----------|-----------------|----------|
| ClickBank | clickbank.com/affiliates | 50–75% | Info products, courses |
| JVZoo | jvzoo.com | 50–100% | Software, digital |
| Digistore24 | digistore24.com/affiliates | 30–70% | European + global |
| Muncheye | muncheye.com | Varies | Launch-jacking strategy |

### Ad Networks
| Network | Approval Speed | Best Format | Revenue Tier |
|---------|---------------|-------------|--------------|
| Adsterra | 1–2 days | Native + Popunder | $$$ |
| Google AdSense | 2–4 weeks (needs 25+ posts) | Display + In-article | $$$$ |

### Ad Zone Placement Logic
```
[Article Start]
  Introduction paragraph
  [AD_ZONE_TOP]    ← AdSense Auto or Adsterra Native
  Table of Contents
  ... Sections 1–4 ...
  [AD_ZONE_MID]    ← AdSense In-Article + Adsterra
  ... Sections 5–8 ...
  [AFFILIATE_CTA]  ← Product recommendation block
  [AD_ZONE_BOTTOM] ← AdSense Display
  Conclusion + FAQ
[Article End]
```

### Digistore24 Link Format
```
https://www.digistore24.com/redir/[PRODUCT_ID]/[YOUR_AFFILIATE_ID]
```
Replace `YOUR_AFFILIATE_ID` with your Digistore24 username in the Affiliate Link Database node.

---

## 🖼️ Image Pipeline Logic

```
Article topic / keyword
        ↓
  Build search queries
        ↓
  ┌─────────────┐     Found?  ✅ → Use image
  │   PEXELS    │
  └─────────────┘     
        ↓ Not found
  ┌─────────────┐     Found?  ✅ → Use image
  │  PIXABAY    │
  └─────────────┘
        ↓ Not found
  ┌──────────────────┐
  │ Ollama BakLLaVA  │  → Generate AI image description
  │ or Stable Diff   │  → POST to SD WebUI API
  └──────────────────┘
        ↓
  Upload to WordPress Media Library
```

**Free API Keys:**
- Pexels: https://www.pexels.com/api/ (Free, 200 req/hour)
- Pixabay: https://pixabay.com/api/docs/ (Free, 100 req/min)

**Stable Diffusion Local Setup:**
```bash
# If using AUTOMATIC1111 locally
git clone https://github.com/AUTOMATIC1111/stable-diffusion-webui
cd stable-diffusion-webui
# Start with API enabled
./webui.sh --api --listen

# API endpoint will be at:
# http://localhost:7860/sdapi/v1/txt2img
```

---

## 📱 Social Media Platform Guide

### Facebook
- **Post type:** Link preview with engaging caption
- **Best time:** 7–9 PM local time
- **Credential:** Facebook Graph API (Business account required)
- **Page ID:** Found in your Facebook Page settings → About

### Instagram
- **Post type:** Image + caption (link in bio)
- **API:** Instagram Graph API (requires Facebook Business account)
- **Setup:** Connect IG Professional account to Facebook Page
- **Note:** Reels require video file upload via separate tool or make.com

### TikTok
- **Post type:** Script queued → manual video creation recommended
- **API:** TikTok Content Posting API v2
- **Script delivery:** Saved to Google Sheets "Reels Scripts" tab
- **Best approach:** Use CapCut or InVideo.io to auto-generate video from script

### Pinterest
- **Post type:** Pin with image + description + link
- **Credential:** Pinterest OAuth2 API
- **Board:** Automatically suggested by Agent 6
- **Image:** Featured image from Pexels/Pixabay

### YouTube Shorts
- **Post type:** Script saved to queue (video creation needed)
- **Upload:** YouTube Data API v3 (video file upload)
- **Recommended tool:** Use InVideo.io or Pictory.ai with script
- **Sheet tab:** "YT Shorts Queue" in Google Sheets

### Reel / Short Universal Script Format
Each post gets a **scene-by-scene reel script** with:
- Hook (0–3s): Stop-scroll text overlay
- Problem (3–10s): Relatable pain point
- Solution (10–25s): Key steps teased
- Value (25–45s): Quick wins shown
- CTA (45–60s): "Link in bio → Full guide on HowTo-Genie"

---

## 💬 Comment Moderation System

**Classification System:**
| Type | Action | Response Style |
|------|--------|---------------|
| Genuine question | Approve + Reply | Helpful, detailed |
| Praise | Approve + Reply | Warm, grateful |
| Negative/complaint | Approve + Reply | Empathetic, solution-focused |
| Spam | Mark as Spam | No reply |
| Gibberish | Trash | No reply |
| Self-promotion | Trash | No reply |

**Comment Workflow Schedule:**
- Main workflow: Runs at 8 AM daily (publishes new content)
- Comment workflow: Separate trigger, runs every 2 hours
- Connect to main workflow with: `Workflow` node → "Execute Workflow"

---

## 🛠️ Complete Setup Checklist

### Phase 1: Core Infrastructure
- [ ] Install n8n (self-hosted recommended for this workflow size)
- [ ] Install Ollama: `curl -fsSL https://ollama.ai/install.sh | sh`
- [ ] Pull model: `ollama pull llama3.2:latest`
- [ ] Install WordPress with a fast theme (Astra, GeneratePress)
- [ ] Install plugins: Yoast SEO, WP Rocket, ShortPixel

### Phase 2: API Credentials to Collect
- [ ] **WordPress:** Application Password (Users → Profile → App Passwords)
- [ ] **Pexels:** Free key at pexels.com/api
- [ ] **Pixabay:** Free key at pixabay.com/api/docs
- [ ] **Facebook:** Business App + Page Access Token
- [ ] **Instagram:** Graph API token (via Facebook Developer)
- [ ] **Pinterest:** OAuth2 App at developers.pinterest.com
- [ ] **TikTok:** Content Posting API at developers.tiktok.com
- [ ] **Google Sheets:** OAuth2 in n8n credentials
- [ ] **ClickBank:** Affiliate ID from clickbank.com
- [ ] **JVZoo:** Affiliate ID from jvzoo.com
- [ ] **Digistore24:** Affiliate ID from digistore24.com
- [ ] **Adsterra:** Publisher account + zone codes
- [ ] **AdSense:** Publisher account (apply after 25+ posts)

### Phase 3: Google Sheets Setup
Create a spreadsheet with these tabs:
1. **Content Log** — All published posts with status
2. **Reels Scripts** — Queue for TikTok/Shorts creation
3. **YT Shorts Queue** — YouTube Shorts scripts
4. **Affiliate Products** — Your active affiliate products DB
5. **Revenue Tracker** — Monthly income tracking

### Phase 4: WordPress Configuration
```php
// Add to functions.php — Auto ad injection
function howto_genie_inject_ads($content) {
  if (!is_single()) return $content;
  
  $paras = explode('</p>', $content);
  $count = count($paras);
  
  // AdSense slots (replace with your actual slot IDs)
  $ad1 = '<div class="ad-unit ad-top"><ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-XXXXXXXX" data-ad-slot="SLOT1" data-ad-format="auto"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script></div>';
  
  $ad2 = '<div class="ad-unit ad-mid"><ins class="adsbygoogle" style="display:block;text-align:center" data-ad-layout="in-article" data-ad-format="fluid" data-ad-client="ca-pub-XXXXXXXX" data-ad-slot="SLOT2"></ins><script>(adsbygoogle=window.adsbygoogle||[]).push({});</script></div>';
  
  // Adsterra native
  $adsterra = '<div class="adsterra-native"><!-- YOUR ADSTERRA NATIVE CODE --></div>';
  
  if ($count > 8) {
    array_splice($paras, 3, 0, [$ad1]);
    array_splice($paras, intval($count * 0.5), 0, [$ad2, $adsterra]);
  }
  
  return implode('</p>', $paras);
}
add_filter('the_content', 'howto_genie_inject_ads');
```

### Phase 5: Test & Launch
- [ ] Import JSON into n8n
- [ ] Replace ALL placeholder values (YOUR_XXX)
- [ ] Run manually once → Review Google Sheets log
- [ ] Check WordPress draft
- [ ] Review social posts in debug output
- [ ] Enable daily CRON trigger
- [ ] Monitor first 7 days closely

---

## ⚡ Performance Optimization

### Ollama Speed Tips
```bash
# Use GPU acceleration (NVIDIA)
ollama run llama3.2:latest --gpu

# Check what's running
ollama ps

# For faster lighter model (less quality but 3x speed)
ollama pull mistral:7b-instruct
```

| Setup | Avg per Agent | Full Pipeline |
|-------|-------------|---------------|
| CPU only (8GB RAM) | 3–5 min | 25–40 min |
| CPU + 16GB RAM | 2–3 min | 18–25 min |
| GPU (RTX 3080+) | 15–30 sec | 3–5 min |

### Cost Summary (Monthly)
| Item | Cost |
|------|------|
| Ollama (local) | $0 |
| n8n (self-hosted) | $0 or $20/mo cloud |
| WordPress hosting | $5–15/mo |
| Pexels/Pixabay | $0 |
| Total | ~$5–35/mo |

---

## 📈 Expected Revenue Timeline

| Month | Posts | Traffic Est. | Revenue Est. |
|-------|-------|-------------|-------------|
| 1–2 | 60 posts | 500–1K/mo | Adsterra $10–50 |
| 3–4 | 120 posts | 2K–5K/mo | Adsterra + Affiliates $100–500 |
| 5–6 | 180 posts | 8K–15K/mo | AdSense + Adsterra + Affiliates $500–2K |
| 12+ | 365 posts | 30K+/mo | $2K–10K+/mo potential |

> **Note:** Results depend on niche, keyword competition, and content quality. Affiliate commissions can significantly boost earnings per post.

---

## 🔗 Key Resource Links

- n8n Docs: https://docs.n8n.io
- Ollama Models: https://ollama.ai/library
- Pexels API: https://www.pexels.com/api/
- Pixabay API: https://pixabay.com/api/docs/
- Digistore24 Affiliates: https://www.digistore24.com/affiliates
- CBEngine: https://www.cbengine.com
- Muncheye: https://muncheye.com
- Adsterra Publishers: https://publishers.adsterra.com
- WP REST API Docs: https://developer.wordpress.org/rest-api/
