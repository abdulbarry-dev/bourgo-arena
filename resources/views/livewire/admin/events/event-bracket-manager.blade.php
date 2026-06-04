<x-ui.dashboard.page-wrapper>
    <x-ui.dashboard.page-header
        :title="__('Bracket Management: :name', ['name' => $event->name])"
        :subtitle="__('Generate, publish, and manage tournament brackets.')"
    >
        <x-slot name="actions">
            <flux:button href="{{ route('admin.events.index') }}" variant="ghost" icon="arrow-left">{{ __('Back to Events') }}</flux:button>
            @if($bracketExists)
                <flux:button wire:click="publishBracket" variant="primary" icon="paper-airplane">{{ __('Publish & Notify') }}</flux:button>
            @endif
        </x-slot>
    </x-ui.dashboard.page-header>

    @if(!$bracketExists)
        <div class="mt-8">
            <x-ui.dashboard.empty-state
                icon="trophy"
                :title="__('No Bracket Generated')"
                :subtitle="__('There is no tournament bracket generated for this event yet. Note: this will reset any existing matches.')"
                :button-label="__('Generate Bracket')"
                button-wire-click="generateBracket"
            />
        </div>
    @else
        <div class="mt-8 overflow-x-auto">
            <div class="flex gap-12 pb-8" style="min-width: max-content;">
                @foreach($matchesByRound as $round => $matches)
                    <div class="flex flex-col gap-6 w-72">
                        <h3 class="font-bold text-zinc-800 dark:text-zinc-200 text-center mb-4">
                            {{ __('Round :round', ['round' => $round]) }}
                        </h3>

                        @foreach($matches as $match)
                            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 shadow-sm flex flex-col gap-3 relative">
                                <div class="flex justify-between items-center text-xs text-zinc-500 font-semibold mb-1">
                                    <span>{{ __('Match #') }}{{ $match->match_number }}</span>
                                    <span class="px-2 py-0.5 rounded {{ $match->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ ucfirst($match->status) }}
                                    </span>
                                </div>

                                {{-- Participant 1 --}}
                                <div class="flex justify-between items-center px-2 py-1.5 rounded {{ $match->winner_id === $match->participant1_id ? 'bg-blue-50 dark:bg-blue-900/20 font-bold' : '' }}">
                                    <span class="truncate text-sm">
                                        @if($match->participant1)
                                            {{ $match->participant1->user?->name ?? 'TBD' }} 
                                            @if($match->participant1->team)
                                                <span class="text-xs text-zinc-400">({{ $match->participant1->team->name }})</span>
                                            @endif
                                        @else
                                            <span class="text-zinc-400 italic">{{ __('TBD') }}</span>
                                        @endif
                                    </span>
                                </div>

                                {{-- Participant 2 --}}
                                <div class="flex justify-between items-center px-2 py-1.5 rounded {{ $match->winner_id === $match->participant2_id ? 'bg-blue-50 dark:bg-blue-900/20 font-bold' : '' }}">
                                    <span class="truncate text-sm">
                                        @if($match->participant2)
                                            {{ $match->participant2->user?->name ?? 'TBD' }}
                                            @if($match->participant2->team)
                                                <span class="text-xs text-zinc-400">({{ $match->participant2->team->name }})</span>
                                            @endif
                                        @else
                                            <span class="text-zinc-400 italic">{{ __('TBD') }}</span>
                                        @endif
                                    </span>
                                </div>

                                @if($match->score)
                                    <div class="text-center text-xs text-zinc-500 font-medium mt-1">
                                        Score: {{ $match->score }}
                                    </div>
                                @endif

                                @if($match->status !== 'completed' && $match->participant1_id && $match->participant2_id)
                                    <div class="mt-2 text-center">
                                        <flux:button wire:click="openAdvanceModal({{ $match->id }})" size="sm" variant="subtle" class="w-full">
                                            {{ __('Advance Winner') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex justify-center">
             <flux:button wire:click="generateBracket" variant="danger" icon="arrow-path" size="sm">
                {{ __('Regenerate Bracket') }}
             </flux:button>
        </div>
    @endif

    {{-- Advance Match Modal --}}
    @if($advancingMatchId)
        @php
            $advancingMatch = \App\Models\EventMatch::find($advancingMatchId);
        @endphp
        <flux:modal name="advance-match-modal" class="min-w-[28rem]">
            <form wire:submit.prevent="confirmAdvance" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Advance Match Winner') }}</flux:heading>
                    <flux:subheading>{{ __('Select the winner of this match to advance them to the next round.') }}</flux:subheading>
                </div>

                <flux:field>
                    <flux:label>{{ __('Winner') }}</flux:label>
                    <flux:select wire:model="winnerId" placeholder="{{ __('Choose the winner...') }}">
                        @if($advancingMatch && $advancingMatch->participant1)
                            <flux:select.option value="{{ $advancingMatch->participant1_id }}">
                                {{ $advancingMatch->participant1->user?->name }} 
                                {{ $advancingMatch->participant1->team ? '('.$advancingMatch->participant1->team->name.')' : '' }}
                            </flux:select.option>
                        @endif
                        @if($advancingMatch && $advancingMatch->participant2)
                            <flux:select.option value="{{ $advancingMatch->participant2_id }}">
                                {{ $advancingMatch->participant2->user?->name }}
                                {{ $advancingMatch->participant2->team ? '('.$advancingMatch->participant2->team->name.')' : '' }}
                            </flux:select.option>
                        @endif
                    </flux:select>
                    <flux:error name="winnerId" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Score (Optional)') }}</flux:label>
                    <flux:input wire:model="matchScore" placeholder="e.g. 21-15, 21-18" />
                    <flux:error name="matchScore" />
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">{{ __('Confirm Winner') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

</x-ui.dashboard.page-wrapper>
