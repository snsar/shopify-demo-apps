<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyShopifyWebhook
{
    /**
     * Xác thực webhook từ Shopify bằng HMAC
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $webhookPayload = $request->getContent();
        $webhookSecret = env('SHOPIFY_WEBHOOK_SECRET', env('SHOPIFY_API_SECRET'));

        if (!$hmacHeader) {
            Log::warning('[Webhook] Thiếu HMAC header', [
                'headers' => $request->headers->all()
            ]);
            return response('Unauthorized', 401);
        }

        // Tính HMAC từ payload
        $calculatedHmac = base64_encode(hash_hmac('sha256', $webhookPayload, $webhookSecret, true));

        // So sánh HMAC
        if (!hash_equals($calculatedHmac, $hmacHeader)) {
            Log::warning('[Webhook] HMAC verification failed', [
                'expected' => $calculatedHmac,
                'received' => $hmacHeader,
                'payload_length' => strlen($webhookPayload)
            ]);
            return response('Unauthorized', 401);
        }

        Log::info('[Webhook] HMAC verification successful');
        return $next($request);
    }
}
