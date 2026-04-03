<div>
    <nav class="flex mb-4" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-4">
            <li>
                <div class="flex items-center">
                    <a href="{{ route('admin.members') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Admin</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <span class="ml-4 text-sm font-medium text-gray-700" aria-current="page">Access Control</span>
                </div>
            </li>
        </ol>
    </nav>

    <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-6">Access Control Dashboard</h1>
    
    <div class="mb-8">
        <livewire:admin.access-control.check-in-monitor />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <livewire:admin.access-control.audit-log />
        </div>
        <div>
            <livewire:admin.access-control.anti-passback-alerts />
        </div>
    </div>
</div>
