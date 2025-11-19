<?php
/**
 * Quick Test Script cho Checkout Flow
 * Chạy: php test-checkout.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST CHECKOUT FLOW ===\n\n";

// ✅ Authenticate user để AddToCartTool có thể hoạt động
$userId = 1;
$user = \App\Models\User::find($userId);
if (!$user) {
    echo "❌ User ID {$userId} không tồn tại!\n";
    exit(1);
}
\Illuminate\Support\Facades\Auth::login($user);
echo "✅ Đã đăng nhập với user: {$user->name} (ID: {$userId})\n\n";

$sessionId = 'test-session-' . time();

$botAgent = app(\App\Services\Bot\BotAgent::class);

// Step 1: Tìm sản phẩm
echo "📦 Step 1: Tìm sản phẩm\n";
echo "User: 'serum cho da dầu'\n";
$result1 = $botAgent->process("serum cho da dầu", $sessionId, $userId);
echo "Bot: " . substr($result1['reply'], 0, 200) . "...\n";
echo "Intent: " . ($result1['intent'] ?? 'N/A') . "\n";
echo "Products: " . count($result1['products'] ?? []) . "\n\n";

// Đợi một chút
sleep(1);

// Step 2: Đặt hàng
echo "🛒 Step 2: Đặt hàng\n";
echo "User: 'Tôi muốn đặt sản phẩm đầu tiên'\n";
$result2 = $botAgent->process("Tôi muốn đặt sản phẩm đầu tiên", $sessionId, $userId);
echo "Bot: " . $result2['reply'] . "\n";
echo "Intent: " . ($result2['intent'] ?? 'N/A') . "\n";
echo "Tools used: " . implode(', ', $result2['tools_used'] ?? []) . "\n\n";

// Check checkout state
$conversation = \App\Models\BotConversation::where('session_id', $sessionId)
    ->where('status', 'active')
    ->first();
if ($conversation) {
    $stateManager = app(\App\Services\Bot\CheckoutStateManager::class);
    $state = $stateManager->getState($conversation);
    echo "✅ Checkout State: " . ($state ?? 'null') . "\n\n";
}

sleep(1);

// Step 3: Áp mã (hoặc skip)
echo "🎫 Step 3: Áp mã giảm giá\n";
echo "User: 'Không'\n";
$result3 = $botAgent->process("Không", $sessionId, $userId);
echo "Bot: " . substr($result3['reply'], 0, 200) . "...\n";
echo "Intent: " . ($result3['intent'] ?? 'N/A') . "\n\n";

sleep(1);

// Step 4: Chọn địa chỉ
echo "📍 Step 4: Chọn địa chỉ\n";
echo "User: 'Địa chỉ số 1'\n";
$result4 = $botAgent->process("Địa chỉ số 1", $sessionId, $userId);
echo "Bot: " . substr($result4['reply'], 0, 200) . "...\n";
echo "Intent: " . ($result4['intent'] ?? 'N/A') . "\n\n";

sleep(1);

// Step 5: Áp mã ship (hoặc skip)
echo "🚚 Step 5: Áp mã vận chuyển\n";
echo "User: 'Không'\n";
$result5 = $botAgent->process("Không", $sessionId, $userId);
echo "Bot: " . substr($result5['reply'], 0, 200) . "...\n";
echo "Intent: " . ($result5['intent'] ?? 'N/A') . "\n\n";

sleep(1);

// Step 6: Chọn payment
echo "💳 Step 6: Chọn phương thức thanh toán\n";
echo "User: 'COD'\n";
$result6 = $botAgent->process("COD", $sessionId, $userId);
echo "Bot: " . substr($result6['reply'], 0, 300) . "...\n";
echo "Intent: " . ($result6['intent'] ?? 'N/A') . "\n\n";

// Check final state
if ($conversation) {
    $conversation->refresh();
    $state = $stateManager->getState($conversation);
    echo "✅ Final Checkout State: " . ($state ?? 'null') . "\n";

    // Check order
    $order = \App\Models\Order::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->first();
    if ($order) {
        echo "✅ Order created: " . $order->code . "\n";
        echo "   Total: " . number_format($order->grand_total, 0, ',', '.') . "₫\n";
        echo "   Payment: " . $order->payment_method . "\n";
    }
}

echo "\n=== TEST HOÀN TẤT ===\n";
echo "Xem logs: tail -f storage/logs/laravel.log\n";

