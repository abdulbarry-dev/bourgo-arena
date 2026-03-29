@include('errors._page', [
    'status' => 401,
    'title' => __('Unauthorized'),
    'message' => __('You need to authenticate before accessing this page.'),
])
