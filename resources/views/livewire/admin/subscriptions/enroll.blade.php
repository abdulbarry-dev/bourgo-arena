<x-layouts::app :title="__('Enroll Subscription')">
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-600 dark:text-zinc-300">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <a href="{{ route('admin.subscriptions') }}" wire:navigate class="font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-zinc-100">
                        {{ __('Subscriptions') }}
                    </a>
                </li>
                <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                <li class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Enroll Subscription') }}</li>
            </ol>
        </nav>

        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Enroll Subscription') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Create a new subscription enrollment with payment and receipt generation.') }}</flux:text>
        </div>

        <livewire:admin.subscriptions.subscription-enrollment />
    </section>
</x-layouts::app>