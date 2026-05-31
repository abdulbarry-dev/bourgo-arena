@props([
    'searchMinWidth' => '240px',
    'controlMinWidth' => '160px',
])

<div {{ $attributes->class(['flex flex-wrap items-end gap-4']) }}>
    <div class="flex-auto" @style(['min-width: '.$searchMinWidth])>
        {{ $search ?? null }}
    </div>

    <div class="flex gap-4 flex-wrap items-end">
        {{ $controls ?? null }}
    </div>
</div>
