<div class="mt-6">
    <x-ui.dashboard.table-shell borderless loading-targets="search,statusFilter,serviceFilter,hasSessionsFilter" :has-rows="$courses->count() > 0">
        <x-slot name="loading">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @for ($i = 0; $i < 6; $i++)
                    <div class="rounded-2xl bg-white p-5 dark:bg-zinc-800">
                        <flux:skeleton class="h-40 w-full rounded-xl mb-4" />
                        <div class="space-y-3">
                            <flux:skeleton class="h-4 w-3/4" />
                            <flux:skeleton class="h-3 w-1/2" />
                            <div class="flex justify-between items-center pt-4">
                                <flux:skeleton class="h-6 w-20 rounded-lg" />
                                <flux:skeleton class="h-8 w-24 rounded-lg" />
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </x-slot>

        <x-slot name="empty">
            <x-ui.dashboard.empty-state
                table
                icon="book-open"
                :title="__('No courses found')"
                :subtitle="__('Create course templates to begin scheduling sessions.')"
                :buttonLabel="__('Add Course')"
                buttonWireClick="openCreateModal"
            />
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($courses as $course)
                <div wire:key="course-card-{{ $course->id }}" class="group relative flex flex-col rounded-2xl bg-white p-5 shadow-sm transition-all hover:shadow-md dark:bg-zinc-900/40">
                    {{-- Header with Carousel --}}
                    @php
                        $carouselImages = collect($course->images ?? [])
                            ->whenEmpty(fn($c) => $course->image_url ? collect([$course->image_url]) : $c)
                            ->values()
                            ->map(fn($p) => Str::startsWith($p, ['http', '/storage']) ? $p : asset('storage/'.$p))
                            ->toArray();
                    @endphp
                    <div class="relative overflow-hidden rounded-xl mb-4 group/carousel" x-data="{ 
                        active: 0, 
                        images: {{ json_encode($carouselImages) }},
                        next() { if(this.images.length) this.active = (this.active + 1) % this.images.length },
                        prev() { if(this.images.length) this.active = (this.active - 1 + this.images.length) % this.images.length },
                        init() { if(this.images.length > 1) setInterval(() => this.next(), 5000) }
                    }">
                        @if(!empty($carouselImages))
                            <div class="relative aspect-video w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                <template x-for="(img, idx) in images" :key="'crs-card-'+idx">
                                    <div 
                                        x-show="active === idx" 
                                        x-transition:enter="transition ease-out duration-700"
                                        x-transition:enter-start="opacity-0 scale-105"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-500"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="absolute inset-0 h-full w-full"
                                    >
                                        <img :src="img" class="h-full w-full object-cover" loading="lazy">
                                    </div>
                                </template>

                                @if(count($carouselImages) > 1)
                                    <div class="absolute inset-0 flex items-center justify-between px-3 opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-300 pointer-events-none">
                                        <button @click.stop="prev" class="pointer-events-auto flex size-8 items-center justify-center rounded-full bg-black/20 text-white shadow-sm ring-1 ring-white/10 hover:bg-black/40 transition-all backdrop-blur-md dark:bg-white/10 dark:hover:bg-white/20">
                                            <flux:icon name="chevron-left" variant="mini" class="size-4" />
                                        </button>
                                        <button @click.stop="next" class="pointer-events-auto flex size-8 items-center justify-center rounded-full bg-black/20 text-white shadow-sm ring-1 ring-white/10 hover:bg-black/40 transition-all backdrop-blur-md dark:bg-white/10 dark:hover:bg-white/20">
                                            <flux:icon name="chevron-right" variant="mini" class="size-4" />
                                        </button>
                                    </div>
                                    
                                    <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5 p-1 rounded-full bg-black/20 backdrop-blur-xs">
                                        <template x-for="(img, idx) in images" :key="'crs-dot-'+idx">
                                            <button 
                                                @click.stop="active = idx"
                                                :class="active === idx ? 'bg-white w-4' : 'bg-white/40 w-1.5 hover:bg-white/60'" 
                                                class="h-1.5 rounded-full transition-all duration-300"
                                            ></button>
                                        </template>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex aspect-video w-full items-center justify-center bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                                <flux:icon name="book-open" class="size-12 opacity-20" />
                            </div>
                        @endif

                        {{-- Course Info Overlay --}}
                        <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-black/60 to-transparent p-4 text-white">
                            <h3 class="truncate font-semibold">{{ $course->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                @if($course->service)
                                    <span class="text-xs font-medium text-blue-200">{{ $course->service->name }}</span>
                                @endif
                                <span class="text-zinc-300">·</span>
                                <span class="text-[10px] font-black uppercase tracking-tighter">{{ __('Catalog') }}</span>
                            </div>
                        </div>
                        
                        <div class="absolute top-2 right-2">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="!px-2 text-white hover:bg-white/20" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="openViewFlyout({{ $course->id }})">{{ __('View Details') }}</flux:menu.item>
                                    <flux:menu.item icon="pencil-square" wire:click="openEditModal({{ $course->id }})">{{ __('Edit') }}</flux:menu.item>

                                    <flux:menu.separator />

                                    @if ($course->status !== 'archived')
                                        <flux:menu.item
                                            icon="archive-box"
                                            x-on:click="Flux.modal('confirm-archive-{{ $course->id }}').show()"
                                        >
                                            {{ __('Archive') }}
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item icon="arrow-path" wire:click="restore({{ $course->id }})">
                                            {{ __('Restore') }}
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        x-on:click="Flux.modal('confirm-delete-{{ $course->id }}').show()"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 gap-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon name="calendar-days" variant="mini" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">{{ __('Sessions') }}</div>
                                <div class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $course->sessions_count ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon name="identification" variant="mini" class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-medium uppercase tracking-wider text-zinc-500">{{ __('Code') }}</div>
                                <div class="truncate text-xs font-mono text-zinc-400">#{{ $course->id }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-auto pt-5 flex items-center justify-between">
                        <x-ui.dashboard.status-badge
                            :status="$course->status"
                            :label="ucfirst($course->status)"
                            :color="match($course->status) {
                                'active' => 'green',
                                'archived' => 'orange',
                                default => 'zinc',
                            }"
                        />

                        <flux:button variant="ghost" size="sm" wire:click="openViewFlyout({{ $course->id }})">
                            {{ __('Details') }}
                        </flux:button>
                    </div>

                    {{-- Confirmation Modals --}}
                    <flux:modal name="confirm-archive-{{ $course->id }}" class="w-full max-w-sm">
                        <flux:heading>{{ __('Archive Course') }}</flux:heading>
                        <flux:text>{{ __('Are you sure you want to archive this course template? This will hide it from active selections.') }}</flux:text>
                        <div class="flex justify-end gap-2 mt-6">
                            <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                            <flux:button variant="primary" wire:click="archive({{ $course->id }})" x-on:click="Flux.modal('confirm-archive-{{ $course->id }}').close()">{{ __('Archive') }}</flux:button>
                        </div>
                    </flux:modal>

                    <flux:modal name="confirm-delete-{{ $course->id }}" class="w-full max-w-sm">
                        <flux:heading>{{ __('Delete Course') }}</flux:heading>
                        <flux:text variant="danger">{{ __('Are you sure you want to permanently delete this course template? This action cannot be undone.') }}</flux:text>
                        <div class="flex justify-end gap-2 mt-6">
                            <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                            <flux:button variant="danger" wire:click="confirmDelete({{ $course->id }})" x-on:click="Flux.modal('confirm-delete-{{ $course->id }}').close()">{{ __('Delete') }}</flux:button>
                        </div>
                    </flux:modal>
                </div>
            @endforeach
        </div>

        @if($courses->hasPages())
            <x-slot name="pagination">
                {{ $courses->links() }}
            </x-slot>
        @endif
    </x-ui.dashboard.table-shell>
</div>
