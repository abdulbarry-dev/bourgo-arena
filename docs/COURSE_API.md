# Courses API Documentation

Course discovery and session scheduling endpoints for mobile applications, covering the public course catalog and authenticated session schedule.

## Base URL

All endpoints are relative to `/api/v1/`.

---

## 1. List Courses (Public Catalog)

Retrieve a paginated list of all active courses.

- **URL:** `GET /courses`
- **Method:** `GET`
- **Authentication:** None

### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | int | No | `15` | Number of courses per page |

### Success Response (200)

```json
{
    "success": true,
    "message": "Courses retrieved successfully.",
    "data": [
        {
            "id": "1",
            "name": "Morning Yoga",
            "description": "Start your day with energising yoga flows suitable for all levels.",
            "images": [
                "http://localhost/storage/courses/yoga-1.jpg",
                "http://localhost/storage/courses/yoga-2.jpg"
            ],
            "image_url": "http://localhost/storage/courses/yoga-cover.jpg",
            "status": "active"
        },
        {
            "id": "2",
            "name": "HIIT Circuit",
            "description": "High-intensity interval training to boost endurance and burn calories.",
            "images": [],
            "image_url": "http://localhost/storage/courses/hiit-cover.jpg",
            "status": "active"
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/courses?page=1",
        "last": "http://localhost/api/v1/courses?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 2,
        "total": 2
    }
}
```

### Response Fields

| Field | Type | Nullable | Description |
|-------|------|:--------:|-------------|
| `id` | string | No | Course unique identifier (cast to string) |
| `name` | string | No | Course display name |
| `description` | string | Yes | Short course description |
| `images` | array | No | Array of image URLs (absolute). Falls back to `[image_url]` if `images` is empty. |
| `image_url` | string | Yes | Primary cover image URL (absolute) |
| `status` | string | No | Course status: `active`, `inactive`, `archived` |

> **Note:** Only courses with `status: "active"` appear in this catalog. Inactive and archived courses are filtered out.

### Custom Pagination

```http
GET /api/v1/courses?per_page=5
```

---

## 2. Show Course Details

Retrieve details for a single active course.

- **URL:** `GET /courses/{course}`
- **Method:** `GET`
- **Authentication:** None

### Success Response (200)

```json
{
    "success": true,
    "message": "Course retrieved successfully.",
    "data": {
        "id": "1",
        "name": "Morning Yoga",
        "description": "Start your day with energising yoga flows suitable for all levels.",
        "images": [
            "http://localhost/storage/courses/yoga-1.jpg",
            "http://localhost/storage/courses/yoga-2.jpg"
        ],
        "image_url": "http://localhost/storage/courses/yoga-cover.jpg",
        "status": "active"
    }
}
```

Response fields are identical to the list endpoint (no nesting).

### Error — Inactive / Not Found (404)

```json
{
    "success": false,
    "message": "Course not found or inactive."
}
```

Only courses with `status === "active"` can be retrieved. Draft (null status), inactive, and archived courses all return a 404.

---

## 3. Course Session Schedule

Retrieve the upcoming 7-day schedule for a specific course. Returns sessions with at least one occurrence in the next 7 days.

- **URL:** `GET /courses/{course}/sessions`
- **Method:** `GET`
- **Authentication:** Required (Sanctum)
- **Additional Middleware:** `verified.account`, `onboarding.completed`, `course.access`

### Access Control

This endpoint is protected by the `course.access` middleware. The authenticated member must:

1. Be a `Member` instance (not a staff/admin user)
2. Have at least one **active, non-expired** subscription (`status: "active"` and `ends_at` in the future)
3. That subscription's plan must include the requested course — either:
   - The plan has `has_all_courses === true` (access to every course), or
   - The course is explicitly linked to the plan via the `course_plan` pivot

If any condition fails, the response is a 403 or 401.

### 7-Day Filtering Logic

The endpoint returns sessions that have **at least one occurrence** within the next 7 days:

