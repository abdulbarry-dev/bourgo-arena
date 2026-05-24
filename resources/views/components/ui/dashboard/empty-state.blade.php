@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40') }}>
    <flux:heading size="sm">{{ $title }}</flux:heading>

    @if ($subtitle)
        <flux:text variant="subtle">{{ $subtitle }}</flux:text>
    @endif

    {{ $slot }}
</div>
