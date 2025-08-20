# Hướng dẫn Webhook Shopify

## Tổng quan

Hệ thống webhook đã được cải thiện với các tính năng bảo mật và xử lý hoàn chỉnh:

- ✅ **HMAC Verification**: Xác thực webhook từ Shopify
- ✅ **CSRF Protection Exemption**: Webhook routes được miễn trừ CSRF
- ✅ **Database Cleanup**: Tự động xóa dữ liệu khi app bị gỡ cài đặt
- ✅ **Error Handling**: Xử lý lỗi toàn diện với logging
- ✅ **Transaction Safety**: Sử dụng database transaction

## Cấu hình

### 1. Environment Variables

Thêm vào file `.env`:

```bash
# Shopify API credentials
SHOPIFY_API_KEY=your_shopify_api_key
SHOPIFY_API_SECRET=your_shopify_api_secret

# Webhook secret (mặc định sẽ dùng SHOPIFY_API_SECRET nếu không có)
SHOPIFY_WEBHOOK_SECRET=your_webhook_secret

# Host URL (không có https://)
HOST=your-domain.com
```

### 2. Shopify App Configuration

Trong file `storefront/shopify.app.toml`:

```toml
[webhooks]
api_version = "2025-07"

[[webhooks.subscriptions]]
topics = ["app/uninstalled"]
uri = "https://your-domain.com/api/webhooks/app-uninstalled"

# Thêm webhook khác nếu cần
[[webhooks.subscriptions]]
topics = ["orders/paid"]
uri = "https://your-domain.com/api/webhooks/orders/paid"
```

## Webhook Endpoints

### 1. App Uninstalled (`/api/webhooks/app-uninstalled`)

**Mục đích**: Được gọi khi merchant gỡ cài đặt app

**Xử lý**:
- Xóa tất cả sessions của shop
- Xóa dữ liệu products đã import
- Xóa dữ liệu draft orders đã import
- Logging chi tiết

**Payload mẫu**:
```json
{
  "domain": "example-shop.myshopify.com",
  "id": 12345,
  "name": "Example Shop",
  // ... other shop data
}
```

### 2. Orders Create (`/api/webhooks/orders/create`)

**Mục đích**: Được gọi khi có order mới được tạo - tự động lưu vào database

**Xử lý**:
- Kiểm tra order đã tồn tại chưa (tránh duplicate)
- Tạo record mới trong bảng `orders`
- Lưu đầy đủ thông tin order, customer, line items
- Logging chi tiết

**Payload mẫu**:
```json
{
  "id": 123456789,
  "name": "#1001",
  "order_number": 1001,
  "financial_status": "pending",
  "fulfillment_status": null,
  "total_price": "199.99",
  "currency": "USD",
  "customer": {
    "id": 987654321,
    "email": "customer@example.com",
    "phone": "+1234567890"
  },
  "line_items": [...],
  "billing_address": {...},
  "shipping_address": {...}
}
```

### 3. Orders Paid (`/api/webhooks/orders/paid`)

**Mục đích**: Được gọi khi có đơn hàng được thanh toán - cập nhật status

**Xử lý**:
- Tìm order trong database theo `shopify_order_id`
- Cập nhật `financial_status`
- Cập nhật `raw_data` với payload mới

**Headers quan trọng**:
- `X-Shopify-Hmac-Sha256`: HMAC signature
- `X-Shopify-Shop-Domain`: Shop domain
- `X-Shopify-Topic`: Webhook topic

## Bảo mật

### HMAC Verification

Tất cả webhook đều được xác thực bằng HMAC SHA-256:

```php
// Middleware VerifyShopifyWebhook tự động xử lý
$calculatedHmac = base64_encode(hash_hmac('sha256', $webhookPayload, $webhookSecret, true));
if (!hash_equals($calculatedHmac, $hmacHeader)) {
    return response('Unauthorized', 401);
}
```

### CSRF Protection

Webhook routes được miễn trừ CSRF protection trong `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'api/webhooks/*',
]);
```

## Logging

Tất cả webhook activity được log với mức độ chi tiết:

- **Info**: Webhook received, processing steps
- **Warning**: Validation failures, missing data
- **Error**: Processing errors, exceptions

Log location: `storage/logs/laravel.log`

## Testing

### 1. Test với ngrok

```bash
# Chạy ngrok để expose local server
ngrok http 80

# Cập nhật webhook URL trong shopify.app.toml
uri = "https://your-ngrok-url.ngrok-free.app/api/webhooks/app-uninstalled"
```

### 2. Test HMAC Verification

```bash
# Test với curl
curl -X POST https://your-domain.com/api/webhooks/app-uninstalled \
  -H "Content-Type: application/json" \
  -H "X-Shopify-Hmac-Sha256: CALCULATED_HMAC" \
  -d '{"domain": "test-shop.myshopify.com"}'
```

### 3. Shopify CLI Test

```bash
# Deploy app với webhook
shopify app deploy

# Test webhook từ Partner Dashboard
# Hoặc sử dụng Shopify CLI để simulate webhook
```

## Troubleshooting

### 1. Webhook không được gọi

- Kiểm tra URL có đúng không
- Kiểm tra app đã được deploy chưa
- Kiểm tra network connectivity

### 2. HMAC Verification Failed

- Kiểm tra `SHOPIFY_WEBHOOK_SECRET` trong .env
- Đảm bảo webhook payload không bị modify
- Kiểm tra Content-Type header

### 3. Database Errors

- Kiểm tra database connection
- Kiểm tra foreign key constraints
- Xem logs để debug transaction issues

## Mở rộng

### API Endpoints để xem Orders

Sau khi webhook lưu orders vào DB, bạn có thể sử dụng các API sau:

```bash
# Lấy danh sách orders
GET /api/orders?shopify_shop=your-shop.myshopify.com&per_page=20

# Lấy chi tiết 1 order
GET /api/orders/{id}?shopify_shop=your-shop.myshopify.com

# Lấy thống kê orders
GET /api/orders/stats/summary?shopify_shop=your-shop.myshopify.com
```

**Response mẫu thống kê**:
```json
{
  "success": true,
  "data": {
    "total_orders": 150,
    "paid_orders": 120,
    "pending_orders": 30,
    "fulfilled_orders": 100,
    "total_revenue": "25000.00",
    "recent_orders": [...]
  }
}
```

### Thêm Webhook mới

1. Thêm route trong `routes/api.php`:
```php
Route::post('/webhooks/new-webhook', [ShopifyWebhookController::class, 'handleNewWebhook']);
```

2. Thêm method trong `ShopifyWebhookController`:
```php
public function handleNewWebhook(Request $request)
{
    // Xử lý webhook logic
}
```

3. Cập nhật `shopify.app.toml`:
```toml
[[webhooks.subscriptions]]
topics = ["new/topic"]
uri = "https://your-domain.com/api/webhooks/new-webhook"
```

### Webhook Retry Logic

Shopify sẽ retry webhook nếu response không phải 2xx. Đảm bảo:
- Return đúng HTTP status code
- Xử lý idempotent (có thể chạy nhiều lần)
- Log để track retry attempts

## Best Practices

1. **Always return 200 OK** cho successful webhook processing
2. **Process quickly** - Shopify có timeout cho webhook
3. **Use database transactions** cho data consistency
4. **Log everything** cho debugging
5. **Handle duplicates** - webhook có thể được gửi nhiều lần
6. **Validate payload** trước khi processing
7. **Use queues** cho heavy processing tasks
