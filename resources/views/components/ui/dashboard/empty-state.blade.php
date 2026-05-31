@props([
    'title',
    'subtitle' => null,
    'table' => false,
])

<div {{ $attributes->class($table ? 'flex flex-col items-center justify-center gap-2 py-10 text-center' : 'rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/40') }}>
    <flux:heading size="sm">{{ $title }}</flux:heading>

    @if ($subtitle)
        <flux:text variant="subtle" class="max-w-md">
            {{ $subtitle }}
        </flux:text>
    @endif

    {{ $slot }}
</div>
