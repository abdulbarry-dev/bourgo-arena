# NFC Access System — Flutter Integration Specification

This document provides the technical contract for integrating the NFC access system into the Bourgo Arena Flutter application.

## Overview

The NFC system is split into two distinct parts:
1. **Physical NFC**: Status of the physical key fob/card assigned to the member.
2. **Digital NFC**: Setup and status of the smartphone's Host Card Emulation (HCE) access.

---

## Authentication & Headers

All requests require a valid Member Bearer Token and the account must be verified and onboarding completed.

**Headers:**
```http
Authorization: Bearer {{token}}
Accept: application/json
Content-Type: application/json
```

---

## 1. Physical NFC Status

### Get Physical Status
Returns whether the member has an active physical NFC card.

**Endpoint:** `GET /api/v1/member/nfc/physical-status`

**Response Example (Active Card):**
```json
{
  "success": true,
  "message": "Physical NFC status retrieved.",
  "data": {
    "has_card": true,
    "card_uid": "A1B2C3D4",
    "card_status": "active",
    "is_ready": true,
    "fallback_methods": ["pin", "fingerprint"]
  }
}
```

**State Logic for Flutter:**
- If `is_ready` is `true`, show the card as active in the UI.
- If `has_card` is `false`, show a CTA to "Request Physical Card" (managed by staff).

---

## 2. Digital NFC Readiness

### Check Digital Status
Determines if the current smartphone is eligible for digital NFC access.

**Endpoint:** `GET /api/v1/member/nfc/digital-status`

**Request Body:**
```json
{
  "device_model": "Samsung Galaxy S24",
  "os_version": "Android 14",
  "nfc_enabled": true,
  "supports_hce": true
}
```

**Response Example (Eligible but not setup):**
```json
{
  "success": true,
  "message": "Digital NFC supported.",
  "data": {
    "supported": true,
    "configured": false,
    "eligible": true,
    "is_ready": false,
    "setup_status": "not_started",
    "reasons": [],
    "fallback_methods": ["pin", "fingerprint", "physical_card"]
  }
}
```

**Response Example (Unsupported):**
```json
{
  "success": true,
  "message": "Device does not support digital NFC.",
  "data": {
    "supported": false,
    "configured": false,
    "eligible": false,
    "is_ready": false,
    "setup_status": "unsupported",
    "reasons": ["manufacturer_blocked"],
    "fallback_methods": ["pin", "fingerprint"]
  }
}
```

---

## 3. Digital NFC Setup

### Initialize/Complete Setup
Records the device as the active digital NFC key for the member.

**Endpoint:** `POST /api/v1/member/nfc/digital-setup`

**Request Body:**
```json
{
  "device_identifier": "unique-hardware-id",
  "device_model": "Samsung Galaxy S24",
  "os_version": "Android 14",
  "nfc_enabled": true,
  "supports_hce": true
}
```

**Response Example:**
```json
{
  "success": true,
  "message": "Digital NFC setup initialized.",
  "data": {
    "setup_status": "completed",
    "supported": true,
    "eligible": true
  }
}
```

---

## State Transition Matrix

| Current State | Action | Next State | Flutter UI Action |
|---------------|--------|------------|-------------------|
| `not_started` | Call Setup | `completed` | Enable HCE Service |
| `completed`   | Call Setup (New Device) | `completed` | Update active device |
| `unsupported` | N/A | `unsupported` | Show fallback methods |
| `revoked`     | Call Setup | `completed` | Re-enable access |

---

## Error Catalog

| Code | Status | Message | Resolution |
|------|--------|---------|------------|
| `401` | Unauthorized | Unauthenticated | Redirect to Login |
| `403` | Forbidden | Onboarding incomplete | Redirect to Onboarding |
| `422` | Unprocessable | Validation errors | Check request payload |

---

## Fallback UX Copy for Unsupported Phones

If `supported` is `false`, display:
> "Digital NFC access is not available on this device. Please use your physical card or biometric PIN for entry."

---

## Flutter Integration Example (Dart)

```dart
Future<void> syncDigitalNfc(String deviceId) async {
  final deviceInfo = await getDeviceInfo(); // Local hardware check
  
  final response = await http.post(
    Uri.parse('/api/v1/member/nfc/digital-setup'),
    body: jsonEncode({
      'device_identifier': deviceId,
      'device_model': deviceInfo.model,
      'os_version': deviceInfo.osVersion,
      'nfc_enabled': deviceInfo.isNfcEnabled,
      'supports_hce': deviceInfo.supportsHce,
    }),
  );
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body)['data'];
    if (data['setup_status'] == 'completed') {
      // Start local Android HCE Service
    }
  }
}
```

---
*Bourgo Arena — technical specification v1.0*
