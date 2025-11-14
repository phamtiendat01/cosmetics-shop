<?php $__env->startSection('title','Chi tiết đơn hàng'); ?>

<?php $__env->startSection('content'); ?>
<?php
use Illuminate\Support\Str;

/* ---------- Chuẩn hoá trạng thái để đồng bộ với admin ---------- */
$raw = $order->status ?? '';
$norm = Str::snake($raw);
$aliases = [
'pending'=>'cho_xac_nhan','cho_thanh_toan'=>'cho_xac_nhan','cho_xu_ly'=>'cho_xac_nhan','cho_xac_nhan'=>'cho_xac_nhan',
'confirmed'=>'da_xac_nhan','da_xac_nhan'=>'da_xac_nhan',
'processing'=>'dang_xu_ly','dang_xu_ly'=>'dang_xu_ly',
'shipping'=>'dang_giao','dang_giao'=>'dang_giao',
'completed'=>'hoan_tat','hoan_thanh'=>'hoan_tat','hoan_tat'=>'hoan_tat',
'cancelled'=>'huy','da_huy'=>'huy','huy'=>'huy',
'refunded'=>'hoan_tien','da_hoan_tien'=>'hoan_tien','hoan_tien'=>'hoan_tien',
];
$canon = $aliases[$norm] ?? $norm;

$displaySteps = [
'dat_hang'=>'Đặt hàng',
'xac_nhan'=>'Xác nhận',
'xu_ly'=>'Xử lý',
'giao_hang'=>'Giao hàng',
'hoan_tat'=>'Hoàn tất',
];
$mapToIndex = ['cho_xac_nhan'=>0,'pending'=>0,'da_xac_nhan'=>1,'dang_xu_ly'=>2,'dang_giao'=>3,'hoan_tat'=>4];
$currentIndex = $mapToIndex[$canon] ?? 0;
$statusLabel = array_values($displaySteps)[$currentIndex] ?? ucfirst($canon);

$endedLabel = in_array($canon, ['huy','hoan_tien'], true) ? ($canon==='huy' ? 'Đã huỷ' : 'Đã hoàn tiền') : null;
$endedPill = $canon==='huy' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-sky-50 text-sky-700 border border-sky-200';

$mainPill = match ($canon) {
'hoan_tat' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
'dang_giao' => 'bg-sky-50 text-sky-700 border border-sky-200',
'dang_xu_ly' => 'bg-amber-50 text-amber-700 border border-amber-200',
'da_xac_nhan'=> 'bg-violet-50 text-violet-700 border border-violet-200',
'huy' => 'bg-rose-50 text-rose-700 border border-rose-200',
'hoan_tien' => 'bg-sky-50 text-sky-700 border border-sky-200',
default => 'bg-rose-50/60 text-ink/70 border border-rose-200',
};

$payStatus = Str::snake($order->payment_status ?? '');
$payLabel = match ($payStatus) {
'paid'=>'Đã thanh toán','refunded'=>'Đã hoàn tiền','failed'=>'Thanh toán thất bại','pending'=>'Chờ thanh toán',
default => Str::title(str_replace('_',' ',$payStatus ?: 'Chưa thanh toán')),
};
$payPill = match ($payStatus) {
'paid'=>'bg-emerald-50 text-emerald-700 border border-emerald-200',
'refunded'=>'bg-sky-50 text-sky-700 border border-sky-200',
'failed'=>'bg-rose-50 text-rose-700 border border-rose-200',
default=>'bg-rose-50/60 text-ink/70 border border-rose-200',
};
$methodMap = ['COD'=>'Thanh toán khi nhận hàng (COD)','VNPAY'=>'VNPay','MOMO'=>'Momo','VIETQR'=>'VietQR'];

/* ---------- Địa chỉ ---------- */
$shipping = $shipping ?? (is_array($order->shipping_address) ? $order->shipping_address : []);
$receiverName = $shipping['name'] ?? $shipping['full_name'] ?? $order->shipping_name ?? $order->recipient_name ?? $order->customer_name ?? '—';
$receiverPhone = $shipping['phone'] ?? $order->shipping_phone ?? $order->recipient_phone ?? '—';
$addrLine1 = $shipping['address'] ?? $shipping['address_line1'] ?? $order->shipping_address_line1 ?? null;
$ward = $shipping['ward'] ?? $shipping['ward_name'] ?? null;
$district = $shipping['district'] ?? $shipping['district_name'] ?? null;
$city = $shipping['city'] ?? $shipping['province'] ?? $shipping['city_name'] ?? null;
$fullAddress = collect([$addrLine1,$ward,$district,$city])->filter()->implode(', ');

