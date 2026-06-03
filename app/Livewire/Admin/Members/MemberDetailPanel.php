<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use App\Models\Subscription;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class MemberDetailPanel extends Component
{
    use AuthorizesRequests;

    public ?int $memberId = null;

    public ?Member $member = null;

    public bool $isDetailPanelOpen = false;

    public string $activeTab = 'profile';

    public int $loyaltyLimit = 20;

    public int $loyaltyPoints = 0;

    public $loyaltyTransactions = null;

    public function mount(?int $memberId = null): void
    {
        $memberFromQuery = request()->query('member');
        $tabFromQuery = request()->query('tab');

        if (is_string($tabFromQuery) && $tabFromQuery !== '') {
            $this->activeTab = $tabFromQuery;
        }

        $resolvedMemberId = $memberId
            ?? (is_numeric($memberFromQuery) ? (int) $memberFromQuery : null);

        if ($resolvedMemberId !== null) {
            $this->loadMember((int) $resolvedMemberId);
            $this->isDetailPanelOpen = true;
        }
    }

    #[On('open-member-detail-panel')]
    public function openDetailPanel(int $memberId): void
    {
        $this->loadMember($memberId);
        $this->isDetailPanelOpen = true;
    }

    public function closeDetailPanel(): void
    {
        $this->isDetailPanelOpen = false;
    }

    #[On('member-selected')]
    public function loadMember(int $memberId): void
    {
        $this->memberId = $memberId;

        session(['members.selected_member_id' => $memberId]);

        $this->member = Member::query()
            ->with([
                'parent.validSubscriptions.plan',
                'children.validSubscriptions.plan',
                'validSubscriptions.plan',
                'validSubscriptions.enrolledBy',
            ])
            ->find($memberId);

        // preload loyalty when requested
        if ($this->activeTab === 'loyalty') {
            $this->loadLoyalty();
        }
    }

    public function loadLoyalty(): void
    {
        if ($this->member === null) {
            return;
        }

        $result = app(LoyaltyService::class)->getBalanceAndTransactions($this->member, $this->loyaltyLimit);
        $this->loyaltyPoints = $result['points'] ?? 0;
        $this->loyaltyTransactions = $result['transactions'] ?? null;
    }

    public function updatedActiveTab(string $value): void
    {
        if ($value === 'loyalty') {
            $this->loadLoyalty();
        }
    }

    #[On('subscription-created')]
    public function refreshFromSubscriptionCreated(int $memberId): void
    {
        if ($this->memberId !== null && $this->memberId !== $memberId) {
            return;
        }

        $this->loadMember($memberId);
    }

    #[On('subscription-updated')]
    public function refreshFromSubscriptionUpdated(int $subscriptionId): void
    {
        $memberId = Subscription::query()
            ->whereKey($subscriptionId)
            ->value('member_id');

        if ($memberId === null) {
            return;
        }

        if ($this->memberId !== null && $this->memberId !== (int) $memberId) {
            return;
        }

        $this->loadMember((int) $memberId);
    }

    public function render(): View
    {
        return view('livewire.admin.members.member-detail-panel');
    }

    private function resolveSelectedMember(): Member
    {
        abort_if($this->memberId === null, 404, 'No member selected.');

        $member = Member::query()->find($this->memberId);

        abort_if($member === null, 404, 'Member not found.');

        return $member;
    }
}
