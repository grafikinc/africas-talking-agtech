# AI Delivery Over USSD & SMS from Feature Phones to Smartphones

**AI delivery over 2G. No smartphone. No data plan. No app.**

## The Problem

3.4 billion people don't use mobile internet. Not because there's no coverage — 96% of the world's population is covered by mobile broadband — but because every product built on top of it assumes a smartphone, a data plan, and literacy. In Sub-Saharan Africa, the usage gap is 60%. The intelligence exists. The pipe doesn't.

## The Solution

We built the pipe.

AgroFutures delivers real-time, AI-generated intelligence to $15 feature phones over 2G. A farmer dials a shortcode, the system generates a personalized advisory from live weather, satellite, and biological model data, and calls them back with it spoken in their language.

USSD for input. Voice callback for output. Works on any phone manufactured in the last 25 years. Zero data cost. Zero literacy requirement.

Agriculture is the first vertical. The rail is domain-agnostic — a second vertical (senior health/companionship, US, SMS via Twilio) runs on the same architecture: [twilio-senior-health-2g-AI-access](https://github.com/grafikinc/twilio-senior-health-2g-AI-access).

```
User dials *384# on any feature phone
       ↓
Africa's Talking USSD Gateway (2G)
       ↓
Menu navigation layer (bilingual, 182-char constraint handling)
       ↓
Intelligence API
  · Ingests real-time data (weather, satellite, ocean, soil)
  · Runs domain-specific biological state machines
  · Generates personalized advisory via LLM
  · Stores in Cloudflare KV for voice delivery
       ↓
Voice callback — TTS in user's language + interactive keypad menus
       ↓
User receives automated call with AI-generated response
```

## Proof

This is production output from the system, generated 2026-06-04 for Gachororo Community Farm (Murang'a County, Kenya) at grain fill stage:

```json
{
  "crop": "Maize",
  "headline": "Gachororo Community Farm at Grain Fill: Do not irrigate, scout for FAW/MLND at dawn.",
  "water": "YTD 637mm vs 409mm = +228mm SURPLUS. Soil saturated at 64%. Do NOT irrigate. Ensure drainage on 12% slope.",
  "pest": "Fall Armyworm & MLND risk: humidity 70% near trigger, temps 26°C ideal. Scout leaf whorls 6-8 AM for frass. Apply treatment within 7 days if first instar detected.",
  "generated_at": "2026-06-04T01:41:26.809Z"
}
```

That advisory was generated from live conditions, pushed to Cloudflare KV, and delivered as a voice callback in Kikuyu. On a $15 Nokia. Over 2G.

**150,000 registered farmers. 4 climate zones. 4 languages. Live.**

---

## Supported Farm Types

**Coastal / Marine** — Aquaculture (seaweed, oysters, crab), fishing zone advisories, blue carbon MRV

**Soil / Regenerative** — Soil health, carbon sequestration, EU Digital Product Passport compliance

**Terrestrial** — Crop advisories (maize, olives, cotton), pest/disease modeling, climate adaptation pivots

## The Intelligence Layer (Proprietary)

Not in this repo. What it does:

- Queries real-time weather, oceanographic, NDVI/SST/chlorophyll satellite data
- Runs species-specific biological state machines (crop physiology, pest cycles, marine conditions)
- Generates personalized advisories via LLM
- Outputs to Cloudflare KV for voice delivery
- 4 languages live (en, sw, luo, kik) — expanding to 12+

## What's in This Repo

Open-source USSD/voice interface layer. MIT licensed.

```
agrofutures-ussd/
├── README.md
├── LICENSE (MIT)
├── .gitignore
├── config.example.php
└── api/
    ├── ussd.php              # USSD menu handler
    └── voice-callback.php    # Voice response + TTS
```

### Setup

1. Copy `config.example.php` → `config.php`
2. Add Africa's Talking credentials and intelligence API endpoint
3. Point AT USSD webhook → `https://yourdomain.com/api/ussd.php`
4. Point AT Voice callback → `https://yourdomain.com/api/voice-callback.php`

### Requirements

- PHP 7.4+, cURL extension
- Africa's Talking account (sandbox or production)
- AT voice-enabled phone number
- Your own intelligence API

## Deployment

**Live**: Africa's Talking sandbox
**Next**: Production carrier deployment (Safaricom, MTN, Vodacom)
**Languages**: 4 live, 12+ planned
**Users**: 150,000 registered (Murang'a County, Kenya)
**Zones**: 4 validated climate zones

## Open Source

Open plumbing, proprietary intelligence. Fork this to build USSD/voice applications on Africa's Talking infrastructure.

The architecture applies anywhere feature phones dominate. 300M+ addressable users in East Africa alone.

## Demo

[Africa's Talking Walkthrough](https://www.dropbox.com/scl/fi/krltg7d03tldp5yab4lmm/03-MultiLang-Menu.mp4?rlkey=l0t1gkshihjjwla7lpl4cf9ty&st=o5j4j3kj&dl=0)

## Contact

Built by [GrafikInc](https://grafikinc.com) in Kilifi, Kenya.

**Email**: jason@mcguiness.design
**Web**: [grafikinc.com](https://grafikinc.com) · [mcguiness.design](https://mcguiness.design)
**GitHub**: [github.com/grafikinc/africas-talking-agtech](https://github.com/grafikinc/africas-talking-agtech/)

## License

MIT
