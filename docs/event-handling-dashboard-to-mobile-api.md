# Event Handling Flow: Dashboard Management to Mobile API

## Scope

This document exports the current event handling logic from the admin dashboard to the mobile application API.

It covers:

- Admin event management in the dashboard.
- The shared event data model.
- Public and authenticated event API endpoints for mobile clients.
- The business rules enforced by the application.

## Main Data Model

The core model is `Event`.

### Event fields

- `name`
- `description`
- `sport_type`
- `format`
- `max_participants`
- `registration_deadline`
- `start_date`
- `end_date`
- `requires_check_in`
- `status`

### Event relations

- `participants()` -> many `EventParticipant` records.
- `matches()` -> many `EventMatch` records.

### Event participant fields used by the flow

- `event_id`
- `user_id`
- `seed_number`
- `has_checked_in`
- `status`
- `withdrawn_at`

### Event match fields used by the flow

- `event_id`
- `round`
- `match_number`
- `participant1_id`
- `participant2_id`
- `scheduled_at`
- `winner_id`
- `score`
- `status`
- `next_match_id`

## Admin Dashboard Flow

The admin dashboard route is exposed through the admin route file and resolves to the Livewire component `App\Livewire\Admin\Events\EventManager`.

### Route entry

- `GET /admin/events`
- Route name: `admin.events.index`
- Access: `role:admin`

### Dashboard UI behavior

The Livewire view renders an event table with:

- Search by event name.
- Status filter.
- Participant count display.
- Start and end dates.
- Status badge.
- Edit action.
- Create/Edit modal.

### Dashboard data query

The component loads events with this shape:

- Filter by `name` when `search` is filled.
- Filter by `status` when `statusFilter` is filled.
- Include `participants_count` with `withCount('participants')`.
- Order newest first with `latest()`.
- Paginate 10 records per page.

### Create flow

When the admin clicks `New Event`:

- The form is reset.
- The create/edit modal is shown.
- The form starts with default values:
  - `sport_type = padel`
  - `format = 1v1`
  - `max_participants = 16`
  - `status = draft`

When the admin saves a new event:

- The component validates the required fields.
- The save runs inside a database transaction.
- A new `Event` row is created with the form data.
- The modal closes and the form is reset.

### Edit flow

When the admin clicks edit on an event:

- The component stores the `editingEventId`.
- The existing event data is copied into the form fields.
- Date fields are formatted for `datetime-local` inputs.
- The modal is shown again in edit mode.

When the admin saves an existing event:

- Validation runs again.
- The update runs inside a database transaction.
- The current event is loaded with `findOrFail`.
- Existing matches for that event are deleted before the event is updated.
- The event row is updated with the new values.

### Important dashboard rule

Editing an event removes its existing matches before saving the updated event. That means event changes are treated as structurally disruptive to the bracket/match data, not just a simple metadata update.

## Mobile API Flow

The mobile application uses the public event endpoints in `routes/api.php`.

These event endpoints are not inside the `v1` prefix. They are exposed at the top-level `/api` path.

### Public event endpoints

- `GET /api/events`
- `GET /api/events/{event}`
- `GET /api/events/{event}/bracket`

### Authenticated event endpoints

These require `auth:sanctum`:

- `GET /api/user/events`
- `POST /api/events/{event}/register`
- `POST /api/events/{event}/withdraw`
- `POST /api/events/{event}/check-in`

## Public API Logic

### List events: `GET /api/events`

Controller: `App\Http\Controllers\Api\EventController@index`

Behavior:

- Accepts optional query filters:
  - `status`
  - `sport_type`
- Includes `participants_count`.
- Excludes events with `status = draft`.
- Sorts newest first.
- Paginates 15 items per page.
- Returns `EventResource` collection output.

### Show event: `GET /api/events/{event}`

Controller: `App\Http\Controllers\Api\EventController@show`

Behavior:

- Draft events return `404`.
- Non-draft events load `participants_count`.
- Returns a single `EventResource`.

### Event bracket: `GET /api/events/{event}/bracket`

Controller: `App\Http\Controllers\Api\EventController@bracket`

Behavior:

