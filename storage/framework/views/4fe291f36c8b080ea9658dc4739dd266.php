
<?php $__env->startSection('title','Đơn #'.$order->code); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Đơn hàng #<?php echo e($order->code); ?></div>
    <div class="flex items-center gap-2">
        <a class="btn btn-outline btn-sm" href="<?php echo e(route('admin.orders.index')); ?>">← Danh sách</a>
        <?php
        // Kiểm tra xem order có QR codes không (kiểm tra trực tiếp từ database)
        $hasQRCodes = \App\Models\ProductQRCode::whereHas('orderItem', function($q) use ($order) {
            $q->where('order_id', $order->id);
        })->exists();
        ?>
        <?php if($hasQRCodes): ?>
        <a class="btn btn-primary btn-sm" href="<?php echo e(route('admin.orders.print-qr-codes', $order)); ?>" target="_blank">
            <i class="fas fa-print mr-1"></i> In QR Codes
        </a>
        <?php endif; ?>
        <a class="btn btn-outline btn-sm" href="<?php echo e(route('admin.order_returns.index', ['order' => $order->id])); ?>">Đổi trả / Hoàn tiền</a>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-3">
    <div class="md:col-span-2 space-y-3">
        <div class="card p-0">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                    // Logic lấy ảnh: snapshot thumbnail -> product thumbnail -> product image -> placeholder
                    $thumb = $it->thumbnail ?? null;
                    
                    if (!$thumb && $it->product) {
                        $thumb = $it->product->thumbnail ?? $it->product->image ?? null;
                    }

                    // Format URL
                    if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://')) {
                        $thumb = asset(str_starts_with($thumb, 'storage/') || str_starts_with($thumb, '/storage/')
                            ? ltrim($thumb, '/') 
                            : 'storage/' . ltrim($thumb, '/'));
                    } elseif (!$thumb) {
                        $thumb = 'https://placehold.co/60x60?text=IMG';
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="cell-thumb">
                                <img class="thumb" src="<?php echo e($thumb); ?>" alt="<?php echo e($it->product_name_snapshot); ?>">
                                <div class="min-w-0">
                                    <div class="font-medium truncate"><?php echo e($it->product_name_snapshot); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo e($it->variant_name_snapshot); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e(number_format($it->unit_price,0)); ?>₫</td>
                        <td><?php echo e($it->qty); ?></td>
                        <td class="font-semibold"><?php echo e(number_format($it->line_total,0)); ?>₫</td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        
        <div class="card p-3">
            <div class="font-semibold mb-2">Hoạt động</div>
            <?php $__empty_1 = true; $__currentLoopData = $order->events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="text-sm py-1 border-t first:border-0">
                <span class="text-slate-500"><?php echo e($ev->created_at->format('d/m/Y H:i')); ?></span> —
                <?php switch($ev->type):
                case ('status_changed'): ?>
                Trạng thái:
                <b><?php echo e(\App\Models\Order::STATUSES[$ev->old['status']] ?? $ev->old['status']); ?></b>
                →
                <b><?php echo e(\App\Models\Order::STATUSES[$ev->new['status']] ?? $ev->new['status']); ?></b>
                <?php break; ?>
                <?php case ('payment_changed'): ?>
                Thanh toán:
                <b><?php echo e(\App\Models\Order::PAY_STATUSES[$ev->old['payment_status']] ?? $ev->old['payment_status']); ?></b>
                →
                <b><?php echo e(\App\Models\Order::PAY_STATUSES[$ev->new['payment_status']] ?? $ev->new['payment_status']); ?></b>
                <?php break; ?>
                <?php case ('tracking_updated'): ?>
                Cập nhật mã vận đơn: <b><?php echo e($ev->new['tracking_no'] ?? ''); ?></b>
                <?php break; ?>
                <?php case ('note_added'): ?>
                Ghi chú: <?php echo e($ev->new['notes'] ?? ''); ?>

                <?php break; ?>
                <?php default: ?>
                <?php echo e($ev->type); ?>

                <?php endswitch; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-sm text-slate-500">Chưa có hoạt động.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-3">
        <div class="card p-3">
            <div class="text-sm text-slate-500">Khách hàng</div>
            <div class="mt-1">
                <div class="font-medium"><?php echo e($order->customer_name); ?></div>
                <div class="text-sm"><?php echo e($order->customer_phone); ?> <?php echo e($order->customer_email ? '· '.$order->customer_email : ''); ?></div>
                <div class="text-sm text-slate-600 mt-1"><?php echo e($order->address_text ?: '—'); ?></div>
            </div>
        </div>

        <div class="card p-3">
            <div class="text-sm text-slate-500">Giao hàng</div>
            <div class="mt-1 space-y-1 text-sm">
                <div>Phương thức: <b><?php echo e($order->shipping_method ?: '—'); ?></b></div>
                <div>Mã vận đơn: <b><?php echo e($order->tracking_no ?: '—'); ?></b></div>
            </div>
        </div>

        <div class="card p-3">
            <div class="text-sm text-slate-500">Thanh toán & Trạng thái</div>
            <form method="post" action="<?php echo e(route('admin.orders.update', ['admin_order' => $order->id])); ?>" class="space-y-2">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div>
                    <label class="label">Trạng thái đơn</label>
                    <select name="status" class="form-control" id="statusSelect">
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($k); ?>" <?php if($order->status===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="label">Trạng thái thanh toán</label>
                    <select name="payment_status" class="form-control" id="paySelect">
                        <?php $__currentLoopData = $payOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($k); ?>" <?php if($order->payment_status===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="label">Mã vận đơn</label>
                    <input name="tracking_no" value="<?php echo e(old('tracking_no',$order->tracking_no)); ?>" class="form-control">
                </div>
                <div>
                    <label class="label">ĐVVC</label>
                    <input name="shipping_method" value="<?php echo e(old('shipping_method',$order->shipping_method)); ?>" class="form-control">
                </div>
                <div>
                    <label class="label">Ghi chú nội bộ</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Thêm note"><?php echo e(old('notes',$order->notes)); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>

        
        <div class="card p-3">
            <div class="font-semibold mb-2">Thao tác nhanh</div>
            <?php
            $canCancel = in_array($order->status, ['pending','confirmed','processing'], true)
            && ( $order->payment_status !== 'paid' || strtoupper($order->payment_method) === 'COD' );
            ?>
            <?php if($canCancel): ?>
            <form method="POST" action="<?php echo e(route('admin.orders.cancel', ['admin_order' => $order->id])); ?>"
                onsubmit="return confirm('Bạn có chắc muốn huỷ đơn này? Tồn kho sẽ được cộng lại.');">
                <?php echo csrf_field(); ?>
                <button class="btn btn-outline btn-danger w-full">Huỷ đơn (COD/chưa thanh toán)</button>
            </form>
            <?php else: ?>
            <div class="text-sm text-slate-500">
                Huỷ đơn khả dụng khi trạng thái là <b>Chờ xác nhận / Đã xác nhận / Đang xử lý</b> và đơn <b>chưa thanh toán online</b>.
            </div>
            <?php endif; ?>
        </div>

        
        <div class="card p-3">
            <div class="text-sm text-slate-500">Đổi trả / Hoàn tiền</div>
            <?php
            $retCount = $order->returns()->count();
            $latestReturn = $order->returns()->latest()->first();
            ?>

            <?php if($retCount): ?>
            <div class="mt-1 text-sm">Có <b><?php echo e($retCount); ?></b> yêu cầu.</div>
            <div class="mt-2 flex gap-2">
                <a class="btn btn-outline btn-sm" href="<?php echo e(route('admin.order_returns.index', ['order' => $order->id])); ?>">Danh sách</a>
                <?php if($latestReturn): ?>
                <a class="btn btn-outline btn-sm" href="<?php echo e(route('admin.order_returns.show', $latestReturn)); ?>">Xem gần nhất #<?php echo e($latestReturn->id); ?></a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="mt-1 text-sm text-slate-500">Chưa có yêu cầu trả hàng.</div>
            <a class="btn btn-outline btn-sm mt-2" href="<?php echo e(route('admin.order_returns.index', ['order' => $order->id])); ?>">Tạo/Xem yêu cầu</a>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <div class="text-sm text-slate-500">Tổng kết</div>
            <div class="mt-2 text-sm space-y-1">
                <div class="flex justify-between"><span>Tạm tính</span><b><?php echo e(number_format($order->subtotal,0)); ?>₫</b></div>
                <div class="flex justify-between"><span>Giảm giá</span><b>-<?php echo e(number_format($order->discount_total,0)); ?>₫</b></div>
                <div class="flex justify-between"><span>Phí vận chuyển</span><b><?php echo e(number_format($order->shipping_fee,0)); ?>₫</b></div>
                <div class="flex justify-between"><span>Thuế</span><b><?php echo e(number_format($order->tax_total,0)); ?>₫</b></div>
                <div class="divider"></div>
                <div class="flex justify-between text-base"><span>Tổng thanh toán</span><b><?php echo e(number_format($order->grand_total,0)); ?>₫</b></div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Select đẹp (nếu có TomSelect)
    if (document.getElementById('statusSelect')) new TomSelect('#statusSelect', {
        create: false
    });
    if (document.getElementById('paySelect')) new TomSelect('#paySelect', {
        create: false
    });

    // Ẩn alert sau 3s
    document.querySelectorAll('[data-auto-dismiss]')?.forEach(el => {
        const ms = +el.getAttribute('data-auto-dismiss') || 3000;
        setTimeout(() => el.remove(), ms);
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/orders/show.blade.php ENDPATH**/ ?>