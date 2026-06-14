<?php

namespace App\Livewire\Admin\Reservations;

use App\DTOs\StoreReservationDTO;
use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Repositories\ReservationRepository;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ReservationManager extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $search = '';

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public bool $isDetailPanelOpen = false;

    public ?int $selectedReservationId = null;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public string $memberSearch = '';

    public string $activitySearch = '';

    public ?int $createMemberId = null;

    public ?int $createActivityId = null;

    public ?int $createActivitySessionId = null;

    public string $createDate = '';

    public ?int $editReservationId = null;

    public ?int $editMemberId = null;

    public ?int $editActivityId = null;

    public ?int $editActivitySessionId = null;

    public string $editDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openReservationDetail(int $reservationId): void
    {
        $this->selectedReservationId = $reservationId;
        $this->isDetailPanelOpen = true;
    }

    public function closeReservationDetail(): void
    {
        $this->isDetailPanelOpen = false;
    }

    public function openCreateModal(): void
    {
        $this->ensureStaff();
        $this->resetValidation();
        $this->resetCreateForm();
        $this->createDate = today()->toDateString();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $reservationId): void
    {
        $this->ensureStaff();
        $this->resetValidation();
        $this->resetEditForm();

        $reservation = ApiReservation::query()
            ->with(['member', 'activity', 'session'])
            ->findOrFail($reservationId);

        $this->editReservationId = $reservation->id;
        $this->editMemberId = $reservation->member_id;
        $this->editActivityId = $reservation->activity_id;
        $this->editActivitySessionId = $reservation->activity_session_id;
        $this->editDate = $reservation->date?->toDateString() ?? today()->toDateString();
        $this->showEditModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    public function updatedCreateActivityId(): void
    {
        $this->createActivitySessionId = null;
    }

    public function updatedEditActivityId(): void
    {
        $this->editActivitySessionId = null;
    }

    public function updatedCreateDate(): void
    {
        $this->createActivitySessionId = null;
    }

    public function updatedEditDate(): void
    {
        $this->editActivitySessionId = null;
    }

    public bool $showActionModal = false;

    public ?int $actionReservationId = null;

    public string $actionType = '';

    public string $actionTitle = '';

    public string $actionPrompt = '';

    private function ensureStaff(): void
    {
        $user = auth()->user();

        if ($user === null || ! $user->isStaff()) {
            throw new AuthorizationException;
        }
    }

    public function verifyPayment(int $paymentId): void
    {
        $this->ensureStaff();

        $payment = Payment::query()->findOrFail($paymentId);

        $service = app(PaymentService::class);

        $result = $service->verify($payment, $payment->gateway_transaction_id ?? null);

        if (! empty($result['success'])) {
            if ($payment->reservation) {
                $payment->reservation->update(['payment_status' => 'paid']);
            }

            $this->dispatch('toast', message: __('Payment verified.'), type: 'success');

            return;
        }

        $this->dispatch('toast', message: __('Payment verification failed.'), type: 'danger');
    }

    public function createReservation(): void
    {
        $this->ensureStaff();

        $validated = $this->validate($this->createRules());

        $member = Member::query()->findOrFail($validated['createMemberId']);
        $session = ActivitySession::query()->findOrFail($validated['createActivitySessionId']);

        $reservationService = app(ReservationService::class);
        $reservationService->assertNoActiveReservationForSession($member, $session->id, $validated['createDate']);

        $reservation = $reservationService->makeActivityReservation(
            $member,
            new StoreReservationDTO(
                activityId: $session->activity_id,
                activitySessionId: $session->id,
                date: $validated['createDate'],
            )
        );

        $reservation->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $this->closeCreateModal();
        $this->dispatch('toast', message: __('Reservation created successfully.'), type: 'success');
    }

    public function updateReservation(): void
    {
        $this->ensureStaff();

        if ($this->editReservationId === null) {
            return;
        }

        $validated = $this->validate($this->editRules());
        $reservation = ApiReservation::query()->findOrFail($this->editReservationId);

        DB::transaction(function () use ($validated, $reservation): void {
            $reservationRepository = app(ReservationRepository::class);
            $reservationRepository->lockSessionForUpdate((int) $validated['editActivitySessionId']);

            if ((int) $validated['editActivitySessionId'] !== (int) $reservation->activity_session_id) {
                $alreadyReserved = ApiReservation::where('activity_session_id', (int) $validated['editActivitySessionId'])
                    ->whereDate('date', $validated['editDate'])
                    ->where('status', '!=', 'cancelled')
                    ->where('id', '!=', $reservation->id)
                    ->exists();

                if ($alreadyReserved) {
                    throw ValidationException::withMessages([
                        'editActivitySessionId' => ['This activity session is already reserved for this date.'],
                    ]);
                }
            }

            $reservation->update([
                'member_id' => (int) $validated['editMemberId'],
                'activity_id' => (int) $validated['editActivityId'],
                'activity_session_id' => (int) $validated['editActivitySessionId'],
                'date' => $validated['editDate'],
            ]);
        });

        $this->closeEditModal();
        $this->dispatch('toast', message: __('Reservation updated successfully.'), type: 'success');
    }

    public function confirmReservation(int $reservationId): void
    {
        $this->ensureStaff();

        $reservation = ApiReservation::query()->findOrFail($reservationId);
        if ($reservation->status === 'confirmed') {
            $this->dispatch('toast', message: __('Reservation already confirmed.'), type: 'info');

            return;
        }

        $reservation->update(['status' => 'confirmed']);

        $this->dispatch('toast', message: __('Reservation confirmed.'), type: 'success');
    }

    public function cancelReservation(int $reservationId): void
    {
        $this->ensureStaff();

        $reservation = ApiReservation::query()->findOrFail($reservationId);
        if ($reservation->status === 'cancelled') {
            $this->dispatch('toast', message: __('Reservation already cancelled.'), type: 'info');

            return;
        }

        $reservation->update(['status' => 'cancelled']);

        $this->dispatch('toast', message: __('Reservation cancelled.'), type: 'success');
    }

    public function deleteReservation(int $reservationId): void
    {
        $this->ensureStaff();

        $reservation = ApiReservation::query()->findOrFail($reservationId);

        try {
            $reservation->delete();
            $this->dispatch('toast', message: __('Reservation deleted.'), type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: __('Failed to delete reservation.'), type: 'danger');
        }
    }

    public function openActionModal(string $action, int $reservationId): void
    {
        $this->ensureStaff();

        $this->actionType = $action;
        $this->actionReservationId = $reservationId;
        $reservation = ApiReservation::query()->with('member')->find($reservationId);

        match ($action) {
            'confirm' => $this->setActionContent(__('Confirm Reservation'), __('Are you sure you want to confirm this reservation?'), ['member' => $reservation?->member?->name, 'amount' => $reservation?->price]),
            'cancel' => $this->setActionContent(__('Cancel Reservation'), __('Are you sure you want to cancel this reservation? This action cannot be undone.'), ['member' => $reservation?->member?->name, 'amount' => $reservation?->price]),
            'delete' => $this->setActionContent(__('Delete Reservation'), __('Delete this reservation? This action cannot be undone.'), ['member' => $reservation?->member?->name, 'amount' => $reservation?->price]),
            default => $this->setActionContent(__('Confirm Action'), __('Are you sure you want to perform this action?'), ['member' => $reservation?->member?->name, 'amount' => $reservation?->price]),
        };
    }

    private function setActionContent(string $title, string $prompt): void
    {
        $this->actionTitle = $title;
        $this->actionPrompt = $prompt;
        $this->showActionModal = true;
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->actionReservationId = null;
        $this->actionType = '';
        $this->actionTitle = '';
        $this->actionPrompt = '';
    }

    public function confirmAction(): void
    {
        $this->ensureStaff();

        if ($this->actionReservationId === null || $this->actionType === '') {
            $this->closeActionModal();

            return;
        }

        $id = (int) $this->actionReservationId;

        match ($this->actionType) {
            'confirm' => $this->confirmReservation($id),
            'cancel' => $this->cancelReservation($id),
            'delete' => $this->deleteReservation($id),
            default => null,
        };

        $this->closeActionModal();
    }

    #[Computed]
    public function reservations(): LengthAwarePaginator
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::query()
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function activities(): Collection
    {
        return Activity::query()
            ->active()
            ->orderBy('title')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function availableSessions(): Collection
    {
        if ($this->createActivityId === null) {
            return collect();
        }

        $query = ActivitySession::query()
            ->with('activity')
            ->where('activity_id', $this->createActivityId)
            ->where('is_cancelled', false)
            ->orderBy('day_of_week')
            ->orderBy('starts_at');

        if ($this->createDate !== '' && $this->createDate !== '0') {
            try {
                $dayOfWeek = Carbon::parse($this->createDate)->dayOfWeek;
                $query->where('day_of_week', $dayOfWeek);
            } catch (\Exception $e) {
                // Ignore parsing errors if date is invalid
            }

            $reservedIds = ApiReservation::where('activity_id', $this->createActivityId)
                ->whereDate('date', $this->createDate)
                ->where('status', '!=', 'cancelled')
                ->pluck('activity_session_id');

            $query->whereNotIn('id', $reservedIds);
        }

        return $query->get()->values();
    }

    #[Computed]
    public function editAvailableSessions(): Collection
    {
        if ($this->editActivityId === null) {
            return collect();
        }

        $query = ActivitySession::query()
            ->with('activity')
            ->where('activity_id', $this->editActivityId)
            ->where('is_cancelled', false)
            ->orderBy('day_of_week')
            ->orderBy('starts_at');

        if ($this->editDate !== '' && $this->editDate !== '0') {
            try {
                $dayOfWeek = Carbon::parse($this->editDate)->dayOfWeek;
                $query->where('day_of_week', $dayOfWeek);
            } catch (\Exception $e) {
                // Ignore parsing errors if date is invalid
            }

            $reservedIds = ApiReservation::where('activity_id', $this->editActivityId)
                ->whereDate('date', $this->editDate)
                ->where('status', '!=', 'cancelled')
                ->when($this->editReservationId !== null, fn ($q) => $q->where('id', '!=', $this->editReservationId))
                ->pluck('activity_session_id');

            if ($reservedIds->isNotEmpty()) {
                $query->whereNotIn('id', $reservedIds);
            }
        }

        return $query->get()->values();
    }

    #[Computed]
    public function selectedCreateSession(): ?ActivitySession
    {
        if ($this->createActivitySessionId === null) {
            return null;
        }

        return ActivitySession::query()
            ->with('activity')
            ->find($this->createActivitySessionId);
    }

    #[Computed]
    public function selectedEditSession(): ?ActivitySession
    {
        if ($this->editActivitySessionId === null) {
            return null;
        }

        return ActivitySession::query()
            ->with('activity')
            ->find($this->editActivitySessionId);
    }

    #[Computed]
    public function selectedReservation(): ?ApiReservation
    {
        if ($this->selectedReservationId === null) {
            return null;
        }

        return $this->baseQuery()
            ->whereKey($this->selectedReservationId)
            ->first();
    }

    public function render(): View
    {
        return view('livewire.admin.reservations.reservation-manager');
    }

    private function createRules(): array
    {
        return [
            'createMemberId' => ['required', 'exists:members,id'],
            'createActivityId' => ['required', 'exists:activities,id'],
            'createDate' => ['required', 'date', 'after_or_equal:today'],
            'createActivitySessionId' => [
                'required',
                Rule::exists('activity_sessions', 'id')->where(fn ($query) => $query
                    ->where('activity_id', $this->createActivityId)
                    ->where('is_cancelled', false)),
            ],
        ];
    }

    private function editRules(): array
    {
        return [
            'editMemberId' => ['required', 'exists:members,id'],
            'editActivityId' => ['required', 'exists:activities,id'],
            'editDate' => ['required', 'date', 'after_or_equal:today'],
            'editActivitySessionId' => [
                'required',
                Rule::exists('activity_sessions', 'id')->where(fn ($query) => $query
                    ->where('activity_id', $this->editActivityId)
                    ->where('is_cancelled', false)),
            ],
        ];
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'memberSearch',
            'activitySearch',
            'createMemberId',
            'createActivityId',
            'createActivitySessionId',
            'createDate',
        ]);

        $this->createDate = today()->toDateString();
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editReservationId',
            'editMemberId',
            'editActivityId',
            'editActivitySessionId',
            'editDate',
        ]);
    }

    private function baseQuery(): Builder
    {
        return ApiReservation::query()
            ->with([
                'member.validSubscriptions.plan',
                'activity',
                'session',
                'payments' => fn ($query) => $query->orderByDesc('id'),
            ])
            ->withCount('payments')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(function (Builder $builder) use ($term): void {
                    $builder
                        ->whereHas('member', function (Builder $memberQuery) use ($term): void {
                            $memberQuery
                                ->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        })
                        ->orWhereHas('activity', function (Builder $activityQuery) use ($term): void {
                            $activityQuery->where('title', 'like', $term);
                        })
                        ->orWhereHas('session', function (Builder $sessionQuery) use ($term): void {
                            $sessionQuery
                                ->where('starts_at', 'like', $term);
                        });
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->paymentStatusFilter !== '', fn (Builder $query) => $query->where('payment_status', $this->paymentStatusFilter))
            ->orderByDesc('date')
            ->orderByDesc('id');
    }
}