/* ---------- Icon cho step ---------- */
$icons = [
'dat_hang' => 'M3 6h18M3 6l2 12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2L21 6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2',
'xac_nhan' => 'M20 6L9 17l-5-5',
'xu_ly' => 'M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83',
'giao_hang' => 'M3 7h11v10H3zM14 10h5l2 3v4h-7zM5 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
'hoan_tat' => 'M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.54 5.82 22 7 14.14l-5-4.87 6.91-1.01z',
];

/* ---------- Map timeline ---------- */
$statusTextMap = [
'pending'=>'Đặt hàng','confirmed'=>'Xác nhận','processing'=>'Xử lý','shipping'=>'Giao hàng','completed'=>'Hoàn tất','cancelled'=>'Đã huỷ','refunded'=>'Đã hoàn tiền',
'cho_xac_nhan'=>'Đặt hàng','da_xac_nhan'=>'Xác nhận','dang_xu_ly'=>'Xử lý','dang_giao'=>'Giao hàng','hoan_tat'=>'Hoàn tất','huy'=>'Đã huỷ','hoan_tien'=>'Đã hoàn tiền',
];
$payTextMap = ['unpaid'=>'Chưa thanh toán','pending'=>'Chờ thanh toán','paid'=>'Đã thanh toán','refunded'=>'Đã hoàn tiền','failed'=>'Thanh toán thất bại'];

/* ---------- Cancel / Return ---------- */
$canCancel = in_array(Str::snake($order->status), ['pending','confirmed','processing','cho_xac_nhan','da_xac_nhan','dang_xu_ly'], true)
&& ( ($order->payment_status ?? '') !== 'paid' || strtoupper($order->payment_method ?? '') === 'COD' );

/* 14 ngày kể từ hoàn tất */
$windowDays = (int) config('orders.return_window_days', 14);
$events = $order->events ?? collect();
$completedAt = null;
if (!empty($order->completed_at)) { try { $completedAt = \Carbon\Carbon::parse($order->completed_at); } catch (\Throwable $e) {} }
if (!$completedAt && $events->count()) {
foreach ($events->sortByDesc('created_at') as $ev) {
if (($ev->type ?? '') !== 'status_changed') continue;
$new = data_get($ev,'new.status');
if (in_array(Str::snake((string)$new), ['completed','hoan_tat'], true)) { $completedAt = \Carbon\Carbon::parse($ev->created_at); break; }
}
}
$withinWindow = $completedAt ? now()->lte($completedAt->copy()->addDays($windowDays)) : false;
$okStatusForReturn = in_array($canon, ['dang_giao','hoan_tat'], true);
$canReturn = $okStatusForReturn && $withinWindow;
?>

