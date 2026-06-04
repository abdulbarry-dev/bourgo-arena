# Mobile API Integration Guide: Guest Access & Authentication Flow

This document outlines the API structure for the Bourgo Arena mobile application, specifically focusing on the new **Browse-as-Guest** mode.

## 1. Overview
The API is designed to allow unauthenticated users (Guests) to explore the facility's catalog, including services, plans, courses, activities, and events. However, any interaction that modifies data or reserves a slot requires a valid authenticated session.

**Base URL:** `{{APP_URL}}/api/v1`

---

## 2. Public Endpoints (Guest Accessible)
Guests can call these endpoints without an `Authorization` header.

### Catalog Exploration
| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/search` | `GET` | Global search across services, events, and courses. |
| `/services` | `GET` | List all active services (Gym, Yoga, Padel, etc.). |
| `/services/{id}` | `GET` | Detailed information about a specific service. |
| `/plans` | `GET` | List available subscription plans and pricing. |
| `/plans/{id}` | `GET` | Details of a specific plan. |
| `/activities` | `GET` | List all courts and facility offerings. |
| `/activities/{id}` | `GET` | Details of a specific activity/court. |
| `/activities/{id}/slots` | `GET` | View available time slots for an activity. |
| `/courses` | `GET` | List the course catalog templates. |
| `/courses/{id}` | `GET` | Details of a course template. |
| `/courses/{id}/sessions` | `GET` | View upcoming sessions for a specific course. |
| `/events` | `GET` | List all upcoming championships and tournaments. |
| `/events/{id}` | `GET` | Details of a specific event. |
| `/events/{id}/bracket` | `GET` | View the tournament bracket for an event. |
| `/tiers` | `GET` | List all membership tiers, requirements, and benefits. |

---

## 3. Protected Endpoints (Auth Required)
Any request to these endpoints without a valid Bearer Token will return a `401 Unauthorized` response.

### Core Actions
*   `POST /reservations` - Book a court or activity slot.
*   `POST /subscriptions` - Purchase or renew a plan.
*   `POST /events/{id}/register` - Register for a tournament.
*   `GET /member/profile` - View personal account details.
*   `GET /notifications` - View user-specific alerts.

---

## 4. Frontend Integration Strategy (Mobile App)

### The "Guest" Experience
1.  **Browse Mode:** Allow the user to navigate through the Catalog screens using the Public Endpoints listed above.
2.  **Interaction Trigger:** When a Guest clicks an action button (e.g., "Book Now", "Buy Plan", "Register"), the mobile app should **intercept** the action.
3.  **Auth Modal:** Instead of making the API call and receiving a 401, the frontend should display a high-fidelity Modal:
    *   **Headline:** "Ready to join the action?"
    *   **Body:** "You need to be logged in to make a reservation or register for events."
    *   **Actions:** [Login] [Sign Up] [Maybe Later]
4.  **Graceful Redirect:** After a successful login/registration, the app should ideally return the user to the item they were trying to interact with.

### Handling 401 Unauthorized
Even if the frontend doesn't intercept the action, the API will protect the data. The mobile app should implement a global Interceptor for 401 errors:
```javascript
// Example logic
if (response.status === 401) {
    showAuthRequiredModal();
}
```

---

## 5. Membership Tiers
The `/tiers` endpoint provides the static metadata for the rewards system. This is useful for displaying "What you get" when a user is browsing plans.

**Tier Levels:**
*   **Standard:** Base access.
*   **Plus:** 20% loyalty boost (Requires 2 active subs).
*   **Ultra:** 50% loyalty boost (Requires 3 active subs).
*   **Max:** 100% loyalty boost (Requires 4+ active subs).

---

## 6. Authentication Flow
Use the standard Sanctum flow provided in the `/auth` prefix:
1.  `POST /auth/register` or `POST /auth/login`.
2.  Store the returned `token` securely on the device.
3.  Include `Authorization: Bearer {token}` in all subsequent protected requests.
