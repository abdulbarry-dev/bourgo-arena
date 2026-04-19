<?php

namespace App\Livewire\Admin\Members;

use App\Jobs\SendMemberPasswordResetEmail;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class MemberDetailPanel extends Component
{
    use AuthorizesRequests;

    public ?int $memberId = null;

    public ?Member $member = null;

    public bool $showSuspendModal = false;

    public bool $showActivateModal = false;

    public bool $showResetPasswordModal = false;

    public bool $showDeleteModal = false;

    public function mount(?int $memberId = null): void
    {
        $memberFromQuery = request()->query('member');

        $resolvedMemberId = $memberId
            ?? (is_numeric($memberFromQuery) ? (int) $memberFromQuery : null)
            ?? session('members.selected_member_id');

        if ($resolvedMemberId !== null) {
            $this->loadMember((int) $resolvedMemberId);
        }
    }

    #[On('member-selected')]
    public function loadMember(int $memberId): void
    {
        $this->memberId = $memberId;

        session(['members.selected_member_id' => $memberId]);

        $this->member = Member::query()
            ->with([
                'parent',
                'children',
                'activeSubscription.plan',
                'activeSubscription.enrolledBy',
                'nfcCard.assignedBy',
                'checkInEvents' => function ($query): void {
                    $query
                        ->with('terminal')
                        ->latest('checked_in_at')
                        ->limit(10);
                },
            ])
            ->find($memberId);
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

    public function suspend(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('suspend', $member);

        if ($member->status === 'suspended') {
            $this->showSuspendModal = false;
            $this->dispatch('toast', message: 'Member is already suspended.', type: 'info');

            return;
        }

        $member->update(['status' => 'suspended']);

        $this->showSuspendModal = false;

        $this->loadMember($member->id);
        $this->dispatch('member-updated', memberId: $member->id);
        $this->dispatch('toast', message: 'Member suspended successfully.', type: 'success');
    }

    public function activate(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('activate', $member);

        if ($member->status === 'active') {
            $this->showActivateModal = false;
            $this->dispatch('toast', message: 'Member is already active.', type: 'info');

            return;
        }

        $member->update(['status' => 'active']);

        $this->showActivateModal = false;

        $this->loadMember($member->id);
        $this->dispatch('member-updated', memberId: $member->id);
        $this->dispatch('toast', message: 'Member activated successfully.', type: 'success');
    }

    public function resetPassword(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('resetPassword', Member::class);

        SendMemberPasswordResetEmail::dispatch($member->id);

        $this->showResetPasswordModal = false;

        $this->dispatch('member-updated', memberId: $member->id);
        $this->dispatch('toast', message: 'Password reset email sent successfully.', type: 'success');
    }

    public function delete(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('delete', $member);

        $deletedMemberId = $member->id;

        $member->delete();

        session()->forget('members.selected_member_id');
        session()->flash('toast', [
            'message' => 'Member deleted successfully.',
            'type' => 'success',
        ]);

        $this->reset(
            'memberId',
            'member',
            'showDeleteModal',
            'showSuspendModal',
            'showActivateModal',
            'showResetPasswordModal',
        );

        $this->dispatch('member-updated', memberId: $deletedMemberId);

        $this->redirectRoute('admin.members');
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
