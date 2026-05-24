<div class="flex items-center justify-end">
    @if ($alerts->count() > 0)
        <flux:button size="sm" variant="subtle" wire:click="dismissAllAlerts">
            {{ __('Dismiss All') }}
        </flux:button>
    @endif
</div>