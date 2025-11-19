# 🧪 Hướng Dẫn Test Flow Đặt Hàng Qua Bot Chat

## 📋 Chuẩn bị

1. **Đảm bảo đã đăng nhập** vào website
2. **Có ít nhất 1 sản phẩm** trong database (active)
3. **Có ít nhất 1 địa chỉ** trong sổ địa chỉ (hoặc sẽ nhập mới)
4. **Có mã giảm giá** (nếu muốn test áp mã) - không bắt buộc
5. **Có mã vận chuyển** (nếu muốn test áp mã ship) - không bắt buộc

---

## 🚀 Cách Test

### **Cách 1: Test qua UI (Khuyến nghị)**

1. Mở website và đăng nhập
2. Mở chatbot widget (góc dưới bên phải)
3. Test theo flow dưới đây

### **Cách 2: Test qua API**

Sử dụng Postman hoặc curl để test:

```bash
# 1. Tìm sản phẩm
POST /bot/chat
{
  "message": "serum cho da dầu"
}

# 2. Đặt hàng
POST /bot/chat
{
  "message": "Tôi muốn đặt sản phẩm đầu tiên"
}

# 3. Áp mã (nếu có)
POST /bot/chat
{
  "message": "Mã MÃ123"
}

# 4. Chọn địa chỉ
POST /bot/chat
{
  "message": "Địa chỉ số 1"
}

# 5. Áp mã ship (nếu có)
POST /bot/chat
{
  "message": "Mã SHIP50"
}

# 6. Chọn payment
POST /bot/chat
{
  "message": "COD"
}
```

---

## 📝 Test Cases

### **Test Case 1: Flow đầy đủ (có mã giảm giá + mã ship)**

```
1. User: "serum cho da dầu"
   → Bot: [List sản phẩm]

2. User: "Tôi muốn đặt sản phẩm đầu tiên"
   → Bot: "Đã thêm **[Serum A]** vào giỏ hàng! 
          Bạn có muốn áp mã giảm giá không?"

3. User: "Có"
   → Bot: "Bạn có các mã giảm giá sau:
          1. **MÃ123** - Giảm 10%
          2. **MÃ456** - Trừ 30.000đ
          Bạn muốn áp mã nào?"

4. User: "Mã MÃ123"
   → Bot: "Đã áp dụng mã **MÃ123** thành công! Giảm 50.000₫
          Bạn muốn giao hàng đến địa chỉ nào?"

5. User: "Địa chỉ số 1"
   → Bot: "✅ Đã chọn địa chỉ: **[Địa chỉ]**
          Phí vận chuyển: **30.000₫** (khoảng cách: 5.2km)
          Bạn có muốn áp mã vận chuyển không?"

6. User: "Có"
   → Bot: "Bạn có các mã vận chuyển sau:
          1. **SHIP50** - Giảm 50%
          Bạn muốn áp mã nào?"

7. User: "Mã SHIP50"
   → Bot: "Đã áp dụng mã vận chuyển **SHIP50** thành công! Giảm 15.000₫ phí ship.
          📋 **TÓM TẮT ĐƠN HÀNG:**
          Tổng sản phẩm: 500.000₫
          Giảm giá: -50.000₫
          Phí vận chuyển: 30.000₫
          Giảm phí ship: -15.000₫
          ─────────────────────
          **TỔNG CỘNG: 465.000₫**
          Bạn muốn thanh toán bằng phương thức nào?"

8. User: "COD"
   → Bot: "🎉 **Đặt hàng thành công!**
          Mã đơn hàng: **ORD123456**
          Phương thức thanh toán: **COD**"
```

---

### **Test Case 2: Flow không có mã (skip tất cả)**

```
1. User: "Tôi muốn đặt sản phẩm đầu tiên"
   → Bot: "Đã thêm **[Serum A]** vào giỏ hàng! 
          Bạn có muốn áp mã giảm giá không?"

2. User: "Không"
   → Bot: "Đã bỏ qua bước mã giảm giá.
          Bạn muốn giao hàng đến địa chỉ nào?"

3. User: "Địa chỉ số 1"
   → Bot: "✅ Đã chọn địa chỉ: **[Địa chỉ]**
          Phí vận chuyển: **30.000₫**
          Bạn có muốn áp mã vận chuyển không?"

4. User: "Không"
   → Bot: "Đã bỏ qua bước mã vận chuyển.
          📋 **TÓM TẮT ĐƠN HÀNG:**
          Tổng sản phẩm: 500.000₫
          Phí vận chuyển: 30.000₫
          ─────────────────────
          **TỔNG CỘNG: 530.000₫**
          Bạn muốn thanh toán bằng phương thức nào?"

5. User: "COD"
   → Bot: "🎉 **Đặt hàng thành công!**"
```

---

### **Test Case 3: User chưa đăng nhập**

```
1. User: "Tôi muốn đặt sản phẩm đầu tiên"
   → Bot: "Bạn cần đăng nhập để đặt hàng. 
          Vui lòng đăng nhập tại: [link đăng nhập]"
```

---

### **Test Case 4: User không có địa chỉ**

```
1. User: "Tôi muốn đặt sản phẩm đầu tiên"
   → Bot: "Đã thêm **[Serum A]** vào giỏ hàng! 
          Bạn có muốn áp mã giảm giá không?"

2. User: "Không"
   → Bot: "Bạn muốn giao hàng đến địa chỉ nào?
          Bạn chưa có địa chỉ nào. Mình sẽ hướng dẫn bạn thêm địa chỉ.
          Vui lòng cung cấp:
          - Tên người nhận
          - Số điện thoại
          - Địa chỉ chi tiết
          - Quận/Huyện
          - Tỉnh/Thành phố"
```

