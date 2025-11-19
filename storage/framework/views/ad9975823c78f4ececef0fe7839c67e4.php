
<?php $__env->startSection('title', 'CosmeBot - Tổng quan'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">CosmeBot Dashboard</div>
    <div class="toolbar-actions"></div>
</div>


<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-3">
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.1s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e(number_format($stats['total_conversations'])); ?></div>
        <div class="text-xs text-slate-500">Tổng hội thoại</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.2s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e(number_format($stats['active_conversations'])); ?></div>
        <div class="text-xs text-slate-500">Đang hoạt động</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.3s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e(number_format($stats['total_messages'])); ?></div>
        <div class="text-xs text-slate-500">Tổng tin nhắn</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.4s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e(number_format($stats['total_intents'])); ?></div>
        <div class="text-xs text-slate-500">Intents</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.5s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e(number_format($stats['total_tools'])); ?></div>
        <div class="text-xs text-slate-500">Tools</div>
    </div>
</div>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="grid lg:grid-cols-2 gap-3 mb-3">
    
    <div class="card p-4" style="animation: fadeInUp 0.4s ease-out 0.6s backwards;">
        <div class="font-semibold mb-3">Tin nhắn theo ngày (30 ngày)</div>
        <div class="relative" style="height: 200px;">
            <canvas id="dailyMessagesChart"></canvas>
        </div>
    </div>

    
    <div class="card p-4" style="animation: fadeInUp 0.4s ease-out 0.7s backwards;">
        <div class="font-semibold mb-3">Top Intents (30 ngày)</div>
        <div class="space-y-2">
            <?php $__empty_1 = true; $__currentLoopData = $topIntents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $intent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center justify-between p-2 bg-slate-50 rounded hover:bg-slate-100 transition-colors"
                 style="animation: fadeInUp 0.3s ease-out <?php echo e(0.8 + ($index * 0.05)); ?>s backwards;">
                <div class="flex items-center gap-2.5 flex-1 min-w-0">
                    <span class="text-xs text-slate-400 w-4"><?php echo e($index + 1); ?>.</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate"><?php echo e($intent->intent); ?></div>
                        <div class="text-xs text-slate-500"><?php echo e($intent->count); ?> lần</div>
                    </div>
                </div>
                <div class="text-sm font-semibold ml-2"><?php echo e($intent->count); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center py-8 text-sm text-slate-500">Chưa có dữ liệu</div>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.8s backwards;">
    <div class="font-semibold mb-3">Thao tác nhanh</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <a href="<?php echo e(route('admin.bot.intents')); ?>" class="btn btn-outline btn-sm flex-col items-start h-auto py-2.5 hover:bg-slate-50 transition-colors">
            <i class="fa-solid fa-brain"></i>
            <span class="text-xs">Intents</span>
        </a>
        <a href="<?php echo e(route('admin.bot.tools')); ?>" class="btn btn-outline btn-sm flex-col items-start h-auto py-2.5 hover:bg-slate-50 transition-colors">
            <i class="fa-solid fa-toolbox"></i>
            <span class="text-xs">Tools</span>
        </a>
        <a href="<?php echo e(route('admin.bot.conversations')); ?>" class="btn btn-outline btn-sm flex-col items-start h-auto py-2.5 hover:bg-slate-50 transition-colors">
            <i class="fa-solid fa-comments"></i>
            <span class="text-xs">Hội thoại</span>
        </a>
        <a href="<?php echo e(route('admin.bot.analytics')); ?>" class="btn btn-outline btn-sm flex-col items-start h-auto py-2.5 hover:bg-slate-50 transition-colors">
            <i class="fa-solid fa-chart-bar"></i>
            <span class="text-xs">Analytics</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dailyData = <?php echo json_encode($dailyMessages, 15, 512) ?>;
    const labels = Object.keys(dailyData).map(date => {
        const [year, month, day] = date.split('-');
        return `${day}/${month}`;
    });
    const data = Object.values(dailyData);

    const canvas = document.getElementById('dailyMessagesChart');
    if (canvas && window.Chart) {
        const ctx = canvas.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 200);
        grad.addColorStop(0, 'rgba(244,63,94,.28)');
        grad.addColorStop(1, 'rgba(244,63,94,.03)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Số tin nhắn',
                    data: data,
                    borderColor: 'rgb(244,63,94)',
                    backgroundColor: grad,
                    borderWidth: 2.5,
                    pointRadius: 2.5,
                    pointHoverRadius: 4,
                    pointBackgroundColor: 'rgb(244,63,94)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 10,
                        titleFont: { size: 12 },
                        bodyFont: { size: 12 },
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)', drawBorder: false },
                        ticks: { font: { size: 11 }, color: '#64748b' }
                    }
                }
            }
        });
    }
});
</script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/index.blade.php ENDPATH**/ ?>