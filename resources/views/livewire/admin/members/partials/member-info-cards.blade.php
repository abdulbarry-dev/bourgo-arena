<div class="grid gap-4 xl:grid-cols-2">
    <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <flux:heading size="sm">{{ __('Profile') }}</flux:heading>

        <dl class="grid gap-3 text-sm">
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $member->name }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->email }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->phone }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $member->status }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->date_of_birth?->format('Y-m-d') ?? __('N/A') }}</dd>
            </div>
        </dl>
    </div>

    <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <flux:heading size="sm">{{ __('Subscription & Access') }}</flux:heading>

        <dl class="grid gap-3 text-sm">
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Active Plan') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->activeSubscription?->plan?->name ?? __('No active plan') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Subscription End Date') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->activeSubscription?->ends_at?->format('Y-m-d') ?? __('N/A') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Card UID') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->nfcCard?->uid ?? __('Not assigned') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Card Status') }}</dt>
                <dd class="capitalize text-zinc-800 dark:text-zinc-200">{{ $member->nfcCard?->status ?? __('Unassigned') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Card Assigned') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->nfcCard?->assigned_at?->format('Y-m-d H:i') ?? __('N/A') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Enrolled By') }}</dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->activeSubscription?->enrolledBy?->name ?? __('N/A') }}</dd>
            </div>
        </dl>
    </div>

    @if ($member->parent || $member->children->isNotEmpty())
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:heading size="sm">{{ __('Family') }}</flux:heading>

            <dl class="grid gap-3 text-sm">
                @if ($member->parent)
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Parent / Guardian') }}</dt>
                        <dd>
                            <flux:link wire:click="loadMember({{ $member->parent->id }})" class="cursor-pointer">
                                {{ $member->parent->name }}
                            </flux:link>
                        </dd>
                    </div>
                @endif

                @if ($member->children->isNotEmpty())
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Children / Dependents') }}</dt>
                        <dd class="space-y-1">
                            @foreach ($member->children as $child)
                                <div class="flex items-center gap-2">
                                    <flux:link wire:click="loadMember({{ $child->id }})" class="cursor-pointer">
                                        {{ $child->name }}
                                    </flux:link>
                                    <flux:badge size="sm" variant="subtle" class="capitalize">{{ $child->status }}</flux:badge>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif
</div>