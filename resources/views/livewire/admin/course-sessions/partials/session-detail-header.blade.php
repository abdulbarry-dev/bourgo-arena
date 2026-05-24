<div>
    <div class="mb-1 flex items-center gap-3">
        <flux:heading size="lg">{{ __($session->course->name) }}</flux:heading>
        <flux:badge :color="$badgeColor" size="sm" class="capitalize">{{ __($status) }}</flux:badge>
    </div>

    <flux:subheading>{{ \Carbon\Carbon::parse($date)->format('l, j M Y') }} {{ __('at') }} {{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}</flux:subheading>

    <div class="mt-2 text-sm text-gray-500">
        {{ __('Instructor') }}: {{ __($session->course->instructor) }} &bull; {{ __('Capacity') }}: {{ count($data['bookings']) }}/{{ $session->capacity }}
    </div>
</div>