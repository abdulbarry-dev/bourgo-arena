<div class="mt-6">
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Image') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Course Name') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Instructor') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Sessions') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @forelse($courses as $course)
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
                                    <div class="size-2 rounded-full" @style(['background-color: ' . ($course->color ?? '#9ca3af')])></div>
                                    {{ __($course->name) }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ __($course->instructor) }}</td>

                            <td class="px-4 py-3">
                                <flux:badge size="sm" inset="top bottom">
                                    {{ $course->sessions_count ?? $course->sessions()->count() }}
                                </flux:badge>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <flux:button wire:click="openViewModal({{ $course->id }})" variant="subtle" size="sm" icon="eye" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <flux:heading size="sm">{{ __('No courses found') }}</flux:heading>
                                <flux:text variant="subtle">{{ __('Try adding a new course template.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
