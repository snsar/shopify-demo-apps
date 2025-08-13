<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/auth/callback', function (Request $request) {
    $shop = $request->query('shop');
    Log::info('Shop: ' . $shop);

    return response()->json([
        'shop' => $shop,
        'message' => 'Hello World',
    ]);
});
