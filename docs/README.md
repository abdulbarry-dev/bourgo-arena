# Bourgo Arena: Mobile API Documentation

This folder contains the technical specifications for integrating the Bourgo Arena backend with the mobile application.

## 📖 Available Guides

1.  **[Guest Access & Auth Flow](./mobile-api-integration.md)**
    *   Learn how to implement the "Browse-as-Guest" mode.
    *   Understanding the interceptor strategy for restricted actions.
2.  **[API Reference (V1)](./api-v1-reference.md)**
    *   Full list of public and protected endpoints.
    *   Request/Response structures for catalog exploration.
3.  **[Membership Tiers & Loyalty](./membership-tiers.md)**
    *   Technical details on the tiers endpoint and reward multipliers.

---

## 🚀 Quick Start
All API requests must target the versioned prefix:
`https://your-arena-domain.com/api/v1/`

### Guest Mode
No headers required for `GET` requests to catalog endpoints.

### Member Mode
Requires a valid Sanctum token in the header:
`Authorization: Bearer {token}`
