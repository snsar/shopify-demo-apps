# Quote Button Theme Extension Guide

## Tổng quan

Quote Button là một theme extension block cho phép hiển thị nút "Request Quote" trên trang sản phẩm dựa trên cấu hình metafield.

## Metafield Configuration

Extension sử dụng metafield với cấu trúc sau:

- **Namespace**: `quote_snap`
- **Key**: `configuration`
- **Type**: `json`
- **Scope**: `shop` hoặc `product`

### Cấu trúc JSON Configuration

```json
{
  "displayRule": "all|specific",
  "position": "under-button|above-button|replace-button",
  "specificProducts": [product_id1, product_id2],
  "buttonLabel": "Request Quote",
  "alignment": "left|center|right",
  "fontSize": 15,
  "cornerRadius": 15,
  "textColor": {
    "hue": 0,
    "brightness": 1,
    "saturation": 0
  },
  "buttonColor": {
    "hue": 39,
    "brightness": 1,
    "saturation": 1
  },
  "isActive": true
}
```

### Giải thích các thuộc tính:

- **displayRule**: Quy tắc hiển thị
  - `all`: Hiển thị trên tất cả sản phẩm
  - `specific`: Chỉ hiển thị trên sản phẩm được chỉ định

- **position**: Vị trí hiển thị nút
  - `under-button`: Dưới nút Add to Cart
  - `above-button`: Trên nút Add to Cart  
  - `replace-button`: Thay thế nút Add to Cart

- **specificProducts**: Mảng ID sản phẩm (chỉ dùng khi displayRule = "specific")

- **buttonLabel**: Nhãn hiển thị trên nút

- **alignment**: Căn lề nút (left/center/right)

- **fontSize**: Kích thước font chữ (px)

- **cornerRadius**: Độ bo góc nút (px)

- **textColor/buttonColor**: Màu sắc theo định dạng HSB
  - hue: 0-360 (độ màu)
  - brightness: 0-1 (độ sáng)
  - saturation: 0-1 (độ bão hòa)

- **isActive**: Bật/tắt hiển thị nút

## Cách sử dụng

### 1. Cài đặt Metafield

Tạo metafield trong Shopify Admin:

```graphql
mutation {
  metafieldDefinitionCreate(definition: {
    name: "Quote Configuration"
    namespace: "quote_snap"
    key: "configuration"
    type: "json"
    ownerType: SHOP
  }) {
    createdDefinition {
      id
    }
    userErrors {
      field
      message
    }
  }
}
```

### 2. Cập nhật Configuration

Sử dụng GraphQL để cập nhật cấu hình:

```graphql
mutation {
  metafieldsSet(metafields: [
    {
      ownerId: "gid://shopify/Shop/YOUR_SHOP_ID"
      namespace: "quote_snap"
      key: "configuration"
      type: "json"
      value: "{\"displayRule\":\"all\",\"position\":\"under-button\",\"buttonLabel\":\"Request Quote\",\"alignment\":\"center\",\"fontSize\":15,\"cornerRadius\":15,\"textColor\":{\"hue\":0,\"brightness\":1,\"saturation\":0},\"buttonColor\":{\"hue\":39,\"brightness\":1,\"saturation\":1},\"isActive\":true}"
    }
  ]) {
    metafields {
      id
    }
    userErrors {
      field
      message
    }
  }
}
```

### 3. Thêm Block vào Theme

1. Mở Shopify Admin > Online Store > Themes
2. Chọn theme và click "Customize"
3. Vào trang Product template
4. Thêm block "Quote Button" vào vị trí mong muốn
5. Lưu thay đổi

## Xử lý sự kiện Quote Request

Extension tạo ra custom event `quoteRequested` khi user click nút:

```javascript
document.addEventListener('quoteRequested', function(event) {
  const productData = event.detail.product;
  const config = event.detail.config;
  
  // Xử lý logic quote request
  console.log('Quote requested:', productData);
});
```

## Tùy chỉnh CSS

Có thể tùy chỉnh thêm CSS cho nút quote:

```css
.quote-button-container {
  margin: 20px 0;
}

.quote-request-button {
  min-width: 200px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.quote-request-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
```

## Debug Mode

Bật debug mode trong block settings để xem console logs và troubleshoot các vấn đề.

## Lưu ý

- Extension chỉ hoạt động trên trang product (`/products/*`)
- Metafield có thể được set ở cấp shop (áp dụng toàn bộ) hoặc product (áp dụng riêng)
- Nếu cả shop và product đều có metafield, ưu tiên sử dụng product metafield
- Button sẽ không hiển thị nếu `isActive: false` hoặc không match `displayRule`
