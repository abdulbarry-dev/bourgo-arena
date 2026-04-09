<?php

namespace App\Livewire\Admin\CourseSessions;

use App\Models\Booking;
use App\Models\CourseSession;
use App\Models\CourseSessionException;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseSessionManager extends Component
{
    public $currentDate;

    public function mount()
    {
        $this->currentDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function previousWeek()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subWeek()->format('Y-m-d');
    }

    public function nextWeek()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addWeek()->format('Y-m-d');
    }

    public function currentWeek()
    {
        $this->currentDate = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    #[Computed]
    public function weekStart()
    {
        return Carbon::parse($this->currentDate)->startOfWeek(Carbon::MONDAY);
    }

    #[Computed]
    public function weekEnd()
    {
        return Carbon::parse($this->currentDate)->endOfWeek(Carbon::SUNDAY);
    }

    #[Computed]
    public function days()
    {
        $days = [];
        $start = $this->weekStart->copy();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i);
        }

        return $days;
    }

    #[Computed]
    public function sessions()
    {
        return CourseSession::with('course')->where('is_cancelled', false)->get();
    }

    public function sessionsForDay($dayOfWeekIsoIndex) // 0 for Monday, 6 for Sunday
    {
        return $this->sessions->where('day_of_week', $dayOfWeekIsoIndex)->sortBy('starts_at');
    }

    public function isSessionCancelled($sessionId, $date)
    {
        return CourseSessionException::where('course_session_id', $sessionId)
            ->where('date', $date->format('Y-m-d'))
            ->where('is_cancelled', true)
            ->exists();
    }

    public function getBookingsCount($sessionId, $date)
    {
        return Booking::where('course_session_id', $sessionId)
            ->where('date', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->count();
    }

    #[On('course-session-created')]
    #[On('course-session-updated')]
    public function refreshManager()
    {
        unset($this->sessions);
    }

    public function openClassDetails($sessionId, $dateString)
    {
        $this->dispatch('open-session-details', sessionId: $sessionId, date: $dateString);
    }

    public function openCreateModal($dayIndex = null)
    {
        $this->dispatch('open-create-course-session', dayIndex: $dayIndex);
        Flux::modal('create-course-session')->show();
    }

    public function render()
    {
        return view('components.admin.course-sessions.course-session-manager')->layout('layouts.app');
    }
}
