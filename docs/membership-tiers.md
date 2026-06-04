# Membership Tiers Technical Guide

The membership tier system rewards active users with loyalty multipliers. Guests can view the tier structure to understand the benefits of joining.

## 🛠️ Public Endpoint
`GET /v1/tiers`

### Response Structure
```json
{
    "success": true,
    "data": {
        "tiers": [
            {
                "label": "Standard",
                "multiplier": 1.0,
                "requirements": "Default membership tier.",
                "benefits": "Basic access to arena facilities."
            },
            ...
        ],
        "family_tiers": [ ... ]
    }
}
```

## 🔐 Member Endpoint
`GET /v1/member/tier` (Requires Auth)

Returns the specific tier and multiplier assigned to the logged-in user.

## 📈 Tier Logic
| Tier | Requirement | Multiplier |
| :--- | :--- | :--- |
| **Standard** | 0-1 Active Subscriptions | 1.0x |
| **Plus** | 2 Active Subscriptions | 1.2x |
| **Ultra** | 3 Active Subscriptions | 1.5x |
| **Max** | 4+ Active Subscriptions | 2.0x |
