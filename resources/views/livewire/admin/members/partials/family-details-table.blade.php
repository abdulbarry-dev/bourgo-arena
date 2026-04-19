<section class="w-full space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="sm">{{ __('Family Members') }}</flux:heading>
        <flux:badge size="sm" variant="subtle">{{ trans_choice('{1} :count relative|[2,*] :count relatives', $relativesCount = ($member->parent ? 1 : 0) + $member->children->count(), ['count' => $relativesCount]) }}</flux:badge>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/80">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Member') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Role') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Subscription') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-200">{{ __('Access Card') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-200"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900/40">
                    @if ($member->parent)
                        <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100 italic">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="user-circle" variant="mini" class="text-zinc-400" />
                                    <span>{{ $member->parent->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" variant="pill" color="indigo">{{ __('Parent/Guardian') }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" variant="subtle" class="capitalize">{{ $member->parent->status }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $member->parent->activeSubscription?->plan?->name ?? __('No active plan') }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($member->parent->nfcCard)
                                    <code class="text-xs text-zinc-500">{{ $member->parent->nfcCard->uid }}</code>
                                @else
                                    <span class="text-xs text-zinc-400 italic font-light">{{ __('Not assigned') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button variant="ghost" size="sm" icon="arrow-right" :href="route('admin.members.show', $member->parent)" wire:navigate aria-label="{{ __('View Profile') }}" />
                            </td>
                        </tr>
                    @endif

                    @foreach ($member->children as $child)
                        <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center gap-2">
                                    <flux:icon name="user" variant="mini" class="text-zinc-400" />
                                    <span>{{ $child->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" variant="pill" color="zinc">{{ __('Child/Dependent') }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" variant="subtle" class="capitalize">{{ $child->status }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $child->activeSubscription?->plan?->name ?? __('No active plan') }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($child->nfcCard)
                                    <code class="text-xs text-zinc-500">{{ $child->nfcCard->uid }}</code>
                                @else
                                    <span class="text-xs text-zinc-400 italic font-light">{{ __('Not assigned') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button variant="ghost" size="sm" icon="arrow-right" :href="route('admin.members.show', $child)" wire:navigate aria-label="{{ __('View Profile') }}" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
