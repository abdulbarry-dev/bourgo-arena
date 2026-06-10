<flux:modal name="confirm-send-notification" class="min-w-[22rem]">
    <form wire:submit="sendNotification" class="space-y-6 pt-4">
        <flux:heading size="lg">{{ __('Send Notification') }}</flux:heading>
        <flux:subheading>
            {{ __('This notification will be queued and sent to :count recipients via :channels.', [
                'count' => number_format($this->composeMemberCount),
                'channels' => implode(', ', array_map(fn ($c) => __(ucfirst($c)), $composeChannels)),
            ]) }}
        </flux:subheading>

        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/60">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500">{{ __('Type') }}</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $types->firstWhere('id', $composeTypeId)?->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500">{{ __('Subject') }}</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $composeSubject }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500">{{ __('Audience') }}</span>
                    <span class="font-medium text-zinc-900 dark:text-white">
                        {{ $composeAudience === 'all' ? __('All Members') : __(':count specific members', ['count' => count($composeMemberIds)]) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="$dispatch('modal-close', { name: 'confirm-send-notification' })">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Send Now') }}</flux:button>
        </div>
    </form>
</flux:modal>
