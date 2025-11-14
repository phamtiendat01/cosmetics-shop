<?php

namespace App\Console\Commands;

use App\Events\OrderCompleted;
use App\Models\Order;
use Illuminate\Console\Command;

class GenerateQRCodesForOrders extends Command
{
    protected $signature = 'blockchain:generate-qr-for-orders
                            {--order= : Specific order ID or code}
                            {--all : Generate for all completed orders}';

    protected $description = 'Generate QR codes for completed orders';

    public function handle()
    {
        if ($this->option('order')) {
            $orderId = $this->option('order');

            // Tìm order theo ID hoặc code
            $order = Order::where('id', $orderId)
                ->orWhere('code', $orderId)
                ->with('items.variant')
                ->first();

            if (!$order) {
                $this->error('❌ Order not found');
                return 1;
            }

            if (!in_array($order->status, ['completed', 'delivered'])) {
                $this->error('❌ Order is not completed. Status: ' . $order->status);
                return 1;
            }

            $this->info("📦 Generating QR codes for order: {$order->id} ({$order->code})");

            try {
                event(new OrderCompleted($order));
                $this->info('✅ QR codes generated successfully!');

                // Kiểm tra số QR codes đã tạo
                $qrCount = \App\Models\ProductQRCode::whereHas('orderItem', function($q) use ($order) {
                    $q->where('order_id', $order->id);
                })->count();

                $this->info("   Created {$qrCount} QR code(s)");
            } catch (\Exception $e) {
                $this->error('❌ Error: ' . $e->getMessage());
                return 1;
            }
        } elseif ($this->option('all')) {
            $orders = Order::whereIn('status', ['completed', 'delivered'])
                ->with('items.variant')
                ->get();

            if ($orders->isEmpty()) {
                $this->info('ℹ️  No completed orders found');
                return 0;
            }

            $this->info("📦 Found {$orders->count()} completed order(s)");
            $this->newLine();

            $bar = $this->output->createProgressBar($orders->count());
            $bar->start();

            $success = 0;
            $failed = 0;

            foreach ($orders as $order) {
                try {
                    event(new OrderCompleted($order));
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Success: {$success}");
            if ($failed > 0) {
                $this->error("❌ Failed: {$failed}");
            }
        } else {
            $this->error('❌ Please specify --order=ID or --all');
            return 1;
        }

        return 0;
    }
}
