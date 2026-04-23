<?php

return [
    'driver' => env('ADMIN_UI_DRIVER', 'vue'),
    'block_filament_routes' => (bool) env('ADMIN_UI_BLOCK_FILAMENT_ROUTES', true),
    'legacy_redirects' => (bool) env('ADMIN_UI_LEGACY_REDIRECTS', true),
];

