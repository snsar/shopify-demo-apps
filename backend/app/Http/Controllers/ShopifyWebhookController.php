<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function handleUninstalled(Request $request)
    {
        $request = $request->all();

        Log::info('Shopify webhook received', $request);

        return response('OK', 200);
    }
}
