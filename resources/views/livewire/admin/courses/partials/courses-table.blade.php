<div class="mt-6">
    <x-ui.dashboard.table-shell loading-targets="search,statusFilter,categoryFilter,hasSessionsFilter" :has-rows="$courses->count() > 0">
        <x-slot name="loading">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:skeleton class="h-32 w-full rounded-t-2xl" />
                        <div class="p-4">
                            <flux:skeleton class="h-4 w-3/4 mb-2" />
                            <flux:skeleton class="h-3 w-1/2 mb-4" />
                            <div class="flex justify-between items-center">
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
                :subtitle="__('Courses you create will appear here. Start by adding your first course.')"
                :buttonLabel="__('Add Course')"
                buttonWireClick="openCreateModal"
            />
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($courses as $course)
                <div wire:key="course-card-{{ $course->id }}" class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white shadow-sm transition-all hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900/40">
                    {{-- Header Image --}}
                    <div class="relative h-32 w-full overflow-hidden rounded-t-2xl">
                        @if ($course->image_url)
                            <img src="{{ $course->image_url }}" alt="{{ $course->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-emerald-500 to-emerald-700">
                                <flux:icon.book-open class="size-8 text-white/50" />
                            </div>
                        @endif
                        
                        <div class="absolute top-3 right-3">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="!bg-white/90 !backdrop-blur-sm !border-none !shadow-sm dark:!bg-zinc-800/90" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="openViewFlyout({{ $course->id }})">
                                        {{ __('View Details') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil-square" wire:click="openEditModal({{ $course->id }})">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    
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
                                            {{ __('Restore to Active') }}
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

                    {{-- Body --}}
                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-3">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __($course->name) }}</h3>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @if($course->service)
                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ $course->service->name }}</flux:badge>
                                @endif
                                
                                @if(isset($course->category))
                                    <flux:badge size="sm" color="zinc" variant="subtle">{{ ucfirst($course->category) }}</flux:badge>
                                @endif
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-between">
                            <div class="flex flex-col gap-1.5">
                                <x-ui.dashboard.status-badge
                                    :status="$course->status"
                                    :label="match($course->status) {
                                        'active' => __('Active'),
                                        'inactive' => __('Inactive'),
                                        'archived' => __('Archived'),
                                        default => ucfirst($course->status),
                                    }"
                                    :color="match($course->status) {
                                        'active' => 'green',
                                        'inactive' => 'gray',
                                        'archived' => 'orange',
                                        default => 'zinc',
                                    }"
                                />
                                <div class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.calendar class="size-3" />
                                    {{ trans_choice(':count Session|:count Sessions', $course->sessions_count ?? 0) }}
                                </div>
                            </div>

                            <flux:button variant="ghost" size="sm" wire:click="openViewFlyout({{ $course->id }})">
                                {{ __('View Details') }}
                            </flux:button>
                        </div>
                    </div>

                    {{-- Confirmation Modals --}}
                    @if ($course->status !== 'archived')
                        <flux:modal name="confirm-archive-{{ $course->id }}" class="w-full max-w-sm">
                            <flux:heading>{{ __('Archive Course') }}</flux:heading>
                            <flux:text>{{ __('Are you sure you want to archive this course template? This will hide it from active selections.') }}</flux:text>
                            <div class="flex justify-end gap-2 mt-6">
                                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                <flux:button variant="primary" wire:click="archive({{ $course->id }})" x-on:click="Flux.modal('confirm-archive-{{ $course->id }}').close()">{{ __('Archive') }}</flux:button>
                            </div>
                        </flux:modal>
                    @endif
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
