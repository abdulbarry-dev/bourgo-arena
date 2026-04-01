<?php

namespace App\Livewire\Admin\Members;

use App\Jobs\SendMemberPasswordResetEmail;
use App\Models\Member;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
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
        if ($memberId !== null) {
            $this->loadMember($memberId);
        }
    }

    #[On('member-selected')]
    public function loadMember(int $memberId): void
    {
        $this->memberId = $memberId;

        $this->member = Member::query()
            ->with([
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

    public function suspend(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('suspend', $member);

        if ($member->status !== 'suspended') {
            $member->update(['status' => 'suspended']);
        }

        $this->showSuspendModal = false;

        $this->loadMember($member->id);
        $this->dispatch('member-updated', memberId: $member->id);
    }

    public function activate(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('activate', $member);

        if ($member->status !== 'active') {
            $member->update(['status' => 'active']);
        }

        $this->showActivateModal = false;

        $this->loadMember($member->id);
        $this->dispatch('member-updated', memberId: $member->id);
    }

    public function resetPassword(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('resetPassword', Member::class);

        $temporaryPassword = Str::password(14);

        $member->update(['password' => $temporaryPassword]);

        SendMemberPasswordResetEmail::dispatch($member->id, $temporaryPassword);

        $this->showResetPasswordModal = false;

        $this->dispatch('member-updated', memberId: $member->id);
    }

    public function delete(): void
    {
        $member = $this->resolveSelectedMember();

        $this->authorize('delete', $member);

        $deletedMemberId = $member->id;

        $member->delete();

        $this->reset(
            'memberId',
            'member',
            'showDeleteModal',
            'showSuspendModal',
            'showActivateModal',
            'showResetPasswordModal',
        );

        $this->dispatch('member-updated', memberId: $deletedMemberId);
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
