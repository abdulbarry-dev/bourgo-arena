<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class EditMemberFlyout extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?int $memberId = null;

    public ?Member $member = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $dateOfBirth = '';

    public string $gender = 'male';

    public string $emergencyContact = '';

    public bool $isFamilyAccount = false;

    public bool $isProcessing = false;

    #[On('open-edit-member-flyout')]
    public function open(int $memberId): void
    {
        $this->memberId = $memberId;
        $this->member = Member::findOrFail($memberId);

        $this->authorize('update', $this->member);

        $this->name = $this->member->name;
        $this->email = $this->member->email ?? '';
        $this->phone = $this->member->phone ?? '';
        $this->dateOfBirth = $this->member->date_of_birth?->toDateString() ?? '';
        $this->gender = $this->member->gender;
        $this->emergencyContact = $this->member->emergency_contact ?? '';
        $this->isFamilyAccount = $this->member->is_family_account;

        $this->resetValidation();
        $this->show = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->member);

        $this->isProcessing = true;

        try {
            $validated = $this->validate($this->rules(), $this->messages());

            $this->member->update([
                'name' => $validated['name'],
                'email' => $validated['email'] ? strtolower($validated['email']) : null,
                'phone' => $validated['phone'] ?: null,
                'date_of_birth' => $validated['dateOfBirth'],
                'gender' => $validated['gender'],
                'emergency_contact' => $validated['emergencyContact'] !== '' ? $validated['emergencyContact'] : null,
                'is_family_account' => $validated['isFamilyAccount'],
            ]);

            $this->dispatch('member-updated', memberId: $this->member->id);
            $this->dispatch('toast', message: __('Member profile updated successfully.'), type: 'success');

            $this->show = false;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('update', __('Could not update member profile. Please try again.'));
        } finally {
            $this->isProcessing = false;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                $this->member->isChild() ? 'required' : 'required',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($this->memberId)->whereNull('deleted_at'),
            ],
            'phone' => [
                $this->member->isChild() ? 'required' : 'required',
                'string',
                'max:20',
                'regex:/^\\+?[0-9]{8,15}$/',
                Rule::unique('members', 'phone')->ignore($this->memberId)->whereNull('deleted_at'),
            ],
            'dateOfBirth' => [
                'required',
                'date',
                $this->member->isChild() ? 'before:today' : 'before_or_equal:'.now()->subYears(16)->toDateString(),
            ],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'emergencyContact' => ['required', 'string', 'max:255'],
            'isFamilyAccount' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'phone.regex' => __('Phone number must be digits only, with optional leading +.'),
            'dateOfBirth.before_or_equal' => __('Member must be at least 16 years old.'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.members.edit-member-flyout');
    }
}
