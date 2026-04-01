<section class="w-full space-y-6">
    <div>
        <flux:heading size="lg">{{ __('NFC Card Assignment') }}</flux:heading>
        <flux:text variant="subtle">{{ __('Assign a Mifare card UID to the selected member.') }}</flux:text>
    </div>

    @if ($this->selectedMember === null)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('No member selected') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Select a member in the table before assigning a card.') }}</flux:text>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <dl class="grid gap-2 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedMember->name }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                    <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $this->selectedMember->status }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Current Card') }}</dt>
                    <dd class="text-zinc-800 dark:text-zinc-200">{{ $this->selectedMember->nfcCard?->uid ?? __('Not assigned') }}</dd>
                </div>
            </dl>
        </div>

        <form wire:submit="assign" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:input
                wire:model.blur="uid"
                :label="__('Card UID')"
                :placeholder="__('Example: 04A3B29CD1')"
                autocomplete="off"
            />

            <flux:field>
                <flux:label>{{ __('Card Status') }}</flux:label>
                <flux:select wire:model="cardStatus">
                    <option value="active">{{ __('Active') }}</option>
                    <option value="suspended">{{ __('Suspended') }}</option>
                    <option value="lost">{{ __('Lost') }}</option>
                </flux:select>
                <flux:error name="cardStatus" />
            </flux:field>

            <flux:error name="memberId" />
            <flux:error name="uid" />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="assign">
                    <span wire:loading.remove wire:target="assign">{{ __('Assign Card') }}</span>
                    <span wire:loading wire:target="assign">{{ __('Assigning...') }}</span>
                </flux:button>

                <x-action-message on="card-assigned">
                    {{ __('Card assigned successfully — member notified.') }}
                </x-action-message>
            </div>
        </form>
    @endif
</section>
