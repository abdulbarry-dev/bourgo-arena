@php
    $status = $status ?? 500;
    $title = $title ?? __('Unexpected Error');
    $message = $message ?? __('Something went wrong while processing your request.');
    $previousUrl = url()->previous();
    $fallbackUrl = route('home');
    $goBackUrl = $previousUrl !== url()->current() ? $previousUrl : $fallbackUrl;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $status.' - '.$title])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <main class="mx-auto flex min-h-svh w-full max-w-5xl items-center justify-center p-6 md:p-10">
            <section class="relative w-full overflow-hidden rounded-2xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-zinc-800 md:p-10">
                <x-placeholder-pattern class="pointer-events-none absolute inset-0 size-full stroke-gray-900/10 dark:stroke-neutral-100/10" />

                <div class="relative flex flex-col gap-8">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">{{ $status }}</span>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('System Error') }}</p>
                            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100 md:text-3xl">{{ $title }}</h1>
                        </div>
                    </div>

                    <p class="max-w-2xl text-sm leading-6 text-zinc-600 dark:text-zinc-300 md:text-base">{{ $message }}</p>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ $goBackUrl }}"
                            class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                        >
                            {{ __('Go back') }}
                        </a>
                    </div>
                </div>
            </section>
        </main>

    </body>
</html>
