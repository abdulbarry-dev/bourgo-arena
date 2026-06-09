<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Event;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * Search across all entity types.
     *
     * @return array{
     *   members: Collection,
     *   events: Collection,
     *   courses: Collection,
     *   subscriptions: Collection,
     *   services: Collection,
     *   plans: Collection,
     *   activities: Collection,
     * }
     */
    public function search(string $query, int $limit = 5): array
    {
        $term = trim($query);

        if ($term === '') {
            return $this->emptyResults();
        }

        return [
            'members' => $this->searchMembers($term, $limit),
            'events' => $this->searchEvents($term, $limit),
            'courses' => $this->searchCourses($term, $limit),
            'subscriptions' => $this->searchSubscriptions($term, $limit),
            'services' => $this->searchServices($term, $limit),
            'plans' => $this->searchPlans($term, $limit),
            'activities' => $this->searchActivities($term, $limit),
        ];
    }

    /**
     * Search a single entity type with pagination support.
     *
     * @return LengthAwarePaginator
     */
    public function searchType(string $type, string $query, int $perPage = 15): mixed
    {
        $term = trim($query);

        return match ($type) {
            'members' => $this->searchMembersPaginated($term, $perPage),
            'events' => $this->searchEventsPaginated($term, $perPage),
            'courses' => $this->searchCoursesPaginated($term, $perPage),
            'subscriptions' => $this->searchSubscriptionsPaginated($term, $perPage),
            'services' => $this->searchServicesPaginated($term, $perPage),
            'plans' => $this->searchPlansPaginated($term, $perPage),
            'activities' => $this->searchActivitiesPaginated($term, $perPage),
            default => collect()->paginate($perPage),
        };
    }

    /**
     * Get total counts per type for a given query (for tab badges).
     *
     * @return array<string, int>
     */
    public function countByType(string $query): array
    {
        $term = trim($query);

        if ($term === '') {
            return array_fill_keys(['members', 'events', 'courses', 'subscriptions', 'services', 'plans', 'activities'], 0);
        }

        return [
            'members' => $this->memberBaseQuery($term)->count(),
            'events' => $this->eventBaseQuery($term)->count(),
            'courses' => $this->courseBaseQuery($term)->count(),
            'subscriptions' => $this->subscriptionBaseQuery($term)->count(),
            'services' => $this->serviceBaseQuery($term)->count(),
            'plans' => $this->planBaseQuery($term)->count(),
            'activities' => $this->activityBaseQuery($term)->count(),
        ];
    }

    // -------------------------------------------------------------------------
    // Private search helpers — palette (limited)
    // -------------------------------------------------------------------------

    private function searchMembers(string $term, int $limit): Collection
    {
        return $this->memberBaseQuery($term)
            ->with(['validSubscriptions.plan'])
            ->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'status', 'avatar']);
    }

    private function searchEvents(string $term, int $limit): Collection
    {
        return $this->eventBaseQuery($term)
            ->with(['service:id,name'])
            ->withCount('participants')
            ->limit($limit)
            ->get(['id', 'name', 'description', 'service_id', 'format', 'start_date', 'end_date', 'registration_deadline', 'canceled_at', 'max_participants']);
    }

    private function searchCourses(string $term, int $limit): Collection
    {
        return $this->courseBaseQuery($term)
            ->with(['service:id,name'])
            ->withCount('sessions')
            ->limit($limit)
            ->get(['id', 'name', 'description', 'service_id', 'status']);
    }

    private function searchSubscriptions(string $term, int $limit): Collection
    {
        return $this->subscriptionBaseQuery($term)
            ->with(['member:id,name,email', 'plan:id,name'])
            ->limit($limit)
            ->get(['id', 'member_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'amount_paid']);
    }

    private function searchServices(string $term, int $limit): Collection
    {
        return $this->serviceBaseQuery($term)
            ->withCount(['plans', 'courses', 'events', 'activities'])
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'description', 'status']);
    }

    private function searchPlans(string $term, int $limit): Collection
    {
        return $this->planBaseQuery($term)
            ->with(['service:id,name'])
            ->withCount('subscriptions')
            ->limit($limit)
            ->get(['id', 'name', 'service_id', 'price', 'duration_days', 'is_archived']);
    }

    private function searchActivities(string $term, int $limit): Collection
    {
        return $this->activityBaseQuery($term)
            ->limit($limit)
            ->get(['id', 'title', 'base_price', 'is_active']);
    }

    // -------------------------------------------------------------------------
    // Private search helpers — full page (paginated)
    // -------------------------------------------------------------------------

    private function searchMembersPaginated(string $term, int $perPage): mixed
    {
        return $this->memberBaseQuery($term)
            ->with(['validSubscriptions.plan'])
            ->paginate($perPage, ['id', 'name', 'email', 'phone', 'status', 'avatar']);
    }

    private function searchEventsPaginated(string $term, int $perPage): mixed
    {
        return $this->eventBaseQuery($term)
            ->with(['service:id,name'])
            ->withCount('participants')
            ->paginate($perPage, ['id', 'name', 'description', 'service_id', 'format', 'start_date', 'end_date', 'registration_deadline', 'canceled_at', 'max_participants']);
    }

    private function searchCoursesPaginated(string $term, int $perPage): mixed
    {
        return $this->courseBaseQuery($term)
            ->with(['service:id,name'])
            ->withCount('sessions')
            ->paginate($perPage, ['id', 'name', 'description', 'service_id', 'status']);
    }

    private function searchSubscriptionsPaginated(string $term, int $perPage): mixed
    {
        return $this->subscriptionBaseQuery($term)
            ->with(['member:id,name,email', 'plan:id,name'])
            ->paginate($perPage, ['id', 'member_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'amount_paid']);
    }

    private function searchServicesPaginated(string $term, int $perPage): mixed
    {
        return $this->serviceBaseQuery($term)
            ->withCount(['plans', 'courses', 'events', 'activities'])
            ->paginate($perPage, ['id', 'name', 'slug', 'description', 'status']);
    }

    private function searchPlansPaginated(string $term, int $perPage): mixed
    {
        return $this->planBaseQuery($term)
            ->with(['service:id,name'])
            ->withCount('subscriptions')
            ->paginate($perPage, ['id', 'name', 'service_id', 'price', 'duration_days', 'is_archived']);
    }

    private function searchActivitiesPaginated(string $term, int $perPage): mixed
    {
        return $this->activityBaseQuery($term)
            ->paginate($perPage, ['id', 'title', 'base_price', 'is_active']);
    }

    // -------------------------------------------------------------------------
    // Base queries (reused for count + fetch)
    // -------------------------------------------------------------------------

    private function memberBaseQuery(string $term): Builder
    {
        return Member::query()->searchable($term);
    }

    private function eventBaseQuery(string $term): Builder
    {
        return Event::query()
            ->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
    }

    private function courseBaseQuery(string $term): Builder
    {
        return Course::query()
            ->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
    }

    private function subscriptionBaseQuery(string $term): Builder
    {
        return Subscription::query()
            ->whereHas('member', function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
    }

    private function serviceBaseQuery(string $term): Builder
    {
        return Service::query()
            ->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
    }

    private function planBaseQuery(string $term): Builder
    {
        return Plan::withoutGlobalScopes()
            ->where('name', 'like', "%{$term}%");
    }

    private function activityBaseQuery(string $term): Builder
    {
        return Activity::query()
            ->where(function ($q) use ($term): void {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
    }

    /**
     * @return array<string, Collection>
     */
    private function emptyResults(): array
    {
        return array_fill_keys(
            ['members', 'events', 'courses', 'subscriptions', 'services', 'plans', 'activities'],
            collect()
        );
    }
}
