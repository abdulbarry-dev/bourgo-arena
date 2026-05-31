<div class="relative w-full">
    @if ($session->course->image_url)
        <div class="relative h-52 w-full overflow-hidden border-b border-zinc-200 dark:border-zinc-700">
            <img
                src="{{ $session->course->image_url }}"
                alt="{{ $session->course->name }}"
                class="h-full w-full object-cover object-center"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-black/10"></div>
        </div>
    @else
        <div class="relative h-52 w-full overflow-hidden border-b border-zinc-200 bg-gradient-to-br from-zinc-800 via-zinc-900 to-zinc-950 dark:border-zinc-700">
            <div class="absolute inset-0 opacity-40" aria-hidden="true">
                <div class="absolute -right-10 -top-10 size-44 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-14 left-1/3 size-52 rounded-full bg-white/5 blur-3xl"></div>
            </div>
            <div class="relative flex h-full flex-col items-center justify-center gap-3 px-6">
                <div class="flex size-16 items-center justify-center rounded-2xl border border-white/10 bg-white/10 shadow-lg backdrop-blur-sm">
                    <flux:icon name="academic-cap" class="size-8 text-white/80" />
                </div>
                <span class="text-xs font-medium uppercase tracking-wider text-white/50">{{ __('No cover image') }}</span>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
        </div>
    @endif

    <div class="absolute bottom-4 left-6 pr-16">
        <h2 class="text-xl font-bold tracking-tight text-white drop-shadow-sm">{{ __($session->course->name) }}</h2>
        <div class="mt-1 flex items-center gap-1.5 text-sm font-medium text-zinc-200">
            <flux:icon name="user" variant="mini" class="size-4" />
            <span>{{ __($session->course->instructor) }}</span>
        </div>
    </div>

    <div class="absolute top-4 right-10">
        <x-ui.dashboard.status-badge
            :status="$status"
            :label="__($status)"
            :color="$badgeColor"
            class="capitalize"
        />
    </div>
</div>
