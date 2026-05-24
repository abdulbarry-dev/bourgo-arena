<div {{ $attributes->class('relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700') }}>
    <x-placeholder-pattern class="absolute inset-0 size-full stroke-zinc-900/20 dark:stroke-zinc-100/20" />

    {{ $slot }}
</div>