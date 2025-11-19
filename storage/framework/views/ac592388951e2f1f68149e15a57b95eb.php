
<?php
// Helper nhỏ: nếu có route thì dùng route, không thì dùng URL fallback
$link = fn(string $name, string $fallback = '#', array $params = [], bool $absolute = true)
=> \Illuminate\Support\Facades\Route::has($name)
? route($name, $params, $absolute)
: $fallback;
?>


<?php $__env->startSection('title', 'Hạng thành viên'); ?>

<?php $__env->startSection('content'); ?>
<?php
/** @var \App\Models\UserTier $userTier */
/** @var array $summary ['yearSpend','current','next','toNext','percent','expiresAt'] */
/** @var \Illuminate\Support\Collection<\App\Models\MemberTier> $tiersList */

    $tier = $userTier->tier; // MemberTier hiện tại (bắt buộc)
    $next = $summary['next']; // MemberTier kế tiếp (hoặc null)
    $spend = (int) $summary['yearSpend'];
    $percent = (int) $summary['percent'];
    $toNext = (int) ($summary['toNext'] ?? 0);
    $expiry = $userTier->expires_at?->format('d/m/Y') ?? '—';

    // Tone theo hạng
    $tones = [
    'platinum' => ['chip' => 'bg-zinc-900 text-white', 'bar' => 'from-zinc-700 to-stone-500', 'ring' => 'ring-zinc-900/20'],
    'gold' => ['chip' => 'bg-amber-500 text-white', 'bar' => 'from-amber-500 to-yellow-400', 'ring' => 'ring-amber-500/20'],
    'silver' => ['chip' => 'bg-slate-500 text-white', 'bar' => 'from-slate-500 to-gray-400', 'ring' => 'ring-slate-500/20'],
    'member' => ['chip' => 'bg-rose-500 text-white', 'bar' => 'from-rose-500 to-pink-500', 'ring' => 'ring-rose-500/20'],
    ];
    $tone = $tones[$tier->code] ?? $tones['member'];

    // Perks JSON (an toàn)
    $perks = is_array($tier->perks_json) ? $tier->perks_json : (json_decode($tier->perks_json ?? '[]', true) ?: []);
    ?>

    <div class="mx-auto max-w-6xl p-4 sm:p-6 space-y-6">
        
        <section class="relative overflow-hidden rounded-2xl border bg-gradient-to-r from-rose-50 to-pink-50 <?php echo e($tone['ring']); ?> ring-1">
            <div class="absolute inset-0 pointer-events-none opacity-30"
                style="background-image: radial-gradient(32rem 16rem at 10% 10%, rgba(255,255,255,.8) 0, rgba(255,255,255,0) 70%),
                                      radial-gradient(20rem 12rem at 90% 60%, rgba(255,255,255,.6) 0, rgba(255,255,255,0) 70%);">
            </div>

            <div class="relative p-5 md:p-7">
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-black/5">
                        <i class="fa-solid fa-medal text-rose-500"></i>
                    </div>

                    <div class="text-xl md:text-2xl font-semibold tracking-tight">
                        Hạng của bạn: <span class="align-middle"><?php echo e($tier->name); ?></span>
                    </div>

                    <span class="md:ml-auto inline-flex items-center rounded-full text-xs px-3 py-1 <?php echo e($tone['chip']); ?> shadow-sm ring-1 ring-black/5">
                        Hiệu lực đến <?php echo e($expiry); ?>

                    </span>
                </div>

                <div class="mt-3 grid sm:grid-cols-3 gap-3 text-sm text-slate-700">
                    <div class="rounded-lg bg-white/70 backdrop-blur p-3 ring-1 ring-black/5">
                        <div class="text-slate-500">Chi tiêu năm nay</div>
                        <div class="font-semibold"><?php echo e(number_format($spend)); ?>₫</div>
                    </div>
                    <div class="rounded-lg bg-white/70 backdrop-blur p-3 ring-1 ring-black/5">
                        <div class="text-slate-500">Điểm thưởng</div>
                        <div class="font-semibold">x<?php echo e(number_format($tier->point_multiplier, 2)); ?></div>
                    </div>
                    <div class="rounded-lg bg-white/70 backdrop-blur p-3 ring-1 ring-black/5">
                        <div class="text-slate-500">Mục tiêu kế tiếp</div>
                        <div class="font-semibold">
                            <?php if($next): ?>
                            Còn <?php echo e(number_format($toNext)); ?>₫ để lên <?php echo e($next->name); ?>

                            <?php else: ?>
                            Bạn đang ở hạng cao nhất 🎉
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <div class="mt-4">
                    <div class="h-2.5 rounded-full bg-white/70 ring-1 ring-black/5 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo e($percent); ?>">
                        <div class="h-2.5 rounded-full bg-gradient-to-r <?php echo e($tone['bar']); ?> transition-all duration-700" style="width: <?php echo e($percent); ?>%"></div>
                    </div>
                    <?php if($next): ?>
                    <div class="mt-1 flex justify-between text-[12px] text-slate-500">
                        <span><?php echo e($tier->name); ?></span>
                        <span><?php echo e($next->name); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        
        <section class="grid lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border bg-white p-5 ring-1 ring-black/5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full <?php echo e($tone['chip']); ?> shadow-sm">
                            <i class="fa-solid fa-gift text-[12px]"></i>
                        </span>
                        <h2 class="font-medium">Quyền lợi hiện tại</h2>
                    </div>

                    <ul class="grid sm:grid-cols-2 gap-3 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 text-rose-500"><i class="fa-solid fa-gem"></i></span>
                            <span>Nhân điểm thưởng: <b>x<?php echo e(number_format($tier->point_multiplier, 2)); ?></b></span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 text-rose-500"><i class="fa-solid fa-truck-fast"></i></span>
                            <span>Miễn phí vận chuyển: <b><?php echo e((int) $tier->monthly_ship_quota); ?></b> lần/tháng</span>
                        </li>

                        <?php if($tier->auto_coupon_code): ?>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 text-rose-500"><i class="fa-solid fa-ticket"></i></span>
                            <span>Coupon hạng: <b><?php echo e($tier->auto_coupon_code); ?></b></span>
                        </li>
                        <?php endif; ?>

                        <?php $__currentLoopData = $perks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 text-rose-500"><i class="fa-regular fa-star"></i></span>
                            <span><?php echo e($perk); ?></span>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                    <div class="mt-4 flex gap-2">
                        <a href="<?php echo e($link('cart.index', url('/cart'))); ?>"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-500 text-white px-4 py-2 text-sm shadow-sm hover:bg-rose-600 transition">
                            <i class="fa-solid fa-basket-shopping"></i> Mua sắm ngay
                        </a>
                        <a href="<?php echo e(url('/sale')); ?>"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm ring-1 ring-slate-200 hover:bg-slate-50 transition">
                            <i class="fa-solid fa-fire-flame-curved text-rose-500"></i> Ưu đãi hiện có
                        </a>
                    </div>
                </div>

                
                <div class="rounded-2xl border bg-white p-5 ring-1 ring-black/5">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-800 text-white shadow-sm">
                            <i class="fa-regular fa-circle-question text-[12px]"></i>
                        </span>
                        <h2 class="font-medium">Câu hỏi nhanh</h2>
                    </div>
                    <dl class="space-y-3 text-sm text-slate-700">
                        <div>
                            <dt class="font-medium">Điểm có hết hạn không?</dt>
                            <dd class="text-slate-600">Có. Điểm sẽ hết hạn vào <b>31/12 năm sau</b> kể từ ngày cộng điểm.</dd>
                        </div>
                        <div>
                            <dt class="font-medium">Khi nào thăng hạng?</dt>
                            <dd class="text-slate-600">Khi chi tiêu năm đạt ngưỡng hạng kế. Hạng giữ đến <b>31/12 năm sau</b>.</dd>
                        </div>
                    </dl>
                </div>
            </div>

            
            <div class="rounded-2xl border bg-white p-5 ring-1 ring-black/5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-800 text-white shadow-sm">
                        <i class="fa-solid fa-stairs text-[12px]"></i>
                    </span>
                    <h2 class="font-medium">Lộ trình thăng hạng</h2>
                </div>

                <ol class="relative ms-3">
                    <?php
                    $maxSpend = max($spend, ($tiersList->last()->min_spend_year ?? 0));
                    ?>

                    <?php $__currentLoopData = $tiersList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                    $reached = $spend >= $t->min_spend_year;
                    ?>

                    <li class="mb-5">
                        <div class="absolute -left-3 top-1.5 h-full w-px bg-slate-200"></div>

                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full
                           <?php echo e($reached ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600'); ?>

                           ring-1 ring-black/5">
                                <i class="fa-solid <?php echo e($reached ? 'fa-check' : 'fa-ellipsis'); ?> text-[10px]"></i>
                            </span>

                            <div class="flex-1">
                                <div class="flex items-baseline justify-between">
                                    <div class="font-medium <?php echo e($t->id === $tier->id ? 'text-rose-600' : ''); ?>"><?php echo e($t->name); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo e(number_format($t->min_spend_year)); ?>₫/năm</div>
                                </div>

                                
                                <?php
                                $prevThreshold = (int) ($tiersList->firstWhere('min_spend_year', '<', $t->min_spend_year)?->min_spend_year ?? 0);
                                    $localRange = max(1, $t->min_spend_year - $prevThreshold);
                                    $localGain = max(0, min($localRange, $spend - $prevThreshold));
                                    $localPct = (int) floor($localGain * 100 / $localRange);
                                    ?>
                                    <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-1.5 bg-gradient-to-r from-rose-500 to-fuchsia-500" style="width: <?php echo e($localPct); ?>%"></div>
                                    </div>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ol>

                <p class="mt-2 text-xs text-slate-500">
                    Chi tiêu tính theo <b>năm dương lịch</b>. Khi đạt hạng, quyền lợi áp dụng ngay và giữ đến <b>31/12 năm sau</b>.
                </p>
            </div>
        </section>
    </div>
    <?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/account/membership/show.blade.php ENDPATH**/ ?>