| Condition | SQL | Reason |
|-----------|-----|--------|
| Session starts on or before `now + 7 days` | `starts_at_date <= now() + 7 days` | Excludes sessions beginning after the window |
| Session hasn't fully ended yet | `ends_at_date >= now()` | Excludes sessions whose entire range is in the past |
| Not cancelled | `is_cancelled = false` | Excludes admin-cancelled sessions |
| Has end date | `ends_at_date IS NOT NULL` | Excludes incomplete/misconfigured sessions |

**Example:** A session with `starts_at_date: 2026-06-05` and `ends_at_date: 2026-06-20` running weekly on Mondays. If today is June 8, this session has instances on June 8 (within window) and June 15 (within window) — it **is included**. If today is June 23, neither date falls within the 7-day window — it is **excluded**.

### Success Response (200)

```json
{
    "success": true,
    "message": "Sessions retrieved successfully.",
    "data": [
        {
            "id": "10",
            "title": "Morning Yoga",
            "start_time": "08:00",
            "end_time": "09:00",
            "day_of_week": 1,
            "capacity": 20,
            "enrolled": 12,
            "image_url": "http://localhost/storage/courses/yoga-cover.jpg",
            "is_booked": false
        },
        {
            "id": "11",
            "title": "Morning Yoga",
            "start_time": "10:00",
            "end_time": "11:00",
            "day_of_week": 3,
            "capacity": 20,
            "enrolled": 5,
            "image_url": "http://localhost/storage/courses/yoga-cover.jpg",
            "is_booked": true
        }
    ],
    "links": {
        "first": "http://localhost/api/v1/courses/1/sessions?page=1",
        "last": "http://localhost/api/v1/courses/1/sessions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 2,
        "total": 2
    }
}
```

### Session Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Session unique identifier (cast to string) |
| `title` | string | Parent course name (for display context) |
| `start_time` | string | Session start time in `HH:MM` format (24h) |
| `end_time` | string | Computed end time: `starts_at + duration_minutes`, formatted as `HH:MM` (24h) |
| `day_of_week` | int | Day code: 0=Sunday, 1=Monday, ..., 6=Saturday |
| `capacity` | int | Maximum participants allowed |
| `enrolled` | int | Current booking count for this session |
| `image_url` | string\|null | Parent course cover image URL |
| `is_booked` | bool | Whether the authenticated member has a confirmed booking for this session |

### Empty Schedule (200)

```json
{
    "success": true,
    "message": "Sessions retrieved successfully.",
    "data": [],
    "links": {
        "first": "http://localhost/api/v1/courses/1/sessions?page=1",
        "last": "http://localhost/api/v1/courses/1/sessions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": null,
        "last_page": 1,
        "per_page": 15,
        "to": null,
        "total": 0
    }
}
```

Returned when the course has sessions but none fall within the 7-day window.

### Error — Access Denied (403)

```json
{
    "message": "Access denied. Your current plan does not include access to the schedule for this course."
}
```

The member's active subscription plan does not grant access to this course.

### Error — Unauthorized (401)

```json
{
    "message": "Unauthorized."
}
```

The authenticated user is not a Member (e.g., staff account) or the token is missing/invalid.

### Error — Course Not Found / Inactive (404)

```json
{
    "success": false,
    "message": "Course not found or inactive."
}
```

---

## 4. Book a Session

Enrol in a specific session occurrence for a given date.

- **URL:** `POST /courses/{course}/sessions/{session}/book`
- **Method:** `POST`
- **Authentication:** Required (Sanctum)
- **Additional Middleware:** `verified.account`, `onboarding.completed`, `course.access`

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| `date` | string | Yes | Target occurrence date in `YYYY-MM-DD` format |

```json
{
    "date": "2026-06-10"
}
```

### Success Response (201)

```json
{
    "success": true,
    "message": "Successfully enrolled in the session.",
    "data": {
        "id": "42",
        "session_id": "10",
        "course_id": "1",
        "course_name": "Morning Yoga",
        "date": "2026-06-10",
        "start_time": "08:00",
        "end_time": "09:00",
        "status": "confirmed"
    }
}
```

