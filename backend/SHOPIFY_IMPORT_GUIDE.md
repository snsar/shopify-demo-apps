# Hướng dẫn sử dụng Shopify Import Module

Module này cho phép import toàn bộ Products và Draft Orders từ Shopify về database thông qua GraphQL API.

## Cấu hình

### 1. Cập nhật Shopify Scopes

Đảm bảo app Shopify có các scopes cần thiết trong file `.env`:

```env
SHOPIFY_SCOPES=read_products,write_products,read_draft_orders
```

### 2. Chạy Migrations

```bash
php artisan migrate
```

## Sử dụng qua Artisan Commands

### Import Products

```bash
# Import products từ shop
php artisan shopify:import-products myshop.myshopify.com

# Import products và xóa dữ liệu cũ trước
php artisan shopify:import-products myshop.myshopify.com --clear
```

### Import Draft Orders

```bash
# Import draft orders từ shop
php artisan shopify:import-draft-orders myshop.myshopify.com

# Import draft orders và xóa dữ liệu cũ trước
php artisan shopify:import-draft-orders myshop.myshopify.com --clear
```

### Import Tất cả

```bash
# Import tất cả dữ liệu từ shop
php artisan shopify:import-all myshop.myshopify.com

# Import tất cả và xóa dữ liệu cũ trước
php artisan shopify:import-all myshop.myshopify.com --clear
```

## Sử dụng qua API Endpoints

Tất cả endpoints yêu cầu Shopify token authentication.

### 1. Import Products

```http
POST /api/import/products
Content-Type: application/json
X-Shopify-Shop-Domain: myshop.myshopify.com
X-Shopify-Access-Token: your-access-token

{
    "shop": "myshop.myshopify.com",
    "clear": false
}
```

### 2. Import Draft Orders

```http
POST /api/import/draft-orders
Content-Type: application/json
X-Shopify-Shop-Domain: myshop.myshopify.com
X-Shopify-Access-Token: your-access-token

{
    "shop": "myshop.myshopify.com",
    "clear": false
}
```

### 3. Import Tất cả

```http
POST /api/import/all
Content-Type: application/json
X-Shopify-Shop-Domain: myshop.myshopify.com
X-Shopify-Access-Token: your-access-token

{
    "shop": "myshop.myshopify.com",
    "clear": false
}
```

### 4. Lấy Thống kê

```http
GET /api/import/stats?shop=myshop.myshopify.com
X-Shopify-Shop-Domain: myshop.myshopify.com
X-Shopify-Access-Token: your-access-token
```

### 5. Xóa Dữ liệu

```http
DELETE /api/import/clear
Content-Type: application/json
X-Shopify-Shop-Domain: myshop.myshopify.com
X-Shopify-Access-Token: your-access-token

{
    "shop": "myshop.myshopify.com"
}
```

## Cấu trúc Database

### Products

- **products**: Thông tin chính của product
- **product_variants**: Các variant của product
- **product_images**: Hình ảnh của product

### Draft Orders

- **draft_orders**: Thông tin chính của draft order
- **draft_order_line_items**: Các line items của draft order

## Services

### ShopifyGraphQLService

Service chính để gọi GraphQL API Shopify:

```php
$graphQLService = app(ShopifyGraphQLService::class);

// Lấy products với pagination
$response = $graphQLService->getProducts('myshop.myshopify.com', 50, $cursor);

// Lấy draft orders với pagination
$response = $graphQLService->getDraftOrders('myshop.myshopify.com', 50, $cursor);
```

### ShopifyImportService

Service để import dữ liệu về database:

```php
$importService = app(ShopifyImportService::class);

// Import products
$stats = $importService->importProducts('myshop.myshopify.com');

// Import draft orders
$stats = $importService->importDraftOrders('myshop.myshopify.com');

// Import tất cả
$stats = $importService->importAll('myshop.myshopify.com');
```

## Models

### Product

```php
// Tìm product theo Shopify ID
$product = Product::findByShopifyId('gid://shopify/Product/123');

// Tạo/cập nhật từ Shopify data
$product = Product::createOrUpdateFromShopify($shop, $shopifyData);

// Relationships
$product->variants; // ProductVariant[]
$product->images;   // ProductImage[]
```

### DraftOrder

```php
// Tìm draft order theo Shopify ID
$draftOrder = DraftOrder::findByShopifyId('gid://shopify/DraftOrder/123');

// Tạo/cập nhật từ Shopify data
$draftOrder = DraftOrder::createOrUpdateFromShopify($shop, $shopifyData);

// Relationships
$draftOrder->lineItems; // DraftOrderLineItem[]
```

## Logging

Tất cả hoạt động import được log chi tiết trong Laravel logs. Kiểm tra file log để debug:

```bash
tail -f storage/logs/laravel.log
```

## Rate Limiting

Module tự động thêm delay 1 giây giữa các batch để tránh rate limiting của Shopify API.

## Error Handling

- Tất cả lỗi được log chi tiết
- Import tiếp tục với các items khác nếu một item bị lỗi
- Trả về thống kê chi tiết bao gồm số lượng lỗi

## Ví dụ Response

```json
{
    "success": true,
    "message": "Import products thành công",
    "data": {
        "total_products": 150,
        "total_variants": 450,
        "total_images": 300,
        "errors": []
    }
}
```
