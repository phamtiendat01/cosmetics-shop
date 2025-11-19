<?php

namespace App\Tools\Bot;

use App\Services\CouponService;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ApplyCouponTool - Áp mã giảm giá
 */
class ApplyCouponTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để áp mã giảm giá.',
            ];
        }

        // Extract coupon code từ message
        $codeData = $this->extractCouponCode($message, $context);
        $applyCode = strtoupper(trim($codeData['apply_code'] ?? ''));
        $displayCode = $codeData['display_code'] ?? $applyCode;

        if (!$codeData || empty($applyCode)) {
            return [
                'success' => false,
                'message' => 'Mình không tìm thấy mã giảm giá trong tin nhắn. Bạn có thể nói lại mã không?',
            ];
        }

        try {
            // Tìm coupon theo system code hoặc user code (fallback user_coupons)
            $coupon = Coupon::where('code', $applyCode)->first();

            if (!$coupon) {
                $userCouponRow = DB::table('user_coupons')
                    ->where('code', $applyCode)
                    ->orWhere('code', strtoupper(trim($codeData['input_code'] ?? $applyCode)))
                    ->first();

                if ($userCouponRow && $userCouponRow->coupon_id) {
                    $coupon = Coupon::find((int)$userCouponRow->coupon_id);
                    if ($coupon) {
                        $applyCode = strtoupper($coupon->code);
                    }
                }
            }

            if (!$coupon) {
                Log::warning('ApplyCouponTool: Coupon not found', [
                    'apply_code' => $applyCode,
                    'input_code' => $codeData['input_code'] ?? null,
                ]);
                return [
                    'success' => false,
                    'message' => 'Mã giảm giá không tồn tại. Bạn vui lòng kiểm tra lại!',
                ];
            }

            // Sử dụng CouponService::applyCoupon (static method)
            $result = CouponService::applyCoupon($applyCode);

            if (!($result['ok'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể áp dụng mã giảm giá này.',
                ];
            }

            $displayLabel = $displayCode ?: $applyCode;

            // CouponService::applyCoupon đã lưu vào session rồi
            return [
                'success' => true,
                'code' => $displayLabel,
                'applied_code' => $applyCode,
                'discount' => (int)($result['discount'] ?? 0),
                'coupon_name' => $coupon->name,
                'message' => "Đã áp dụng mã **{$displayLabel}** thành công! Giảm " . number_format($result['discount'] ?? 0, 0, ',', '.') . '₫',
            ];
        } catch (\Throwable $e) {
            Log::error('ApplyCouponTool failed', [
                'error' => $e->getMessage(),
                'code' => $applyCode,
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi áp dụng mã giảm giá. Vui lòng thử lại!',
            ];
        }
    }

    /**
     * Extract coupon code từ message hoặc context
     */
    private function extractCouponCode(string $message, array $context): ?array
    {
        $lower = Str::lower(trim($message));

        // Check nếu user nói "không", "không có", "bỏ qua", "skip"
        if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi)\b/u', $lower)) {
            return null; // User không muốn áp mã
        }

        $coupons = $this->resolveAvailableCoupons($context);

        // Nếu user chọn theo số thứ tự (số 1, số 2...) hoặc nhập mỗi số
        $indexMatch = null;
        $normalized = Str::lower(Str::ascii($message));
        if (preg_match('/(?:so|thu)\s*(\d+)/u', $normalized, $m)) {
            $indexMatch = (int)$m[1];
        } elseif (preg_match('/^\s*(\d+)\s*$/u', $normalized, $m)) {
            $indexMatch = (int)$m[1];
        } elseif (preg_match('/\b(\d+)\b/u', $message, $m)) {
            $maybeIndex = (int)$m[1];
            $stripped = trim(preg_replace('/\d+/', '', $message));
            $strippedLength = mb_strlen($stripped);
            $hasKeyword = preg_match('/\b(số|so|thứ|thu)\b/iu', $message);
            if ($maybeIndex > 0 && $maybeIndex <= count($coupons) && ($hasKeyword || $strippedLength <= 4)) {
                $indexMatch = $maybeIndex;
            }
        }

        if ($indexMatch !== null) {
            $index = $indexMatch - 1;
            Log::info('ApplyCouponTool: Parsing coupon index', [
                'message' => $message,
                'normalized' => $normalized,
                'index' => $indexMatch,
                'coupons_count' => count($coupons),
            ]);
            if (isset($coupons[$index])) {
                $selected = $coupons[$index];
                $display = $selected['code'] ?? null;
                $apply = strtoupper($selected['apply_code'] ?? $selected['system_code'] ?? $display ?? '');

                if ($display && $apply) {
                    Log::info('ApplyCouponTool: Extracted coupon by index', [
                        'index' => $index + 1,
                        'display_code' => $display,
                        'apply_code' => $apply,
                    ]);
                    return [
                        'display_code' => $display,
                        'apply_code' => $apply,
                        'input_code' => $display,
                    ];
                }
            }
        }

        // Extract code từ message (pattern: mã X, code X, áp mã X)
        if (preg_match('/\b(?:mã|code)\s+([A-Z0-9]{3,20})\b/ui', $message, $m) ||
            preg_match('/\b([A-Z0-9]{3,20})\b/u', Str::upper($message), $m)) {
            $inputCode = strtoupper($m[1]);
            $display = $inputCode;
            $apply = $inputCode;

            foreach ($coupons as $coupon) {
                $candidates = array_filter([
                    strtoupper($coupon['code'] ?? ''),
                    strtoupper($coupon['user_code'] ?? ''),
                    strtoupper($coupon['system_code'] ?? ''),
                    strtoupper($coupon['apply_code'] ?? ''),
                ]);
                if (in_array($inputCode, $candidates, true)) {
                    $display = $coupon['code'] ?? $inputCode;
                    $apply = strtoupper($coupon['apply_code'] ?? $coupon['system_code'] ?? $coupon['code'] ?? $inputCode);
                    break;
                }
            }

            Log::info('ApplyCouponTool: Extracted coupon from message', [
                'input_code' => $inputCode,
                'apply_code' => $apply,
            ]);

            return [
                'display_code' => $display,
                'apply_code' => $apply,
                'input_code' => $inputCode,
            ];
        }

        Log::warning('ApplyCouponTool: Could not extract coupon code', [
            'message' => $message,
            'coupons_count' => count($coupons),
        ]);

        return null;
    }

    /**
     * Lấy danh sách coupons khả dụng từ nhiều nguồn
     */
    private function resolveAvailableCoupons(array $context): array
    {
        $coupons = $context['checkout_data']['available_coupons'] ?? [];

        if (empty($coupons) && !empty($context['tools_result']['getUserCoupons']['coupons'])) {
            $coupons = $context['tools_result']['getUserCoupons']['coupons'];
        }

        if (empty($coupons) && auth()->check()) {
            try {
                $couponsTool = app(\App\Tools\Bot\GetUserCouponsTool::class);
                $result = $couponsTool->execute('', $context);
                if (!empty($result['coupons'])) {
                    $coupons = $result['coupons'];
                }
            } catch (\Throwable $e) {
                Log::warning('ApplyCouponTool: Failed to fetch coupons', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!is_array($coupons)) {
            return [];
        }

        return array_values($coupons);
    }
}

