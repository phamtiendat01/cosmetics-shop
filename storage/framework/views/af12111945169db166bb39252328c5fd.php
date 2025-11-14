

<?php $__env->startSection('title','Ví Cosme'); ?>

<?php $__env->startSection('content'); ?>
<?php
$fmt = fn($n) => '₫' . number_format((int)$n);

// Badge loại theo ext_type
$extBadge = function($t) {
$type = $t->ext_type ?? null;
$cls = 'bg-slate-50 text-slate-700 border-slate-200';
$txt = 'Khác';
$ico = 'fa-file-lines';

if ($type === 'order_return') {
$cls = 'bg-emerald-50 text-emerald-700 border-emerald-200';
$txt = 'Hoàn từ trả hàng';
$ico = 'fa-rotate-left';
}
return [$cls, $txt, $ico];
};
?>

<div class="max-w-6xl mx-auto px-4 py-6">
    
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-600 to-pink-500 text-white grid place-content-center shadow">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold">Ví Cosme</h1>
                <div class="text-sm text-ink/60">
                    Tiền hoàn từ các đơn sẽ tự động cộng vào đây và có thể dùng để thanh toán các lần mua tiếp theo.
                </div>
            </div>
        </div>
        <a href="<?php echo e(route('account.orders.index')); ?>" class="btn btn-outline">
            <i class="fa-solid fa-receipt mr-1"></i> Đơn hàng của tôi
        </a>
    </div>

    
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm">
                <div class="text-sm text-ink/70 mb-1">Số dư hiện tại</div>
                <div class="text-4xl font-extrabold tracking-tight"><?php echo e($fmt($wallet->balance ?? 0)); ?></div>
                <div class="text-xs text-ink/60 mt-2">Số dư sẽ tự trừ khi bạn chọn thanh toán bằng Ví Cosme.</div>
                <div class="mt-3 flex items-center gap-2 text-xs text-ink/50">
                    <i class="fa-solid fa-shield-heart"></i>
                    Giao dịch ví được lưu theo đơn/phiếu hoàn để bảo vệ người dùng.
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold mb-1">Hướng dẫn nhanh</div>
                        <ul class="text-sm list-disc pl-5 space-y-1 text-ink/70">
                            <li>Tiền hoàn chỉ cộng vào ví sau khi yêu cầu trả hàng được duyệt và đơn được đánh dấu <b>Đã hoàn tiền</b>.</li>
                            <li>Ví có thể được chọn ở bước thanh toán để trừ trực tiếp vào tổng tiền.</li>
                            <li>Mục “Tham chiếu” giúp tra cứu lại đơn/yêu cầu tương ứng.</li>
                        </ul>
                    </div>
                    <div class="hidden sm:block">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 text-xs">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Ví chỉ dùng trong hệ thống Cosme — không phát sinh lãi/lỗ.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="mt-6 rounded-2xl border border-rose-100 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b bg-rose-50/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="font-semibold text-sm">Lịch sử giao dịch</div>
                <div class="hidden sm:flex items-center gap-1 ml-2">
                    <a href="<?php echo e(route('account.wallet.show')); ?>"
                        class="px-2 py-1 rounded text-xs <?php echo e(!$type ? 'bg-white border border-rose-200' : 'hover:bg-white text-ink/70'); ?>">
                        Tất cả <?php if(isset($counts)): ?><span class="ml-1 text-ink/50">(<?php echo e($counts['all']); ?>)</span><?php endif; ?>
                    </a>
                    <a href="<?php echo e(route('account.wallet.show', ['type' => 'credit'])); ?>"
                        class="px-2 py-1 rounded text-xs <?php echo e($type==='credit' ? 'bg-white border border-emerald-200' : 'hover:bg-white text-ink/70'); ?>">
                        Cộng <?php if(isset($counts)): ?><span class="ml-1 text-ink/50">(<?php echo e($counts['credit']); ?>)</span><?php endif; ?>
                    </a>
                    <a href="<?php echo e(route('account.wallet.show', ['type' => 'debit'])); ?>"
                        class="px-2 py-1 rounded text-xs <?php echo e($type==='debit' ? 'bg-white border border-amber-200' : 'hover:bg-white text-ink/70'); ?>">
                        Trừ <?php if(isset($counts)): ?><span class="ml-1 text-ink/50">(<?php echo e($counts['debit']); ?>)</span><?php endif; ?>
                    </a>
                </div>
            </div>
            <?php if($transactions->total() > 0): ?>
            <div class="text-xs text-ink/50">Tổng <?php echo e($transactions->total()); ?> giao dịch</div>
            <?php endif; ?>
        </div>

        <?php if($transactions->count() === 0): ?>
        <div class="p-10 text-center text-ink/60">
            <div class="mx-auto w-16 h-16 rounded-full bg-rose-50 text-rose-600 grid place-content-center mb-3">
                <i class="fa-solid fa-receipt"></i>
            </div>
            Chưa có giao dịch ví.
        </div>
        <?php else: ?>
        <table class="min-w-full text-sm">
            <thead class="bg-rose-50/60 text-ink/70">
                <tr>
                    <th class="px-5 py-3 text-left font-medium">Thời gian</th>
                    <th class="px-5 py-3 text-left font-medium">Loại</th>
                    <th class="px-5 py-3 text-right font-medium">Số tiền</th>
                    <th class="px-5 py-3 text-right font-medium">Số dư sau</th>
                    <th class="px-5 py-3 text-left font-medium">Tham chiếu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rose-100">
                <?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                [$cls, $txt, $ico] = $extBadge($t);
                $isCredit = ($t->type === 'credit');
                ?>
                <tr class="hover:bg-rose-50/40">
                    <td class="px-5 py-3 whitespace-nowrap"><?php echo e(optional($t->created_at)->format('d/m/Y H:i')); ?></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] border <?php echo e($cls); ?>">
                                <i class="fa-solid <?php echo e($ico); ?>"></i><?php echo e($txt); ?>

                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] border
                                    <?php echo e($isCredit ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'); ?>">
                                <i class="fa-solid <?php echo e($isCredit ? 'fa-circle-arrow-down' : 'fa-circle-arrow-up'); ?>"></i>
                                <?php echo e($isCredit ? 'Cộng' : 'Trừ'); ?>

                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right <?php echo e($isCredit ? 'text-emerald-600' : 'text-rose-600'); ?>">
                        <?php echo e(($isCredit ? '+' : '-') . $fmt($t->amount)); ?>

                    </td>
                    <td class="px-5 py-3 text-right"><?php echo e($fmt($t->balance_after)); ?></td>
                    <td class="px-5 py-3">
                        <?php if($t->ref_title): ?>
                        <div class="font-medium"><?php echo e($t->ref_title); ?></div>
                        <?php if($t->ref_code): ?>
                        <div class="text-xs text-ink/60">Mã: <?php echo e($t->ref_code); ?></div>
                        <?php endif; ?>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="px-5 py-3 border-t">
            <?php echo e($transactions->onEachSide(1)->links()); ?>

        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/account/wallet/show.blade.php ENDPATH**/ ?>