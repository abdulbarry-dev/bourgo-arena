<div class="flex items-start gap-4 pb-6 border-b border-zinc-100 dark:border-zinc-800">
    @if ($session->course->image_url)
        <img
            src="{{ $session->course->image_url }}"
            alt="{{ $session->course->name }}"
            class="size-16 rounded-xl object-cover shadow-sm"
        >
    @else
        <div class="flex size-16 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:icon name="academic-cap" class="size-8 text-zinc-400 dark:text-zinc-500" />
        </div>
    @endif

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
            <x-ui.dashboard.status-badge
                :status="$status"
                :label="__($status)"
                :color="$badgeColor"
                class="capitalize"
            />
        </div>
        <flux:heading size="lg" class="truncate">{{ __($session->course->name) }}</flux:heading>
        <flux:subheading>{{ __('Session Details & Attendance') }}</flux:subheading>
    </div>
</div>