<div class="max-w-7xl mx-auto px-4 py-6">

    
    <?php if(session('ok') || $errors->any()): ?>
    <div id="flash" class="fixed right-4 top-20 z-40">
        <?php if(session('ok')): ?>
        <div class="mb-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 shadow-sm">
            <b>Thành công:</b> <?php echo e(session('ok')); ?>

        </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
        <div class="mb-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 shadow-sm">
            <b>Lỗi:</b> <?php echo e($errors->first()); ?>

        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    
    <div class="flex items-start justify-between gap-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold">Đơn #<span id="order-code"><?php echo e($order->code); ?></span></h1>
                <button id="btn-copy" class="text-xs px-2 py-1 rounded-md border border-rose-200 hover:bg-rose-50">Sao chép</button>
            </div>
            <div class="text-sm text-ink/60 mt-1">Đặt lúc <?php echo e(optional($order->created_at)->format('d/m/Y H:i')); ?></div>
        </div>

        <div class="text-right space-y-2">
            <div class="text-sm">Trạng thái:
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs <?php echo e($mainPill); ?>"><?php echo e($statusLabel); ?></span>
            </div>
            <div class="text-sm">Thanh toán:
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs <?php echo e($payPill); ?>"><?php echo e($payLabel); ?></span>
            </div>

            
            <?php if($order->payment_status!=='paid' && Route::has('payment.vietqr.show') && ($order->payment_method ?? '')==='VIETQR'): ?>
            <a href="<?php echo e(route('payment.vietqr.show', $order)); ?>"
                class="inline-flex items-center rounded-md bg-gradient-to-r from-rose-600 to-pink-600 text-white px-4 py-2 text-sm hover:from-rose-500 hover:to-pink-500">
                Tiếp tục thanh toán
            </a>
            <?php endif; ?>

            
            <?php if($canCancel): ?>
            <button id="btn-open-cancel"
                class="inline-flex items-center rounded-md border border-rose-200 px-4 py-2 text-sm text-rose-700 hover:text-white hover:border-transparent hover:bg-gradient-to-r hover:from-rose-600 hover:to-pink-600 transition">
                Huỷ đơn (COD/chưa thanh toán)
            </button>
            <?php endif; ?>

            
            <div class="text-xs mt-1">
                <?php if($okStatusForReturn && $completedAt): ?>
                <?php if($withinWindow): ?>
                <span class="text-ink/60">Trả hàng khả dụng đến:
                    <b><?php echo e($completedAt->copy()->addDays($windowDays)->format('d/m/Y H:i')); ?></b></span>
                <?php else: ?>
                <span class="text-rose-600">Đơn đã quá hạn <?php echo e($windowDays); ?> ngày kể từ khi hoàn tất.</span>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if($canReturn && Route::has('account.returns.create')): ?>
            <a href="<?php echo e(route('account.returns.create', $order)); ?>"
                class="inline-flex items-center rounded-md border border-rose-200 px-4 py-2 text-sm
                  text-rose-700 hover:text-white hover:border-transparent hover:bg-gradient-to-r
                  hover:from-rose-600 hover:to-pink-600 transition">
                Yêu cầu trả hàng
            </a>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="mt-6 bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
        <div class="flex items-center gap-4">
            <?php $i=0; ?>
            <?php $__currentLoopData = $displaySteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
               <?php echo e($i <= $currentIndex ? 'text-white shadow-md bg-gradient-to-br from-rose-600 to-pink-500' : 'text-rose-300 border border-rose-200 bg-white'); ?>">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
                        <?php echo isset($icons[$key]) ? '
                        <path d="'.$icons[$key].'" />' : ''; ?>

                    </svg>
                </div>
                <div class="text-xs font-medium <?php echo e($i <= $currentIndex ? 'text-ink' : 'text-ink/40'); ?>"><?php echo e($label); ?></div>
            </div>
            <?php if(!$loop->last): ?>
            <div class="flex-1 h-1 rounded-full <?php echo e($i < $currentIndex ? 'bg-gradient-to-r from-rose-500 to-pink-500' : 'bg-rose-100'); ?>"></div>
            <?php endif; ?>
            <?php $i++; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if($endedLabel): ?>
        <div class="mt-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs <?php echo e($endedPill); ?>"><?php echo e($endedLabel); ?></span></div>
        <?php endif; ?>
    </div>

    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        
        <div class="space-y-6">
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-semibold mb-2">Người nhận</div>
                <div class="text-ink"><?php echo e($receiverName ?: '—'); ?></div>
                <div class="text-ink/70 text-sm mt-1"><?php echo e($receiverPhone ?: '—'); ?></div>
            </div>

            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-semibold mb-2">Địa chỉ giao hàng</div>
                <div class="text-ink"><?php echo e($fullAddress ?: '—'); ?></div>
            </div>

            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5 space-y-1">
                <div class="text-sm font-semibold">Thanh toán</div>
                <div class="text-sm">Phương thức:
                    <span class="font-medium"><?php echo e($methodMap[$order->payment_method ?? ''] ?? ($order->payment_method ?? '—')); ?></span>
                </div>
                <div class="text-sm">Trạng thái:
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs <?php echo e($payPill); ?>"><?php echo e($payLabel); ?></span>
                </div>
            </div>
        </div>

        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-rose-50/60 text-ink/70">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium">Sản phẩm</th>
                            <th class="px-6 py-3 text-center font-medium">SL</th>
                            <th class="px-6 py-3 text-right font-medium">Đơn giá</th>
                            <th class="px-6 py-3 text-right font-medium">Thành tiền</th>
                            <th class="px-6 py-3 text-right font-medium">Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100">
                        <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                        $variant = $it->variant;
                        if (is_string($variant)) { $decoded = json_decode($variant, true); if (json_last_error() === JSON_ERROR_NONE) $variant = $decoded; }
                        if (!is_array($variant)) $variant = [];
                        $variantLabel = $it->variant_name ?? $it->variant_name_snapshot ?? ($variant['name'] ?? ($variant['title'] ?? null));

                        // Ảnh: snapshot -> variant -> product thumbnail/image
                        $thumb = $it->thumbnail
                        ?? ($variant['image'] ?? $variant['thumbnail'] ?? null)
                        ?? optional($it->product)->thumbnail
                        ?? optional($it->product)->image;

                        if ($thumb && !Str::startsWith($thumb, ['http://','https://'])) {
                        $thumb = asset(Str::startsWith($thumb, ['storage/','/storage/']) ? ltrim($thumb,'/') : 'storage/'.ltrim($thumb,'/'));
                        }

                        $unit = (float)($it->unit_price ?? $it->price ?? 0);
                        if (!$unit && isset($variant['price'])) $unit = (float)$variant['price'];
                        if (!$unit && optional($it->product)->price) $unit = (float)$it->product->price;
                        $lineTotal = $unit * (int)$it->qty;

                        $rv = $it->review ?? null;
                        ?>
                        <tr>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <?php if($thumb): ?>
                                    <img src="<?php echo e($thumb); ?>" class="w-12 h-12 rounded object-cover border" alt="">
                                    <?php else: ?>
                                    <div class="w-12 h-12 rounded border flex items-center justify-center text-ink/40">IMG</div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium"><?php echo e($it->product_name_snapshot ?? optional($it->product)->name ?? 'Sản phẩm'); ?></div>
                                        <?php if($variantLabel): ?>
                                        <div class="mt-0.5 inline-flex rounded-full border px-2 py-0.5 text-xs text-ink/70"><?php echo e($variantLabel); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-center"><?php echo e($it->qty); ?></td>
                            <td class="px-6 py-3 text-right">₫<?php echo e(number_format($unit)); ?></td>
                            <td class="px-6 py-3 text-right">₫<?php echo e(number_format($lineTotal)); ?></td>
                            <td class="px-6 py-3 text-right">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', $it)): ?>
                                <a href="<?php echo e(route('account.order-items.reviews.create', [$order, $it])); ?>"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-rose-200 bg-white
                     text-ink/80 hover:text-white hover:border-transparent hover:bg-gradient-to-r hover:from-rose-600 hover:to-pink-600 hover:shadow-md transition">
                                    <i class="fa-regular fa-star"></i> Đánh giá
                                </a>
                                <?php else: ?>
                                <?php if($rv): ?>
                                <span class="inline-flex items-center gap-1 text-amber-500">
                                    <?php for($s=1;$s<=5;$s++): ?>
                                        <i class="fa-solid fa-star <?php echo e($s <= ($rv->rating ?? 0) ? '' : 'opacity-30'); ?>"></i>
                                        <?php endfor; ?>
                                </span>
                                <?php elseif($order->payment_status !== 'paid' || !in_array(Str::snake($order->status), ['hoan_thanh','completed'])): ?>
                                <span class="text-ink/60 text-xs">Chưa đủ điều kiện</span>
                                <?php else: ?>
                                <span class="text-ink/60 text-xs">Đã đánh giá</span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                        // Lấy QR codes cho order item này
                        $qrCodes = \App\Models\ProductQRCode::where('order_item_id', $it->id)->get();
                        ?>

                        <?php if($qrCodes->isNotEmpty() && in_array(Str::snake($order->status), ['hoan_tat', 'completed'])): ?>
                        <tr class="bg-blue-50/50">
                            <td colspan="5" class="px-6 py-4">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 mt-1">
                                        <i class="fas fa-qrcode text-blue-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                                            <span>🔗 Mã QR Code xác thực CosmeChain</span>
                                        </div>
                                        <div class="space-y-2">
                                            <?php $__currentLoopData = $qrCodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-blue-200 shadow-sm">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-xs text-gray-600 mb-1 font-medium">QR Code:</div>
                                                    <code class="text-xs font-mono break-all"><?php echo e($qr->qr_code); ?></code>
                                                </div>
                                                <div class="flex gap-2 flex-shrink-0">
                                                    <?php if($qr->qr_image_url || $qr->qr_image_path): ?>
                                                    <a href="<?php echo e(route('blockchain.qr.download', $qr->id)); ?>"
                                                       class="px-3 py-1.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white text-xs rounded-lg hover:from-brand-600 hover:to-brand-700 transition flex items-center gap-1 shadow-md">
                                                        <i class="fas fa-download"></i>
                                                        <span>Download</span>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="<?php echo e(route('blockchain.verify.qr', $qr->qr_code)); ?>" target="_blank"
                                                       class="px-3 py-1.5 bg-gradient-to-r from-green-500 to-green-600 text-white text-xs rounded-lg hover:from-green-600 hover:to-green-700 transition flex items-center gap-1 shadow-md">
                                                        <i class="fas fa-search"></i>
                                                        <span>Verify</span>
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-3 flex items-start gap-2 bg-blue-50 p-2 rounded">
                                            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                            <span>Quét QR code để xác thực sản phẩm chính hãng trên hệ thống CosmeChain. Mỗi sản phẩm có một mã QR code duy nhất.</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="text-ink/70">Tạm tính</div>
                    <div class="text-right font-medium">
                        ₫<?php echo e(number_format($order->subtotal ?? ($order->grand_total - ($order->shipping_fee ?? 0) + ($order->discount_total ?? 0)))); ?>

                    </div>
                    <div class="text-ink/70">Giảm giá</div>
                    <div class="text-right">-₫<?php echo e(number_format($order->discount_total ?? 0)); ?></div>
                    <div class="text-ink/70">Phí vận chuyển</div>
                    <div class="text-right">₫<?php echo e(number_format($order->shipping_fee ?? 0)); ?></div>
                    <?php if(!empty($order->tax_total)): ?>
                    <div class="text-ink/70">Thuế</div>
                    <div class="text-right">₫<?php echo e(number_format($order->tax_total)); ?></div>
                    <?php endif; ?>
                    <div class="col-span-2 border-t border-rose-100 my-1"></div>
                    <div class="text-ink font-semibold">Tổng cộng</div>
                    <div class="text-right text-lg font-semibold">₫<?php echo e(number_format($order->grand_total)); ?></div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="mt-6 bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
        <div class="text-sm font-semibold mb-2">Hoạt động</div>
        <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="text-sm py-2 border-t first:border-0 flex items-start gap-2">
            <span class="text-slate-500 whitespace-nowrap"><?php echo e(optional($ev->created_at)->format('d/m/Y H:i')); ?></span>
            <span>—</span>
            <div class="flex-1">
                <?php switch($ev->type):
                case ('status_changed'): ?>
                <?php $oldSt = data_get($ev->old,'status'); $newSt = data_get($ev->new,'status'); ?>
                Trạng thái:
                <b><?php echo e($statusTextMap[$oldSt] ?? Str::title(str_replace('_',' ',$oldSt))); ?></b>
                →
                <b><?php echo e($statusTextMap[$newSt] ?? Str::title(str_replace('_',' ',$newSt))); ?></b>
                <?php break; ?>

                <?php case ('payment_changed'): ?>
                <?php $oldPay = data_get($ev->old,'payment_status'); $newPay = data_get($ev->new,'payment_status'); ?>
                Thanh toán:
                <b><?php echo e($payTextMap[$oldPay] ?? Str::title(str_replace('_',' ',$oldPay))); ?></b>
                →
                <b><?php echo e($payTextMap[$newPay] ?? Str::title(str_replace('_',' ',$newPay))); ?></b>
                <?php break; ?>

                <?php case ('tracking_updated'): ?>
                Cập nhật mã vận đơn: <b><?php echo e(data_get($ev->new,'tracking_no','')); ?></b>
                <?php break; ?>

                <?php case ('note_added'): ?>
                Ghi chú: <?php echo e(data_get($ev->new,'notes','')); ?>

                <?php break; ?>

                
                <?php case ('return_requested'): ?>
                <?php $rid = data_get($ev->new,'order_return_id'); $expected = (int) data_get($ev->new,'expected',0); ?>
                Yêu cầu trả hàng #<?php echo e($rid); ?> được gửi. Tạm tính hoàn: <b><?php echo e(number_format($expected)); ?>₫</b>
                <?php break; ?>

                <?php case ('return_approved'): ?>
                <?php $rid = data_get($ev->new,'order_return_id'); ?>
                Yêu cầu trả hàng #<?php echo e($rid); ?> đã được duyệt.
                <?php break; ?>

                <?php case ('return_received'): ?>
                <?php $rid = data_get($ev->new,'order_return_id'); $final = (int) data_get($ev->new,'final',0); ?>
                Kho đã nhận hàng trả #<?php echo e($rid); ?>. Dự kiến hoàn: <b><?php echo e(number_format($final)); ?>₫</b>
                <?php break; ?>

                <?php case ('refund_processed'): ?>
                <?php
                $rid = data_get($ev->new,'order_return_id');
                $amount = (int) data_get($ev->new,'amount',0);
                $provider = data_get($ev->meta,'provider','');
                ?>
                Đã hoàn tiền <b><?php echo e(number_format($amount)); ?>₫</b> <?php echo e($provider ? 'qua '.$provider : ''); ?> cho yêu cầu #<?php echo e($rid); ?>.
                <?php break; ?>

                <?php default: ?>
                <?php echo e($ev->type); ?>

                <?php endswitch; ?>
                <?php if(!empty(data_get($ev,'meta.by'))): ?>
                <span class="text-ink/50"> · <?php echo e(data_get($ev,'meta.by')); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-sm text-slate-500">Chưa có hoạt động.</div>
        <?php endif; ?>
    </div>
