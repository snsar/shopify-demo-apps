# Hệ thống Kiểm tra Token Shopify

Hệ thống này cung cấp cơ chế kiểm tra tính hợp lệ của token Shopify một cách toàn diện và an toàn.

## Các Thành phần

### 1. TokenValidationService

Service chính để kiểm tra tính hợp lệ của token Shopify:

- `validateToken(string $shop, string $accessToken): bool` - Kiểm tra token có hợp lệ không
- `validateSession(string $shop): array` - Kiểm tra session từ database
- `validateScopes(string $shop, string $accessToken, array $requiredScopes): bool` - Kiểm tra permissions
- `getShopInfo(string $shop, string $accessToken): ?array` - Lấy thông tin shop
- `removeInvalidSession(string $shop): bool` - Xóa session không hợp lệ

### 2. ValidateShopifyToken Middleware (Nâng cấp theo Official Style)

Middleware được viết lại theo phong cách của Shopify official middleware với các tính năng:

- Hỗ trợ cả online và offline access modes
- GraphQL validation giống như official middleware  
- Bearer token support cho embedded apps
- Request attributes injection
- Comprehensive error handling

```php
// Sử dụng middleware với offline mode (default)
Route::middleware(['validate.shopify.token'])->group(function () {
    // Các routes sử dụng offline tokens
});

// Sử dụng middleware với online mode
Route::middleware(['validate.shopify.token:online'])->group(function () {
    // Các routes cần per-user authentication
});

// Sử dụng middleware với scopes cụ thể
Route::post('/products')->middleware('validate.shopify.token:offline:write_products');
Route::get('/user/info')->middleware('validate.shopify.token:online:read_users');
```

### 3. ShopifyService (Nâng cấp)

Service được nâng cấp với tích hợp validation:

- `makeAuthenticatedRequest()` - Thực hiện API calls với validation tự động
- `checkConnection()` - Kiểm tra trạng thái kết nối
- `isInstalled()` - Kiểm tra app có được cài đặt không
- Các method CRUD cho products với validation tự động

## Cách sử dụng

### 1. API Endpoints mới

Sau khi cài đặt, các API endpoints sau sẽ có sẵn:

#### Offline Mode Endpoints (App-level access)
```bash
# Kiểm tra trạng thái kết nối
GET /api/connection/status?shop=myshop.myshopify.com

# Lấy thông tin shop
GET /api/shop/info?shop=myshop.myshopify.com

# Lấy danh sách sản phẩm
GET /api/products?shop=myshop.myshopify.com

# Tạo sản phẩm mới (cần scope write_products)
POST /api/products
{
    "shop": "myshop.myshopify.com",
    "title": "Sản phẩm mới",
    "body_html": "Mô tả sản phẩm"
}

# Validate token
POST /api/validate-token?shop=myshop.myshopify.com
```

#### Online Mode Endpoints (Per-user access)
```bash
# Lấy thông tin user hiện tại
GET /api/user/info?shop=myshop.myshopify.com

# Cài đặt admin (chỉ dành cho account owner)
POST /api/admin/settings?shop=myshop.myshopify.com
{
    "setting_key": "value"
}
```

### 2. Sử dụng trong Controller

```php
use App\Services\TokenValidationService;
use App\Services\ShopifyService;

class MyController extends Controller
{
    public function index(Request $request, ShopifyService $shopifyService)
    {
        // Lấy thông tin từ middleware (official style)
        $session = $request->attributes->get('shopifySession');
        $shop = $request->attributes->get('shopifyShop');
        
        // Hoặc backward compatibility
        $shop = $request->input('shopify_shop');
        $session = $request->input('shopify_session');
        
        // Service tự động validate token
        $products = $shopifyService->getProducts($shop);
        
        return response()->json($products);
    }
    
    public function userAction(Request $request)
    {
        // Với online mode, có thể truy cập thông tin user
        $session = $request->attributes->get('shopifySession');
        
        if ($session->account_owner) {
            // Chỉ account owner mới được thực hiện
        }
        
        return response()->json([
            'user' => $session->user_first_name . ' ' . $session->user_last_name,
            'email' => $session->user_email
        ]);
    }
}
```

### 3. Kiểm tra thủ công

```php
$tokenValidationService = app(TokenValidationService::class);

// Kiểm tra session
$result = $tokenValidationService->validateSession('myshop.myshopify.com');
if ($result['valid']) {
    // Token hợp lệ
    $session = $result['session'];
} else {
    // Token không hợp lệ
    $reason = $result['reason'];
}

// Kiểm tra scopes
$hasWritePermission = $tokenValidationService->validateScopes(
    'myshop.myshopify.com',
    $accessToken,
    ['write_products']
);
```

## Xử lý Lỗi

### Token không hợp lệ

Khi token không hợp lệ, hệ thống sẽ:

1. **API Requests**: Trả về JSON với status 401
```json
{
    "error": "Invalid or expired token",
    "message": "Token không hợp lệ hoặc đã hết hạn",
    "reason": "Token không hợp lệ với Shopify",
    "shop": "myshop.myshopify.com",
    "auth_url": "https://myshop.myshopify.com/admin/oauth/authorize?..."
}
```

2. **Web Requests**: Redirect đến trang xác thực OAuth

### Thiếu permissions

Khi thiếu scopes yêu cầu:

```json
{
    "error": "Insufficient permissions",
    "message": "Ứng dụng không có đủ quyền để thực hiện hành động này",
    "required_scopes": ["write_products"]
}
```

## Cấu hình

Các settings trong `config/shopify.php`:

```php
return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES', 'read_products,write_products'),
    'redirect_uri' => env('SHOPIFY_REDIRECT_URI'),
    'host_name' => env('HOST'),
];
```

## Logging

Hệ thống ghi log chi tiết:

- `[TokenValidation]` - Các hoạt động validation
- `[ShopifyService]` - API calls và kết quả
- `[ValidateShopifyToken]` - Middleware processing

## Bảo mật

- Tất cả tokens được validate với Shopify API
- Sessions không hợp lệ tự động bị xóa
- HMAC verification cho OAuth callbacks
- Sanitization cho shop domains
- Rate limiting và error handling

## Performance

- Services được đăng ký singleton
- Caching validation results trong request lifecycle
- Batch processing cho multiple validations
- Minimal API calls với efficient endpoints
