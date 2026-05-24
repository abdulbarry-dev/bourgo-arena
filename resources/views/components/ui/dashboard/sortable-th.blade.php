@props([
    'label',
    'column',
    'sortBy' => null,
    'sortDirection' => 'asc',
    'align' => 'left',
])

<th {{ $attributes->class([
    'px-4 py-3 font-medium text-zinc-700 dark:text-zinc-200',
    $align === 'right' ? 'text-right' : 'text-left',
]) }}>
    <button type="button" class="inline-flex items-center gap-1 {{ $align === 'right' ? 'justify-end' : '' }}" wire:click="sort('{{ $column }}')">
        <span>{{ $label }}</span>

        @if ($sortBy === $column)
            <span aria-hidden="true">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
        @endif
    </button>
</th>