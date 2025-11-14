
<?php $__env->startSection('title', 'CosmeBot - Tổng quan'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .stat-card {
        @apply bg-white border border-slate-200 rounded-xl p-6 shadow-sm;
    }
    .stat-number {
        @apply text-3xl font-bold text-slate-900 mb-1;
    }
    .stat-label {
        @apply text-sm text-slate-600;
    }
</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
        <i class="fa-solid fa-robot text-rose-600"></i>
        CosmeBot Dashboard
    </h1>
    <p class="text-slate-600 mt-1">Tổng quan và thống kê chatbot</p>
</div>


<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-number text-rose-600"><?php echo e(number_format($stats['total_conversations'])); ?></div>
        <div class="stat-label">Tổng hội thoại</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-blue-600"><?php echo e(number_format($stats['active_conversations'])); ?></div>
        <div class="stat-label">Hội thoại đang hoạt động</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-green-600"><?php echo e(number_format($stats['total_messages'])); ?></div>
        <div class="stat-label">Tổng tin nhắn</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-purple-600"><?php echo e(number_format($stats['total_intents'])); ?></div>
        <div class="stat-label">Intents đang hoạt động</div>
    </div>
    <div class="stat-card">
        <div class="stat-number text-orange-600"><?php echo e(number_format($stats['total_tools'])); ?></div>
        <div class="stat-label">Tools đang hoạt động</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-chart-line text-rose-600"></i>
            Tin nhắn theo ngày (30 ngày)
        </h3>
        <canvas id="dailyMessagesChart" height="100"></canvas>
    </div>

    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-list text-rose-600"></i>
            Top Intents (30 ngày)
        </h3>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $topIntents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $intent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div>
                    <div class="font-medium text-slate-900"><?php echo e($intent->intent); ?></div>
                    <div class="text-xs text-slate-500"><?php echo e($intent->count); ?> lần</div>
                </div>
                <div class="text-rose-600 font-semibold"><?php echo e($intent->count); ?></div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-slate-500 text-sm">Chưa có dữ liệu</p>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
        <i class="fa-solid fa-bolt text-rose-600"></i>
        Thao tác nhanh
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="<?php echo e(route('admin.bot.intents')); ?>" class="flex items-center gap-3 p-4 bg-rose-50 rounded-lg hover:bg-rose-100 transition">
            <i class="fa-solid fa-brain text-rose-600 text-xl"></i>
            <div>
                <div class="font-semibold text-slate-900">Quản lý Intents</div>
                <div class="text-xs text-slate-600">Cấu hình ý định</div>
            </div>
        </a>
        <a href="<?php echo e(route('admin.bot.tools')); ?>" class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
            <i class="fa-solid fa-toolbox text-blue-600 text-xl"></i>
            <div>
                <div class="font-semibold text-slate-900">Quản lý Tools</div>
                <div class="text-xs text-slate-600">Cấu hình công cụ</div>
            </div>
        </a>
        <a href="<?php echo e(route('admin.bot.conversations')); ?>" class="flex items-center gap-3 p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
            <i class="fa-solid fa-comments text-green-600 text-xl"></i>
            <div>
                <div class="font-semibold text-slate-900">Hội thoại</div>
                <div class="text-xs text-slate-600">Xem lịch sử chat</div>
            </div>
        </a>
        <a href="<?php echo e(route('admin.bot.analytics')); ?>" class="flex items-center gap-3 p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
            <i class="fa-solid fa-chart-bar text-purple-600 text-xl"></i>
            <div>
                <div class="font-semibold text-slate-900">Analytics</div>
                <div class="text-xs text-slate-600">Phân tích dữ liệu</div>
            </div>
        </a>
    </div>
</div>

<script>
const dailyData = <?php echo json_encode($dailyMessages, 15, 512) ?>;
const labels = Object.keys(dailyData).map(date => {
    const [year, month, day] = date.split('-');
    return `${day}/${month}`;
});
const data = Object.values(dailyData);

new Chart(document.getElementById('dailyMessagesChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Số tin nhắn',
            data: data,
            borderColor: 'rgb(244, 63, 94)',
            backgroundColor: 'rgba(244, 63, 94, 0.1)',
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } }
        }
    }
});
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/index.blade.php ENDPATH**/ ?>