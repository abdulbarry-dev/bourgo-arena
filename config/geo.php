<?php

return [
    'enabled' => env('GEO_ENABLED', true),
    'allowed_countries' => ['TN'],
    'exempt_staff' => true,
    'block_local_ips' => false,
    'cache_ttl_minutes' => 1440,
    'fail_closed' => true,
    'rotation_detection_minutes' => 5,
];
