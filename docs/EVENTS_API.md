# Events API Documentation

This document details all event-related endpoints for mobile application integration, covering event discovery, tournament brackets, participant registration, withdrawal, and check-in.

## Base URL

All endpoints are relative to `/api/v1/`.

## Authentication

| Endpoint Group | Auth Required | Middleware |
|---------------|---------------|------------|
| List / Show / Bracket | No | None (public) |
| Register / Withdraw / Check-In / My Events | Yes | `auth:sanctum`, `verified.account`, `onboarding.completed` |

Public endpoints return only **published** events (those with a `registration_deadline` in the future). Draft events are excluded.

### Authenticated Responses

When a request is authenticated (valid Sanctum token), list/show endpoints include an additional `is_registered` boolean field indicating whether the current user is registered for that event. For unauthenticated requests, this field is omitted entirely.

---

## Event Status Lifecycle

Events have a **computed status** (not a database column) derived from their dates:

| Status | Condition |
|--------|-----------|
| `open` | `registration_deadline` is in the future |
| `in_progress` | `start_date` has passed, `end_date` has not |
| `completed` | `end_date` is in the past |
| `canceled` | `canceled_at` is set (admin cancelled) |
| `draft` | No dates configured (hidden from API) |

---

## 1. List Events

Retrieve a paginated list of published events, optionally filtered by sport type.

