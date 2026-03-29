@include('errors._page', [
    'status' => 403,
    'title' => __('Forbidden'),
    'message' => __('You do not have permission to access this area with your current role.'),
])
