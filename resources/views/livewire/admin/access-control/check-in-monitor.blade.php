<div class="space-y-6">
    @include('livewire.admin.access-control.partials.check-in-monitor-header')

    @if ($alertCount > 0)
        @include('livewire.admin.access-control.partials.check-in-monitor-alert-banner')
    @endif

    @include('livewire.admin.access-control.partials.check-in-monitor-feed')
    @include('livewire.admin.access-control.partials.terminal-controls-flyout')
</div>
