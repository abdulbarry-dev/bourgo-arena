<div class="mt-6">
    <x-ui.dashboard.table-shell :has-rows="$courses->count() > 0">
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

        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Image') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Course Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Service') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Sessions') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                @foreach($courses as $course)
                    <tr wire:key="course-row-{{ $course->id }}">
                        <td class="px-4 py-3">
                            @if ($course->image_url)
                                <img src="{{ $course->image_url }}" alt="{{ $course->name }}" class="size-10 rounded-lg object-cover">
                            @else
                                <div class="size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <flux:icon.book-open class="size-5 text-zinc-400" />
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                            <div class="flex items-center gap-2">
                                <div class="size-2 rounded-full" style="background-color: #9ca3af"></div>
                                {{ __($course->name) }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            @if($course->service)
                                <flux:badge size="sm" color="blue" inset="top bottom">{{ $course->service->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400 italic text-xs">{{ __('N/A') }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
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
                        </td>

                        <td class="px-4 py-3">
                            <x-ui.dashboard.status-badge status="course-sessions" color="zinc" :label="$course->sessions_count ?? $course->sessions()->count()" />
                        </td>

                        <td class="px-4 py-3 text-right">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="!px-2" />
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
                        </td>
                    </tr>

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
                @endforeach
            </tbody>
        </table>
    </x-ui.dashboard.table-shell>
</div>
