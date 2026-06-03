@props([
    'status',
    'label' => null,
    'size' => 'sm',
    'inset' => 'top bottom',
    'color' => null,
])

@php
    $normalizedStatus = strtolower(trim((string) $status));

    $resolvedColor = $color ?? match ($normalizedStatus) {
        'active', 'authorized', 'online', 'all-inclusive' => 'green',
        'pending', 'warning' => 'amber',
        'suspended', 'denied', 'offline', 'lost', 'archived' => 'red',
        default => 'zinc',
    };

    $displayLabel = $label ?? ucfirst($normalizedStatus);
@endphp

<flux:badge size="{{ $size }}" color="{{ $resolvedColor }}" inset="{{ $inset }}">
    {{ $displayLabel }}
</flux:badge>