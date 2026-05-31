@props([
    'columns' => 'md:grid-cols-3',
])

<div {{ $attributes->class(['grid', 'gap-4', $columns]) }}>
    {{ $slot }}
</div>