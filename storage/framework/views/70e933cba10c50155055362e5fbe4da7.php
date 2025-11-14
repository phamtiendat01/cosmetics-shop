
<?php $__env->startSection('title', 'CosmeBot - Chi tiết hội thoại'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-comments text-rose-600"></i>
            Hội thoại #<?php echo e($conversation->id); ?>

        </h1>
        <p class="text-slate-600 mt-1">
            <?php if($conversation->user): ?>
            User: <?php echo e($conversation->user->name); ?> (<?php echo e($conversation->user->email); ?>)
            <?php else: ?>
            Guest - Session: <?php echo e($conversation->session_id); ?>

            <?php endif; ?>
        </p>
    </div>
    <a href="<?php echo e(route('admin.bot.conversations')); ?>" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
        <i class="fa-solid fa-arrow-left mr-2"></i> Quay lại
    </a>
</div>


<div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <div class="text-sm text-slate-600">Trạng thái</div>
            <div class="font-semibold text-slate-900">
                <?php if($conversation->status === 'active'): ?>
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Đang hoạt động</span>
                <?php elseif($conversation->status === 'completed'): ?>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Hoàn tất</span>
                <?php else: ?>
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">Bỏ dở</span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <div class="text-sm text-slate-600">Số tin nhắn</div>
            <div class="font-semibold text-slate-900"><?php echo e($conversation->messages->count()); ?></div>
        </div>
        <div>
            <div class="text-sm text-slate-600">Bắt đầu</div>
            <div class="font-semibold text-slate-900"><?php echo e($conversation->started_at->format('d/m/Y H:i')); ?></div>
        </div>
        <div>
            <div class="text-sm text-slate-600">Cập nhật</div>
            <div class="font-semibold text-slate-900"><?php echo e($conversation->updated_at->format('d/m/Y H:i')); ?></div>
        </div>
    </div>
</div>


<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-900">Lịch sử tin nhắn</h3>
    </div>
    <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto">
        <?php $__empty_1 = true; $__currentLoopData = $conversation->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="flex gap-4 <?php echo e($msg->isUser() ? 'justify-end' : 'justify-start'); ?>">
            <div class="max-w-[70%] <?php echo e($msg->isUser() ? 'order-2' : 'order-1'); ?>">
                <div class="flex items-center gap-2 mb-1">
                    <?php if($msg->isUser()): ?>
                    <i class="fa-solid fa-user text-blue-600"></i>
                    <span class="text-xs font-semibold text-slate-700">User</span>
                    <?php else: ?>
                    <i class="fa-solid fa-robot text-rose-600"></i>
                    <span class="text-xs font-semibold text-slate-700">CosmeBot</span>
                    <?php endif; ?>
                    <?php if($msg->intent): ?>
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs"><?php echo e($msg->intent); ?></span>
                    <?php endif; ?>
                    <?php if($msg->confidence): ?>
                    <span class="text-xs text-slate-500">(<?php echo e(number_format($msg->confidence * 100, 0)); ?>%)</span>
                    <?php endif; ?>
                </div>
                <div class="p-3 rounded-lg <?php echo e($msg->isUser() ? 'bg-blue-50 text-blue-900' : 'bg-rose-50 text-slate-900'); ?>">
                    <div class="text-sm whitespace-pre-wrap"><?php echo nl2br(e($msg->content)); ?></div>
                    <?php if($msg->tools_used && !empty($msg->tools_used)): ?>
                    <div class="mt-2 pt-2 border-t border-slate-200">
                        <div class="text-xs text-slate-600 mb-1">Tools used:</div>
                        <div class="flex flex-wrap gap-1">
                            <?php $__currentLoopData = array_keys($msg->tools_used); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tool): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs"><?php echo e($tool); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-xs text-slate-400 mt-1"><?php echo e($msg->created_at->format('H:i:s')); ?></div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center text-slate-500 py-8">Chưa có tin nhắn nào.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/conversation.blade.php ENDPATH**/ ?>