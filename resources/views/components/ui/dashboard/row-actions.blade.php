@props([
    'justify' => 'end',
])

<div {{ $attributes->class(['flex', 'items-center', 'gap-2', $justify === 'end' ? 'justify-end' : 'justify-start']) }}>
    {{ $slot }}
</div>