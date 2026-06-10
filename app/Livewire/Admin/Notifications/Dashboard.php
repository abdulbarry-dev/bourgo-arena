<?php

namespace App\Livewire\Admin\Notifications;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSmsNotification;
use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Services\Admin\NotificationDispatchService;
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
    public string $typeFlyoutMode = 'create';

    public ?NotificationType $editingType = null;

    public string $typeName = '';

    public string $typeDescription = '';

    public string $typeCategory = 'system';

    public string $typeCustomCategory = '';

    public bool $addingCustomCategory = false;

    public string $typeIcon = 'bell';

    public bool $typePushEnabled = true;

    public bool $typeEmailEnabled = true;

    public bool $typeSmsEnabled = false;

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
        $this->dispatch('modal-show', name: 'type-form-flyout');
    }

    public function openEditTypeFlyout(int $typeId): void
    {
        $type = NotificationType::findOrFail($typeId);
        $this->editingType = $type;
        $this->typeName = $type->name;
        $this->typeDescription = $type->description ?? '';

        $predefinedCategories = ['billing', 'events', 'promotions', 'system'];
        if (in_array($type->category, $predefinedCategories)) {
            $this->typeCategory = $type->category;
            $this->addingCustomCategory = false;
            $this->typeCustomCategory = '';
        } else {
            $this->typeCategory = '__custom';
            $this->typeCustomCategory = $type->category;
            $this->addingCustomCategory = true;
        }

        $this->typeIcon = $type->icon;
        $this->typePushEnabled = $type->push_enabled;
        $this->typeEmailEnabled = $type->email_enabled;
        $this->typeSmsEnabled = $type->sms_enabled;
        $this->typeFlyoutMode = 'edit';
        $this->dispatch('modal-show', name: 'type-form-flyout');
    }

    public function saveType(): void
    {
        $rules = [
            'typeName' => 'required|string|max:255',
            'typeDescription' => 'nullable|string|max:1000',
            'typeCategory' => 'required|string|max:255',
            'typeIcon' => 'required|string|max:255',
            'typePushEnabled' => 'boolean',
            'typeEmailEnabled' => 'boolean',
            'typeSmsEnabled' => 'boolean',
        ];

        if ($this->addingCustomCategory) {
            $rules['typeCustomCategory'] = 'required|string|max:255';
        }

        $this->validate($rules);

        $category = $this->addingCustomCategory ? $this->typeCustomCategory : $this->typeCategory;

        $data = [
            'name' => $this->typeName,
            'description' => $this->typeDescription ?: null,
            'category' => $category,
            'icon' => $this->typeIcon,
            'push_enabled' => $this->typePushEnabled,
            'email_enabled' => $this->typeEmailEnabled,
            'sms_enabled' => $this->typeSmsEnabled,
        ];

        if ($this->typeFlyoutMode === 'edit' && $this->editingType) {
            $this->editingType->update($data);
            $this->dispatch('toast', message: __('Notification type updated successfully.'), type: 'success');
        } else {
            NotificationType::create($data);
            $this->dispatch('toast', message: __('Notification type created successfully.'), type: 'success');
        }

        $this->editingType = null;
        $this->dispatch('modal-close', name: 'type-form-flyout');
    }

    public function confirmDeleteType(int $typeId): void
    {
        $this->deletingType = NotificationType::findOrFail($typeId);
        $this->dispatch('modal-show', name: 'confirm-delete-type');
    }

    public function deleteType(): void
    {
        if ($this->deletingType) {
            $this->deletingType->delete();
            $this->deletingType = null;
            $this->dispatch('toast', message: __('Notification type deleted successfully.'), type: 'success');
            $this->dispatch('modal-close', name: 'confirm-delete-type');
        }
    }

    public function retryLog(int $logId): void
    {
        $log = NotificationLog::findOrFail($logId);

        $log->update(['status' => 'queued', 'sent_at' => null]);

        $job = match ($log->channel) {
            'push' => new SendPushNotification($log->id, $log->member_id),
            'email' => new SendEmailNotification($log->id),
            'sms' => new SendSmsNotification($log->id, $log->member_id),
            default => null,
        };

        if ($job !== null) {
            app()->call([$job, 'handle']);
        }

        $this->dispatch('toast', message: __('Notification retry processed.'), type: 'success');
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
            $type->refresh();

            $hasActiveChannel = $type->push_enabled || $type->email_enabled || $type->sms_enabled;

            if (! $hasActiveChannel) {
                $type->update(['is_active' => false]);
            } elseif (optional($type)->is_active === false) {
                $type->update(['is_active' => true]);
            }
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

    public function selectIcon(string $icon): void
    {
        $this->typeIcon = $icon;
    }

    public function updatedTypeCategory(string $value): void
    {
        $this->addingCustomCategory = ($value === '__custom');

        if ($this->addingCustomCategory) {
            $this->typeCustomCategory = '';
        }
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

        $this->dispatch('modal-show', name: 'confirm-send-notification');
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
        $this->dispatch('modal-close', name: 'confirm-send-notification');
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

    public function getAvailableIconsProperty(): array
    {
        return [
            'bell', 'bell-alert', 'bell-slash', 'megaphone',
            'chat-bubble-left-right', 'chat-bubble-bottom-center',
            'envelope', 'envelope-open',
            'device-phone-mobile', 'gift', 'sparkles', 'star',
            'calendar', 'calendar-date-range',
            'banknotes', 'credit-card', 'receipt-refund',
            'exclamation-triangle', 'exclamation-circle',
            'information-circle', 'check-circle',
            'cog-6-tooth', 'users', 'trophy', 'tag', 'bolt',
            'paper-airplane', 'rectangle-group', 'rss',
            'globe-alt', 'ticket',
        ];
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

        $staleCount = NotificationLog::query()
            ->where('status', 'queued')
            ->where('created_at', '<', now()->subMinutes(5))
            ->count();

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
            'staleCount' => $staleCount,
            'logs' => $logs,
            'registeredDevices' => $registeredDevices,
            'categories' => $categories,
        ]);
    }

    private function resetTypeForm(): void
    {
        $this->reset([
            'typeName', 'typeDescription', 'typeCategory',
            'typeCustomCategory', 'addingCustomCategory',
            'typeIcon', 'typePushEnabled', 'typeEmailEnabled', 'typeSmsEnabled',
            'editingType',
        ]);
        $this->typeCategory = 'system';
        $this->typeIcon = 'bell';
        $this->typePushEnabled = true;
        $this->typeEmailEnabled = true;
        $this->typeSmsEnabled = false;
    }
}
