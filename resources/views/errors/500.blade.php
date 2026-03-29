@include('errors._page', [
    'status' => 500,
    'title' => __('Server Error'),
    'message' => __('An unexpected server error happened. Please try again or contact support if the problem persists.'),
])
