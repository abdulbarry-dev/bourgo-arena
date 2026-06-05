<x-layouts::dashboard :title="__('Dashboard')">
    <x-ui.dashboard.page-header
        :title="__('Dashboard')"
        :subtitle="__('Operational overview and quick access to the admin workspace.')"
    />

    <!-- Stats Overview -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Stat Card 1 -->
        <x-ui.dashboard.panel padding="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Members') }}</p>
                    <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">1,248</h3>
                </div>
                <div class="flex size-12 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                    <flux:icon.users class="size-6" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="flex items-center font-medium text-emerald-600 dark:text-emerald-400">
                    <flux:icon.arrow-up-right class="mr-1 size-4" />
                    12%
                </span>
                <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs last month') }}</span>
            </div>
        </x-ui.dashboard.panel>

        <!-- Stat Card 2 -->
        <x-ui.dashboard.panel padding="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Active Subscriptions') }}</p>
                    <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">856</h3>
                </div>
                <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <flux:icon.credit-card class="size-6" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="flex items-center font-medium text-emerald-600 dark:text-emerald-400">
                    <flux:icon.arrow-up-right class="mr-1 size-4" />
                    5%
                </span>
                <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs last month') }}</span>
            </div>
        </x-ui.dashboard.panel>

        <!-- Stat Card 3 -->
        <x-ui.dashboard.panel padding="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Upcoming Events') }}</p>
                    <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">14</h3>
                </div>
                <div class="flex size-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                    <flux:icon.calendar class="size-6" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="flex items-center font-medium text-emerald-600 dark:text-emerald-400">
                    <flux:icon.arrow-up-right class="mr-1 size-4" />
                    2
                </span>
                <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('new this week') }}</span>
            </div>
        </x-ui.dashboard.panel>

        <!-- Stat Card 4 -->
        <x-ui.dashboard.panel padding="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Revenue (MTD)') }}</p>
                    <h3 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">$12,450</h3>
                </div>
                <div class="flex size-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                    <flux:icon.banknotes class="size-6" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="flex items-center font-medium text-rose-600 dark:text-rose-400">
                    <flux:icon.arrow-down-right class="mr-1 size-4" />
                    1.2%
                </span>
                <span class="ml-2 text-zinc-500 dark:text-zinc-400">{{ __('vs last month') }}</span>
            </div>
        </x-ui.dashboard.panel>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <!-- Main Content Area: Recent Registrations / Activity -->
        <div class="lg:col-span-2">
            <x-ui.dashboard.panel padding="p-0" class="overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Recent Registrations') }}</h3>
                        <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            {{ __('View all') }} &rarr;
                        </a>
                    </div>
                </div>
                
                <x-ui.dashboard.table-shell borderless="true">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-medium">{{ __('Member') }}</th>
                                <th scope="col" class="px-6 py-3 font-medium">{{ __('Plan') }}</th>
                                <th scope="col" class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900/40">
                            @foreach(range(1, 5) as $i)
                            <tr class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-full bg-zinc-200 dark:bg-zinc-700"></div>
                                        <div>
                                            <p class="font-medium text-zinc-900 dark:text-white">John Doe {{ $i }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">john.doe{{ $i }}@example.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $i % 2 == 0 ? 'Premium Access' : 'Standard Pass' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <x-ui.dashboard.status-badge status="active" />
                                </td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                    {{ now()->subHours($i * 2)->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.dashboard.table-shell>
            </x-ui.dashboard.panel>
        </div>

        <!-- Sidebar Area: Upcoming Tournaments / Tasks -->
        <div class="flex flex-col gap-6">
            <x-ui.dashboard.panel padding="p-0">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Upcoming Tournaments') }}</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        @foreach(range(1, 3) as $i)
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">APR</span>
                                <span class="font-bold text-zinc-900 dark:text-white">{{ 10 + $i }}</span>
                            </div>
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white">Spring Championship {{ $i }}</h4>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">12/16 Participants registered</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-6">
                        <flux:button class="w-full" variant="outline">{{ __('View All Events') }}</flux:button>
                    </div>
                </div>
            </x-ui.dashboard.panel>
        </div>
    </div>
</x-layouts::dashboard>
