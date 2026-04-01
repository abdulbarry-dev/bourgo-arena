<?php

namespace App\Livewire\Admin\Members;

use App\Jobs\NotifyMemberCardAssigned;
use App\Models\Member;
use App\Models\NfcCard;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NfcCardAssignment extends Component
{
    use AuthorizesRequests;

    public ?int $memberId = null;

    public string $uid = '';

    public string $cardStatus = 'active';

    public function mount(?int $memberId = null): void
    {
        $this->memberId = $memberId ?? session('members.selected_member_id');
    }

    #[On('member-selected')]
    public function setMember(int $memberId): void
    {
        $this->memberId = $memberId;

        session(['members.selected_member_id' => $memberId]);

        $this->reset('uid');
        $this->cardStatus = 'active';
        $this->resetErrorBag();
    }

    public function updatedUid(): void
    {
        $this->uid = strtoupper(trim($this->uid));

        if ($this->uid === '') {
            return;
        }

        $this->validateOnly('uid', $this->rules(), $this->messages());
    }

    public function assign(): void
    {
        $this->authorize('assign', NfcCard::class);

        $this->uid = strtoupper(trim($this->uid));

        $validated = $this->validate($this->rules(), $this->messages());

        $member = Member::query()->find($validated['memberId']);

        if ($member === null) {
            $this->addError('memberId', __('The selected member was not found.'));

            return;
        }

        if (! in_array($member->status, ['pending', 'active'], true)) {
            $this->addError('memberId', __('Cards can only be assigned to pending or active members.'));

            return;
        }

        NfcCard::query()->updateOrCreate(
            ['member_id' => $member->id],
            [
                'uid' => strtoupper($validated['uid']),
                'status' => $validated['cardStatus'],
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ],
        );

        if ($member->status === 'pending') {
            $member->update(['status' => 'active']);
        }

        NotifyMemberCardAssigned::dispatch($member->id);

        $this->reset('uid');

        $this->dispatch('card-assigned', memberId: $member->id);
        $this->dispatch('toast', message: 'Card assigned successfully — member notified', type: 'success');
    }

    #[Computed]
    public function selectedMember(): ?Member
    {
        if ($this->memberId === null) {
            return null;
        }

        return Member::query()
            ->with('nfcCard')
            ->find($this->memberId);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $existingCardId = $this->memberId === null
            ? null
            : NfcCard::query()->where('member_id', $this->memberId)->value('id');

        return [
            'memberId' => [
                'required',
                'integer',
                Rule::exists('members', 'id')->whereNull('deleted_at'),
            ],
            'uid' => [
                'required',
                'string',
                'min:8',
                'max:32',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('nfc_cards', 'uid')->ignore($existingCardId),
            ],
            'cardStatus' => [
                'required',
                Rule::in(['active', 'suspended', 'lost']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'uid.regex' => __('The card UID must only contain letters and numbers.'),
            'uid.unique' => __('This card UID is already assigned to another member.'),
            'memberId.required' => __('Select a member before assigning a card.'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.members.nfc-card-assignment');
    }
}
