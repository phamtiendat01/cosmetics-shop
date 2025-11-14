<?php

namespace App\Console\Commands;

use App\Events\OrderConfirmed;
use App\Models\Order;
use Illuminate\Console\Command;

class GenerateMissingQRCodes extends Command
{
    protected $signature = 'blockchain:generate-missing-qr {order_id?}';
    protected $description = 'Generate QR codes for orders that should have them but don\'t';

    public function handle()
    {
        $orderId = $this->argument('order_id');

        if ($orderId) {
            $orders = Order::where('id', $orderId)->get();
        } else {
            // Tìm các order đã ở trạng thái phù hợp nhưng chưa có QR codes
            $orders = Order::whereIn('status', ['confirmed', 'processing', 'completed', 'delivered'])
                ->where(function($q) {
                    $q->where('payment_status', 'paid')
                      ->orWhere('payment_method', 'COD');
                })
                ->whereDoesntHave('items.qrCodes')
                ->with('items')
                ->get();
        }

        if ($orders->isEmpty()) {
            $this->info('Không tìm thấy order nào cần generate QR codes.');
            return 0;
        }

        $this->info("Tìm thấy {$orders->count()} order(s) cần generate QR codes.");

        foreach ($orders as $order) {
            $this->info("Processing order: {$order->code} (ID: {$order->id})");
            
            try {
                event(new OrderConfirmed($order));
                $this->info("  ✓ Event fired for order {$order->code}");
            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
            }
        }

        $this->info("\nHoàn tất!");
        return 0;
    }
}

