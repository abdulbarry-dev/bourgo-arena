<?php

namespace App\Livewire\Auth;

use App\Services\Members\MemberOnboardingTokenService;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class MemberOnboardingPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $tokenIsValid = true;

    public bool $completed = false;

    public function mount(string $token, ?string $email = null): void
    {
        $this->token = $token;
        $this->email = $email ?? '';

        $onboardingToken = app(MemberOnboardingTokenService::class)->resolveValidToken($this->token);

        if ($onboardingToken === null) {
            $this->tokenIsValid = false;

            return;
        }

        if ($this->email === '') {
            $this->email = $onboardingToken->email;
        }
    }

    public function setPassword(): void
    {
        if (! $this->tokenIsValid) {
            $this->addError('token', __('The onboarding link is invalid or has expired.'));

            return;
        }

        $validated = $this->validate($this->rules());

        $updated = app(MemberOnboardingTokenService::class)->consume(
            $this->token,
            $validated['email'],
            $validated['password'],
        );

        if (! $updated) {
            $this->addError('email', __('The onboarding link is invalid, expired, or does not match this email.'));

            return;
        }

        $this->completed = true;
        $this->password = '';
        $this->passwordConfirmation = '';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', Password::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
        ];
    }

    public function render()
    {
        return view('livewire.auth.member-onboarding-password');
    }
}
