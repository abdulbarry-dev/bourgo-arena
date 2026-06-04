@props(['status' => 'active'])

@php
$config = match ($status) {
    'active'     => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', __('Active')],
    'open'       => ['bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300', __('Open')],
    'in_progress'=> ['bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', __('In Progress')],
    'draft'      => ['bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300', __('Draft')],
    'completed'  => ['bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300', __('Completed')],
    'canceled'   => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', __('Canceled')],
    'suspended'  => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', __('Suspended')],
    'inactive'   => ['bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400', __('Inactive')],
    'archived'   => ['bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400', __('Archived')],
    'pending'    => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', __('Pending')],
    'pending_verification' => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', __('Pending')],
    'pending_onboarding'   => ['bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', __('Onboarding')],
    'expired'    => ['bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400', __('Expired')],
    default      => ['bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400', ucfirst($status)],
};
[$classes, $label] = $config;
@endphp

<span class="inline-flex shrink-0 items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $classes }}">
    {{ $label }}
</span>
