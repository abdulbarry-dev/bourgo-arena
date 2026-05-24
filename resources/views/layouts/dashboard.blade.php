@props(['title' => null])

<x-layouts::app :title="$title">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</x-layouts::app>
