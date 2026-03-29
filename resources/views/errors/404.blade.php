@include('errors._page', [
    'status' => 404,
    'title' => __('Page Not Found'),
    'message' => __('The page you are trying to open does not exist or has been moved.'),
])
