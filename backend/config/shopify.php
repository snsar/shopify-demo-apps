<?php

return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES', 'read_products,write_products,read_draft_orders,read_orders,write_orders'),
    'redirect_uri' => env('SHOPIFY_REDIRECT_URI', env('APP_URL') . '/api/auth/callback'),
    'host_name' => env('HOST'),
];
