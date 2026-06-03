<?php

namespace App\Livewire\Admin\Managers;

use App\Mail\ManagerWelcomeEmail;
use App\Models\User;
use App\UserRole;
use Flux\Flux;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Managers Administration')]
class Index extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $sortBy = 'created_at';

    #[Url(history: true)]
    public $sortDirection = 'desc';

    #[Url(history: true)]
    public string $statusFilter = '';

    public bool $showFlyout = false;

    public bool $showViewFlyout = false;

    public string $flyoutMode = 'view'; // 'view' or 'create'

    public ?User $selectedManager = null;

    public $name = '';

    public $email = '';

    public $phone = '';

    public string $banReason = '';

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateFlyout()
    {
        $this->reset(['name', 'email', 'phone', 'selectedManager']);
        $this->resetValidation();
        $this->flyoutMode = 'create';
        $this->showFlyout = true;
    }

    public function openViewFlyout(User $manager)
    {
        $this->selectedManager = $manager;
        $this->flyoutMode = 'view';
        $this->showViewFlyout = true;
    }

    public function openEditFlyout(int $managerId)
    {
        $manager = User::findOrFail($managerId);
        $this->selectedManager = $manager;
        $this->name = $manager->name;
        $this->email = $manager->email;
        $this->phone = $manager->phone;
        $this->flyoutMode = 'edit';
        $this->showFlyout = true;
    }

    public function selectManager(User $manager)
    {
        $this->selectedManager = $manager;
    }

    public function updateManager()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$this->selectedManager->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,'.$this->selectedManager->id,
        ]);

        $this->selectedManager->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $this->showFlyout = false;
        $this->dispatch('toast', message: __('Manager updated successfully.'), type: 'success');
    }

    public function createManager()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
        ]);

        $randomPassword = Str::password(16);

        $manager = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($randomPassword),
            'role' => UserRole::Manager,
            'email_verified_at' => now(),
        ]);

        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($manager);

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $manager->email]);

        Mail::to($manager->email)->send(new ManagerWelcomeEmail($manager, $randomPassword, $resetUrl));

        $this->showFlyout = false;
        $this->dispatch('toast', message: __('Manager created successfully. Invite sent.'), type: 'success');
    }

    public function toggleBan()
    {
        if ($this->selectedManager && $this->selectedManager->id !== Auth::id()) {
            if ($this->selectedManager->isBanned()) {
                $this->selectedManager->unban();
                $this->dispatch('toast', message: __('Manager unbanned successfully.'), type: 'success');
            } else {
                $this->banReason = '';
                $this->resetValidation('banReason');
                Flux::modal('ban-manager-modal')->show();
            }
        }
    }

    public function confirmBanManager()
    {
        $this->validate([
            'banReason' => ['required', 'string', 'min:8', 'regex:/^[a-zA-Z\s]+$/'],
        ], [
            'banReason.regex' => 'The reason must only contain alphabetic characters and spaces.',
            'banReason.min' => 'The reason must be at least 8 characters long.',
        ]);

        if ($this->selectedManager && $this->selectedManager->id !== Auth::id()) {
            $this->selectedManager->ban($this->banReason);
            Flux::modal('ban-manager-modal')->close();
            $this->dispatch('toast', message: __('Manager banned successfully.'), type: 'success');
        }
    }

    public function deleteManager()
    {
        if ($this->selectedManager && $this->selectedManager->id !== Auth::id()) {
            try {
                $this->selectedManager->delete();
                $this->showFlyout = false;
                $this->selectedManager = null;

                Flux::modal('confirm-delete')->close();
                $this->dispatch('toast', message: __('Manager deleted successfully.'), type: 'success');
            } catch (QueryException $e) {
                if ($e->getCode() === '23503') {
                    $this->dispatch('toast', message: __('Cannot delete a manager who has interacted with the application data. Please contact the owner.'), type: 'error');
                } else {
                    throw $e;
                }
            }
        }
    }

    public function render()
    {
        $query = User::query()->where('role', UserRole::Manager->value);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        // Apply status filter
        if ($this->statusFilter === 'banned') {
            $query->whereNotNull('banned_at');
        } elseif ($this->statusFilter === 'not_banned') {
            $query->whereNull('banned_at');
        }

        $query->orderBy($this->sortBy, $this->sortDirection);

        return view('livewire.admin.managers.index', [
            'managers' => $query->paginate(10),
        ]);
    }
}
