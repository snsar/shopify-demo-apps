
# 🚀 Shopify App Installation Flow

Tài liệu này mô tả chi tiết **luồng cài đặt và xác thực (OAuth) cho Shopify App**.  
Áp dụng cho ứng dụng custom app hoặc public app kết nối với Shopify Store.

---

## 📌 Luồng cài đặt OAuth

1. **🔗 Build link Authorize**  
   - Khi merchant bấm "Install app", bạn cần redirect tới Shopify Authorization URL:  
     ```
     https://{shop}.myshopify.com/admin/oauth/authorize
     ```
   - Các tham số cần có:
     - `client_id` → API Key của app
     - `scope` → danh sách quyền (ví dụ: read_products, write_orders)
     - `redirect_uri` → URL callback sau khi cài đặt
     - `state` → chuỗi random để chống CSRF

   👉 Ví dụ:
````

https\://{shop}.myshopify.com/admin/oauth/authorize
?client\_id={API\_KEY}
\&scope=read\_products,write\_orders
\&redirect\_uri=[https://your-app.com/auth/callback](https://your-app.com/auth/callback)
\&state={nonce}

```

2. **📥 Shopify callback về `redirect_uri`**  
- Sau khi merchant chấp nhận cài app, Shopify sẽ redirect về `redirect_uri` kèm query:
  - `code` → authorization code
  - `hmac` → chữ ký để verify
  - `state` → chuỗi random (bạn kiểm tra trùng với bước 1)
  - `shop` → shop domain

👉 Ví dụ:
```

[https://your-app.com/auth/callback?code=123456\&hmac=abcd1234\&shop=example.myshopify.com\&state=xyz](https://your-app.com/auth/callback?code=123456&hmac=abcd1234&shop=example.myshopify.com&state=xyz)

````

3. **🔑 Đổi `code` lấy Access Token**  
- Backend app sẽ gọi tới:
  ```
  POST https://{shop}.myshopify.com/admin/oauth/access_token
  ```
- Payload:
  ```json
  {
    "client_id": "{API_KEY}",
    "client_secret": "{API_SECRET}",
    "code": "{code_from_callback}"
  }
  ```

- Shopify trả về JSON:
  ```json
  {
    "access_token": "{ACCESS_TOKEN}",
    "scope": "read_products,write_orders"
  }
  ```

👉 Access Token này cần lưu vào DB để dùng cho các API sau này.

4. **📡 Dùng Access Token để gọi Shopify Admin API**  
- Sau khi có token, bạn có thể gọi bất kỳ API nào, ví dụ lấy sản phẩm:
  ```
  GET https://{shop}.myshopify.com/admin/api/2025-07/products.json
  ```
- Thêm Header:
  ```
  X-Shopify-Access-Token: {ACCESS_TOKEN}
  ```

5. **🔄 Refresh Token (nếu cần)**  
- Với app **offline** → token không hết hạn.  
- Với app **online** → token hết hạn sau 1 ngày → bạn cần lấy lại theo OAuth flow.  

---

## 📂 Cấu trúc luồng trong code (gợi ý)

````

/routes/web.php
/auth       → build link authorize
/auth/callback → xử lý callback, verify hmac, exchange token
/dashboard  → trang chính sau khi cài đặt thành công

```

---

## ✅ Checklist bảo mật
- [ ] Luôn verify `hmac` từ Shopify gửi về.  
- [ ] Lưu `state` để tránh CSRF.  
- [ ] Không log `access_token` ra console.  
- [ ] Sử dụng HTTPS cho `redirect_uri`.  

---

## 📚 Tham khảo
- [Shopify OAuth Guide](https://shopify.dev/docs/apps/auth/oauth)
- [Admin API Reference](https://shopify.dev/docs/api/admin-rest)
```

