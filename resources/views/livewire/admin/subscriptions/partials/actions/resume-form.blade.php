            @if ($action === 'resume')
                <div class="space-y-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/30">
                    <flux:text>
                        {{ __('This will reactivate access and extend the subscription by remaining days.') }}
                    </flux:text>

                    <flux:button wire:click="resume" variant="primary" wire:loading.attr="disabled" wire:target="resume">
                        <span wire:loading.remove wire:target="resume">{{ __('Resume Subscription') }}</span>
                        <span wire:loading wire:target="resume">{{ __('Resuming...') }}</span>
                    </flux:button>
                </div>
            @endif
