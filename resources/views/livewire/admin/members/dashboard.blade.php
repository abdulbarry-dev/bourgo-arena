<x-layouts::app :title="__('Members')">
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('dashboard') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Dashboard') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Members') }}</li>
            </ol>
        </nav>

        <div>
            <livewire:admin.members.member-table :selection-enabled="false" />
            <livewire:admin.members.add-member-flyout />
        </div>
    </section>
</x-layouts::app>