### Booking Response Fields

| Field | Type | Nullable | Description |
|-------|------|:--------:|-------------|
| `id` | string | No | Booking unique identifier |
| `session_id` | string | No | Parent session ID |
| `course_id` | string | No | Parent course ID |
| `course_name` | string | No | Parent course display name |
| `date` | string | No | Booked occurrence date (`YYYY-MM-DD`) |
| `start_time` | string | No | Session start time in `HH:MM` (24h) |
| `end_time` | string | No | Computed end time: `starts_at + duration_minutes`, formatted as `HH:MM` (24h) |
| `status` | string | No | Booking status: `confirmed`, `waitlisted`, `cancelled` |

### Validation Rules (All return 422)

| Guard | Condition | Error Message |
|-------|-----------|---------------|
| Session belongs to course | `session.course_id !== course.id` | `"Session not found."` (404) |
| Session not cancelled | `session.is_cancelled === true` | `"This session has been cancelled."` |
| Session hasn't ended | `session.ends_at_date < today()` | `"This session has ended and cannot be booked."` |
| Date format | Must be `Y-m-d` | `"The date field must match the format Y-m-d."` |
| Date not in past | `date < today()` | `"The selected date is in the past."` |
| Date matches session day | `date.dayOfWeek === session.day_of_week` | `"The selected date does not match this session's schedule."` |
| Date within session range | `date >= session.starts_at_date && date <= session.ends_at_date` | `"The selected date is outside this session's schedule."` |
| Not already booked | No non-cancelled booking for same member+session+date | `"You are already enrolled in this session for this date."` |
| Not at capacity | `confirmed bookings for this session+date < session.capacity` | `"Session is at full capacity."` |

> **Note:** Cancelled bookings do NOT count against capacity and do NOT block re-booking.

### Error — Missing Date (422)

```json
{
    "message": "The date field is required."
}
```

### Error — Invalid Date Format (422)

```json
{
    "message": "The date field must match the format Y-m-d."
}
```

---

## Data Model Reference

### Course

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `service_id` | int | FK to services (for grouping by sport/activity type) |
| `name` | string | Course display name |
| `description` | text\|null | Course description |
| `images` | json\|null | Array of image paths |
| `image_url` | string\|null | Primary cover image path |
| `status` | string\|null | `active`, `inactive`, `archived` |

### CourseSession

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `course_id` | int | FK to courses |
| `day_of_week` | int | 0 (Sunday) to 6 (Saturday) |
| `starts_at` | time | Recurring start time (e.g., `08:00:00`) |
| `starts_at_date` | date | First occurrence date (range start) |
| `ends_at_date` | date | Last occurrence date (range end) |
| `duration_minutes` | int | Session length in minutes |
| `capacity` | int | Maximum participants |
| `is_cancelled` | bool | Admin cancellation flag |
| `cancelled_at` | datetime\|null | When the session was cancelled |

### Booking

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Primary key |
| `member_id` | int | FK to members |
| `course_session_id` | int | FK to course_sessions (nullable — shared with court_slot_id) |
| `court_slot_id` | int | FK to court_slots (nullable — shared with course_session_id) |
| `date` | date | Target occurrence date for recurring sessions |
| `status` | enum | `confirmed`, `waitlisted`, `cancelled` |
| `waitlist_position` | int\|null | Position on the waitlist |
| `cancelled_at` | datetime\|null | When the booking was cancelled |

> **Note:** Exactly one of `course_session_id` or `court_slot_id` must be set. The `course.access` middleware ensures only course bookings route through the course flow.

---

## 5. Check Booking Status

Check if the authenticated member is booked (scheduled) for a specific course session on a given date.

```
GET /api/v1/courses/{course}/sessions/{session}/booking?date=YYYY-MM-DD
```

**Auth:** Required (Sanctum token)  
**Middleware:** `course.access` — member must have a valid subscription covering the course.  

### Query Parameters

| Parameter | Required | Format | Description |
|-----------|:--------:|--------|-------------|
| `date` | Yes | `Y-m-d` | The occurrence date to check |

