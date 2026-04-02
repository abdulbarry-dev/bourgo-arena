<?php

namespace App\Livewire\Admin\Members;

use App\Jobs\SendMemberWelcomeEmail;
use App\Jobs\SendMemberWelcomePush;
use App\Jobs\SendMemberWelcomeSms;
use App\Models\Member;
use App\Models\MemberNotification;
use App\Services\Members\MemberOnboardingTokenService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class AddMemberForm extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $dateOfBirth = '';

    public string $gender = 'male';

    public string $emergencyContact = '';

    public bool $isProcessing = false;

    public function create(): void
    {
        $this->authorize('create', Member::class);

        $this->isProcessing = true;

        try {
            $validated = $this->validate($this->rules(), $this->messages());

            $temporaryPassword = Str::password(length: 12, letters: true, numbers: true, symbols: true);

            $member = DB::transaction(function () use ($validated, $temporaryPassword): Member {
                $member = Member::query()->create([
                    'name' => $validated['name'],
                    'email' => strtolower($validated['email']),
                    'phone' => $validated['phone'],
                    'date_of_birth' => $validated['dateOfBirth'],
                    'gender' => $validated['gender'],
                    'emergency_contact' => $validated['emergencyContact'] !== '' ? $validated['emergencyContact'] : null,
                    'avatar' => null,
                    'status' => 'pending',
                    'rgpd_consented_at' => now(),
                    'password' => $temporaryPassword,
                ]);

                MemberNotification::query()->create([
                    'member_id' => $member->id,
                    'type' => 'member_welcome',
                    'title' => 'Welcome to Bourgo Arena',
                    'message' => 'Your account has been created. Complete your password setup within 24 hours.',
                    'channel' => 'in_app',
                    'status' => 'delivered',
                    'is_read' => false,
                    'metadata' => ['source' => 'admin_member_create'],
                    'delivered_at' => now(),
                ]);

                return $member;
            });

            $onboarding = app(MemberOnboardingTokenService::class)->createForMember($member, 24);

            SendMemberWelcomeEmail::dispatch(
                $member->id,
                $temporaryPassword,
                $onboarding['url'],
                $onboarding['expires_at']->toDateTimeString(),
            );
            SendMemberWelcomeSms::dispatch($member->id);
            SendMemberWelcomePush::dispatch($member->id);

            $this->dispatch('member-created', memberId: $member->id);
            session()->flash('toast', [
                'message' => 'Member created successfully. Welcome notifications have been queued.',
                'type' => 'success',
            ]);

            $this->redirectRoute('admin.members.show', ['member' => $member->id]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('create', 'Member could not be created right now. Please try again.');
            $this->dispatch('toast', message: 'Member creation failed. Please review the form and try again.', type: 'danger');
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'email')->whereNull('deleted_at'),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\\+?[0-9]{8,15}$/',
                Rule::unique('members', 'phone')->whereNull('deleted_at'),
            ],
            'dateOfBirth' => [
                'required',
                'date',
                'before_or_equal:'.now()->subYears(16)->toDateString(),
            ],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'emergencyContact' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be digits only, with optional leading +.',
            'dateOfBirth.before_or_equal' => 'Member must be at least 16 years old.',
        ];
    }

    public function render()
    {
        return view('livewire.admin.members.add-member-form');
    }
}
