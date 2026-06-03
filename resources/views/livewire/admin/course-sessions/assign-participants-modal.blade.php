<div>
    <flux:modal name="assign-participants-modal" variant="flyout" class="max-w-xl w-full">
        <div wire:ignore.self>
            @if ($session && $date)
                <div class="space-y-6 pt-4">
                    <div>
                        <flux:heading size="lg">{{ __('Assign Participants') }}</flux:heading>
                        <flux:subheading>{{ __($session->course->name) }} • {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</flux:subheading>
                    </div>

                    <div class="space-y-6">
                        <form wire:submit.prevent="enrollMember" class="rounded-xl border border-emerald-100 bg-emerald-50/30 p-4 dark:border-emerald-900/20 dark:bg-emerald-950/10">
                            <div class="flex items-end gap-3">
                                <flux:field class="flex-1">
                                    <flux:label>{{ __('Select Member') }}</flux:label>
                                    <flux:select wire:model="memberIdToEnroll" :placeholder="__('Search members...')" searchable required>
                                        @foreach ($data['availableMembers'] as $member)
                                            <flux:select.option value="{{ $member->id }}">{{ trim($member->name) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="memberIdToEnroll" />
                                </flux:field>
                                
                                <flux:button
                                    type="submit"
                                    variant="primary"
                                    :disabled="count($data['bookings']) >= $session->capacity"
                                >
                                    {{ __('Add') }}
                                </flux:button>
                            </div>
                        </form>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-900 dark:text-zinc-100">{{ __('Enrolled Members') }}</h3>
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-black text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ count($data['bookings']) }} / {{ $session->capacity }}
                                </span>
                            </div>

                            @if ($data['bookings']->count() > 0)
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($data['bookings'] as $booking)
                                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                                    {{ substr($booking->member?->name, 0, 1) }}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $booking->member?->name }}</div>
                                                    <div class="truncate text-[10px] font-medium text-zinc-400">{{ $booking->member?->email }}</div>
                                                </div>
                                            </div>
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                icon="trash"
                                                size="sm"
                                                class="text-red-500 hover:text-red-600 dark:text-red-400"
                                                wire:click="removeBooking({{ $booking->id }})"
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                                    <flux:icon name="users" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                    <p class="mt-2 text-sm font-medium text-zinc-400">{{ __('No members enrolled yet') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Done') }}</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>

