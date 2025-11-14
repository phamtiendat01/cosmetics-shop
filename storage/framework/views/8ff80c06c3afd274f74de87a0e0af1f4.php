
<?php $__env->startSection('title', 'CosmeBot - Hội thoại'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
        <i class="fa-solid fa-comments text-rose-600"></i>
        Hội thoại
    </h1>
    <p class="text-slate-600 mt-1">Xem lịch sử hội thoại với chatbot</p>
</div>


<div class="bg-white border border-slate-200 rounded-xl p-4 mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Tìm kiếm</label>
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Session ID, tên user..."
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Trạng thái</label>
            <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                <option value="">Tất cả</option>
                <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Đang hoạt động</option>
                <option value="completed" <?php echo e(request('status') === 'completed' ? 'selected' : ''); ?>>Hoàn tất</option>
                <option value="abandoned" <?php echo e(request('status') === 'abandoned' ? 'selected' : ''); ?>>Bỏ dở</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">
            <i class="fa-solid fa-search mr-2"></i> Tìm
        </button>
    </form>
</div>


<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">User</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Session</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Số tin nhắn</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Trạng thái</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Cập nhật</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            <?php $__empty_1 = true; $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4 text-slate-900">#<?php echo e($conv->id); ?></td>
                <td class="px-6 py-4">
                    <?php if($conv->user): ?>
                    <div class="font-medium text-slate-900"><?php echo e($conv->user->name); ?></div>
                    <div class="text-xs text-slate-500"><?php echo e($conv->user->email); ?></div>
                    <?php else: ?>
                    <span class="text-slate-400">Guest</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <code class="text-xs bg-slate-100 px-2 py-1 rounded"><?php echo e(Str::limit($conv->session_id, 20)); ?></code>
                </td>
                <td class="px-6 py-4 text-slate-900"><?php echo e($conv->messages_count ?? 0); ?></td>
                <td class="px-6 py-4">
                    <?php if($conv->status === 'active'): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Đang hoạt động</span>
                    <?php elseif($conv->status === 'completed'): ?>
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">Hoàn tất</span>
                    <?php else: ?>
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">Bỏ dở</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-sm text-slate-600"><?php echo e($conv->updated_at->format('d/m/Y H:i')); ?></td>
                <td class="px-6 py-4">
                    <a href="<?php echo e(route('admin.bot.conversation', $conv)); ?>" 
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        <i class="fa-solid fa-eye mr-1"></i> Xem
                    </a>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                    Chưa có hội thoại nào.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<div class="mt-4">
    <?php echo e($conversations->links()); ?>

</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/conversations.blade.php ENDPATH**/ ?>