### Validation Rules

| Rule | Check | Error Message (422) |
|------|-------|---------------------|
| Date format | Must be `Y-m-d` | `"The date field must match the format Y-m-d."` |
| Date matches session day | `date.dayOfWeek === session.day_of_week` | `"The selected date does not match this session's schedule."` |
| Date within session range | `date >= session.starts_at_date && date <= session.ends_at_date` | `"The selected date is outside this session's schedule."` |

### Success Response — Booked (200)

```json
{
    "success": true,
    "message": null,
    "data": {
        "is_booked": true,
        "booking": {
            "id": "42",
            "session_id": "5",
            "course_id": "3",
            "course_name": "Morning Yoga",
            "date": "2026-06-10",
            "start_time": "08:00",
            "end_time": "09:00",
            "status": "confirmed"
        }
    }
}
```

### Success Response — Not Booked (200)

```json
{
    "success": true,
    "message": null,
    "data": {
        "is_booked": false,
        "booking": null
    }
}
```

### Error — Session Not Found (404)

```json
{
    "success": false,
    "message": "Session not found."
}
```

### Booking Status Values

| `status` | Meaning |
|----------|---------|
| `confirmed` | User is actively enrolled for this date |
| `waitlisted` | User is on the waitlist (enrolled when a spot opens) |
| `cancelled` | User cancelled — treated as **not booked** by this endpoint |

> **Note:** Cancelled bookings (`status = 'cancelled'`) are excluded from the query. A previously cancelled booking returns `is_booked: false`.

### Edge Cases

1. **Past dates**: The endpoint does NOT reject past dates — you can check historical bookings. This is intentional so the mobile app can verify attendance history.
2. **Outside date range**: Returns 422 immediately. Use the `/sessions` endpoint first to determine valid dates.
3. **Cancelled master session**: The endpoint still works — it checks the booking table, not the session's `is_cancelled` flag. If a user was booked before the session was cancelled, `is_booked` remains `true` (booking status is `confirmed`, not `cancelled` by the booking operator).
4. **Re-booking after cancellation**: Since cancelled bookings return `is_booked: false`, the user can re-book the same slot. The `POST /book` endpoint treats cancelled bookings as non-blocking.

---

## Flutter Integration Notes

### Dart Model — Course

```dart
class Course {
  final String id;
  final String name;
  final String? description;
  final List<String> images;
  final String? imageUrl;
  final String status;

  Course({
    required this.id,
    required this.name,
    this.description,
    required this.images,
    this.imageUrl,
    required this.status,
  });

  factory Course.fromJson(Map<String, dynamic> json) {
    return Course(
      id: json['id'] as String,
      name: json['name'] as String,
      description: json['description'] as String?,
      images: List<String>.from(json['images'] ?? []),
      imageUrl: json['image_url'] as String?,
      status: json['status'] as String,
    );
  }
}
```

### Dart Model — CourseSession

```dart
class CourseSession {
  final String id;
  final String title;
  final String startTime;
  final String endTime;
  final int dayOfWeek;
  final int capacity;
  final int enrolled;
  final String? imageUrl;

  CourseSession({
    required this.id,
    required this.title,
    required this.startTime,
    required this.endTime,
    required this.dayOfWeek,
    required this.capacity,
    required this.enrolled,
    this.imageUrl,
  });

  factory CourseSession.fromJson(Map<String, dynamic> json) {
    return CourseSession(
      id: json['id'] as String,
      title: json['title'] as String,
      startTime: json['start_time'] as String,
      endTime: json['end_time'] as String,
      dayOfWeek: json['day_of_week'] as int,
      capacity: json['capacity'] as int,
      enrolled: json['enrolled'] as int,
      imageUrl: json['image_url'] as String?,
    );
  }

  String get dayName {
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    return days[dayOfWeek];
  }
}
```

### Dart Model — Booking