- Loads matches with both participants and their linked users.
- Orders matches by `round` ascending, then `match_number` ascending.
- Groups the result by round.
- Returns a JSON object shaped as:
  - `data.round_1`
  - `data.round_2`
  - and so on
- Each round is serialized with `EventMatchResource`.

## Authenticated API Logic

### My events: `GET /api/user/events`

Controller: `App\Http\Controllers\Api\EventParticipantController@myEvents`

Behavior:

- Filters `EventParticipant` records by the current authenticated user.
- Eager loads the related `event`.
- Returns a collection of `EventParticipantResource` objects.

### Register: `POST /api/events/{event}/register`

Controller: `App\Http\Controllers\Api\EventParticipantController@register`

Behavior:

- Rejects the request unless the event status is `open`.
- Rejects duplicate registrations for the same user and event.
- Counts current participants.
- If the current count is at or above `max_participants`, the user is put on the waitlist.
- Otherwise the user is approved.
- Creates the `EventParticipant` row.
- Returns `201` with:
  - `message`
  - `status`
  - `data` serialized by `EventParticipantResource`

### Withdraw: `POST /api/events/{event}/withdraw`

Controller: `App\Http\Controllers\Api\EventParticipantController@withdraw`

Behavior:

- Finds the current user’s participant record for the event.
- Rejects repeated withdrawal when status is already `withdrawn`.
- Runs the withdrawal logic inside a database transaction.
- Marks the participant as `withdrawn` and stores `withdrawn_at`.

Additional behavior when the event is in progress:

- Looks for a scheduled match containing the withdrawn participant.
- Assigns the opponent as winner by walkover.
- Marks the match as `walkover`.
- Pushes the winner into the next match when `next_match_id` exists.

Additional behavior when the event has not started:

- If the event status is `open` or `draft`, the oldest waitlisted participant is promoted to `pending`.

### Check in: `POST /api/events/{event}/check-in`

Controller: `App\Http\Controllers\Api\EventParticipantController@checkIn`

Behavior:

- Rejects check-in if the event does not require it.
- Rejects check-in if today is not the same day as the event `start_date`.
- Marks the participant as checked in.
- Returns a success message.

## API Resources Returned to Mobile

### EventResource

Returned by the event list and event detail endpoints.

Fields:

- `id`
- `name`
- `description`
- `sport_type`
- `format`
- `max_participants`
- `participants_count`
- `registration_deadline`
- `start_date`
- `end_date`
- `requires_check_in`
- `status`

### EventParticipantResource

Returned by participant endpoints.

Fields:

- `id`
- `event_id`
- `user`
  - `id`
  - `name`
  - `initials`
- `seed_number`
- `status`
- `has_checked_in`
- `registered_at`
- `event`

### EventMatchResource

Returned by the bracket endpoint.

Fields:

- `id`
- `round`
- `match_number`
- `scheduled_at`
- `score`
- `status`
- `participant1`
- `participant2`
- `winner_id`
- `next_match_id`

## Business Rules Summary

- Draft events are hidden from the public event list.
- Draft events return `404` on event detail requests.
- Registration is only allowed when the event status is `open`.
- Duplicate registrations are blocked.
- Capacity overflow moves users to `waitlisted`.
- Withdrawals can auto-promote waitlisted users before the event starts.
- Withdrawals during an active bracket can trigger walkover resolution.
- Check-in is only allowed when the event requires it and only on the event day.
- Editing an event in the dashboard deletes its current matches before updating the event.

## Verified Behavior In Tests

The following behaviors are covered by the existing feature tests:

- Admin event manager renders successfully.
- The admin page shows the expected empty state.
- A new event can be created from the dashboard.
- Public event listing excludes drafts.
- Public event detail rejects drafts.
- Registration succeeds for open events.
- Registration switches to waitlist when capacity is full.
- Withdrawal marks the participant as withdrawn and promotes waitlisted users.

## Practical Mobile Integration Notes

- Use `GET /api/events` for discovery screens.
- Use `GET /api/events/{event}` for detail screens.
- Use `GET /api/events/{event}/bracket` for bracket views.
- Use the authenticated participant endpoints for registration state, withdrawal, and check-in.
- Handle `422` responses for invalid lifecycle actions such as registering for a closed event or checking in when check-in is not enabled.
