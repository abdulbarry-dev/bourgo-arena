<div class="grid gap-6 xl:grid-cols-2">
    {{-- Personal Information --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Personal Information') }}</h3>
        </div>
        <div class="p-5">
            <dl class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($member->gender ?? 'N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->date_of_birth?->format('M d, Y') ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Emergency Contact') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->emergency_contact ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Member Since') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->created_at?->format('M d, Y') ?? __('N/A') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Subscription & Access --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700 flex justify-between items-center">
            <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Subscription & Access') }}</h3>
            @if($member->activeSubscription)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    {{ __('Active') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                    <span class="size-1.5 rounded-full bg-zinc-400"></span>
                    {{ __('Inactive') }}
                </span>
            @endif
        </div>
        <div class="p-5">
            <dl class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2 text-sm">
                <div class="sm:col-span-2">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Active Plan') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->activeSubscription?->plan?->name ?? __('No active plan') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Subscription End Date') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->activeSubscription?->ends_at?->format('M d, Y') ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Enrolled By') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $member->activeSubscription?->enrolledBy?->name ?? __('N/A') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
