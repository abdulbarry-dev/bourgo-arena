<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Services\Admin\NotificationDispatchService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Notification Center')]
class Dashboard extends Component
{
    use AuthorizesRequests, WithPagination;

    // ─── Type CRUD ───────────────────────────────────────
    public bool $showTypeFlyout = false;

    public string $typeFlyoutMode = 'create';

    public ?NotificationType $editingType = null;

    public string $typeName = '';

    public string $typeSlug = '';

    public string $typeDescription = '';

    public string $typeCategory = 'system';

    public string $typeIcon = 'bell';

    public bool $typePushEnabled = true;

    public bool $typeEmailEnabled = true;

    public bool $typeSmsEnabled = false;

    public bool $typeIsActive = true;

    public ?NotificationType $deletingType = null;

    // ─── Compose ─────────────────────────────────────────
    public ?int $composeTypeId = null;

    /** @var array<int, string> */
    public array $composeChannels = [];

    public string $composeAudience = 'all';

    /** @var array<int, int> */
    public array $composeMemberIds = [];

    public string $composeMemberSearch = '';

    public string $composeSubject = '';

    public string $composeBody = '';

    // ─── History ─────────────────────────────────────────
    public string $logStatusFilter = '';

    public function openCreateTypeFlyout(): void
    {
        $this->resetTypeForm();
        $this->typeFlyoutMode = 'create';
        $this->showTypeFlyout = true;
    }

    public function openEditTypeFlyout(int $typeId): void
    {
        $type = NotificationType::findOrFail($typeId);
        $this->editingType = $type;
        $this->typeName = $type->name;
        $this->typeSlug = $type->slug;
        $this->typeDescription = $type->description ?? '';
        $this->typeCategory = $type->category;
        $this->typeIcon = $type->icon;
        $this->typePushEnabled = $type->push_enabled;
        $this->typeEmailEnabled = $type->email_enabled;
        $this->typeSmsEnabled = $type->sms_enabled;
        $this->typeIsActive = $type->is_active;
        $this->typeFlyoutMode = 'edit';
        $this->showTypeFlyout = true;
    }

    public function saveType(): void
    {
        $this->validate([
            'typeName' => 'required|string|max:255',
            'typeSlug' => 'required|string|max:255|unique:notification_types,slug,'.($this->editingType?->id ?? 'NULL'),
            'typeDescription' => 'nullable|string|max:1000',
            'typeCategory' => 'required|in:billing,events,promotions,system',
            'typeIcon' => 'required|string|max:255',
            'typePushEnabled' => 'boolean',
            'typeEmailEnabled' => 'boolean',
            'typeSmsEnabled' => 'boolean',
            'typeIsActive' => 'boolean',
        ]);

        $data = [
            'slug' => $this->typeSlug,
            'name' => $this->typeName,
            'description' => $this->typeDescription ?: null,
            'category' => $this->typeCategory,
            'icon' => $this->typeIcon,
            'push_enabled' => $this->typePushEnabled,
            'email_enabled' => $this->typeEmailEnabled,
            'sms_enabled' => $this->typeSmsEnabled,
            'is_active' => $this->typeIsActive,
        ];

        if ($this->typeFlyoutMode === 'edit' && $this->editingType) {
            $this->editingType->update($data);
            $this->dispatch('toast', message: __('Notification type updated successfully.'), type: 'success');
        } else {
            NotificationType::create($data);
            $this->dispatch('toast', message: __('Notification type created successfully.'), type: 'success');
        }

        $this->showTypeFlyout = false;
        $this->editingType = null;
    }

    public function confirmDeleteType(int $typeId): void
    {
        $this->deletingType = NotificationType::findOrFail($typeId);
        Flux::modal('confirm-delete-type')->show();
    }

    public function deleteType(): void
    {
        if ($this->deletingType) {
            $this->deletingType->delete();
            $this->deletingType = null;
            $this->dispatch('toast', message: __('Notification type deleted successfully.'), type: 'success');
        }
    }

    public function toggleTypeChannel(int $typeId, string $channel): void
    {
        $type = NotificationType::find($typeId);

        if ($type === null) {
            return;
        }

        $column = $channel.'_enabled';

        if (in_array($column, ['push_enabled', 'email_enabled', 'sms_enabled'])) {
            $type->update([$column => ! $type->{$column}]);
        }
    }

    public function toggleTypeActive(int $typeId): void
    {
        $type = NotificationType::find($typeId);

        if ($type === null) {
            return;
        }

        $type->update(['is_active' => ! $type->is_active]);
    }

    // ─── Compose ─────────────────────────────────────────

