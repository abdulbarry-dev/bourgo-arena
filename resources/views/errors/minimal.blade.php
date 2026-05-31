<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>@yield('code') - @yield('title')</title>
        
        <!-- Dashboard Meta Icon -->
        <link rel="icon" href="{{ asset('assets/icons/brandmark-vert.webp') }}" type="image/webp">
        <link rel="apple-touch-icon" href="{{ asset('assets/icons/brandmark-vert.webp') }}">
        
        <!-- Application Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Tailwind CSS (Directly from app) -->
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-900" style="font-family: 'Instrument Sans', sans-serif;">
        <main class="mx-auto flex min-h-svh w-full max-w-lg items-center justify-center p-6 md:p-10">
            <section class="relative w-full overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 md:p-10">
                <div class="pointer-events-none absolute inset-0 size-full opacity-5 bg-[url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23000000\' fill-opacity=\'1\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'2\' cy=\'2\' r=\'2\'/%3E%3C/g%3E%3C/svg%3E')] dark:invert"></div>

                <div class="relative flex flex-col gap-6 text-center sm:text-left">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <span class="mx-auto sm:mx-0 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 font-bold text-2xl">
                            @yield('code')
                        </span>
                        <div>
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">{{ __('System Alert') }}</p>
                            <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100 md:text-2xl mt-1">@yield('title')</h1>
                        </div>
                    </div>

                    <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        @yield('message')
                    </p>

                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="inline-flex w-full sm:w-auto items-center justify-center rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300 shadow-sm">
                            {{ __('Go Back') }}
                        </a>
                        <a href="{{ url('/') }}" class="inline-flex w-full sm:w-auto items-center justify-center rounded-lg border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-zinc-900 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700 shadow-sm">
                            {{ __('Return Home') }}
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