---

## 🔍 Kiểm tra Logs

Nếu có lỗi, check logs:

```bash
# Xem logs real-time
tail -f storage/logs/laravel.log

# Hoặc trên Windows (PowerShell)
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

**Các log quan trọng:**
- `BotAgent: toolsResult after execute` - Xem tools đã chạy
- `BotAgent: Calling LLMService::generate` - Xem LLM có được gọi
- `BotAgent: handleCheckoutFlow` - Xem state transitions
- `AddToCartTool failed` - Lỗi khi add to cart
- `ApplyCouponTool failed` - Lỗi khi áp mã
- `PlaceOrderTool failed` - Lỗi khi đặt hàng

---

## 🗄️ Kiểm tra Database

### **Check conversation state:**
```sql
SELECT 
    id, 
    user_id, 
    status, 
    JSON_EXTRACT(metadata, '$.checkout_state') as checkout_state,
    JSON_EXTRACT(metadata, '$.checkout_data') as checkout_data,
    updated_at
FROM bot_conversations 
WHERE status = 'active' 
ORDER BY updated_at DESC 
LIMIT 5;
```

### **Check cart session:**
```php
// Trong tinker hoặc controller
dd(session('cart.items'));
dd(session('applied_coupon'));
dd(session('applied_ship'));
```

### **Check order đã tạo:**
```sql
SELECT * FROM orders 
WHERE user_id = [user_id] 
ORDER BY created_at DESC 
LIMIT 5;
```

---

## ✅ Checklist Test

- [ ] **Test 1**: Flow đầy đủ (có mã giảm giá + mã ship)
- [ ] **Test 2**: Flow không có mã (skip tất cả)
- [ ] **Test 3**: User chưa đăng nhập
- [ ] **Test 4**: User không có địa chỉ
- [ ] **Test 5**: User không có coupon (bot phải nói "chưa có mã")
- [ ] **Test 6**: User không có shipping voucher (bot phải nói "chưa có mã")
- [ ] **Test 7**: Test với ví Cosme (WALLET payment)
- [ ] **Test 8**: Test với các payment methods khác (COD, VietQR, MoMo, VNPay)
- [ ] **Test 9**: Test với địa chỉ có lat/lng (tính ship chính xác)
- [ ] **Test 10**: Test với địa chỉ không có lat/lng (fallback)

---

## 🐛 Troubleshooting

### **Lỗi: "Tool not found"**
→ Check `app/Services/Bot/ToolExecutor.php` đã có tool trong `$hardcodedHandlers`

### **Lỗi: "CheckoutStateManager not found"**
→ Check `app/Services/Bot/BotAgent.php` đã inject `CheckoutStateManager` trong constructor

### **Lỗi: "State không chuyển"**
→ Check logs `BotAgent: handleCheckoutFlow` để xem state transitions
→ Check `bot_conversations.metadata` trong database

### **Lỗi: "Cart trống sau khi add"**
→ Check session có được lưu không
→ Check `CartController::addToCart()` có hoạt động không

### **Lỗi: "Order không được tạo"**
→ Check `PlaceOrderTool` có được gọi không
→ Check `CheckoutController::place()` có hoạt động không
→ Check validation errors

---

## 🎯 Quick Test Script

Tạo file `test-checkout.php` trong root:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test với user ID = 1
$userId = 1;
$sessionId = 'test-session-' . time();

$botAgent = app(\App\Services\Bot\BotAgent::class);

// Step 1: Tìm sản phẩm
echo "=== Step 1: Tìm sản phẩm ===\n";
$result1 = $botAgent->process("serum cho da dầu", $sessionId, $userId);
echo "Reply: " . $result1['reply'] . "\n\n";

// Step 2: Đặt hàng
echo "=== Step 2: Đặt hàng ===\n";
$result2 = $botAgent->process("Tôi muốn đặt sản phẩm đầu tiên", $sessionId, $userId);
echo "Reply: " . $result2['reply'] . "\n\n";

// Step 3: Áp mã (nếu có)
echo "=== Step 3: Áp mã ===\n";
$result3 = $botAgent->process("Không", $sessionId, $userId);
echo "Reply: " . $result3['reply'] . "\n\n";

// Step 4: Chọn địa chỉ
echo "=== Step 4: Chọn địa chỉ ===\n";
$result4 = $botAgent->process("Địa chỉ số 1", $sessionId, $userId);
echo "Reply: " . $result4['reply'] . "\n\n";

// Step 5: Áp mã ship (nếu có)
echo "=== Step 5: Áp mã ship ===\n";
$result5 = $botAgent->process("Không", $sessionId, $userId);
echo "Reply: " . $result5['reply'] . "\n\n";

// Step 6: Chọn payment
echo "=== Step 6: Chọn payment ===\n";
$result6 = $botAgent->process("COD", $sessionId, $userId);
echo "Reply: " . $result6['reply'] . "\n\n";

echo "=== Test hoàn tất ===\n";
```

Chạy:
```bash
php test-checkout.php
```

---

## 📞 Support

Nếu gặp lỗi, check:
1. Logs: `storage/logs/laravel.log`
2. Database: `bot_conversations.metadata`
3. Session: `session('cart.items')`, `session('applied_coupon')`, `session('applied_ship')`

Chúc bạn test thành công! 🚀

