<div class="grid gap-4 xl:grid-cols-2">
    {{-- Profil --}}
    <x-ui.dashboard.panel class="space-y-4 p-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">{{ __('Profile') }}</flux:heading>
            <flux:badge size="sm" variant="subtle" class="capitalize">{{ $member->account_type_label }}</flux:badge>
        </div>

        <dl class="grid gap-3 text-sm">
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $member->name }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Email') }} 
                    @if(!$member->email && $member->isChild()) <span class="text-xs text-zinc-400">({{ __('Parent') }})</span> @endif
                </dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->fallback_email ?? __('N/A') }}</dd>
            </div>
            <div>
                <dt class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Phone') }}
                    @if(!$member->phone && $member->isChild()) <span class="text-xs text-zinc-400">({{ __('Parent') }})</span> @endif
                </dt>
                <dd class="text-zinc-800 dark:text-zinc-200">{{ $member->fallback_phone ?? __('N/A') }}</dd>
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
    </x-ui.dashboard.panel>

    {{-- Subscription & Access --}}
    <x-ui.dashboard.panel class="space-y-4 p-4">
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
    </x-ui.dashboard.panel>
</div>