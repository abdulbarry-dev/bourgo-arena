<x-layouts::auth :title="__('Complete member setup')">
    <livewire:auth.member-onboarding-password :token="$token" :email="request('email')" />
</x-layouts::auth>
