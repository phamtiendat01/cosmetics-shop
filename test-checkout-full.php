<?php
/**
 * Comprehensive Test Script cho Checkout Flow
 * Test tất cả các trường hợp và tìm lỗi
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== COMPREHENSIVE CHECKOUT FLOW TEST ===\n\n";

// ✅ Authenticate user
$userId = 1;
$user = \App\Models\User::find($userId);
if (!$user) {
    echo "❌ User ID {$userId} không tồn tại!\n";
    exit(1);
}
\Illuminate\Support\Facades\Auth::login($user);
echo "✅ Đã đăng nhập với user: {$user->name} (ID: {$userId})\n\n";

$botAgent = app(\App\Services\Bot\BotAgent::class);
$errors = [];
$warnings = [];

// Helper function để test và log
function testStep($stepName, $message, $botAgent, $sessionId, $userId, &$errors, &$warnings) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📝 Step: {$stepName}\n";
    echo "User: '{$message}'\n";
    
    try {
        $result = $botAgent->process($message, $sessionId, $userId);
        
        // Check response
        if (empty($result['reply'])) {
            $errors[] = "{$stepName}: Response rỗng!";
            echo "❌ ERROR: Response rỗng!\n";
        } else {
            echo "Bot: " . substr($result['reply'], 0, 200) . "...\n";
        }
        
        // Check intent
        $intent = $result['intent'] ?? 'unknown';
        echo "Intent: {$intent}\n";
        
        // Check tools used
        $toolsUsed = $result['tools_used'] ?? [];
        echo "Tools used: " . implode(', ', $toolsUsed) . "\n";
        
        // Check checkout state
        $conversation = \App\Models\BotConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();
        if ($conversation) {
            $stateManager = app(\App\Services\Bot\CheckoutStateManager::class);
            $state = $stateManager->getState($conversation);
            echo "Checkout State: " . ($state ?? 'null') . "\n";
            
            if ($state && $state !== 'idle' && $state !== 'order_placed') {
                $data = $stateManager->getData($conversation);
                if (!empty($data)) {
                    echo "Checkout Data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
                }
            }
        }
        
        // Check for fallback response (warning)
        if (str_contains($result['reply'], 'Mình hiểu bạn đang tìm kiếm thông tin')) {
            $warnings[] = "{$stepName}: Bot trả về fallback response thay vì response đúng cho checkout flow!";
            echo "⚠️ WARNING: Bot trả về fallback response!\n";
        }
        
        // Check cart
        $cartItems = session('cart.items', []);
        $cartCount = count($cartItems);
        echo "Cart count: {$cartCount}\n";
        
        return $result;
    } catch (\Throwable $e) {
        $errors[] = "{$stepName}: Exception - " . $e->getMessage();
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "Trace: " . substr($e->getTraceAsString(), 0, 300) . "...\n";
        return null;
    }
}

// Test Case 1: Flow đầy đủ với coupon và shipping voucher
echo "\n" . str_repeat("#", 60) . "\n";
echo "TEST CASE 1: Flow đầy đủ (có coupon + shipping voucher)\n";
echo str_repeat("#", 60) . "\n";

$sessionId1 = 'test-full-' . time();
$result1 = testStep("1.1 Tìm sản phẩm", "serum cho da dầu", $botAgent, $sessionId1, $userId, $errors, $warnings);
sleep(1);

$result2 = testStep("1.2 Đặt hàng", "Tôi muốn đặt sản phẩm đầu tiên", $botAgent, $sessionId1, $userId, $errors, $warnings);
if (empty($result2['reply']) || str_contains($result2['reply'], 'Mình hiểu bạn đang tìm kiếm')) {
    $errors[] = "1.2: Bot không trả về message đúng cho add_to_cart!";
}
sleep(1);

$result3 = testStep("1.3 Skip coupon", "Không", $botAgent, $sessionId1, $userId, $errors, $warnings);
if (empty($result3['reply']) || str_contains($result3['reply'], 'Mình hiểu bạn đang tìm kiếm')) {
    $errors[] = "1.3: Bot không trả về message đúng cho checkout_skip_coupon!";
}
if (!str_contains($result3['reply'], 'địa chỉ')) {
    $errors[] = "1.3: Bot không hỏi về địa chỉ sau khi skip coupon!";
}
sleep(1);

$result4 = testStep("1.4 Chọn địa chỉ", "Địa chỉ số 1", $botAgent, $sessionId1, $userId, $errors, $warnings);
if (empty($result4['reply']) || str_contains($result4['reply'], 'Mình hiểu bạn đang tìm kiếm')) {
    $errors[] = "1.4: Bot không trả về message đúng cho checkout_select_address!";
}
sleep(1);

$result5 = testStep("1.5 Skip shipping voucher", "Không", $botAgent, $sessionId1, $userId, $errors, $warnings);
if (empty($result5['reply']) || str_contains($result5['reply'], 'Mình hiểu bạn đang tìm kiếm')) {
    $errors[] = "1.5: Bot không trả về message đúng cho checkout_skip_shipping_voucher!";
}
if (!str_contains($result5['reply'], 'TÓM TẮT ĐƠN HÀNG') && !str_contains($result5['reply'], 'thanh toán')) {
    $errors[] = "1.5: Bot không tóm tắt đơn hàng và hỏi payment!";
}
sleep(1);

$result6 = testStep("1.6 Chọn payment", "COD", $botAgent, $sessionId1, $userId, $errors, $warnings);
if (empty($result6['reply']) || str_contains($result6['reply'], 'Mình hiểu bạn đang tìm kiếm')) {
    $errors[] = "1.6: Bot không trả về message đúng cho checkout_select_payment!";
}
if (!str_contains($result6['reply'], 'thành công') && !str_contains($result6['reply'], 'Đặt hàng')) {
    $warnings[] = "1.6: Bot có thể chưa đặt hàng thành công!";
}

// Check order
$order1 = \App\Models\Order::where('user_id', $userId)
    ->orderByDesc('created_at')
    ->first();
if ($order1) {
    echo "\n✅ Order created: {$order1->code}\n";
    echo "   Total: " . number_format($order1->grand_total, 0, ',', '.') . "₫\n";
} else {
    $errors[] = "1.6: Order không được tạo!";
}

// Test Case 2: Flow không có coupon và shipping voucher
echo "\n\n" . str_repeat("#", 60) . "\n";
echo "TEST CASE 2: Flow không có coupon và shipping voucher\n";
echo str_repeat("#", 60) . "\n";

$sessionId2 = 'test-skip-' . time();
testStep("2.1 Tìm sản phẩm", "kem dưỡng ẩm", $botAgent, $sessionId2, $userId, $errors, $warnings);
sleep(1);
testStep("2.2 Đặt hàng", "Tôi muốn đặt sản phẩm đầu tiên", $botAgent, $sessionId2, $userId, $errors, $warnings);
sleep(1);
testStep("2.3 Skip coupon", "Không", $botAgent, $sessionId2, $userId, $errors, $warnings);
sleep(1);
testStep("2.4 Chọn địa chỉ", "Địa chỉ số 1", $botAgent, $sessionId2, $userId, $errors, $warnings);
sleep(1);
testStep("2.5 Skip shipping voucher", "Không", $botAgent, $sessionId2, $userId, $errors, $warnings);
sleep(1);
testStep("2.6 Chọn payment", "COD", $botAgent, $sessionId2, $userId, $errors, $warnings);

// Test Case 3: Apply coupon
echo "\n\n" . str_repeat("#", 60) . "\n";
echo "TEST CASE 3: Apply coupon\n";
echo str_repeat("#", 60) . "\n";

$sessionId3 = 'test-coupon-' . time();
testStep("3.1 Tìm sản phẩm", "serum", $botAgent, $sessionId3, $userId, $errors, $warnings);
sleep(1);
testStep("3.2 Đặt hàng", "Tôi muốn đặt sản phẩm đầu tiên", $botAgent, $sessionId3, $userId, $errors, $warnings);
sleep(1);
testStep("3.3 Apply coupon", "Số 1", $botAgent, $sessionId3, $userId, $errors, $warnings);
sleep(1);
testStep("3.4 Chọn địa chỉ", "Địa chỉ số 1", $botAgent, $sessionId3, $userId, $errors, $warnings);
sleep(1);
testStep("3.5 Skip shipping voucher", "Không", $botAgent, $sessionId3, $userId, $errors, $warnings);
sleep(1);
testStep("3.6 Chọn payment", "COD", $botAgent, $sessionId3, $userId, $errors, $warnings);

// Test Case 4: User chưa đăng nhập
echo "\n\n" . str_repeat("#", 60) . "\n";
echo "TEST CASE 4: User chưa đăng nhập\n";
echo str_repeat("#", 60) . "\n";

\Illuminate\Support\Facades\Auth::logout();
$sessionId4 = 'test-no-auth-' . time();
$result4 = testStep("4.1 Đặt hàng (chưa đăng nhập)", "Tôi muốn đặt sản phẩm đầu tiên", $botAgent, $sessionId4, null, $errors, $warnings);
if (!str_contains($result4['reply'] ?? '', 'đăng nhập')) {
    $errors[] = "4.1: Bot không yêu cầu đăng nhập khi user chưa đăng nhập!";
}

// Re-login
\Illuminate\Support\Facades\Auth::login($user);

// Summary
echo "\n\n" . str_repeat("=", 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Total Errors: " . count($errors) . "\n";
echo "Total Warnings: " . count($warnings) . "\n\n";

if (!empty($errors)) {
    echo "❌ ERRORS:\n";
    foreach ($errors as $i => $error) {
        echo ($i + 1) . ". {$error}\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️ WARNINGS:\n";
    foreach ($warnings as $i => $warning) {
        echo ($i + 1) . ". {$warning}\n";
    }
}

if (empty($errors) && empty($warnings)) {
    echo "✅ Tất cả test cases đều PASS!\n";
} else {
    echo "\n❌ Có lỗi cần fix!\n";
}

echo "\nXem logs: tail -f storage/logs/laravel.log\n";
