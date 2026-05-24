<div class="flex items-center justify-between pt-8">
    <flux:button variant="ghost" x-on:click="$flux.modal('session-detail-panel').close()">{{ __('Close') }}</flux:button>
    <flux:button variant="danger" wire:click="confirmCancelSessionInstance">{{ __('Cancel Class') }}</flux:button>
</div>