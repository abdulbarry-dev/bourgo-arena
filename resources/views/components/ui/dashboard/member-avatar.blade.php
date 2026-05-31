@props([
    'member',
    'size' => 'md',
    'rounded' => 'full',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-8 text-xs',
        'md' => 'size-10 text-sm',
        'lg' => 'size-14 text-base',
        'xl' => 'size-20 text-2xl',
        default => 'size-10 text-sm',
    };

    $iconClasses = match ($size) {
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-6',
        'xl' => 'size-8',
        default => 'size-5',
    };

    $roundedClass = $rounded === 'xl' ? 'rounded-2xl' : 'rounded-full';
    $initials = $member->initials();
@endphp

@if ($member->avatar_url)
    <img
        src="{{ $member->avatar_url }}"
        alt="{{ $member->name }}"
        {{ $attributes->class([$sizeClasses, $roundedClass, 'shrink-0 object-cover ring-2 ring-white dark:ring-zinc-900']) }}
    />
@else
    <div
        {{ $attributes->class([
            $sizeClasses,
            $roundedClass,
            'flex shrink-0 items-center justify-center bg-gradient-to-br from-zinc-600 via-zinc-700 to-zinc-900 font-semibold uppercase tracking-wide text-white ring-2 ring-white dark:from-zinc-700 dark:via-zinc-800 dark:to-zinc-950 dark:ring-zinc-900',
        ]) }}
        title="{{ $member->name }}"
    >
        @if ($initials !== '')
            {{ $initials }}
        @else
            <flux:icon name="user" class="{{ $iconClasses }} text-white/80" />
        @endif
    </div>
@endif
