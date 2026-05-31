@props(['padding' => 'p-6'])

<div {{ $attributes->class(["rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/40", $padding]) }}>
    {{ $slot }}
</div>