</div>


<div id="cancel-modal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-close-modal></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-rose-100 animate-[fadeIn_0.15s_ease-out]">
            <div class="p-5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gradient-to-br from-rose-600 to-pink-600 text-white shadow">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold">Bạn có muốn xác nhận hủy đơn không?</h3>
                        <p class="text-sm text-ink/70 mt-1">
                            Sau khi huỷ: <span class="font-medium">đơn sẽ chuyển trạng thái “Đã huỷ”</span>,
                            các mã giảm/ưu đãi sẽ được giải phóng và hàng được cộng trả về kho (nếu có).
                        </p>
                    </div>
                </div>

                <form id="cancel-form" method="POST" action="<?php echo e(route('account.orders.cancel', ['order' => $order->id])); ?>" class="mt-5">
                    <?php echo csrf_field(); ?>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-outline" data-close-modal>Để sau</button>
                        <button id="btn-confirm-cancel" type="submit"
                            class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-white bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 shadow">
                            <span class="spinner hidden h-4 w-4 border-2 border-white/60 border-t-transparent rounded-full animate-spin"></span>
                            <span class="label">Xác nhận hủy</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div id="toast" class="hidden fixed left-1/2 -translate-x-1/2 bottom-6 bg-slate-900 text-white text-sm px-4 py-2 rounded-xl shadow-xl">
    Đã sao chép mã đơn
