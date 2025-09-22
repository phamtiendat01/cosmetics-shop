<?php
// app/Http/Controllers/BotController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\GeminiClient;
use App\Services\GeminiTools;
use App\Services\BotTools;
use App\Services\ConversationMemory;
use App\Services\BotLogger;

class BotController extends Controller
{
    public function chat(Request $r)
    {
        $t0  = BotLogger::start();
        $msg = trim((string) $r->input('message', ''));
        $chips = ['Tư vấn chọn mỹ phẩm', 'Phí ship', 'Chính sách đổi trả', 'Tra cứu đơn', '/reset'];

        // Reset hội thoại
        if (Str::startsWith($msg, '/reset')) {
            ConversationMemory::reset();
            $reply = 'Đã làm mới hội thoại ✨ Bạn cần mình tư vấn gì nè? (vd: da dầu mụn, ngân sách 300-500k)';
            BotLogger::save($msg, $reply, ['t0' => $t0, 'handled_by' => 'reset', 'intent' => 'none']);
            return response()->json(['reply' => $reply, 'suggestions' => $chips, 'products' => []]);
        }

        // MEMORY
        $mem = ConversationMemory::load();
        ConversationMemory::extractUserSignals($msg, $mem);
        $userForModel = ConversationMemory::applyCorefHint($msg, $mem);
        $lower = Str::lower($msg);

        /* ===== GUARD: greeting & toxic (không gọi search/resolve) ===== */
        foreach (['chào', 'xin chào', 'hello', 'hi', 'hey', 'alo'] as $g) {
            if (Str::startsWith($lower, $g)) {
                $reply = "Chào bạn 👋 Mình là CosmeBot. Bạn cần tư vấn loại da, ngân sách hay tên sản phẩm nào nè?";
                BotLogger::save($msg, $reply, ['t0' => $t0, 'handled_by' => 'greet', 'intent' => 'none']);
                return response()->json(['reply' => $reply, 'products' => [], 'suggestions' => ['Tư vấn chọn mỹ phẩm', 'Tra cứu đơn', '/reset']]);
            }
        }
        if (Str::contains($lower, ['ngu', 'óc', 'dm', 'đm', 'vl', 'cc', 'cút', 'vkl', 'lol'])) {
            $reply = "Mình ở đây để giúp bạn mua sắm cho vui vẻ nè 😊 Bạn cho mình biết bạn đang cần tư vấn gì: loại da, ngân sách hay tên sản phẩm nhé?";
            BotLogger::save($msg, $reply, ['t0' => $t0, 'handled_by' => 'guard', 'intent' => 'none']);
            return response()->json(['reply' => $reply, 'products' => [], 'suggestions' => ['Tư vấn chọn mỹ phẩm', '/reset']]);
        }
        /* ===== FAST PATH: đắt nhất / rẻ nhất (có thể kèm brand/category) ===== */
        if (
            Str::contains($lower, ['đắt nhất', 'cao nhất', 'giá cao nhất', 'mắc nhất', 'max price'])
            || Str::contains($lower, ['rẻ nhất', 'thấp nhất', 'giá thấp nhất', 'min price', 'bình dân nhất'])
        ) {

            $isCheapest = Str::contains($lower, ['rẻ nhất', 'thấp nhất', 'giá thấp nhất', 'min price', 'bình dân nhất']);
            [$cat, $brand] = \App\Services\BotTools::guessFilters($msg);

            $cards = \App\Services\BotTools::topByPrice($isCheapest ? 'asc' : 'desc', $cat, $brand, 6);

            $where = $brand ? " của **$brand**" : '';
            if ($cat) $where .= ($where ? ' trong' : ' trong') . " danh mục **$cat**";

            $title = $isCheapest ? "Các sản phẩm rẻ nhất$where nè:" : "Các sản phẩm đắt nhất$where nè:";
            $reply = $cards ? $title : "Mình chưa tìm ra danh sách phù hợp. Bạn thử chỉ rõ brand/danh mục giúp mình nhé!";

            \App\Services\BotLogger::save($msg, $reply, [
                't0' => $t0,
                'handled_by' => 'top_price',
                'intent' => $isCheapest ? 'cheapest' : 'most_expensive',
                'product_count' => count($cards)
            ]);

            \App\Services\ConversationMemory::pushTurn($mem, 'user', $msg);
            \App\Services\ConversationMemory::pushTurn($mem, 'assistant', $reply);
            \App\Services\ConversationMemory::save($mem);

            return response()->json([
                'reply' => $reply,
                'products' => $cards,
                'suggestions' => $isCheapest
                    ? ['Cho mình loại cao cấp hơn', 'Lọc theo brand', '/reset']
                    : ['Cho mình loại rẻ hơn', 'Lọc theo brand', '/reset']
            ]);
        }


        /* ===== FAST PATH A: “cho vài sản phẩm …” ===== */
        if (Str::contains($lower, [
            'mấy sản phẩm',
            'vài sản phẩm',
            'sản phẩm nào cũng được',
            'gợi ý sản phẩm',
            'random',
            'xem thử sản phẩm',
            'sản phẩm của shop',
            'giới thiệu sản phẩm',
            'nhanh lên'
        ])) {
            $cards = BotTools::pickProducts(8);
            $text = $cards
                ? 'Mình chọn nhanh vài món đang hot/còn hàng để bạn tham khảo nè ✨'
                : 'Hiện mình chưa lấy được danh sách. Bạn nói ngân sách/loại da để mình lọc kỹ hơn nhé!';
            BotLogger::save($msg, $text, ['t0' => $t0, 'handled_by' => 'pick', 'intent' => 'generic', 'product_count' => count($cards)]);
            ConversationMemory::pushTurn($mem, 'user', $msg);
            ConversationMemory::pushTurn($mem, 'assistant', $text);
            ConversationMemory::save($mem);
            return response()->json(['reply' => $text, 'products' => $cards, 'suggestions' => $chips]);
        }

        /* ===== FAST PATH B: “còn hàng không” ===== */
        if (Str::contains($lower, ['còn không', 'còn ko', 'còn k', 'còn hàng', 'hết hàng', 'in stock', 'available'])) {
            $hit = BotTools::resolveProduct($msg);
            if (($hit['found'] ?? false)) {
                $av = BotTools::checkAvailability($hit['slug']);
                if (($av['found'] ?? false)) {
                    $status = $av['status'] ?? 'unknown';
                    $txt = match ($status) {
                        'in_stock'     => "Có nè 💖 **{$av['name']}** đang còn hàng. Muốn mình gợi ý dung tích/phối routine không?",
                        'out_of_stock' => "Tiếc quá 😣 **{$av['name']}** hiện hết hàng. Mình gợi ý sản phẩm tương tự nhé?",
                        default        => "Mình chưa đọc được tồn kho realtime của **{$av['name']}**. Bạn mở trang sản phẩm để xem lựa chọn sẵn có nha!",
                    };
                    $info = BotTools::getProductInfo($hit['slug']);
                    BotLogger::save($msg, $txt, [
                        't0' => $t0,
                        'handled_by' => 'fast_stock',
                        'intent' => 'availability',
                        'matched_slug' => $info['slug'] ?? null,
                        'product_count' => $info['found'] ? 1 : 0
                    ]);
                    return response()->json([
                        'reply' => $txt,
                        'products' => $info['found'] ? [[
                            'url' => url('/products/' . $info['slug']),
                            'img' => $info['img'] ?: asset('images/placeholder.png'),
                            'name' => $info['name'],
                            'price' => number_format($info['price_min']) . '₫',
                            'compare' => null,
                            'discount' => null
                        ]] : [],
                        'suggestions' => ['Gợi ý thay thế', 'So sánh với món khác', '/reset'],
                    ]);
                }
            }
        }

        /* ===== FAST PATH C: nhắc tên sản phẩm rõ ràng ===== */
        $hit = BotTools::resolveProduct($msg);
        $normLen = mb_strlen(Str::slug($msg));
        if (($hit['found'] ?? false) && (($hit['_score'] ?? 0) >= 0.6 || $normLen >= 6)) {
            $info = BotTools::getProductInfo($hit['slug']);
            if (($info['found'] ?? false)) {
                $reply = "Thông tin **{$info['name']}** trong hệ thống:\n- Mô tả: " . ($info['short_desc'] ?: mb_substr($info['long_desc'] ?? '', 0, 180) . '…') .
                    "\n- Giá từ: **" . number_format($info['price_min']) . "₫**\nBạn muốn mình so sánh với món khác không?";
                BotLogger::save($msg, $reply, [
                    't0' => $t0,
                    'handled_by' => 'fast_product',
                    'intent' => 'product_info',
                    'matched_slug' => $info['slug'],
                    'product_count' => 1
                ]);
                ConversationMemory::pushTurn($mem, 'user', $msg);
                ConversationMemory::pushTurn($mem, 'assistant', $reply);
                ConversationMemory::save($mem);
                return response()->json([
                    'reply' => $reply,
                    'products' => [[
                        'url' => url('/products/' . $info['slug']),
                        'img' => $info['img'] ?: asset('images/placeholder.png'),
                        'name' => $info['name'],
                        'price' => number_format($info['price_min']) . '₫',
                        'compare' => null,
                        'discount' => null
                    ]],
                    'suggestions' => ['So sánh', 'Cách dùng chuẩn', '/reset'],
                ]);
            }
        }

        /* ===== LLM (nếu có key) — giữ nguyên tool-calling của bạn ===== */
        $gem = new GeminiClient();
        if ($gem->enabled()) {
            try {
                $system = ConversationMemory::systemFromMemory($mem)
                    . "\n\nNguyên tắc: Không bịa. Khi cần gọi tools pickProducts/searchProducts/resolveProduct/getProductInfo/checkAvailability/compareProducts/getOrderStatus/validateCoupon/getPolicy.";
                $contents = array_merge(
                    ConversationMemory::asGeminiHistory($mem),
                    [
                        ['role' => 'user', 'parts' => [['text' => '(Few-shot) Da dầu mụn, ngân sách 300-500k, gợi ý giúp mình với?']]],
                        ['role' => 'model', 'parts' => [['text' => 'Mình sẽ gợi ý 3–5 món kèm lý do và cách dùng 🌸']]],
                        ['role' => 'user', 'parts' => [['text' => $userForModel]]],
                    ]
                );
                $tools = GeminiTools::declarations();

                $products = [];
                $final = null;
                for ($i = 0; $i < 3; $i++) {
                    $resp = $gem->generate($contents, $tools, $system);
                    $cand = $resp['candidates'][0]['content'] ?? [];
                    $parts = $cand['parts'] ?? [];
                    $calls = [];
                    foreach ($parts as $p) if (isset($p['functionCall'])) $calls[] = $p['functionCall'];
                    if (!$calls) {
                        $texts = [];
                        foreach ($parts as $p) if (isset($p['text'])) $texts[] = $p['text'];
                        $final = trim(implode("\n\n", $texts)) ?: $final;
                        break;
                    }
                    $contents[] = $cand;
                    foreach ($calls as $c) {
                        $name = $c['name'] ?? '';
                        $args = $c['args'] ?? [];
                        $toolRes = BotTools::call($name, $args);
                        if (in_array($name, ['pickProducts', 'searchProducts']) && isset($toolRes['result'])) $products = $toolRes['result'];
                        $contents[] = ['role' => 'user', 'parts' => [['functionResponse' => ['name' => $name, 'response' => $toolRes]]]];
                    }
                }
                if (!$final) {
                    $resp2 = $gem->generate($contents, $tools, $system);
                    $final = $resp2['candidates'][0]['content']['parts'][0]['text'] ?? 'Mình đã xử lý xong nè!';
                }
                BotLogger::save($msg, $final, ['t0' => $t0, 'handled_by' => 'llm', 'intent' => 'mixed', 'product_count' => count($products)]);
                ConversationMemory::pushTurn($mem, 'user', $msg);
                ConversationMemory::pushTurn($mem, 'assistant', $final);
                ConversationMemory::save($mem);
                return response()->json(['reply' => $final, 'products' => $products, 'suggestions' => $chips]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        /* ===== FREE MODE: tư vấn cơ bản từ DB ===== */
        $products = [];
        $args = [
            'query'      => $msg ?: null,
            'price_min'  => $mem['prefs']['budget_min'] ?? null,
            'price_max'  => $mem['prefs']['budget_max'] ?? null,
            'limit'      => 8
        ];
        $res = BotTools::call('searchProducts', $args);
        $products = $res['result'] ?? [];
        $reply = $products
            ? 'Mình gợi ý vài món phù hợp nè. Cần lọc theo loại da/concern không? 💖'
            : 'Bạn nói rõ hơn loại da/concern và ngân sách để mình tư vấn chính xác nha 🌸';

        BotLogger::save($msg, $reply, ['t0' => $t0, 'handled_by' => $products ? 'search' : 'fallback', 'intent' => 'consult', 'product_count' => count($products), 'ok' => (bool)$products]);
        ConversationMemory::pushTurn($mem, 'user', $msg);
        ConversationMemory::pushTurn($mem, 'assistant', $reply);
        ConversationMemory::save($mem);

        return response()->json(['reply' => $reply, 'products' => $products, 'suggestions' => $chips]);
    }
}
