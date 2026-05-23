# Hikvision Access Control Guide — Bourgo Arena

Members can access the gym terminals using three primary methods. This guide explains the technical workflow for each.

## 1. Access Methods

### A. Physical NFC Card (Key Fob)
*   **How it works**: The user taps a physical MIFARE or proximity tag on the reader.
*   **Validation**: The terminal reads the hardware UID and sends it to the API.

### B. Digital NFC Card (Android Smartphone)
*   **How it works**: The member's Android phone emulates a physical card UID using **Host Card Emulation (HCE)**.
*   **Validation**: The terminal sees the phone as a standard NFC card and transmits the UID to the API.
*   **Requirement**: Android 12+, HCE support, and a registered device in the mobile app.

### C. Personal PIN Code
*   **How it works**: The user enters their 4-digit PIN on the terminal keypad.
*   **Technical Workflow**: 
    1.  The terminal validates the PIN against its local whitelist (synced from the server).
    2.  If valid, the terminal sends a check-in event to the API.
    3.  The API maps the terminal's "Employee String" (which contains the Member ID) to the member record to log the access.

---

## 2. Prerequisites for All Methods

*   **Active Status**: Member account must be `active`.
*   **Active Subscription**: Access is denied if the subscription is expired or suspended.
*   **Assigned ID/UID**: The member must have a record in the `nfc_cards` table (for NFC) and a `pin` set in the `members` table.

---

## 3. Access Workflow (The "Tap/Entry")

1.  **Interaction**: User Taps (NFC) or Types (PIN).
2.  **Notification**: Terminal calls `/api/checkin` via **ISAPI**.
3.  **Authorization**: `ProcessTerminalCheckInAction` validates member eligibility.
4.  **Entry**: Terminal relay unlocks the door.

---

## 4. Fallbacks

If any method fails:
1.  Try an alternative method (e.g., if phone battery is dead, use Physical Card).
2.  Use the **Remote Unlock** feature in the mobile app (API-based).
3.  Contact staff for manual override via the **Admin Dashboard**.
