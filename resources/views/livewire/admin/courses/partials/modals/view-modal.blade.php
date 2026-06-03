<flux:modal wire:model="showViewFlyout" variant="flyout" class="w-full max-w-xl">
    @if ($viewingCourse)
        <div class="p-6">
            <div class="flex items-center justify-between">
                <flux:heading size="xl">{{ $viewingCourse->name }}</flux:heading>
                <x-ui.dashboard.status-badge
                    :status="$viewingCourse->status"
                    :label="ucfirst($viewingCourse->status)"
                    :color="match($viewingCourse->status) {
                        'active' => 'green',
                        'inactive' => 'gray',
                        'archived' => 'orange',
                        default => 'zinc',
                    }"
                />
            </div>
            
            <flux:text variant="subtle" class="mt-2">
                {{ __('Created on :date', ['date' => $viewingCourse->created_at->format('M d, Y')]) }}
            </flux:text>

            <div class="mt-8">
                @if ($viewingCourse->image_url)
                    <img src="{{ $viewingCourse->image_url }}" alt="{{ $viewingCourse->name }}" class="w-full h-64 object-cover rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                @endif
            </div>

            <div class="mt-8 space-y-6">
                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Description') }}</flux:heading>
                    <flux:text>{{ $viewingCourse->description ?: __('No description provided.') }}</flux:text>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                        <flux:text variant="subtle" size="sm">{{ __('Parent Service') }}</flux:text>
                        <flux:heading size="lg">{{ $viewingCourse->service?->name ?? __('N/A') }}</flux:heading>
                    </div>
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                        <flux:text variant="subtle" size="sm">{{ __('Total Sessions') }}</flux:text>
                        <flux:heading size="lg">{{ $viewingCourse->sessions->count() }}</flux:heading>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between gap-2 px-6 pb-6">
            <flux:button variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $viewingCourse->id }})">{{ __('Edit') }}</flux:button>
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Close') }}</flux:button>
            </flux:modal.close>
        </div>
    @endif
</flux:modal>
