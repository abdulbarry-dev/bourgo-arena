@if ($session && $date)
    @php
        $status = $this->sessionData['status'];
        $badgeColor = match ($status) {
            'canceled' => 'red',
            'validated' => 'zinc',
            'setted' => 'blue',
            default => 'zinc',
        };
    @endphp

    <div class="space-y-6">
        @include('livewire.admin.course-sessions.partials.session-detail-header', ['status' => $status, 'badgeColor' => $badgeColor])

        @if ($status === 'canceled')
            @include('livewire.admin.course-sessions.partials.session-detail-cancelled-state')
        @elseif ($status === 'validated')
            @include('livewire.admin.course-sessions.partials.session-detail-validated-state')
        @else
            @include('livewire.admin.course-sessions.partials.session-detail-enroll-form')
            @include('livewire.admin.course-sessions.partials.session-detail-bookings')
            @include('livewire.admin.course-sessions.partials.session-detail-actions')
            @include('livewire.admin.course-sessions.partials.session-detail-master-actions')
        @endif
    </div>
@endif