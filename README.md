# AI Delivery Over USSD & SMS from Feature Phones to Smartphones

**AI delivery over 2G. No smartphone. No data plan. No app. Utilizes existing infrastructure.**

## The Problem

[3.4 billion people don't use mobile internet.](https://www.forbes.com/sites/johnkoetsier/2026/02/18/34-billion-people-dont-have-access-to-mobile-internet-costing-the-global-economy-3-trillion/) Not because there's no coverage, as 96% of the world's population is covered by mobile broadband, but because every product built on top of it assumes a smartphone, a data plan, and literacy. In Sub-Saharan Africa, the usage gap is 60%. The intelligence exists. The pipe doesn't.

## The Solution

Work with what exists, and has existed for decades from infrastructure to mobile phones. 

AgroFutures delivers real-time, AI-generated crop intelligence from over 40 datapoints to any phone, from $15 feature phones to the latest iPhone. A farmer dials a shortcode, the system generates a personalized micro-advisory from live weather, satellite, and biological model data, and calls them back with it spoken in their language to help mitigate literacy issues.

USSD for input. Voice callback for output. Works on any phone manufactured in the last 25 years. Zero data cost. Zero literacy requirement.

Agriculture is the first vertical. The rail is domain-agnostic — a second vertical (senior health/companionship, US, SMS via Twilio) runs on the same architecture: [twilio-senior-health-2g-AI-access](https://github.com/grafikinc/twilio-senior-health-2g-AI-access).

```
User dials *384# on any feature phone
       ↓
Africa's Talking USSD Gateway (2G)
       ↓
Menu navigation layer (multilang, 182-char constraint handling)
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

[Africa's Talking](https://www.dropbox.com/scl/fi/pkksf1qkp1hni87mvmtfg/04-Africa-s-Talking-Luo.mp4?rlkey=3k09r81gmtqfuzhxrxrc1ypju&dl=0)

This is production output from the system, generated 2026-06-04 for Gachororo Community Farm (Kiambu County, Kenya) at grain fill stage:

```json
{
  "crop": "Maize",
  "headline": "Gachororo Community Farm at Grain Fill: Do not irrigate, scout for FAW/MLND at dawn.",
  "water": "YTD 637mm vs 409mm = +228mm SURPLUS. Soil saturated at 64%. Do NOT irrigate. Ensure drainage on 12% slope.",
  "pest": "Fall Armyworm & MLND risk: humidity 70% near trigger, temps 26°C ideal. Scout leaf whorls 6-8 AM for frass. Apply treatment within 7 days if first instar detected.",
  "generated_at": "2026-06-04T01:41:26.809Z"
}
```
```json
{
  "headline": "Pur ma Gachoro Pur: YTD 640.3mm kod 412.0mm = 228.3mm SURPLUS, kelo 68.8%.",
  "water": "YTD Cumulative: 640.3mm adier kod 412.0mm maber = 228.3mm SURPLUS. Kik pii; Kiyweyo piyo nikech lowo othiek.",
  "pest": "FAW & MLND: Chok Marach Nikech Lowech Othiek. Ng'iyo e wiye oduma okunyi 6-8. Tich gi biopesticide ka inen.",
  "microclimate": "Weche mag tong': piwuok piyo. Gero miter mar apar gi achiel. Kungo lowo mondo okony pi wuok.",
  "outlook": "Dwe mabiro: pi nok. Ikri gi Kudho Maromo gi joma nigi lowo mang'eny."
}
```
```json
{
  "headline": "Gachororo Community Farm: YTD 642.7mm actual vs 412.0mm ideal = 230.7mm SURPLUS; Yield Impact 68.8% - mũtĩ mũega wa mbembe ũrĩ na maaĩ maingĩ, rĩrĩa ũrĩ na mĩaka 67.",
  "water": "YTD Cumulative: 642.7mm actual vs 412.0mm ideal = 230.7mm SURPLUS. Tiga kũhũra maaĩ nĩgũkorwo tĩri ũrĩ na maaĩ maingĩ na ũrĩ hatarĩ ya mafuriko. Rĩrĩa ũrĩ na Ferralsols, maaĩ marathama na ihenya, no nĩ ũndũ wa maaĩ maingĩ, tiga kũhũra.",
  "pest": "Fall Armyworm na Maize Lethal Necrosis (MLND) nĩ ciamũrĩtwo nĩ ũrugarĩ wa heho (23.5°C) na unyihũ (75%). Rĩrĩa ũrĩ na mĩaka …wa kũhũra maaĩ, thima mĩtĩ ya mbembe kĩroko (6-8 AM) nĩguo wone thũmbĩ. Thũmbĩ ĩngĩoneka, hũra na dawa mbere ya mĩthenya 7.",
  "microclimate": "Ferralsols: tĩri ũrĩ na mĩnyoroko mĩega na maaĩ marathama na ihenya. Mũthemba wa tĩri ũrĩ na mũthirima wa 12% na ũrore wa gi…Kũrĩa kũrĩ na maaĩ maingĩ, tiga kũhũra na rĩrĩa ũrĩ na mĩaka 67, thima mĩtĩ nĩguo wone kana nĩ ĩrĩ na thina wa maaĩ maingĩ.",
  "outlook": "Mweri wa Mũgwanja (July) ũrĩ na maaĩ manini (7mm ideal). Tũma mĩtĩ ĩkũre na kũhũra tiga, no rĩrĩa ũrĩ na maaĩ maingĩ, tiga kũhũra. Thima mĩtĩ ya mbembe nĩguo wone kana nĩ ĩrĩ na thina wa thũmbĩ kana mĩrimũ."
}
```

That advisory was generated from live conditions, pushed to Cloudflare KV, and delivered as a voice callback in Kikuyu.

---


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
**Zones**: 4 validated climate zones

## Open Source

Open plumbing, proprietary intelligence. Fork this to build USSD/voice applications on Africa's Talking infrastructure.

The architecture applies anywhere feature phones dominate. 300M+ addressable users in East Africa alone.

## Contact

Built by [GrafikInc](https://grafikinc.com) in Kilifi, Kenya.

**Email**: jason@mcguiness.design
**Web**: [grafikinc.com](https://grafikinc.com) · [mcguiness.design](https://mcguiness.design)
**GitHub**: [github.com/grafikinc/africas-talking-agtech](https://github.com/grafikinc/africas-talking-agtech/)

## License

MIT
