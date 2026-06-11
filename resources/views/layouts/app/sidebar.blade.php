<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    @php
        $isLockedDashboardPage = request()->routeIs('admin.reservations.index', 'admin.activities.index', 'admin.services.index');
    @endphp

    <body @class([
        'min-h-screen bg-white dark:bg-zinc-800 overflow-x-hidden',
        'h-dvh overflow-hidden' => $isLockedDashboardPage,
    ])>
        <flux:sidebar sticky collapsible class="transition-all duration-300 ease-in-out border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 overflow-x-hidden">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                {{-- Global Search Trigger --}}
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('livewire:dispatch', { name: 'openPalette', component: 'shared.global-search' }); $wire.$dispatch('openPalette')"
                    @click.window.stop
                    onclick="window.dispatchEvent(new CustomEvent('open-global-search'))"
                    class="group flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:overflow-hidden"
                    id="sidebar-search-trigger"
                >
                    <flux:icon.magnifying-glass class="size-4 shrink-0 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300" />
                    <span class="flex-1 text-left in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Search') }}</span>
                </button>

                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @if(auth()->user()?->can('access-dashboard-module', 'members'))
                    <flux:sidebar.item icon="user-group" :href="route('admin.members')" :current="request()->routeIs('admin.members*')" wire:navigate>
                        {{ __('Members') }}
                    </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->can('access-dashboard-module', 'reservations'))
                    <flux:sidebar.item icon="calendar-date-range" :href="route('admin.reservations.index')" :current="request()->routeIs('admin.reservations.*')" wire:navigate>
                        {{ __('Reservations') }}
                    </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->can('access-dashboard-module', 'activities'))
                    <flux:sidebar.item icon="calendar-date-range" :href="route('admin.activities.index')" :current="request()->routeIs('admin.activities.*')" wire:navigate>
                        {{ __('Activities & Courts') }}
                    </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->isAdmin())
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.payments.audit')" :current="request()->routeIs('admin.payments.audit*')" wire:navigate>
                            {{ __('Payments Audit') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="bell" :href="route('admin.notifications')" :current="request()->routeIs('admin.notifications*')" wire:navigate>
                            {{ __('Notifications') }}
                        </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->can('access-dashboard-module', 'subscriptions'))
                    <flux:sidebar.item icon="credit-card" :href="route('admin.subscriptions')" :current="request()->routeIs('admin.subscriptions*')" wire:navigate>
                        {{ __('Subscriptions') }}
                    </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->can('access-dashboard-module', 'schedule'))
                    <flux:sidebar.item icon="calendar-date-range" :href="route('admin.course-sessions.index')" :current="request()->routeIs('admin.course-sessions.*')" wire:navigate>
                        {{ __('Schedule') }}
                    </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->can('access-dashboard-module', 'courses'))
                        <flux:sidebar.item icon="book-open" :href="route('admin.courses.index')" :current="request()->routeIs('admin.courses.*')" wire:navigate>
                            {{ __('Courses') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="trophy" :href="route('admin.events.index')" :current="request()->routeIs('admin.events.*')" wire:navigate>
                            {{ __('Events & Tournaments') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.plans')" :current="request()->routeIs('admin.plans*')" wire:navigate>
                            {{ __('Plans') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="puzzle-piece" :href="route('admin.services.index')" :current="request()->routeIs('admin.services.*')" wire:navigate>
                            {{ __('Services') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user-circle" :href="route('admin.managers.index')" :current="request()->routeIs('admin.managers.*')" wire:navigate>
                            {{ __('Managers') }}
                        </flux:sidebar.item>
                    @endif
            </flux:sidebar.nav>
 
            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <livewire:shared.notifications.toast-manager />
        <livewire:shared.global-search />

    </body>
</html>
