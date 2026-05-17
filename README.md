# AgroFutures — Climate Intelligence Over USSD

**Zero-bandwidth AI-powered advisories delivered to feature phones in East Africa.**

Built on [Africa's Talking](https://africastalking.com) USSD and Voice APIs.

## What This Is

A bilingual (English / Kiswahili) USSD and voice interface that delivers **AI-generated climate intelligence** to farmers and fishermen on $15 feature phones.

**No smartphone. No data bundles. No literacy required.**

### The AI Layer

Users trigger real-time AI advisory generation via USSD menu selections. The system:

- Queries current weather, oceanographic, and satellite data
- Runs biological simulation models (crop physiology, pest cycles, marine conditions)
- Generates personalized advisories using LLMs
- Delivers via **automated voice callback** in the user's native language

**This brings the entire body of agricultural and marine knowledge — synthesized by AI in real-time — to any feature phone user in their own language.**

### Supported Farm Types

- **Coastal/Marine** — aquaculture species (seaweed, oysters, crab), fishing zones, blue carbon tracking
- **Soil/Regenerative** — soil health metrics, carbon sequestration, EU Digital Product Passport compliance
- **Terrestrial** — crop advisories (maize, olives, cotton), pest/disease modeling, climate adaptation pivots

## Architecture

```text
Farmer dials *384# on feature phone
         ↓
  Africa's Talking USSD Gateway
         ↓
  ussd.php — bilingual menu navigation (EN/SW)
         ↓
  Advisory Intelligence API (AI generation + biological models)
       • Fetches real-time weather/ocean data
       • Runs species-specific state machines
       • Generates advisory via LLM
       • Stores in KV for voice delivery
         ↓
  voice-callback.php — TTS delivery + interactive GetDigits
         ↓
  Farmer receives automated call with personalized advisory
```

**The advisory intelligence API (biological models, AI generation, data pipelines) is proprietary and not included in this repository.**

This repo contains only the USSD/Voice interface layer.

## Key Features

✅ **AI-Triggered Advisory Generation** — Users request updates via USSD, system generates fresh advisories  
✅ **Bilingual Navigation** — English and Kiswahili menu options  
✅ **Voice Callbacks with TTS** — Automated calls in user's language (sw-KE, luo-KE, kik-KE, en-US)  
✅ **Interactive Voice Menus** — GetDigits for drill-down navigation via phone keypad  
✅ **Multi-Farm Support** — Coastal, soil/regenerative, and terrestrial farm types  
✅ **182-Character USSD Constraint Handling** — Intelligent text truncation for legacy networks  
✅ **Priority-Based Voice Summarization** — Safety alerts first, top 3 actionable items

## What Makes This Different

**Not a static knowledge base.** Every advisory is generated on-demand using:

- Current weather/ocean conditions
- Satellite imagery (NDVI, SST, chlorophyll)
- Species-specific biological models
- AI synthesis of best practices

**Example flow:**

1. Fisherman dials `*384#` → selects "Faza Marine" → "Update All Data"
2. System fetches: Sea surface temperature (30°C), chlorophyll levels (high bloom), wave conditions
3. AI generates: "Fishing conditions SAFE-GO. Target Kiwayu Channel at dawn for kingfish. Avoid reef zones due to thermal stress."
4. System calls fisherman's phone → plays advisory in Swahili
5. Fisherman presses `1` to hear seaweed advisory, `2` for crab conditions, etc.

**All with zero data cost. All on a $15 Nokia.**

## Setup

1. Copy `config.example.php` → `config.php`
2. Add your Africa's Talking credentials and advisory API endpoint
3. Point AT USSD webhook → `https://yourdomain.com/api/ussd.php`
4. Point AT Voice callback → `https://yourdomain.com/api/voice-callback.php`

## Requirements

- PHP 7.4+
- cURL extension
- Africa's Talking account (sandbox or production)
- Africa's Talking voice-enabled phone number
- Your own advisory intelligence API

## File Structure

```text
agrofutures-ussd/
├── README.md
├── LICENSE (MIT)
├── .gitignore
├── config.example.php
└── api/
    ├── ussd.php           # USSD menu handler
    └── voice-callback.php  # Voice response + TTS
```

## Deployment Status

**Currently:** Live on Africa's Talking sandbox (testing)  
**Next:** Production carrier deployment (Safaricom, MTN, Vodacom)  
**Languages:** 4 supported (en, sw, luo, kik) — expanding to 12  
**Addressable Market:** 300M feature phone users (East Africa)

## Use Cases

### Coastal Communities

- Real-time fishing advisories (safe zones, target species, weather conditions)
- Aquaculture health alerts (thermal stress, disease risk, harvest timing)
- Blue carbon tracking (mangrove/seaweed CO₂ sequestration for carbon credits)

### Soil/Regenerative Farmers

- EU Digital Product Passport compliance (fashion supply chain verification)
- Soil health monitoring (organic matter, nitrogen, pH)
- Carbon sequestration measurement (soil carbon credits)

### Smallholder Farmers

- Crop yield predictions (based on rainfall, temperature, soil conditions)
- Pest/disease outbreak warnings (timed to breeding cycles)
- Climate adaptation pivots (long-term crop transition recommendations)

## Why Feature Phones Matter

**96% of rural East African farmers use basic feature phones** due to:

- Cost ($15 Nokia vs $80+ smartphone)
- Data bundle expenses (prohibitive for daily use)
- Network coverage (2G available, 4G spotty)
- Durability (feature phones last years in harsh conditions)

**Furthermore, functional literacy among rural smallholder farmers is critically low.** They struggle to read fertilizer labels, let alone AI-generated text in smartphone apps.

**Voice delivery solves both problems:** No data required. No reading required. Just listen.

## The AI Advantage

Traditional agricultural extension systems deliver **static knowledge** (PDFs, printed manuals, recorded videos).

This system delivers **dynamic intelligence:**

- Advisories generated fresh based on today's conditions
- Personalized to the user's specific location and crops
- Grounded in real-time data (not outdated extension manuals)
- Synthesized by AI from the full corpus of agricultural knowledge

**Example of AI vs. Static Content:**

**Static PDF (2015):** "Plant olives in Mediterranean climates."

**AI Advisory (2026):** "Don't plant olives this year. Your winter reservoir is 33mm (needs 60mm minimum). Mediterranean olive yields have collapsed to 35% due to aquifer depletion and summer heatwaves. Pivot to pistachios (€8.5K/ha) or saffron (€60K/ha) — both are dormant in summer heat and harvest in autumn moisture. Contact us for 5-year climate transition planning."

## Open Source Commitment

This repository is **MIT licensed** to support the developer community building on Africa's Talking infrastructure.

**What's included:** USSD navigation, voice callback handlers, bilingual menu logic  
**What's not included:** Advisory intelligence API, biological models, AI generation pipelines

We believe in **open plumbing, proprietary intelligence.** Fork this repo to build your own USSD/Voice apps on AT infrastructure.

## Demo

**2-minute video:** [Coming Soon]

**Live on AT simulator:** [Coming Soon]

## Contact

Built by [GrafikInc](https://grafikinc.com) in Kilifi, Kenya.

**For partnerships, technical questions, or production deployment support:**  
**Email:** [jason@mcguiness.design](mailto:jason@mcguiness.design)  
**Website Consulting:** [grafikinc.com](https://grafikinc.com)  
**Website Portfolio:** [mcguiness.design](https://mcguiness.design)  
**GitHub:** [https://github.com/grafikinc/africas-talking-agtech/](https://github.com/grafikinc/africas-talking-agtech/)

---

**Note:** This system is designed for the African continent where 300+ million people have feature phones but are excluded from smartphone-first AI applications. If you're building for similar markets (South Asia, Latin America, rural US), this architecture is directly applicable.

## License

MIT License — see `LICENSE` file

---

*"This brings the entire body of agricultural and marine knowledge — synthesized by AI in real-time — to any feature phone user in their own language."*
