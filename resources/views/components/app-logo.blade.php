@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand href="{{ route('home') }}" name="{{ config('app.name') }}" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="size-8" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand href="{{ route('home') }}" name="{{ config('app.name') }}" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="size-8" />
        </x-slot>
    </flux:brand>
@endif
