<!-- View Event Modal -->
<flux:modal name="view-event-modal" variant="flyout" class="max-w-2xl w-full" x-on:hidden="$wire.closeViewModal()">
    @if($eventToView)
        <div class="space-y-6">
            {{-- Premium Header Carousel --}}
            @php
                $carouselImages = collect($eventToView->images ?? [])
                    ->whenEmpty(fn($c) => $eventToView->image_url ? collect([$eventToView->image_url]) : $c)
                    ->values()
                    ->map(fn($p) => Str::startsWith($p, ['http', '/storage']) ? $p : asset('storage/'.$p))
                    ->toArray();
            @endphp
            <div class="relative -mx-6 -mt-6 overflow-hidden bg-zinc-900 aspect-video group/viewer" 
                 x-data="{ 
                    active: 0, 
                    images: {{ json_encode($carouselImages) }},
                    next() { if(this.images.length) this.active = (this.active + 1) % this.images.length },
                    prev() { if(this.images.length) this.active = (this.active - 1 + this.images.length) % this.images.length },
                    init() { if(this.images.length > 1) setInterval(() => this.next(), 5000) }
                 }">
                
                @if(!empty($carouselImages))
                    <template x-for="(img, idx) in images" :key="'evt-view-'+idx">
                        <div x-show="active === idx" 
                             x-transition:enter="transition ease-out duration-700"
                             x-transition:enter-start="opacity-0 scale-110"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-500"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 h-full w-full">
                            <img :src="img" x-on:error="$el.remove()" class="h-full w-full object-cover opacity-80" alt="">
                        </div>
                    </template>

                    {{-- Gradient Overlay --}}
                    <div class="absolute inset-0 bg-linear-to-t from-zinc-900 via-zinc-900/20 to-transparent"></div>

                    {{-- Carousel Controls --}}
                    @if(count($carouselImages) > 1)
                        <div class="absolute inset-0 flex items-center justify-between px-4 opacity-0 group-hover/viewer:opacity-100 transition-opacity duration-300 pointer-events-none">
                            <button @click.stop="prev" class="pointer-events-auto flex size-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-white/20 transition-all border border-white/10">
                                <flux:icon name="chevron-left" class="size-5" />
                            </button>
                            <button @click.stop="next" class="pointer-events-auto flex size-10 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-white/20 transition-all border border-white/10">
                                <flux:icon name="chevron-right" class="size-5" />
                            </button>
                        </div>

                        <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2 p-1.5 rounded-full bg-black/40 backdrop-blur-sm border border-white/10">
                            <template x-for="(img, idx) in images" :key="'evt-view-dot-'+idx">
                                <button @click.stop="active = idx"
                                        :class="active === idx ? 'bg-white w-6' : 'bg-white/30 w-2 hover:bg-white/50'" 
                                        class="h-2 rounded-full transition-all duration-300"></button>
                            </template>
                        </div>
                    @endif
                @else
                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-zinc-800 to-zinc-900">
                        <flux:icon name="trophy" class="size-20 text-white/10" />
                    </div>
                @endif

                {{-- Quick Badge --}}
                <div class="absolute top-6 left-6">
                    <x-ui.dashboard.status-badge
                        :status="$eventToView->status"
                        :label="ucfirst(str_replace('_', ' ', $eventToView->status))"
                        :color="match($eventToView->status) {
                            'open' => 'green',
                            'in_progress' => 'blue',
                            'completed' => 'zinc',
                            'canceled' => 'red',
                            'draft' => 'gray',
                            default => 'zinc',
                        }"
                        class="shadow-xl"
                    />
                </div>

                {{-- Event Title Overlay --}}
                <div class="absolute bottom-12 left-6 right-6">
                    <h2 class="text-3xl font-black tracking-tight text-white drop-shadow-md">{{ $eventToView->name }}</h2>
                    <div class="mt-2 flex items-center gap-3">
                        @if($eventToView->service)
                            <span class="inline-flex items-center rounded-md bg-blue-500/20 px-2 py-1 text-xs font-bold text-blue-300 backdrop-blur-md ring-1 ring-inset ring-blue-500/30">
                                {{ $eventToView->service->name }}
                            </span>
                        @endif
                        <span class="text-zinc-400 font-bold text-xs uppercase tracking-widest">{{ $eventToView->format }}</span>
                    </div>
                </div>
            </div>

            {{-- Event Details Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Participants') }}</span>
                    <span class="text-xl font-black text-zinc-900 dark:text-zinc-100">{{ $eventToView->participants_count }} <span class="text-sm font-medium text-zinc-500">/ {{ $eventToView->max_participants }}</span></span>
                </div>
                <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Registration') }}</span>
                    <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $eventToView->registration_deadline?->format('M d, Y') ?? __('N/A') }}</span>
                    <span class="text-[10px] text-zinc-500 font-medium">{{ $eventToView->registration_deadline?->format('H:i') }}</span>
                </div>
                <div class="flex flex-col p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700/50">
                    <span class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-1">{{ __('Format') }}</span>
                    <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $eventToView->format }}</span>
                    <span class="text-[10px] text-zinc-500 font-medium">{{ __('Competitive') }}</span>
                </div>
            </div>

            {{-- Description Section --}}
            @if($eventToView->description)
                <div class="space-y-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400">{{ __('About this event') }}</h3>
                    <div class="prose dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400 leading-relaxed text-sm">
                        {{ $eventToView->description }}
                    </div>
                </div>
            @endif

            {{-- Timeline Section --}}
            <div class="space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-400">{{ __('Event Timeline') }}</h3>
                <div class="relative space-y-4 before:absolute before:inset-y-0 before:left-3.5 before:w-px before:bg-zinc-200 dark:before:bg-zinc-700">
                    <div class="relative flex items-start gap-4">
                        <div class="mt-1 flex size-7 items-center justify-center rounded-full bg-white dark:bg-zinc-800 ring-4 ring-zinc-50 dark:ring-zinc-900 border border-zinc-200 dark:border-zinc-700 z-10">
                            <flux:icon name="play" variant="mini" class="size-3 text-green-500" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ __('Start Date') }}</p>
                            <p class="text-[11px] text-zinc-500">{{ $eventToView->start_date?->format('l, M d, Y \a\t H:i') ?? __('N/A') }}</p>
                        </div>
                    </div>
                    <div class="relative flex items-start gap-4">
                        <div class="mt-1 flex size-7 items-center justify-center rounded-full bg-white dark:bg-zinc-800 ring-4 ring-zinc-50 dark:ring-zinc-900 border border-zinc-200 dark:border-zinc-700 z-10">
                            <flux:icon name="flag" variant="mini" class="size-3 text-red-500" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ __('End Date') }}</p>
                            <p class="text-[11px] text-zinc-500">{{ $eventToView->end_date?->format('l, M d, Y \a\t H:i') ?? __('N/A') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Check-in Note --}}
            @if($eventToView->requires_check_in)
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/50 flex gap-3">
                    <flux:icon name="information-circle" class="size-5 text-amber-600 shrink-0" />
                    <div class="text-xs text-amber-800 dark:text-amber-400 leading-tight font-medium">
                        {{ __('This event requires manual check-in. Participants must be validated on site before the competition begins.') }}
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex gap-2 pt-6">
                <flux:button variant="ghost" wire:click="closeViewModal" class="flex-1">{{ __('Close') }}</flux:button>
                <flux:button variant="primary" icon="users" href="{{ route('admin.events.participants', $eventToView->id) }}" class="flex-1">{{ __('Manage Participants') }}</flux:button>
                <flux:dropdown>
                    <flux:button variant="ghost" icon="ellipsis-horizontal" class="!px-2" />
                    <flux:menu>
                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $eventToView->id }})">{{ __('Edit Event') }}</flux:menu.item>
                        <flux:menu.item icon="trophy" href="{{ route('admin.events.bracket', $eventToView->id) }}">{{ __('Tournament Bracket') }}</flux:menu.item>
                        <flux:menu.separator />
                        @if(in_array($eventToView->status, ['draft', 'open']))
                            <flux:menu.item icon="x-circle" wire:click="openCancelModal({{ $eventToView->id }})" variant="danger">{{ __('Cancel Event') }}</flux:menu.item>
                        @endif
                        <flux:menu.item icon="trash" wire:click="openDeleteModal({{ $eventToView->id }})" variant="danger">{{ __('Delete Event') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    @endif
</flux:modal>