- **URL:** `GET /events`
- **Authentication:** None

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sport_type` | string | No | Filter by service slug (e.g. `padel`, `tennis`, `boxing`) |
| `page` | int | No | Page number for pagination (default: 1) |

### Success Response (200)

```json
{
    "data": [
        {
            "id": "1",
            "name": "Friday Night Showdown Championship",
            "description": "A fast-paced weekly tournament open to all skill levels.",
            "images": [
                "https://picsum.photos/seed/abc123/800/600",
                "https://picsum.photos/seed/def456/800/600"
            ],
            "image_url": "https://picsum.photos/seed/abc123/800/600",
            "format": "1v1",
            "max_participants": 16,
            "participants_count": 12,
            "registration_deadline": "2026-06-15T18:00:00.000000Z",
            "start_date": "2026-06-16T19:00:00.000000Z",
            "end_date": "2026-06-16T22:00:00.000000Z",
            "requires_check_in": true,
            "status": "open",
            "created_at": "2026-06-10T08:00:00.000000Z"
        },
        {
            "id": "2",
            "name": "Doubles Cup Championship",
            "description": "Partner-based tournament with group stages.",
            "images": [
                "https://picsum.photos/seed/ghi789/800/600",
                "https://picsum.photos/seed/jkl012/800/600",
                "https://picsum.photos/seed/mno345/800/600"
            ],
            "image_url": "https://picsum.photos/seed/ghi789/800/600",
            "format": "2v2",
            "max_participants": 32,
            "participants_count": 30,
            "registration_deadline": "2026-06-20T12:00:00.000000Z",
            "start_date": "2026-06-22T10:00:00.000000Z",
            "end_date": "2026-06-22T18:00:00.000000Z",
            "requires_check_in": false,
            "status": "open",
            "created_at": "2026-06-10T08:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/events?page=1",
        "last": "http://localhost/api/v1/events?page=3",
        "prev": null,
        "next": "http://localhost/api/v1/events?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 42
    }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique event identifier |
| `name` | string | Event display name |
| `description` | string\|null | Event description |
| `images` | array | Array of image URLs (max 3) |
| `image_url` | string\|null | Convenience field — first image from the `images` array, or null |
| `format` | string | Tournament format: `1v1`, `2v2`, or `5v5` |
| `max_participants` | int | Maximum capacity (powers of 2: 8, 16, 32) |
| `participants_count` | int | Current number of registered participants (non-cancelled) |
| `is_registered` | bool | **Only when authenticated.** Whether the current user is registered for this event |
| `registration_deadline` | string\|null | ISO 8601 datetime when registration closes |
| `start_date` | string\|null | ISO 8601 datetime when the event begins |
| `end_date` | string\|null | ISO 8601 datetime when the event ends |
| `requires_check_in` | bool | Whether participants must check in on event day |
| `status` | string | Computed status (see status lifecycle above) |
| `created_at` | string\|null | ISO 8601 datetime when the event was created |

### Authenticated Response (200)

When the request includes a valid Sanctum token, each event object includes the `is_registered` field:

```json
{
    "data": [
        {
            "id": "1",
            "name": "Friday Night Showdown Championship",
            "description": "A fast-paced weekly tournament open to all skill levels.",
            "images": [
                "https://picsum.photos/seed/abc123/800/600",
                "https://picsum.photos/seed/def456/800/600"
            ],
            "image_url": "https://picsum.photos/seed/abc123/800/600",
            "format": "1v1",
            "max_participants": 16,
            "participants_count": 12,
            "is_registered": true,
            "registration_deadline": "2026-06-15T18:00:00.000000Z",
            "start_date": "2026-06-16T19:00:00.000000Z",
            "end_date": "2026-06-16T22:00:00.000000Z",
            "requires_check_in": true,
            "status": "open",
            "created_at": "2026-06-10T08:00:00.000000Z"
        },
        {
            "id": "2",
            "name": "Doubles Cup Championship",
            "description": "Partner-based tournament with group stages.",
            "images": [
                "https://picsum.photos/seed/ghi789/800/600",
                "https://picsum.photos/seed/jkl012/800/600",
                "https://picsum.photos/seed/mno345/800/600"
            ],
            "image_url": "https://picsum.photos/seed/ghi789/800/600",
            "format": "2v2",
            "max_participants": 32,
            "participants_count": 30,
            "is_registered": false,
            "registration_deadline": "2026-06-20T12:00:00.000000Z",
            "start_date": "2026-06-22T10:00:00.000000Z",
            "end_date": "2026-06-22T18:00:00.000000Z",
            "requires_check_in": false,
            "status": "open",
            "created_at": "2026-06-10T08:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/events?page=1",
        "last": "http://localhost/api/v1/events?page=3",
        "prev": null,
        "next": "http://localhost/api/v1/events?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "per_page": 15,
        "to": 15,
        "total": 42
    }
}
```

### Filtered by Sport

```http
GET /api/v1/events?sport_type=padel
```

Filters events whose linked Service slug matches `padel`.

---

## 2. Show Event Detail

Retrieve full details for a single event. Draft events return 404.

- **URL:** `GET /events/{event}`
- **Authentication:** None

### Success Response (200)

```json
{
    "data": {
        "id": "1",
        "name": "Friday Night Showdown Championship",
        "description": "A fast-paced weekly tournament open to all skill levels.",
        "images": [
            "https://picsum.photos/seed/abc123/800/600",
            "https://picsum.photos/seed/def456/800/600"
        ],
        "image_url": "https://picsum.photos/seed/abc123/800/600",
        "format": "1v1",
        "max_participants": 16,
        "participants_count": 12,
        "registration_deadline": "2026-06-15T18:00:00.000000Z",
        "start_date": "2026-06-16T19:00:00.000000Z",
        "end_date": "2026-06-16T22:00:00.000000Z",
        "requires_check_in": true,
        "status": "open",
        "created_at": "2026-06-10T08:00:00.000000Z"
    }
}
```

### Authenticated Response (200)

When the request includes a valid Sanctum token, the event object includes the `is_registered` field:

```json
{
    "data": {
        "id": "1",
        "name": "Friday Night Showdown Championship",
        "description": "A fast-paced weekly tournament open to all skill levels.",
        "images": [
            "https://picsum.photos/seed/abc123/800/600",
            "https://picsum.photos/seed/def456/800/600"
        ],
        "image_url": "https://picsum.photos/seed/abc123/800/600",
        "format": "1v1",
        "max_participants": 16,
        "participants_count": 12,
        "is_registered": true,
        "registration_deadline": "2026-06-15T18:00:00.000000Z",
        "start_date": "2026-06-16T19:00:00.000000Z",
        "end_date": "2026-06-16T22:00:00.000000Z",
        "requires_check_in": true,
        "status": "open",
        "created_at": "2026-06-10T08:00:00.000000Z"
    }
}
```

### Error Response — Draft / Not Found (404)

```json
{
    "message": "No query results for model [App\\Models\\Event] 99"
}
```

---

## 3. View Tournament Bracket

Retrieve the match bracket for an event, grouped by round.

- **URL:** `GET /events/{event}/bracket`
- **Authentication:** None

### Success Response (200)

```json
{
    "data": {
        "round_1": [
            {
                "id": 101,
                "round": 1,
                "match_number": 1,
                "score": null,
                "status": "scheduled",
                "participant1": {
                    "id": 201,
                    "event_id": 1,
                    "user": {
                        "id": 10,
                        "name": "Karim Ben Ali",
                        "initials": "KB"
                    },
                    "seed_number": 1,
                    "status": "approved",
                    "has_checked_in": true,
                    "registered_at": "2026-06-10T14:30:00.000000Z",
                    "event": null
                },
                "participant2": {
                    "id": 202,
                    "event_id": 1,
                    "user": {
                        "id": 12,
                        "name": "Sami Trabelsi",
                        "initials": "ST"
                    },
                    "seed_number": 8,
                    "status": "approved",
                    "has_checked_in": true,
                    "registered_at": "2026-06-10T15:45:00.000000Z",
                    "event": null
                },
                "winner_id": null,
                "next_match_id": 105
            },
            {
                "id": 102,
                "round": 1,
                "match_number": 2,
                "score": null,
                "status": "completed",
                "participant1": {
                    "id": 203,
                    "event_id": 1,
                    "user": {
                        "id": 14,
                        "name": "Mehdi Gharbi",
                        "initials": "MG"
                    },
                    "seed_number": 4,
                    "status": "approved",
                    "has_checked_in": true,
                    "registered_at": "2026-06-10T14:45:00.000000Z",
                    "event": null
                },
                "participant2": null,
                "winner_id": 203,
                "next_match_id": 105
            }
        ],
        "round_2": [
            {
                "id": 105,
                "round": 2,
                "match_number": 1,
                "score": null,
                "status": "scheduled",
                "participant1": {
                    "id": 201,
                    "event_id": 1,
                    "user": {
                        "id": 10,
                        "name": "Karim Ben Ali",
                        "initials": "KB"
                    },
                    "seed_number": 1,
                    "status": "approved",
                    "has_checked_in": true,
                    "registered_at": "2026-06-10T14:30:00.000000Z",
                    "event": null
                },
                "participant2": {
                    "id": 203,
                    "event_id": 1,
                    "user": {
                        "id": 14,
                        "name": "Mehdi Gharbi",
                        "initials": "MG"
                    },
                    "seed_number": 4,
                    "status": "approved",
                    "has_checked_in": true,
                    "registered_at": "2026-06-10T14:45:00.000000Z",
                    "event": null
                },
                "winner_id": null,
                "next_match_id": 107
            }
        ],
        "round_3": [
            {
                "id": 107,
                "round": 3,
                "match_number": 1,
                "score": null,
                "status": "scheduled",
                "participant1": null,
                "participant2": null,
                "winner_id": null,
                "next_match_id": null
            }
        ]
    }
}
```

### Bracket Explanation

- **Rounds** are numbered 1 through N, where Round 1 is the opening round and the highest round is the final.
- **Match numbers** are sequential within each round.
- **Byes:** When participant count is not a power of 2, some Round 1 matches have only `participant1` (no `participant2`). These are automatically marked `status: "completed"` with `score: "BYE"` and the sole participant advances to their next match.
- **Walkovers:** When a participant withdraws mid-tournament, their opponent advances via walkover (`status: "walkover"`). See withdrawal behavior below.
- **`next_match_id`** links to the match where the winner will be placed. `null` for final-round matches.
- **`event`** field inside participant objects is `null` in bracket context (not loaded for efficiency).

### Empty Bracket

If no matches have been generated yet, all `round_*` objects will be empty:

```json
{
    "data": []
}
```

---

## 4. My Events

List all events the authenticated user has registered for.

- **URL:** `GET /user/events`
- **Authentication:** Required (Sanctum)

### Success Response (200)

```json
{
    "data": [
        {
            "id": 201,
            "event_id": 1,
            "user": {
                "id": 10,
                "name": "Karim Ben Ali",
                "initials": "KB"
            },
            "seed_number": 1,
            "status": "approved",
            "has_checked_in": true,
            "registered_at": "2026-06-10T14:30:00.000000Z",
            "event": {
                "id": "1",
                "name": "Friday Night Showdown Championship",
                "description": "A fast-paced weekly tournament open to all skill levels.",
                "images": [
                    "https://picsum.photos/seed/abc123/800/600",
                    "https://picsum.photos/seed/def456/800/600"
                ],
                "image_url": "https://picsum.photos/seed/abc123/800/600",
                "format": "1v1",
                "max_participants": 16,
                "participants_count": 12,
                "registration_deadline": "2026-06-15T18:00:00.000000Z",
                "start_date": "2026-06-16T19:00:00.000000Z",
                "end_date": "2026-06-16T22:00:00.000000Z",
                "requires_check_in": true,
                "status": "open",
                "created_at": "2026-06-10T08:00:00.000000Z"
            }
        },
        {
            "id": 250,
            "event_id": 3,
            "user": {
                "id": 10,
                "name": "Karim Ben Ali",
                "initials": "KB"
            },
            "seed_number": null,
            "status": "waitlisted",
            "has_checked_in": false,
            "registered_at": "2026-06-11T09:15:00.000000Z",
            "event": {
                "id": "3",
                "name": "Doubles Cup Championship",
                "description": "Partner-based tournament with group stages.",
                "images": [
                    "https://picsum.photos/seed/ghi789/800/600",
                    "https://picsum.photos/seed/jkl012/800/600",
                    "https://picsum.photos/seed/mno345/800/600"
                ],
                "image_url": "https://picsum.photos/seed/ghi789/800/600",
                "format": "2v2",
                "max_participants": 32,
                "participants_count": 32,
                "registration_deadline": "2026-06-20T12:00:00.000000Z",
                "start_date": "2026-06-22T10:00:00.000000Z",
                "end_date": "2026-06-22T18:00:00.000000Z",
                "requires_check_in": false,
                "status": "open",
                "created_at": "2026-06-10T08:00:00.000000Z"
            }
        }
    ]
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Participant record ID |
| `event_id` | int | Linked event ID |
| `user` | object | User info (id, name, initials) |
| `seed_number` | int\|null | Tournament seed (set by admin after bracket generation) |
| `status` | string | `pending`, `approved`, `waitlisted`, `withdrawn`, `canceled` |
| `has_checked_in` | bool | Whether the user checked in (event-day only) |
| `registered_at` | string | ISO 8601 datetime of registration |
| `event` | object | Full event resource (loaded with participant) — includes `images`, `image_url`, `created_at`, etc. |

### Participant Statuses

| Status | Meaning |
|--------|---------|
| `pending` | Registered but not yet approved by admin |
| `approved` | Approved and eligible for bracketing |
| `waitlisted` | Event is full; waiting for a slot to open |
| `withdrawn` | User voluntarily withdrew |
| `canceled` | Admin cancelled the participant (or event was cancelled) |

---

## 5. Register for Event

Register the authenticated user for an event. Automatically waitlists when capacity is reached.

- **URL:** `POST /events/{event}/register`
- **Authentication:** Required (Sanctum)
- **Content-Type:** `application/json`

### Request Body

No request body required. The event is inferred from the URL, the user from the auth token.

### Success Response — Registered (201)

```json
{
    "message": "Successfully registered",
    "status": "pending",
    "data": {
        "id": 215,
        "event_id": 1,
        "user": {
            "id": 10,
            "name": "Karim Ben Ali",
            "initials": "KB"
        },
        "seed_number": null,
        "status": "pending",
        "has_checked_in": false,
        "registered_at": "2026-06-12T10:30:00.000000Z",
        "event": null
    }
}
```

### Success Response — Waitlisted (201)

When the current participant count has already reached `max_participants`:

```json
{
    "message": "Successfully registered",
    "status": "waitlisted",
    "data": {
        "id": 216,
        "event_id": 1,
        "user": {
            "id": 15,
            "name": "Nadia Rachid",
            "initials": "NR"
        },
        "seed_number": null,
        "status": "waitlisted",
        "has_checked_in": false,
        "registered_at": "2026-06-12T11:00:00.000000Z",
        "event": null
    }
}
```

### Error — Event not open (422)

```json
{
    "message": "Event is not open for registration"
}
```

The event's `status` must be `open` (registration deadline is in the future). Events that are `in_progress`, `completed`, `canceled`, or `draft` cannot accept registrations.

### Error — Already registered (422)

```json
{
    "message": "Already registered"
}
```

A user may only register once per event. This includes users with `pending`, `approved`, or `waitlisted` status.

---

## 6. Withdraw from Event

Withdraw the authenticated user's registration from an event. Behavior differs depending on event status.

- **URL:** `POST /events/{event}/withdraw`
- **Authentication:** Required (Sanctum)
- **Content-Type:** `application/json`

### Success Response (200)

```json
{
    "message": "Successfully withdrawn"
}
```

The participant's status is set to `withdrawn` and `withdrawn_at` is recorded.

### Withdrawal Behavior by Event Status

| Event Status | Behavior |
|-------------|----------|
| `open` / `draft` | Participant withdrawn. If they were `waitlisted`, oldest waitlisted user is **promoted** to `pending`. |
| `in_progress` | Participant withdrawn. Any active **scheduled** match where they are a participant is **forfeited as a walkover**. The opponent advances to the next match automatically. |
| `completed` | Participant withdrawn (post-tournament record). No bracket side-effects. |
| `canceled` | Blocked — already withdrawn/cancelled. |

### Walkover During Tournament

If a participant withdraws while the tournament is `in_progress`:

1. Their active scheduled match is identified.
2. The opponent is set as `winner_id`, match `status` becomes `walkover`.
3. The winner is placed in the next match (`next_match_id`), filling `participant1_id` or `participant2_id` depending on whether the original match number was odd or even.

### Error — Already withdrawn (422)

```json
{
    "message": "Already withdrawn"
}
```

### Error — Not registered (404)

```json
{
    "message": "No query results for model [App\\Models\\EventParticipant]."
}
```

---

## 7. Check In to Event

Check in for an event on the day it starts. Only available if the event requires check-in.

- **URL:** `POST /events/{event}/check-in`
- **Authentication:** Required (Sanctum)
- **Content-Type:** `application/json`

### Success Response (200)

```json
{
    "message": "Successfully checked in"
}
```

Sets `has_checked_in = true` on the participant record. The bracket generator only includes checked-in participants when `requires_check_in` is enabled on the event.

### Error — Event does not require check-in (422)

```json
{
    "message": "This event does not require check-in"
}
```

### Error — Not event day (422)

```json
{
    "message": "Check-in is only available on the day of the event"
}
```

Check-in is restricted to the calendar date matching `event.start_date`. Attempting to check in on any other day (before or after) returns this error.

### Error — Not registered (404)

```json
{
    "message": "No query results for model [App\\Models\\EventParticipant]."
}
```

---

## Bracket Generation Logic

Brackets are generated by administrators. The algorithm works as follows:

1. **Participant selection:** All approved participants for the event. If `requires_check_in` is true, only participants with `has_checked_in = true` are included.
2. **Bracket size:** Calculated as the nearest power of 2 equal to or greater than the participant count: `pow(2, ceil(log2(count)))`.
3. **Byes:** `bracketSize - participantCount` participants receive byes in Round 1.
4. **Tree construction:** Matches are created from the final round backwards to Round 1, linked via `next_match_id`.
5. **Seeding:** Participants are assigned to Round 1 matches in sequence. Byes are distributed evenly — every other match gets a bye if byes remain.
6. **Bye advancement:** For bye matches, the sole participant is auto-advanced: `winner_id` set, `status = "completed"`, `score = "BYE"`, and advanced to their next match.

The bracket generation is idempotent — calling it again deletes all existing matches and rebuilds from scratch.

---

## Data Models Reference

### Event

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `service_id` | int | FK to services (for sport type filtering) |
| `name` | string | Event name |
| `description` | text\|null | Event description |
| `images` | json\|null | Array of image URLs (max 3) |
| `format` | string | Tournament format: `1v1`, `2v2`, `5v5` |
| `max_participants` | int | Total capacity (typically 8, 16, or 32) |
| `registration_deadline` | datetime\|null | Cutoff for new registrations |
| `start_date` | datetime\|null | Event start |
| `end_date` | datetime\|null | Event end |
| `requires_check_in` | bool | Whether day-of check-in is required |
| `canceled_at` | timestamp\|null | Set when admin cancels the event |
| `status` | string (computed) | `open`, `in_progress`, `completed`, `canceled`, `draft` |

### EventMatch

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `event_id` | int | FK to events |
| `round` | int | Round number (1 = first round) |
| `match_number` | int | Position within the round |
| `participant1_id` | int\|null | FK to event_participants |
| `participant2_id` | int\|null | FK to event_participants |
| `winner_id` | int\|null | FK to event_participants |
| `score` | string\|null | Match score (e.g. `21-15, 18-21, 21-19`) |
| `status` | string | `scheduled`, `completed`, `walkover` |
| `next_match_id` | int\|null | Self-referencing FK to event_matches (next round) |
| `scheduled_at` | timestamp\|null | Scheduled match time |

### EventParticipant

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `event_id` | int | FK to events |
| `user_id` | int | FK to users |
| `team_id` | int\|null | FK to teams (for team formats) |
| `seed_number` | int\|null | Tournament seed (1 = top seed) |
| `has_checked_in` | bool | Day-of check-in status |
| `status` | string | `pending`, `approved`, `waitlisted`, `withdrawn`, `canceled` |
| `withdrawn_at` | datetime\|null | Timestamp of withdrawal |

---

## Error Codes Reference

| HTTP Status | Error | Meaning |
|-------------|-------|---------|
| 200 | — | Success |
| 201 | — | Resource created (registration) |
| 401 | — | Missing or invalid Sanctum token |
| 403 | — | Account not verified / onboarding not completed |
| 404 | — | Event not found, draft event, or participant record not found |
| 422 | `Event is not open for registration` | Event status is not `open` |
| 422 | `Already registered` | Duplicate registration attempt |
| 422 | `Already withdrawn` | Duplicate withdrawal attempt |
| 422 | `This event does not require check-in` | Check-in is not required for this event |
| 422 | `Check-in is only available on the day of the event` | Check-in outside the event date |

---

## Full Participant Workflow

```
1. Browse events
   GET /api/v1/events
   GET /api/v1/events?sport_type=padel

2. View event details
   GET /api/v1/events/{event}

3. Register
   POST /api/v1/events/{event}/register
   → status: pending (if space available) or waitlisted (if full)

4. View my registrations
   GET /api/v1/user/events

5. Check in (on event day, if required)
   POST /api/v1/events/{event}/check-in
   → has_checked_in = true

6. View bracket (once published by admin)
   GET /api/v1/events/{event}/bracket
   → round_1, round_2, ... with match details

7. Withdraw (if needed)
   POST /api/v1/events/{event}/withdraw
   → status: withdrawn
```

---

## Admin-Only Operations (Not via API)

The following operations are only available through the admin dashboard (Livewire), not the mobile API:

| Action | Description |
|--------|-------------|
| Create event | Define name, format, dates, max_participants |
| Edit event | Update event details and dates |
| Cancel event | Sets `canceled_at`, notifies all participants, flags payments for reconciliation |
| Delete event | Soft-deletes the event |
| Manage participants | Approve/reject registration, assign seed numbers |
| Generate bracket | Creates match tree via `GenerateTournamentBracketAction` |
| Publish bracket | Sends `BracketPublishedNotification` to all participants |
| Advance match winner | Record a match result, advance winner to next round |