```dart
class Booking {
  final String id;
  final String sessionId;
  final String courseId;
  final String courseName;
  final String date;
  final String startTime;
  final String endTime;
  final String status;

  Booking({
    required this.id,
    required this.sessionId,
    required this.courseId,
    required this.courseName,
    required this.date,
    required this.startTime,
    required this.endTime,
    required this.status,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      id: json['id'] as String,
      sessionId: json['session_id'] as String,
      courseId: json['course_id'] as String,
      courseName: json['course_name'] as String,
      date: json['date'] as String,
      startTime: json['start_time'] as String,
      endTime: json['end_time'] as String,
      status: json['status'] as String,
    );
  }

  bool get isConfirmed => status == 'confirmed';
  bool get isCancelled => status == 'cancelled';
  bool get isWaitlisted => status == 'waitlisted';
}
```

### Typical Flutter Workflow

```dart
// 1. Browse active courses
final coursesResponse = await api.get('/courses');
final courses = (coursesResponse['data'] as List)
    .map((json) => Course.fromJson(json))
    .toList();

// 2. Tap a course to view its schedule (requires auth)
final sessionsResponse = await api.get('/courses/${courseId}/sessions');
final sessions = (sessionsResponse['data'] as List)
    .map((json) => CourseSession.fromJson(json))
    .toList();

// 3. Display sessions grouped by day of week or date
// dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
// enrolled / capacity for fill-percentage UI

// 4. For each session, check if the user is already booked on a specific date
final bookingStatusResponse = await api.get(
  '/courses/${courseId}/sessions/${sessionId}/booking',
  queryParameters: {'date': '2026-06-10'},
);
final isBooked = bookingStatusResponse['data']['is_booked'] as bool;
if (isBooked) {
  final booking = Booking.fromJson(bookingStatusResponse['data']['booking']);
  // Show "Enrolled" badge, disable booking button
}

// 5. Book a session for a specific date
final bookingResponse = await api.post(
  '/courses/${courseId}/sessions/${sessionId}/book',
  body: {'date': '2026-06-10'},
);
final booking = Booking.fromJson(bookingResponse['data']);
// Show "Enrolled!" confirmation with course_name, date, start_time-end_time

// 6. Handle booking errors per the validation rules table above
// Show inline error messages for each guard (cancelled, full, already enrolled, etc.)
```

### Important Edge Cases

1. **Inactive course → 404**: Always call `/courses` first to get the active list. If a user bookmarks a course ID that later becomes inactive, the `show`, `sessions`, `book`, and `booking` endpoints will return 404.

2. **Expired subscription → 403**: The `sessions`, `book`, and `booking` endpoints return 403 when the member's subscription has expired or their plan excludes the course. Show a "Upgrade your plan to view this schedule" prompt in the UI.

3. **Empty schedule**: A course may exist and be accessible but have no sessions in the next 7 days. Handle `data: []` gracefully — show a "No upcoming sessions this week" message.

4. **Capacity tracking**: Use `enrolled` (current bookings) vs `capacity` (maximum) to show fill-level indicators. `enrolled` reflects real-time booking data.

5. **`end_time` is computed**: The API calculates `end_time` on the fly from `starts_at + duration_minutes`. Never try to store or guess this value — always use the API response.

6. **Booking date guards (client-side pre-validation)**:
   - Only show dates where `date.dayOfWeek == session.dayOfWeek` within `[starts_at_date, ends_at_date]`.
   - Disable past dates in the date picker.
   - Prevent UI taps when `enrolled >= capacity` (show "Full" badge).
   - After a successful booking, increment the local `enrolled` count to keep the fill bar accurate without an extra network call.

7. **Re-booking after cancellation**: Cancelled bookings don't block a new booking for the same session+date. If you allow cancellation in your UI, the user can re-book the same slot later.

8. **Error precedence**: The API returns the first guard that fails. In the UI:
   - If a session is cancelled, don't even show the booking button — grey out the slot.
   - If at capacity, show "Full" and disable the button.
   - If already enrolled, show "Enrolled" badge and disable the button.
   - Call the endpoint optimistically; show the 422 message if any guard triggers at the API level.
