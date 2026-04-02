<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Plan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class PlanForm extends Component
{
    use AuthorizesRequests;

    public ?int $planId = null;

    public string $name = '';

    public string $price = '';

    public int|string $durationDays = '';

    public string $includedServicesInput = '';

    public bool $isArchived = false;

    public bool $isProcessing = false;

    public function mount(?int $planId = null): void
    {
        $this->planId = $planId;

        if ($this->planId === null) {
            $this->authorize('create', Plan::class);

            return;
        }

        $plan = Plan::query()->findOrFail($this->planId);

        $this->authorize('update', $plan);

        $this->name = $plan->name;
        $this->price = number_format((float) $plan->price, 3, '.', '');
        $this->durationDays = $plan->duration_days;
        $this->includedServicesInput = implode(', ', $plan->included_services ?? []);
        $this->isArchived = $plan->is_archived;
    }

    public function save(): void
    {
        $this->isProcessing = true;

        try {
            $validated = $this->validate($this->rules());

            $payload = [
                'name' => $validated['name'],
                'price' => $validated['price'],
                'duration_days' => (int) $validated['durationDays'],
                'included_services' => $this->normalizedServices($validated['includedServicesInput']),
                'is_archived' => (bool) $validated['isArchived'],
            ];

            if ($this->planId === null) {
                $this->authorize('create', Plan::class);

                $plan = Plan::query()->create($payload);

                session()->flash('toast', [
                    'message' => 'Plan created successfully.',
                    'type' => 'success',
                ]);

                $this->redirectRoute('admin.plans.show', ['plan' => $plan->id]);

                return;
            }

            $plan = Plan::query()->findOrFail($this->planId);

            $this->authorize('update', $plan);

            $plan->fill($payload);

            if (! $plan->isDirty()) {
                $this->dispatch('toast', message: 'No changes detected for this plan.', type: 'info');

                return;
            }

            $plan->save();

            session()->flash('toast', [
                'message' => 'Plan updated successfully.',
                'type' => 'success',
            ]);

            $this->redirectRoute('admin.plans.show', ['plan' => $plan->id]);
        } catch (Throwable $exception) {
            report($exception);

            $this->addError('save', 'Plan could not be saved right now. Please try again.');
            $this->dispatch('toast', message: 'Plan save failed. Please review the form and try again.', type: 'danger');
        } finally {
            $this->isProcessing = false;
        }
    }

    public function delete(): void
    {
        if ($this->planId === null) {
            return;
        }

        $plan = Plan::query()->findOrFail($this->planId);

        $this->authorize('delete', $plan);

        if ($plan->subscriptions()->exists()) {
            $this->dispatch('toast', message: 'Plan cannot be deleted because subscriptions are linked to it.', type: 'info');

            return;
        }

        $plan->delete();

        session()->flash('toast', [
            'message' => 'Plan deleted successfully.',
            'type' => 'success',
        ]);

        $this->redirectRoute('admin.plans');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->ignore($this->planId),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0.001',
                'regex:/^\d+(\.\d{1,3})?$/',
            ],
            'durationDays' => [
                'required',
                'integer',
                'min:1',
            ],
            'includedServicesInput' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'isArchived' => [
                'boolean',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizedServices(?string $rawServices): array
    {
        if ($rawServices === null || trim($rawServices) === '') {
            return [];
        }

        $services = preg_split('/[\n,]+/', $rawServices);

        if ($services === false) {
            return [];
        }

        return collect($services)
            ->map(fn (string $service): string => strtolower(trim($service)))
            ->filter(fn (string $service): bool => $service !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.plans.plan-form');
    }
}
