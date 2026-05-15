# Sauti Networks — Climate Intelligence Over USSD

Zero-bandwidth delivery of agricultural and marine advisories to feature phone users in East Africa.

Built on [Africa's Talking](https://africastalking.com) USSD and Voice APIs.

## What This Is

A bilingual (English / Kiswahili) USSD and voice interface that delivers real-time climate intelligence to farmers and fishermen on $15 feature phones. No smartphone. No data bundles. No literacy required.

Supports three farm types:
- **Coastal/Marine** — aquaculture, fishing conditions, blue carbon
- **Soil/Regenerative** — soil health, carbon sequestration, supply chain verification
- **Terrestrial** — crop advisories, pest/disease modeling, climate adaptation

## Architecture

```
Farmer dials *384# on feature phone
         ↓
  Africa's Talking USSD Gateway
         ↓
  ussd.php — bilingual menu navigation
         ↓
  Advisory Intelligence API (external, not included)
         ↓
  voice-callback.php — TTS delivery + interactive GetDigits
         ↓
  Farmer receives voice call with advisory
```

The advisory engine is not included in this repository.

## Setup

1. Copy `config.example.php` → `config.php`
2. Add your Africa's Talking credentials and advisory API endpoint
3. Point AT USSD webhook → `api/ussd.php`
4. Point AT Voice callback → `api/voice-callback.php`

## Requirements

- PHP 7.4+
- cURL extension
- Africa's Talking account with voice number
- Your own advisory data API

## License

MIT

---

Built by [GrafikInc](https://grafikinc.com) in Kilifi, Kenya.
