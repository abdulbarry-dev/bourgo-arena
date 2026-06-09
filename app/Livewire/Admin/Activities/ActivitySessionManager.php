<?php

namespace App\Livewire\Admin\Activities;

use App\Models\Activity;
use App\Models\ActivitySessionException;
use App\Models\ApiReservation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ActivitySessionManager extends Component
{
    public Activity $activity;

    public $currentDate;

    public string $viewMode = 'week';

    public function mount(Activity $activity): void
    {
        $this->activity = $activity;
        $this->currentDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        if ($mode === 'month') {
            $this->currentDate = now()->startOfMonth()->format('Y-m-d');
        } else {
            $this->currentDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }
    }

    public function previousPeriod(): void
    {
        $this->currentDate = $this->viewMode === 'month'
            ? Carbon::parse($this->currentDate)->subMonth()->startOfMonth()->format('Y-m-d')
            : Carbon::parse($this->currentDate)->subWeek()->format('Y-m-d');
    }

    public function nextPeriod(): void
    {
        $this->currentDate = $this->viewMode === 'month'
            ? Carbon::parse($this->currentDate)->addMonth()->startOfMonth()->format('Y-m-d')
            : Carbon::parse($this->currentDate)->addWeek()->format('Y-m-d');
    }

    public function currentPeriod(): void
    {
        $this->currentDate = $this->viewMode === 'month'
            ? now()->startOfMonth()->format('Y-m-d')
            : now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    #[Computed]
    public function weekStart(): Carbon
    {
        return Carbon::parse($this->currentDate)->startOfWeek(Carbon::MONDAY);
    }

    #[Computed]
    public function weekEnd(): Carbon
    {
        return Carbon::parse($this->currentDate)->endOfWeek(Carbon::SUNDAY);
    }

    #[Computed]
    public function monthStart(): Carbon
    {
        return Carbon::parse($this->currentDate)->startOfMonth();
    }

    #[Computed]
    public function monthEnd(): Carbon
    {
        return Carbon::parse($this->currentDate)->endOfMonth();
    }

    #[Computed]
    public function weeks(): array
    {
        $start = $this->monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $end = $this->monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $week[] = $current->copy();
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    #[Computed]
    public function days(): array
    {
        $days = [];
        $start = $this->weekStart->copy();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i);
        }

        return $days;
    }

    public function sessions()
    {
        $query = $this->activity->sessions()
            ->with('activity.service')
            ->where('is_cancelled', false);

        if ($this->viewMode === 'month') {
            $query->where('starts_at_date', '<=', $this->monthEnd->toDateString())
                ->where(function ($q): void {
                    $q->whereNull('ends_at_date')
                        ->orWhere('ends_at_date', '>=', $this->monthStart->toDateString());
                });
        } else {
            $query->where('starts_at_date', '<=', $this->weekEnd->toDateString())
                ->where(function ($q): void {
                    $q->whereNull('ends_at_date')
                        ->orWhere('ends_at_date', '>=', $this->weekStart->toDateString());
                });
        }

        return $query->get();
    }

    public function sessionsForDay(int $dayOfWeekIsoIndex)
    {
        return $this->sessions()->where('day_of_week', $dayOfWeekIsoIndex)->sortBy('starts_at');
    }

    public function monthSessionsForDay(Carbon $date)
    {
        $dayIndex = $date->dayOfWeekIso - 1;
        $dateString = $date->toDateString();

        return $this->sessions()
            ->filter(function ($session) use ($dayIndex, $dateString) {
                if ((int) $session->day_of_week !== $dayIndex) {
                    return false;
                }

                if ($session->starts_at_date
                    && Carbon::parse($session->starts_at_date)->toDateString() > $dateString) {
                    return false;
                }

                if ($session->ends_at_date
                    && Carbon::parse($session->ends_at_date)->toDateString() < $dateString) {
                    return false;
                }

                return true;
            })
            ->sortBy('starts_at')
            ->values();
    }

    #[Computed]
    public function weekSummary(): array
    {
        $sessions = $this->sessions();
        $todayIndex = now()->dayOfWeekIso - 1;

        return [
            'sessions' => $sessions->count(),
            'activeDays' => $sessions->pluck('day_of_week')->unique()->count(),
            'todaySessions' => $sessions->where('day_of_week', $todayIndex)->count(),
        ];
    }

    public function isSessionCancelled(int $sessionId, Carbon $date): bool
    {
        return ActivitySessionException::where('activity_session_id', $sessionId)
            ->where('date', $date->format('Y-m-d'))
            ->where('is_cancelled', true)
            ->exists();
    }

    public function getReservationsCount(int $sessionId, Carbon $date): int
    {
        return ApiReservation::where('activity_session_id', $sessionId)
            ->where('date', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->count();
    }

    public function openSessionDetails(int $sessionId, string $dateString): void
    {
        $this->dispatch('open-activity-session-details', sessionId: $sessionId, date: $dateString);
    }

    public function openCreateModal(?int $dayIndex = null): void
    {
        $date = null;
        if ($dayIndex !== null && $this->viewMode === 'week') {
            $date = $this->weekStart->copy()->addDays($dayIndex)->toDateString();
        }

        $this->dispatch('open-create-activity-session', dayIndex: $dayIndex, date: $date, activityId: $this->activity->id);
    }

    public function openCreateForDate(string $dateString): void
    {
        $date = Carbon::parse($dateString);
        $dayIndex = $date->dayOfWeekIso - 1;

        $this->dispatch('open-create-activity-session', dayIndex: $dayIndex, date: $date->toDateString(), activityId: $this->activity->id);
    }

    public function render()
    {
        return view('components.admin.activities.activity-session-manager');
    }
}
