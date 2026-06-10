<x-ui.dashboard.panel class="p-0" style="animation: fadeInUp 0.4s ease-out both; animation-delay: 0.3s">
    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <flux:heading>{{ __('Compose & Send') }}</flux:heading>
        <flux:text variant="subtle" class="mt-0.5">{{ __('Send a notification to your members.') }}</flux:text>
    </div>

    <form wire:submit="confirmSend" class="space-y-4 p-6">
        {{-- Type Selection --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Notification Type') }}</flux:label>
                <flux:select wire:model.live="composeTypeId" placeholder="{{ __('Select a type...') }}" required>
                    @foreach ($types->where('is_active', true) as $type)
                        <flux:select.option value="{{ $type->id }}">
                            {{ $type->name }} ({{ ucfirst($type->category) }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <div class="min-h-[20px]"><flux:error name="composeTypeId" /></div>
            </flux:field>

            <div>
                <flux:label>{{ __('Member Count') }}</flux:label>
                <div class="mt-1.5 flex h-10 items-center rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
                    <flux:icon.users class="mr-2 size-4 text-zinc-400" />
                    {{ number_format($this->composeMemberCount) }} {{ __('recipients') }}
            </div>
            <div class="min-h-[20px]"><flux:error name="composeChannels" /></div>
        </div>
        </div>

        {{-- Channel Selection --}}
        <div>
            <flux:label>{{ __('Channels') }}</flux:label>
            <div class="mt-2 flex flex-wrap gap-4">
                @php
                    $selectedType = $types->firstWhere('id', $composeTypeId);
                @endphp
                @if ($selectedType)
                    @if ($selectedType->push_enabled)
                        <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-4 py-2.5 transition has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50 dark:border-zinc-700 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-900/20">
                            <flux:checkbox wire:model="composeChannels" value="push" />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Push') }}</span>
                                <p class="text-xs text-zinc-500">{{ __('Mobile notification') }}</p>
                            </div>
                        </label>
                    @endif
                    @if ($selectedType->email_enabled)
                        <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-4 py-2.5 transition has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50 dark:border-zinc-700 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-900/20">
                            <flux:checkbox wire:model="composeChannels" value="email" />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Email') }}</span>
                                <p class="text-xs text-zinc-500">{{ __('Email message') }}</p>
                            </div>
                        </label>
                    @endif
                    @if ($selectedType->sms_enabled)
                        <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-4 py-2.5 transition has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50 dark:border-zinc-700 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-900/20">
                            <flux:checkbox wire:model="composeChannels" value="sms" />
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('SMS') }}</span>
                                <p class="text-xs text-zinc-500">{{ __('Text message (160 chars)') }}</p>
                            </div>
                        </label>
                    @endif
                @else
                    <p class="text-sm text-zinc-400 italic">{{ __('Select a notification type first.') }}</p>
                @endif
            </div>
        </div>

        {{-- Audience Selection --}}
        <div>
            <flux:label>{{ __('Audience') }}</flux:label>
            <div class="mt-2 space-y-3">
                <div class="flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" wire:model.live="composeAudience" value="all" class="size-4 text-indigo-600 focus:ring-indigo-500 dark:bg-zinc-800 dark:border-zinc-600 dark:focus:ring-indigo-400">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('All Members') }}</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" wire:model.live="composeAudience" value="specific" class="size-4 text-indigo-600 focus:ring-indigo-500 dark:bg-zinc-800 dark:border-zinc-600 dark:focus:ring-indigo-400">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Specific Members') }}</span>
                    </label>
                </div>

                @if ($composeAudience === 'specific')
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <flux:input
                            wire:model.live.debounce.300ms="composeMemberSearch"
                            type="search"
                            :placeholder="__('Search members by name or email...')"
                            icon="magnifying-glass"
                        />

                        @if (!empty($this->searchableMembers))
                            <div class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-zinc-100 dark:border-zinc-700">
                                @foreach ($this->searchableMembers as $member)
                                    <button
                                        type="button"
                                        wire:click="addComposeMember({{ $member->id }})"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                    >
                                        <flux:icon.user class="size-4 shrink-0 text-zinc-400" />
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $member->name }}</span>
                                        <span class="text-xs text-zinc-500">{{ $member->email }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if (!empty($composeMemberIds))
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($composeMemberIds as $mid)
                                    @php $m = \App\Models\Member::find($mid); @endphp
                                    @if ($m)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                            {{ $m->name }}
                                            <button type="button" wire:click="removeComposeMember({{ $mid }})" class="text-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-200">&times;</button>
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Subject & Body --}}
        <flux:field>
            <flux:label>{{ __('Subject') }}</flux:label>
            <flux:input wire:model="composeSubject" :placeholder="__('Notification subject line...')" required />
            <div class="min-h-[20px]"><flux:error name="composeSubject" /></div>
        </flux:field>

        <div>
            <flux:field>
                <flux:label>{{ __('Message Body') }}</flux:label>
                <flux:textarea wire:model="composeBody" :placeholder="__('Write your message...')" rows="5" required />
                <div class="min-h-[20px]"><flux:error name="composeBody" /></div>
                <p class="text-xs text-zinc-400">{{ __('You can use') }} <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">@{{ name }}</code> {{ __('and') }} <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">@{{ email }}</code> {{ __('as placeholders. For SMS, messages are truncated to 160 characters.') }}</p>
            </flux:field>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between border-t border-zinc-100 pt-5 dark:border-zinc-800">
            <button type="button" wire:click="resetCompose" class="text-sm text-zinc-500 transition hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ __('Reset form') }}
            </button>
            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary" icon="arrow-right">
                    {{ __('Send Notification') }}
                </flux:button>
            </div>
        </div>
    </form>
</x-ui.dashboard.panel>
