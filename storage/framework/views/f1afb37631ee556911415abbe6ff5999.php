
<?php $__env->startSection('title','Tổng quan'); ?>

<?php $__env->startSection('content'); ?>


<style>
    :root {
        --ink: 15, 23, 42;
        /* slate-900 */
        --muted: 100, 116, 139;
        /* slate-500 */
        --border: 255, 228, 230;
        /* rose-100 */
        --ring: 251, 113, 133;
        /* rose-400 */
        --brand: 244, 63, 94;
        /* rose-500 */
        --brand2: 236, 72, 153;
        /* pink-500 */
        --sky: 14, 165, 233;
        /* sky-500 */
    }

    .glass {
        background: rgba(255, 255, 255, .88);
        backdrop-filter: saturate(180%) blur(6px);
    }

    .card {
        border: 1px solid rgba(var(--border), 1);
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(var(--brand), .06);
    }

    .tile {
        border: 1px solid rgba(var(--border), 1);
        border-radius: 1rem;
        background-image: linear-gradient(180deg, rgba(var(--brand), .05), rgba(var(--brand2), .04));
        box-shadow: 0 8px 24px rgba(var(--brand), .06);
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        border-radius: 999px;
        padding: .25rem .5rem;
        font-size: .75rem;
        font-weight: 600;
        border: 1px solid rgba(var(--border), 1);
    }

    .chip-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 999px;
        background: rgb(var(--brand));
    }

    .soft-table thead th {
        color: rgba(var(--muted), 1);
        font-weight: 600;
    }

    .soft-table tbody tr {
        border-top: 1px solid rgba(var(--border), 1);
    }

    .soft-table tbody tr:hover {
        background: rgba(255, 241, 242, .5);
    }

    .kpi-number {
        letter-spacing: -.01em
    }
</style>


<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Tổng quan</h1>
        <p class="text-slate-500 mt-1">Ảnh chụp nhanh hiệu suất bán hàng & tồn kho.</p>
    </div>
    <div class="flex items-center gap-2">
        <input id="dateRange"
            class="glass rounded-lg border border-rose-200/70 px-3 py-2 text-sm shadow-sm focus:outline-none"
            placeholder="Chọn khoảng ngày" />
    </div>
</div>


<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="tile p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">Doanh thu hôm nay</span>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                    <path d="M7 9h10M7 13h6"></path>
                </svg>
            </span>
        </div>
        <div class="mt-1 text-3xl font-bold kpi-number"><?php echo e(number_format($todayRevenue ?? 0)); ?>₫</div>
    </div>

    <div class="tile p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">Doanh thu tháng</span>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-pink-100 text-pink-600">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 12h18"></path>
                    <path d="M3 6h18"></path>
                    <path d="M3 18h18"></path>
                </svg>
            </span>
        </div>
        <div class="mt-1 text-3xl font-bold kpi-number"><?php echo e(number_format($monthRevenue ?? 0)); ?>₫</div>
    </div>

    <div class="tile p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">Số đơn tháng</span>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 7h18M5 7l1 12h12l1-12"></path>
                    <path d="M9 7V5a3 3 0 016 0v2"></path>
                </svg>
            </span>
        </div>
        <div class="mt-1 text-3xl font-bold kpi-number"><?php echo e(number_format($ordersCount ?? 0)); ?></div>
    </div>

    <div class="tile p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">AOV (giá trị TB)</span>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 12h18"></path>
                    <path d="M12 3v18"></path>
                </svg>
            </span>
        </div>
        <div class="mt-1 text-3xl font-bold kpi-number"><?php echo e(number_format($aov ?? 0)); ?>₫</div>
    </div>
</div>