</div>

<style>
    @keyframes fadeIn {
        from {
            opacity: .0;
            transform: translateY(8px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }
</style>

<script>
    (function() {
        // Copy code toast
        const btn = document.getElementById('btn-copy');
        const code = document.getElementById('order-code')?.textContent || '';
        const toast = document.getElementById('toast');
        btn?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(code);
            } catch (_) {}
            toast.classList.remove('hidden');
            toast.animate([{
                transform: 'translateY(12px)',
                opacity: 0
            }, {
                transform: 'translateY(0)',
                opacity: 1
            }], {
                duration: 200,
                fill: 'forwards'
            });
            setTimeout(() => {
                toast.animate([{
                    opacity: 1
                }, {
                    opacity: 0
                }], {
                    duration: 200,
                    fill: 'forwards'
                });
                setTimeout(() => toast.classList.add('hidden'), 220);
            }, 1200);
        });

        // Flash auto hide
        const flash = document.getElementById('flash');
        if (flash) setTimeout(() => flash.remove(), 3000);

        // Modal logic
        const openBtn = document.getElementById('btn-open-cancel');
        const modal = document.getElementById('cancel-modal');
        const closer = () => modal?.classList.add('hidden');
        const open = () => {
            modal?.classList.remove('hidden');
            setTimeout(() => document.getElementById('btn-confirm-cancel')?.focus(), 10);
        };
        openBtn?.addEventListener('click', open);
        modal?.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-close-modal')) closer();
        });
        document.addEventListener('keydown', (e) => {
            if (!modal || modal.classList.contains('hidden')) return;
            if (e.key === 'Escape') closer();
        });

        // Submit UX
        const form = document.getElementById('cancel-form');
        const confirmBtn = document.getElementById('btn-confirm-cancel');
        form?.addEventListener('submit', () => {
            confirmBtn?.setAttribute('disabled', 'disabled');
            confirmBtn?.querySelector('.spinner')?.classList.remove('hidden');
            const label = confirmBtn?.querySelector('.label');
            if (label) label.textContent = 'Đang huỷ...';
        });
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/account/orders/show.blade.php ENDPATH**/ ?>