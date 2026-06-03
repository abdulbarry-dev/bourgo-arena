@php
    $latestValidSubscription = $member->validSubscriptions->sortByDesc('ends_at')->first();
@endphp

<div class="flex flex-col gap-6">
    {{-- Personal Information --}}
    <section>
        <flux:heading size="xs" class="uppercase tracking-widest text-zinc-500 mb-3">{{ __('Personal Information') }}</flux:heading>
        <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($member->gender ?? 'N/A') }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $member->date_of_birth?->format('M d, Y') ?? __('N/A') }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Emergency Contact') }}</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $member->emergency_contact ?? __('N/A') }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member Since') }}</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $member->created_at?->format('M d, Y') ?? __('N/A') }}</span>
            </div>
        </div>
    </section>

    {{-- Subscription & Access --}}
    <section>
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="xs" class="uppercase tracking-widest text-zinc-500">{{ __('Subscription & Access') }}</flux:heading>
            @if($latestValidSubscription)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <span class="size-1 rounded-full bg-emerald-500"></span>
                    {{ __('Active') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="size-1 rounded-full bg-zinc-400"></span>
                    {{ __('Inactive') }}
                </span>
            @endif
        </div>
        <div class="space-y-3">
            <div class="py-2 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-sm text-zinc-500 dark:text-zinc-400 block mb-1">{{ __('Active Plan') }}</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $latestValidSubscription?->plan?->name ?? __('No active plan') }}</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="py-2 border-b border-zinc-100 dark:border-zinc-800">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400 block mb-1">{{ __('Ends') }}</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $latestValidSubscription?->ends_at?->format('M d, Y') ?? __('N/A') }}</span>
                </div>
                <div class="py-2 border-b border-zinc-100 dark:border-zinc-800">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400 block mb-1">{{ __('Enrolled By') }}</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $latestValidSubscription?->enrolledBy?->name ?? __('N/A') }}</span>
                </div>
            </div>
        </div>
    </section>
</div>