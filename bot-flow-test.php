<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\Bot\BotAgent;
use Illuminate\Support\Facades\Auth;

$botAgent = app(BotAgent::class);
$user = User::findOrFail(1); // Phạm Tiến Đạt
Auth::login($user);

$scenarios = [
    [
        'key' => 'flow_coupon_ship_vietqr',
        'description' => 'Áp mã giảm giá + mã vận chuyển + thanh toán VietQR',
        'messages' => [
            'Mình muốn tìm serum chống lão hoá cao cấp khoảng 2 triệu cho da hỗn hợp',
            'Chốt sản phẩm số 1',
            'Mình muốn xem mã giảm giá',
            'Áp mã số 1',
            'Mình chọn địa chỉ số 1',
            'Mình muốn áp mã vận chuyển SHIP30K',
            'Tôi chọn thanh toán VietQR',
        ],
    ],
    [
        'key' => 'flow_skip_coupon_cod',
        'description' => 'Bỏ qua mã giảm giá, bỏ qua mã vận chuyển, thanh toán COD',
        'messages' => [
            'Mình muốn tìm toner dịu nhẹ dưới 500k cho da nhạy cảm',
            'Chốt sản phẩm số 1',
            'Không áp mã giảm giá',
            'Mình chọn địa chỉ số 1',
            'Không cần mã vận chuyển',
            'Tôi muốn thanh toán khi nhận hàng',
        ],
    ],
    [
        'key' => 'flow_coupon_code_momo',
        'description' => 'Áp mã giảm giá bằng code + bỏ qua mã ship + thanh toán MoMo',
        'messages' => [
            'Tôi cần kem dưỡng ẩm cao cấp khoảng 1 triệu',
            'Đặt sản phẩm số 1',
            'Áp mã SPIN20K',
            'Mình chọn địa chỉ số 1',
            'Không áp mã vận chuyển',
            'Chọn MoMo để thanh toán',
        ],
    ],
];

$results = [];
$cartController = app(App\Http\Controllers\CartController::class);

foreach ($scenarios as $scenario) {
    $sessionId = 'bot-checkout-' . $scenario['key'];
    echo str_repeat('=', 80) . PHP_EOL;
    echo "Scenario: {$scenario['description']}" . PHP_EOL;
    echo "Session: {$sessionId}" . PHP_EOL;

    // Reset conversation & session
    $session = session();
    $session->setId($sessionId);
    $session->start();
    $session->flush();
    $session->regenerate(true);
    $session->setId($sessionId);
    $session->start();

    $cartController->clear();
    session(['cart.items' => []]);
    session(['applied_coupon' => null]);
    session(['applied_ship' => null]);
    session()->save();

    $botAgent->reset($sessionId, $user->id);

    $scenarioLogs = [];
    $success = true;

    foreach ($scenario['messages'] as $idx => $message) {
        $response = $botAgent->process($message, $sessionId, $user->id);
        session()->save();

        $intent = $response['intent'] ?? 'unknown';
        $reply = trim($response['reply'] ?? '');
        $meta = $response['meta'] ?? [];
        $redirectUrl = $response['redirect_url'] ?? ($meta['redirect_url'] ?? null);

        $scenarioLogs[] = [
            'step' => $idx + 1,
            'message' => $message,
            'intent' => $intent,
            'reply_preview' => mb_substr($reply, 0, 200),
            'redirect_url' => $redirectUrl,
            'tools' => $response['tools_used'] ?? [],
        ];

        echo "\n[Step " . ($idx + 1) . "] User: {$message}" . PHP_EOL;
        echo "Bot ({$intent}): " . ($reply ? mb_substr($reply, 0, 200) : '[EMPTY]') . PHP_EOL;
        if ($redirectUrl) {
            echo "Redirect URL: {$redirectUrl}" . PHP_EOL;
        }

        if ($reply === '') {
            $success = false;
            echo "!!! Empty reply detected" . PHP_EOL;
            break;
        }
    }

    $results[] = [
        'scenario' => $scenario['description'],
        'session_id' => $sessionId,
        'success' => $success,
        'logs' => $scenarioLogs,
    ];

    // Reset after scenario
    $botAgent->reset($sessionId, $user->id);
    session()->flush();
    session()->save();
}

file_put_contents('storage/logs/bot-flow-test.log', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo PHP_EOL . 'Summary:' . PHP_EOL;
foreach ($results as $result) {
    echo '- ' . $result['scenario'] . ': ' . ($result['success'] ? 'OK' : 'FAILED') . PHP_EOL;
}
