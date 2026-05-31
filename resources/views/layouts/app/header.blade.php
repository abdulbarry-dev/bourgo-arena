<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>

                @if(auth()->user()?->can('access-dashboard-module', 'members'))
                <flux:navbar.item icon="user-group" :href="route('admin.members')" :current="request()->routeIs('admin.members*')" wire:navigate>
                    {{ __('Members') }}
                </flux:navbar.item>
                @endif

                @if(auth()->user()?->can('access-dashboard-module', 'reservations'))
                <flux:navbar.item icon="calendar-date-range" :href="route('admin.reservations.index')" :current="request()->routeIs('admin.reservations.*')" wire:navigate>
                    {{ __('Reservations') }}
                </flux:navbar.item>
                @endif

                @if(auth()->user()?->can('access-dashboard-module', 'activities'))
                <flux:navbar.item icon="calendar-date-range" :href="route('admin.activities.index')" :current="request()->routeIs('admin.activities.*')" wire:navigate>
                    {{ __('Activities & Courts') }}
                </flux:navbar.item>
                @endif

                @if(auth()->user()?->isAdmin())
                <flux:navbar.item icon="receipt-percent" :href="route('admin.reconciliations.index')" :current="request()->routeIs('admin.reconciliations.*')" wire:navigate>
                    {{ __('Reconciliations') }}
                </flux:navbar.item>
                @endif

                @if(auth()->user()?->can('access-dashboard-module', 'subscriptions'))
                <flux:navbar.item icon="credit-card" :href="route('admin.subscriptions')" :current="request()->routeIs('admin.subscriptions*')" wire:navigate>
                    {{ __('Subscriptions') }}
                </flux:navbar.item>
                @endif

                @if(auth()->user()?->can('access-dashboard-module', 'schedule'))
                <flux:navbar.item icon="calendar-date-range" :href="route('admin.course-sessions.index')" :current="request()->routeIs('admin.course-sessions.*')" wire:navigate>
                    {{ __('Schedule') }}
                </flux:navbar.item>
                @endif

                @if(auth()->user()?->can('access-dashboard-module', 'courses'))
                    <flux:navbar.item icon="book-open" :href="route('admin.courses.index')" :current="request()->routeIs('admin.courses.*')" wire:navigate>
                        {{ __('Courses') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="trophy" :href="route('admin.events.index')" :current="request()->routeIs('admin.events.*')" wire:navigate>
                        {{ __('Events & Tournaments') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="clipboard-document-list" :href="route('admin.plans')" :current="request()->routeIs('admin.plans*')" wire:navigate>
                        {{ __('Plans') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="user-circle" :href="route('admin.managers.index')" :current="request()->routeIs('admin.managers.*')" wire:navigate>
                        {{ __('Managers') }}
                    </flux:navbar.item>
                @endif
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
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
                    <flux:sidebar.item icon="receipt-percent" :href="route('admin.reconciliations.index')" :current="request()->routeIs('admin.reconciliations.*')" wire:navigate>
                        {{ __('Reconciliations') }}
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
                </flux:sidebar.group>
            </flux:sidebar.nav>

        </flux:sidebar>

        {{ $slot }}

        
    </body>
</html>
