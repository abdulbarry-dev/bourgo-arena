<?php

namespace App\Livewire\Admin\Reservations;

use App\DTOs\StoreReservationDTO;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Repositories\ReservationRepository;
use App\Services\Payment\PaymentManager as PMManager;
use App\Services\PaymentService;
use App\Services\ReservationService;
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

    public ?int $createActivitySlotId = null;

    public ?int $editReservationId = null;

    public ?int $editMemberId = null;

    public ?int $editActivityId = null;

    public ?int $editActivitySlotId = null;

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
        $this->showCreateModal = true;
    }

    public function openEditModal(int $reservationId): void
    {
        $this->ensureStaff();
        $this->resetValidation();
        $this->resetEditForm();

        $reservation = ApiReservation::query()
            ->with(['member', 'activity', 'slot'])
            ->findOrFail($reservationId);

        $this->editReservationId = $reservation->id;
        $this->editMemberId = $reservation->member_id;
        $this->editActivityId = $reservation->activity_id;
        $this->editActivitySlotId = $reservation->activity_slot_id;
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
        $this->createActivitySlotId = null;
    }

    public function updatedEditActivityId(): void
    {
        $this->editActivitySlotId = null;
    }

    // Generic action confirmation modal state
    public bool $showActionModal = false;

    public ?int $actionReservationId = null;

    public string $actionType = '';

    public string $actionTitle = '';

    public string $actionPrompt = '';

    public bool $showRefundModal = false;

    public ?int $refundPaymentId = null;

    public string|float $refundAmount = '';

    // history modal state
    public bool $showHistoryModal = false;

    public ?int $historyPaymentId = null;

    public int $historyPerPage = 10;

    public int $historyPage = 1;

    public array $showRaw = [];

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
            $payment->update(['reconciled_by' => auth()->id(), 'reconciled_at' => now()]);

            // record reconciliation event
            try {
                $payment->reconciliations()->create([
                    'admin_id' => auth()->id(),
                    'type' => 'reconciled',
                    'amount' => null,
                ]);
            } catch (\Throwable $e) {
                // non-fatal: reconciliation record failure shouldn't break UX
            }

            if ($payment->reservation) {
                $payment->reservation->update(['payment_status' => 'paid']);
            }

            $this->dispatch('toast', message: __('Payment verified.'), type: 'success');

            return;
        }

        $this->dispatch('toast', message: __('Payment verification failed.'), type: 'danger');
    }

    public function openRefundModal(int $paymentId): void
    {
        $this->ensureStaff();

        $payment = Payment::query()->findOrFail($paymentId);

        $this->refundPaymentId = $payment->id;
        $this->refundAmount = (string) $payment->amount;
        $this->showRefundModal = true;
    }

    public function closeRefundModal(): void
    {
        $this->refundPaymentId = null;
        $this->refundAmount = '';
        $this->showRefundModal = false;
    }

    public function confirmRefund(): void
    {
        $this->ensureStaff();

        if ($this->refundPaymentId === null) {
            return;
        }

        $this->refundPayment((int) $this->refundPaymentId, (float) $this->refundAmount);

        $this->closeRefundModal();
    }

    public function openRefundForReservation(int $reservationId): void
    {
        $this->ensureStaff();

        $reservation = ApiReservation::query()
            ->with(['payments' => fn ($query) => $query->orderByDesc('id')])
            ->findOrFail($reservationId);

        if (! $reservation->isRefundable()) {
            $this->dispatch('toast', message: __('This reservation cannot be refunded because its time slot has already passed.'), type: 'danger');

            return;
        }

        $payment = $reservation->payments->firstWhere('status', 'paid');

        if ($payment === null) {
            $this->dispatch('toast', message: __('No paid payment is available to refund for this reservation.'), type: 'info');

            return;
        }

        $this->openRefundModal($payment->id);
    }

    public function openHistoryModal(int $paymentId): void
    {
        $this->historyPaymentId = $paymentId;
        $this->showHistoryModal = true;
        // use a separate paginator page name to avoid colliding with main pagination
        $this->resetPage('rec_page');
        $this->showRaw = [];
    }

    public function closeHistoryModal(): void
    {
        $this->historyPaymentId = null;
        $this->showHistoryModal = false;
        $this->historyPage = 1;
    }

    public function createReservation(): void
    {
        $this->ensureStaff();

        $validated = $this->validate($this->createRules());

        $member = Member::query()->findOrFail($validated['createMemberId']);
        $slot = ActivitySlot::query()->with('activity')->findOrFail($validated['createActivitySlotId']);

        $reservationService = app(ReservationService::class);
        $reservationService->assertNoActiveReservationForSlot($member, $slot->id);

        $reservationService->makeActivityReservation(
            $member,
            new StoreReservationDTO(
                activityId: $slot->activity_id,
                activitySlotId: $slot->id,
                date: $slot->date->toDateString(),
            )
        );

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
            $oldSlot = $reservationRepository->lockSlotForUpdate((int) $reservation->activity_slot_id);
            $newSlot = $reservationRepository->lockSlotForUpdate((int) $validated['editActivitySlotId']);

            if ((int) $validated['editActivitySlotId'] !== (int) $reservation->activity_slot_id) {
                if ($newSlot->isFullyBooked()) {
                    throw ValidationException::withMessages([
                        'editActivitySlotId' => ['This activity slot is already fully booked.'],
                    ]);
                }

                $oldSlot->decrement('booked_count');
                $newSlot->increment('booked_count');
            }

            $reservation->update([
                'member_id' => (int) $validated['editMemberId'],
                'activity_id' => (int) $validated['editActivityId'],
                'activity_slot_id' => (int) $validated['editActivitySlotId'],
                'date' => $newSlot->date,
                'starts_at' => $newSlot->starts_at,
                'ends_at' => $newSlot->ends_at,
            ]);
        });

        $this->closeEditModal();
        $this->dispatch('toast', message: __('Reservation updated successfully.'), type: 'success');
    }

    public function toggleRaw(int $id): void
    {
        if (! empty($this->showRaw[$id])) {
            unset($this->showRaw[$id]);

            return;
        }

        $this->showRaw[$id] = true;
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

    // Open the shared action confirmation modal
    public function openActionModal(string $action, int $reservationId): void
    {
        $this->ensureStaff();

        $this->actionType = $action;
        $this->actionReservationId = $reservationId;

        match ($action) {
            'confirm' => $this->setActionContent(__('Confirm Reservation'), __('Are you sure you want to confirm this reservation?')),
            'cancel' => $this->setActionContent(__('Cancel Reservation'), __('Are you sure you want to cancel this reservation? This action cannot be undone.')),
            'delete' => $this->setActionContent(__('Delete Reservation'), __('Delete this reservation? This action cannot be undone.')),
            default => $this->setActionContent(__('Confirm Action'), __('Are you sure you want to perform this action?')),
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

    public function refundPayment(int $paymentId, ?float $amount = null): void
    {
        $this->ensureStaff();

        $payment = Payment::query()->with('reservation')->findOrFail($paymentId);

        if ($payment->reservation && ! $payment->reservation->isRefundable()) {
            $this->dispatch('toast', message: __('This payment cannot be refunded because the reservation time slot has already passed.'), type: 'danger');

            return;
        }

        $manager = app(PMManager::class);
        $driver = $manager->driver($payment->driver ?? $manager->getDefaultDriver());

        $transactionId = $payment->gateway_transaction_id ?? $payment->payment_reference;
        $result = $driver->refund($transactionId, $amount ?? (float) $payment->amount);

        if (! empty($result['success'])) {
            $payment->update([
                'status' => 'refunded',
                'metadata' => array_merge($payment->metadata ?? [], ['refund' => $result]),
                'refunded_by' => auth()->id(),
                'refunded_at' => now(),
                'refund_amount' => $amount ?? (float) $payment->amount,
            ]);

            // record refund reconciliation event
            try {
                $payment->reconciliations()->create([
                    'admin_id' => auth()->id(),
                    'type' => 'refunded',
                    'amount' => $amount ?? (float) $payment->amount,
                ]);
            } catch (\Throwable $e) {
                // non-fatal: continue
            }

            if ($payment->reservation) {
                $payment->reservation->update(['payment_status' => 'refunded']);
            }

            $this->dispatch('toast', message: __('Payment refunded.'), type: 'success');

            return;
        }

        $payment->update(['status' => 'failed', 'metadata' => array_merge($payment->metadata ?? [], ['refund_error' => $result])]);
        $this->dispatch('toast', message: __('Refund failed.'), type: 'danger');
    }

    #[Computed]
    public function reservations(): LengthAwarePaginator
    {
        return $this->baseQuery()->paginate(10);
    }

    #[Computed]
    public function members(): Collection
    {
        // Return a larger unfiltered set for client-side searchable select
        return Member::query()
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function activities(): Collection
    {
        // Return activities for client-side searchable select
        return Activity::query()
            ->active()
            ->orderBy('title')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function availableSlots(): Collection
    {
        if ($this->createActivityId === null) {
            return collect();
        }

        return ActivitySlot::query()
            ->where('activity_id', $this->createActivityId)
            ->where('is_available', true)
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (ActivitySlot $slot): bool => ! $slot->isFullyBooked())
            ->values();
    }

    #[Computed]
    public function editAvailableSlots(): Collection
    {
        if ($this->editActivityId === null) {
            return collect();
        }

        return ActivitySlot::query()
            ->where('activity_id', $this->editActivityId)
            ->where('is_available', true)
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (ActivitySlot $slot): bool => ! $slot->isFullyBooked() || $slot->id === $this->editActivitySlotId)
            ->values();
    }

    #[Computed]
    public function selectedCreateSlot(): ?ActivitySlot
    {
        if ($this->createActivitySlotId === null) {
            return null;
        }

        return $this->availableSlots->firstWhere('id', $this->createActivitySlotId);
    }

    #[Computed]
    public function selectedEditSlot(): ?ActivitySlot
    {
        if ($this->editActivitySlotId === null) {
            return null;
        }

        return $this->editAvailableSlots->firstWhere('id', $this->editActivitySlotId);
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

    #[Computed]
    public function paymentReconciliationsPaginated(): ?LengthAwarePaginator
    {
        if ($this->historyPaymentId === null) {
            return null;
        }

        return PaymentReconciliation::query()
            ->where('payment_id', $this->historyPaymentId)
            ->orderByDesc('id')
            ->paginate($this->historyPerPage, ['*'], 'rec_page', $this->historyPage);
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
            'createActivitySlotId' => [
                'required',
                Rule::exists('activity_slots', 'id')->where(fn ($query) => $query
                    ->where('activity_id', $this->createActivityId)
                    ->where('is_available', true)
                    ->whereDate('date', '>=', today())),
            ],
        ];
    }

    private function editRules(): array
    {
        return [
            'editMemberId' => ['required', 'exists:members,id'],
            'editActivityId' => ['required', 'exists:activities,id'],
            'editActivitySlotId' => [
                'required',
                Rule::exists('activity_slots', 'id')->where(fn ($query) => $query
                    ->where('activity_id', $this->editActivityId)
                    ->where('is_available', true)
                    ->whereDate('date', '>=', today())),
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
            'createActivitySlotId',
        ]);
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editReservationId',
            'editMemberId',
            'editActivityId',
            'editActivitySlotId',
        ]);
    }

    private function baseQuery(): Builder
    {
        return ApiReservation::query()
            ->with([
                'member.activeSubscription.plan',
                'activity',
                'slot',
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
                        ->orWhereHas('slot', function (Builder $slotQuery) use ($term): void {
                            $slotQuery->where('date', 'like', $term);
                        });
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->paymentStatusFilter !== '', fn (Builder $query) => $query->where('payment_status', $this->paymentStatusFilter))
            ->orderByDesc('date')
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }
}