    public function updatedComposeTypeId(): void
    {
        if ($this->composeTypeId === null) {
            $this->composeChannels = [];
            $this->composeSubject = '';
            $this->composeBody = '';

            return;
        }

        $type = NotificationType::find($this->composeTypeId);

        if ($type === null) {
            return;
        }

        $this->composeChannels = [];
        if ($type->push_enabled) {
            $this->composeChannels[] = 'push';
        }
        if ($type->email_enabled) {
            $this->composeChannels[] = 'email';
        }
        if ($type->sms_enabled) {
            $this->composeChannels[] = 'sms';
        }

        $this->composeSubject = $type->name;
    }

    public function getComposeMemberCountProperty(): int
    {
        if ($this->composeAudience === 'all') {
            return Member::query()->where('is_archived', false)->count();
        }

        if (! empty($this->composeMemberIds)) {
            return count($this->composeMemberIds);
        }

        return 0;
    }

    public function getSearchableMembersProperty()
    {
        if (empty($this->composeMemberSearch)) {
            return collect();
        }

        return Member::query()
            ->where('is_archived', false)
            ->where(function ($q) {
                $term = '%'.$this->composeMemberSearch.'%';
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            })
            ->limit(10)
            ->get();
    }

    public function addComposeMember(int $memberId): void
    {
        if (! in_array($memberId, $this->composeMemberIds)) {
            $this->composeMemberIds[] = $memberId;
        }
        $this->composeMemberSearch = '';
    }

    public function removeComposeMember(int $memberId): void
    {
        $this->composeMemberIds = array_values(array_filter(
            $this->composeMemberIds,
            fn ($id) => $id !== $memberId,
        ));
    }

    public function confirmSend(): void
    {
        $this->validate([
            'composeTypeId' => 'required|exists:notification_types,id',
            'composeChannels' => 'required|array|min:1',
            'composeChannels.*' => 'in:push,email,sms',
            'composeSubject' => 'required|string|max:255',
            'composeBody' => 'required|string',
        ]);

        Flux::modal('confirm-send-notification')->show();
    }

    public function sendNotification(NotificationDispatchService $dispatchService): void
    {
        $this->validate([
            'composeTypeId' => 'required|exists:notification_types,id',
            'composeChannels' => 'required|array|min:1',
            'composeChannels.*' => 'in:push,email,sms',
            'composeSubject' => 'required|string|max:255',
            'composeBody' => 'required|string',
        ]);

        $type = NotificationType::findOrFail($this->composeTypeId);

        $memberIds = $this->composeAudience === 'specific' ? $this->composeMemberIds : null;

        $logCount = $dispatchService->dispatch(
            type: $type,
            subject: $this->composeSubject,
            body: $this->composeBody,
            channels: $this->composeChannels,
            memberIds: $memberIds,
        );

        $this->resetCompose();
        $this->dispatch('toast', message: __('Notification queued for :count log entries.', ['count' => $logCount]), type: 'success');
    }

    public function resetCompose(): void
    {
        $this->composeTypeId = null;
        $this->composeChannels = [];
        $this->composeAudience = 'all';
        $this->composeMemberIds = [];
        $this->composeMemberSearch = '';
        $this->composeSubject = '';
        $this->composeBody = '';
    }

    public function render()
    {
        $this->authorize('access-dashboard-module', 'notifications');

        $types = NotificationType::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $totalSent = NotificationLog::query()->where('status', 'sent')->count();
        $totalFailed = NotificationLog::query()->where('status', 'failed')->count();
        $totalQueued = NotificationLog::query()->where('status', 'queued')->count();
        $totalAll = $totalSent + $totalFailed + $totalQueued;
        $successRate = $totalAll > 0 ? round(($totalSent / $totalAll) * 100) : 100;

        $logsQuery = NotificationLog::query()
            ->with('notificationType')
            ->orderByDesc('created_at');

        if (! empty($this->logStatusFilter)) {
            $logsQuery->where('status', $this->logStatusFilter);
        }

        $logs = $logsQuery->paginate(10);

        $registeredDevices = MemberDeviceToken::query()->where('is_active', true)->count();

        $categories = NotificationType::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return view('livewire.admin.notifications.dashboard', [
            'types' => $types,
            'totalSent' => $totalSent,
            'totalFailed' => $totalFailed,
            'totalQueued' => $totalQueued,
            'successRate' => $successRate,
            'logs' => $logs,
            'registeredDevices' => $registeredDevices,
            'categories' => $categories,
        ]);
    }

    private function resetTypeForm(): void
    {
        $this->reset([
            'typeName', 'typeSlug', 'typeDescription', 'typeCategory',
            'typeIcon', 'typePushEnabled', 'typeEmailEnabled', 'typeSmsEnabled', 'typeIsActive',
            'editingType',
        ]);
        $this->typeCategory = 'system';
        $this->typeIcon = 'bell';
        $this->typePushEnabled = true;
        $this->typeEmailEnabled = true;
        $this->typeSmsEnabled = false;
        $this->typeIsActive = true;
    }
}
