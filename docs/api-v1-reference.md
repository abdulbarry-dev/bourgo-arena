# API V1: Catalog Reference (Guest Accessible)

Guests can access the following data without authentication. All endpoints return JSON.

## 🏘️ Services
Retrieve the high-level categories of offerings.

*   **List Services:** `GET /services`
*   **View Service:** `GET /services/{id}`

---

## 📋 Plans
Subscription packages available for purchase.

*   **List Plans:** `GET /plans`
*   **View Plan:** `GET /plans/{id}`

---

## 🏟️ Activities & Courts
Physical facilities and court bookings.

*   **List Activities:** `GET /activities`
*   **View Activity:** `GET /activities/{id}`
*   **Available Slots:** `GET /activities/{id}/slots`

---

## 🎓 Courses
Training templates and scheduled sessions.

*   **List Courses:** `GET /courses`
*   **View Course:** `GET /courses/{id}`
*   *Upcoming Sessions:** `GET /courses/{id}/sessions`

---

## 🏆 Events & Tournaments
Championships and brackets.

*   **List Events:** `GET /events`
*   **View Event:** `GET /events/{id}`
*   **View Bracket:** `GET /events/{id}/bracket`

---

## 🔍 Search
Global search across all catalog types.

*   **Search:** `GET /search?q={query}`
