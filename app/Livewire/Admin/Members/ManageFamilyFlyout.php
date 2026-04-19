<?php

namespace App\Livewire\Admin\Members;

use App\Models\Member;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class ManageFamilyFlyout extends Component
{
    use AuthorizesRequests;

    public bool $show = false;

    public ?int $parentId = null;

    public ?Member $parent = null;

    public array $children = [];

    public bool $isProcessing = false;

    #[On('open-manage-family-flyout')]
    public function open(int $memberId): void
    {
        $this->parentId = $memberId;
        $this->parent = Member::findOrFail($memberId);
        
        $this->authorize('update', $this->parent);
        
        $this->resetValidation();
        $this->reset(['children']);
        
        // Add one initial child field if none exist
        if (empty($this->children)) {
            $this->addChild();
        }
        
        $this->show = true;
    }

    public function addChild(): void
    {
        $this->children[] = [
            'name' => '',
            'date_of_birth' => '',
            'gender' => 'male',
        ];
    }

    public function removeChild(int $index): void
    {
        unset($this->children[$index]);
        $this->children = array_values($this->children);
    }

    public function save(): void
    {
        $this->authorize('update', $this->parent);

        $this->isProcessing = true;

        try {
            $validated = $this->validate([
                'children.*.name' => ['required', 'string', 'max:255'],
                'children.*.date_of_birth' => ['required', 'date', 'before:today'],
                'children.*.gender' => ['required', 'in:male,female'],
            ], [
                'children.*.name.required' => 'Child name is required.',
                'children.*.date_of_birth.required' => 'Child date of birth is required.',
            ]);

            DB::transaction(function () use ($validated): void {
                foreach ($validated['children'] as $childData) {
                    Member::query()->create([
                        'parent_id' => $this->parentId,
                        'name' => $childData['name'],
                        'date_of_birth' => $childData['date_of_birth'],
                        'gender' => $childData['gender'],
                        'status' => 'pending',
                        'rgpd_consented_at' => now(),
                    ]);
                }
            });

            $this->dispatch('member-updated', memberId: $this->parentId);
            $this->dispatch('toast', message: 'Children added successfully.', type: 'success');
            
            $this->show = false;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('save', 'Could not add children. Please try again.');
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.members.manage-family-flyout');
    }
}
