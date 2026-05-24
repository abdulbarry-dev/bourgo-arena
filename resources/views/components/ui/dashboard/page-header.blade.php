@props([
    'title',
    'subtitle' => null,
    'headingSize' => 'xl',
])

<div {{ $attributes->class('flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between') }}>
    <div class="space-y-1">
        <flux:heading :size="$headingSize">{{ $title }}</flux:heading>

        @if ($subtitle)
            <flux:text variant="subtle">{{ $subtitle }}</flux:text>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
