<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Session;
use App\Models\Product;
use App\Models\DraftOrder;
use App\Models\Order;
use Exception;

class ShopifyWebhookController extends Controller
{
    /**
     * Xử lý webhook khi app bị gỡ cài đặt
     */
    public function handleUninstalled(Request $request)
    {
        try {
            $payload = $request->json()->all();
            $shop = $payload['domain'] ?? null;

            Log::info('[Webhook] App uninstalled webhook received', [
                'shop' => $shop,
                'payload' => $payload
            ]);

            if (!$shop) {
                Log::error('[Webhook] Không tìm thấy shop domain trong payload');
                return response('Bad Request', 400);
            }

            // Bắt đầu transaction để đảm bảo data consistency
            DB::beginTransaction();

            try {
                // 1. Xóa tất cả sessions của shop
                $deletedSessions = Session::where('shop', $shop)->delete();
                Log::info("[Webhook] Đã xóa {$deletedSessions} sessions cho shop: {$shop}");

                // 2. Xóa dữ liệu sản phẩm đã import (tùy chọn - có thể giữ lại cho báo cáo)
                $deletedProducts = Product::where('shop', $shop)->delete();
                Log::info("[Webhook] Đã xóa {$deletedProducts} products cho shop: {$shop}");

                // 3. Xóa draft orders đã import (tùy chọn)
                $deletedDraftOrders = DraftOrder::where('shop', $shop)->delete();
                Log::info("[Webhook] Đã xóa {$deletedDraftOrders} draft orders cho shop: {$shop}");

                // 4. Có thể thêm logic cleanup khác ở đây
                // Ví dụ: xóa uploaded files, cancel scheduled jobs, etc.

                DB::commit();

                Log::info("[Webhook] App uninstalled cleanup hoàn tất cho shop: {$shop}");

                return response('OK', 200);
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('[Webhook] Lỗi khi xử lý app uninstalled webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Xử lý webhook orders/create - khi có order mới
     */
    public function handleOrdersCreate(Request $request)
    {
        try {
            $payload = $request->json()->all();
            $shop = $request->header('X-Shopify-Shop-Domain') ?? $payload['shop_domain'] ?? null;
            $orderId = $payload['id'] ?? null;

            Log::info('[Webhook] Orders create webhook received', [
                'order_id' => $orderId,
                'shop' => $shop,
                'order_name' => $payload['name'] ?? null,
                'customer_email' => $payload['customer']['email'] ?? null,
                'total_price' => $payload['total_price'] ?? null
            ]);

            if (!$shop || !$orderId) {
                Log::error('[Webhook] Thiếu thông tin shop hoặc order ID', [
                    'shop' => $shop,
                    'order_id' => $orderId
                ]);
                return response('Bad Request', 400);
            }

            // Kiểm tra order đã tồn tại chưa (tránh duplicate)
            $existingOrder = Order::where('shopify_order_id', $orderId)
                ->where('shop', $shop)
                ->first();

            if ($existingOrder) {
                Log::info('[Webhook] Order đã tồn tại, bỏ qua', [
                    'order_id' => $orderId,
                    'existing_id' => $existingOrder->id
                ]);
                return response('OK', 200);
            }

            // Bắt đầu transaction
            DB::beginTransaction();

            try {
                // Tạo order mới từ webhook data
                $order = Order::createFromWebhook($payload, $shop);

                Log::info('[Webhook] Order mới đã được tạo', [
                    'order_id' => $orderId,
                    'local_id' => $order->id,
                    'shop' => $shop,
                    'order_name' => $order->name,
                    'total_price' => $order->total_price,
                    'currency' => $order->currency,
                    'financial_status' => $order->financial_status,
                    'fulfillment_status' => $order->fulfillment_status
                ]);

                DB::commit();

                return response('OK', 200);
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('[Webhook] Lỗi khi xử lý orders create webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->json()->all()
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Xử lý webhook orders/paid (ví dụ)
     */
    public function handleOrdersPaid(Request $request)
    {
        try {
            $payload = $request->json()->all();

            $shop = $request->header('X-Shopify-Shop-Domain') ?? $payload['shop_domain'] ?? null;
            $orderId = $payload['id'] ?? null;

            Log::info('[Webhook] Orders paid webhook received', [
                'order_id' => $orderId,
                'shop' => $shop,
                'financial_status' => $payload['financial_status'] ?? null
            ]);

            // Cập nhật order trong database
            if ($orderId && $shop) {
                $order = Order::where('shopify_order_id', $orderId)
                    ->where('shop', $shop)
                    ->first();

                if ($order) {
                    $order->update([
                        'financial_status' => $payload['financial_status'] ?? $order->financial_status,
                        'shopify_updated_at' => $payload['updated_at'] ?? now(),
                        'raw_data' => $payload
                    ]);

                    Log::info('[Webhook] Order payment status updated', [
                        'order_id' => $orderId,
                        'local_id' => $order->id,
                        'financial_status' => $order->financial_status
                    ]);
                } else {
                    Log::warning('[Webhook] Order không tìm thấy để cập nhật payment status', [
                        'order_id' => $orderId,
                        'shop' => $shop
                    ]);
                }
            }

            return response('OK', 200);
        } catch (Exception $e) {
            Log::error('[Webhook] Lỗi khi xử lý orders paid webhook', [
                'error' => $e->getMessage()
            ]);

            return response('Internal Server Error', 500);
        }
    }
}