<div class="grid lg:grid-cols-3 gap-4 mt-5">
    
    <div class="card p-5 lg:col-span-2 min-w-0">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-semibold">Doanh thu 14 ngày</div>
                <div class="text-xs text-slate-500">Đã lọc các đơn đã thanh toán & hoàn tất</div>
            </div>
            <span class="chip bg-rose-50 text-rose-700"><span class="chip-dot"></span> realtime</span>
        </div>
        <div class="relative mt-4 h-[340px]">
            <canvas id="revChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
    </div>

    
    <div class="card p-5 min-w-0">
        <div class="font-semibold">Tỉ lệ trạng thái đơn (tháng)</div>

        <div class="grid grid-cols-5 gap-4 mt-3">
            <div class="col-span-3">
                <div class="relative h-[260px]">
                    <canvas id="statusChart" class="absolute inset-0 w-full h-full"></canvas>
                </div>
            </div>

            <div class="col-span-2">
                <?php
                $colors = ['#f43f5e','#06b6d4','#22c55e','#f59e0b','#8b5cf6','#64748b','#ef4444','#14b8a6','#10b981','#eab308'];
                $i = 0;
                ?>
                <ul class="space-y-1.5">
                    <?php $__empty_1 = true; $__currentLoopData = ($statusAgg ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full"
                                style="background-color: <?php echo e($colors[$i % count($colors)]); ?>"></span>
                            <span class="text-slate-600"><?php echo e(ucwords(str_replace('_',' ', $label ?: 'unknown'))); ?></span>
                        </div>
                        <span class="font-medium"><?php echo e(number_format($val)); ?></span>
                    </li>
                    <?php $i++; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="text-slate-500 text-sm">Chưa có dữ liệu.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>


<div class="grid lg:grid-cols-3 gap-4 mt-5">
    
    <div class="card p-5 min-w-0">
        <div class="font-semibold">Kênh thanh toán (tháng)</div>
        <div class="relative mt-4 h-[260px]">
            <canvas id="payChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
    </div>

    
    <div class="card p-5 lg:col-span-2 min-w-0">
        <div class="flex items-center justify-between">
            <div class="font-semibold">Top sản phẩm bán chạy (14 ngày)</div>
            <span class="text-xs text-slate-400">Theo SL & doanh thu</span>
        </div>
        <div class="overflow-x-auto mt-3">
            <table class="soft-table w-full text-sm">
                <thead>
                    <tr>
                        <th class="py-2">Sản phẩm</th>
                        <th class="w-24 text-right">SL</th>
                        <th class="w-32 text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = ($topProducts ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $displayName = $row->name ?? $row->product_name_snapshot ?? '—'; ?>
                    <tr>
                        <td class="py-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 font-semibold">
                                    <?php echo e(mb_substr($displayName,0,1)); ?>

                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium leading-5 line-clamp-1"><?php echo e($displayName); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-right"><?php echo e(number_format($row->qty ?? 0)); ?></td>
                        <td class="text-right font-medium"><?php echo e(number_format($row->total ?? 0)); ?>₫</td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="3" class="py-4 text-center text-slate-500">Chưa có dữ liệu.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="grid lg:grid-cols-3 gap-4 mt-5">
    
    <div class="card p-5 min-w-0">
        <div class="font-semibold">Ngành hàng nổi bật (14 ngày)</div>
        <?php
        $catMax = 0;
        if(isset($categoryAgg) && $categoryAgg instanceof \Illuminate\Support\Collection && $categoryAgg->count()){
        $catMax = max($categoryAgg->pluck('total')->toArray());
        }
        if ($catMax <= 0) $catMax=1;
            ?>

            <div class="mt-3 space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = ($categoryAgg ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $pct = min(100, round(($c->total / $catMax) * 100)); ?>
            <div>
                <div class="flex items-center justify-between text-sm">
                    <div class="font-medium"><?php echo e($c->name); ?></div>
                    <div class="text-slate-500"><?php echo e(number_format($c->total)); ?>₫</div>
                </div>
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-rose-50">
                    <div class="h-2 rounded-full"
                        style="width: <?php echo e($pct); ?>%; background: linear-gradient(90deg, rgb(var(--brand)) 0%, rgb(var(--brand2)) 100%);"></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-sm text-slate-500">Chưa có dữ liệu.</div>
            <?php endif; ?>
    </div>
</div>


<div class="card p-5 lg:col-span-2 min-w-0">
    <div class="flex items-center justify-between">
        <div class="font-semibold">Cảnh báo tồn kho thấp</div>
        <span class="chip bg-amber-50 text-amber-700">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <?php echo e($lowStockCount ?? 0); ?> biến thể
        </span>
    </div>

    <?php if(($lowStockItems ?? collect())->count()): ?>
    <div class="mt-3 overflow-x-auto">
        <table class="soft-table w-full text-sm">
            <thead>
                <tr>
                    <th class="py-2">Sản phẩm</th>
                    <th>SKU</th>
                    <th class="w-24 text-right">SL</th>
                    <th class="w-28 text-right">Ngưỡng</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $lowStockItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="py-2"><?php echo e($it->product_name); ?></td>
                    <td class="text-slate-600"><?php echo e($it->sku); ?></td>
                    <td class="text-right text-rose-600 font-medium"><?php echo e($it->qty); ?></td>
                    <td class="text-right text-slate-500"><?php echo e($it->min_qty ?? '-'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="mt-3 text-sm text-slate-500">Tuyệt vời! Hiện tại không có biến thể nào dưới ngưỡng.</div>
    <?php endif; ?>
</div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Date range (nếu flatpickr đã include ở layout)
    if (window.flatpickr) {
        flatpickr('#dateRange', {
            mode: 'range',
            dateFormat: 'd/m/Y'
        });
    }

    var revLabels = <?php echo json_encode($revLabels ?? [], 15, 512) ?>;
    var revSeries = <?php echo json_encode($revSeries ?? [], 15, 512) ?>;
    var statusAgg = <?php echo json_encode((object)($statusAgg ?? []), 15, 512) ?>;
    var payAgg = <?php echo json_encode((object)($payAgg ?? []), 15, 512) ?>;

    if (window.Chart) {
        // === Revenue Area ===
        const rctx = document.getElementById('revChart').getContext('2d');
        const grad = rctx.createLinearGradient(0, 0, 0, 350);
        grad.addColorStop(0, 'rgba(244,63,94,.28)');
        grad.addColorStop(1, 'rgba(244,63,94,.03)');

        new Chart(rctx, {
            type: 'line',
            data: {
                labels: revLabels,
                datasets: [{
                    label: 'Doanh thu',
                    data: revSeries,
                    borderColor: 'rgb(244,63,94)',
                    backgroundColor: grad,
                    tension: .35,
                    fill: true,
                    pointRadius: 2.5,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // === Status Donut ===
        const sctx = document.getElementById('statusChart').getContext('2d');
        const stLabs = Object.keys(statusAgg);
        const stData = Object.values(statusAgg);
        const stColors = ['#f43f5e', '#06b6d4', '#22c55e', '#f59e0b', '#8b5cf6', '#64748b', '#ef4444', '#14b8a6', '#10b981', '#eab308'];

        const centerText = {
            id: 'centerText',
            beforeDraw(chart) {
                if (!stData.length) return;
                let total = stData.reduce((a, b) => a + b, 0);
                const ctx = chart.ctx;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || !meta.data[0]) return;
                const {
                    x,
                    y
                } = meta.data[0];
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = 'rgba(var(--ink),1)';
                ctx.font = '700 18px ui-sans-serif,system-ui';
                ctx.fillText(total, x, y);
                ctx.restore();
            }
        };

        new Chart(sctx, {
            type: 'doughnut',
            data: {
                labels: stLabs,
                datasets: [{
                    data: stData,
                    backgroundColor: stLabs.map((_, i) => stColors[i % stColors.length]),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            },
            plugins: [centerText]
        });

        // === Payment Bar ===
        const pctx = document.getElementById('payChart').getContext('2d');
        const pLab = Object.keys(payAgg);
        const pVal = Object.values(payAgg);

        new Chart(pctx, {
            type: 'bar',
            data: {
                labels: pLab,
                datasets: [{
                    label: 'Đơn',
                    data: pVal,
                    backgroundColor: 'rgba(59,130,246,.18)',
                    borderColor: 'rgb(59,130,246)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/dashboard/index.blade.php ENDPATH**/ ?>