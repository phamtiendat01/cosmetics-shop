<?php

namespace App\Tools\Bot;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * OrderTrackingTool - Tra cứu đơn hàng
 */
class OrderTrackingTool
{
    public function execute(string $message, array $context): ?array
    {
        // Extract order code từ message
        $code = $this->extractOrderCode($message);
        
        if (!$code) {
            return null;
        }
        
        $order = Order::where('code', $code)
            ->orWhere('code', 'like', '%' . $code . '%')
            ->first();
        
        if (!$order) {
            return [
                'found' => false,
                'message' => 'Không tìm thấy đơn hàng với mã: ' . $code,
            ];
        }
        
        return [
            'found' => true,
            'code' => $order->code,
            'status' => $order->status,
            'status_label' => $order->status_label ?? $order->status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $order->payment_status_label ?? $order->payment_status,
            'payment_method' => $order->payment_method,
            'grand_total' => (float)$order->grand_total,
            'placed_at' => $order->placed_at?->format('d/m/Y H:i'),
            'tracking_no' => $order->tracking_no,
            'shipping_method' => $order->shipping_method,
        ];
    }
    
    private function extractOrderCode(string $message): ?string
    {
        // Tìm mã đơn (format: CH-250828-XXXXX hoặc ORD123-XXXXX)
        if (preg_match('/\b(CH|ORD|ORDER)[\s\-]?([A-Z0-9\-]+)\b/i', $message, $matches)) {
            return strtoupper($matches[0]);
        }
        
        // Tìm số đơn (nếu user chỉ nhập số)
        if (preg_match('/\b(đơn|order|mã)\s*[#:]?\s*(\d+)\b/i', $message, $matches)) {
            // Tìm order theo ID
            $orderId = (int)$matches[2];
            $order = Order::find($orderId);
            return $order?->code;
        }
        
        // Tìm bất kỳ chuỗi có format giống mã đơn
        if (preg_match('/\b[A-Z]{2,4}[\s\-]?[0-9]{6}[\s\-]?[A-Z0-9]{5}\b/i', $message, $matches)) {
            return strtoupper(str_replace([' ', '-'], '', $matches[0]));
        }
        
        return null;
    }
}

