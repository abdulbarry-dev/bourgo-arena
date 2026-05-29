<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    @php
        $isLockedDashboardPage = request()->routeIs('dashboard', 'admin.members', 'admin.subscriptions', 'admin.events.index');
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
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @if(auth()->user()?->can('access-dashboard-module', 'members'))
                    <flux:sidebar.item icon="user-group" :href="route('admin.members')" :current="request()->routeIs('admin.members*')" wire:navigate>
                        {{ __('Members') }}
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

        @fluxScripts
    </body>
</html>
