<?php $__env->startSection('title', 'CosmeBot - Quản lý Câu hỏi Tự động'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
<div class="alert alert-danger mb-3"><?php echo e(session('error')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý Câu hỏi Tự động</div>
    <div class="toolbar-actions">
        <button onclick="openModal()" class="btn btn-primary btn-sm">+ Thêm</button>
    </div>
</div>


<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.1s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($tools->total()); ?></div>
        <div class="text-xs text-slate-500">Tổng câu hỏi</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.2s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($tools->where('is_active', true)->count()); ?></div>
        <div class="text-xs text-slate-500">Đang hoạt động</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.3s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($tools->where('is_active', false)->count()); ?></div>
        <div class="text-xs text-slate-500">Đã tắt</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.4s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($tools->pluck('category')->unique()->count()); ?></div>
        <div class="text-xs text-slate-500">Danh mục</div>
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
.table-admin tbody tr {
    animation: fadeInUp 0.3s ease-out backwards;
}
</style>


<div class="card table-wrap p-0">
    <table class="table-admin">
        <thead>
            <tr>
                <th>Câu hỏi</th>
                <th>Câu trả lời</th>
                <th>Danh mục</th>
                <th>Thứ tự</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $tools->sortBy('order'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $tool): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr style="animation-delay: <?php echo e(0.5 + ($index * 0.03)); ?>s;">
                <td>
                    <div class="flex items-center gap-2">
                        <?php if($tool->icon): ?>
                        <span class="text-lg"><?php echo e($tool->icon); ?></span>
                        <?php else: ?>
                        <i class="fa-solid fa-question-circle text-slate-400"></i>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium mb-0.5"><?php echo e($tool->question ?? 'Chưa có câu hỏi'); ?></div>
                            <div class="text-xs text-slate-500 font-mono bg-slate-50 px-1.5 py-0.5 rounded inline-block"><?php echo e($tool->name); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-xs text-slate-700 max-w-md line-clamp-2 leading-relaxed">
                        <?php echo e(Str::limit($tool->answer ?? 'Chưa có câu trả lời', 100)); ?>

                    </div>
                </td>
                <td>
                    <span class="badge"><?php echo e(ucfirst($tool->category ?? 'general')); ?></span>
                </td>
                <td>
                    <span class="text-xs font-medium"><?php echo e($tool->order ?? 0); ?></span>
                </td>
                <td>
                    <?php if($tool->is_active): ?>
                    <span class="badge badge-green"><span class="badge-dot"></span>Hoạt động</span>
                    <?php else: ?>
                    <span class="badge badge-red"><span class="badge-dot"></span>Tắt</span>
                    <?php endif; ?>
                </td>
                <td class="col-actions">
                    <div class="actions">
                        <button onclick="editTool(<?php echo e(json_encode($tool)); ?>)" class="btn btn-table btn-outline">Sửa</button>
                        <form action="<?php echo e(route('admin.bot.tools.destroy', $tool)); ?>" method="POST" 
                            onsubmit="return confirmDelete('<?php echo e(addslashes($tool->question ?? 'câu hỏi này')); ?>')"
                            class="inline">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-table btn-danger">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="py-6 text-center text-slate-500">Chưa có câu hỏi tự động nào.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php if($tools->hasPages()): ?>
<div class="pagination mt-3">
    <?php echo e($tools->onEachSide(1)->links('pagination::tailwind')); ?>

</div>
<?php endif; ?>


<div id="toolModal" class="modal hidden">
    <div class="modal-card max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <form method="POST" action="<?php echo e(route('admin.bot.tools.store')); ?>" class="flex flex-col h-full">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="tool_id">

            
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="font-semibold" id="modal_title">Thêm câu hỏi tự động</h3>
                <button type="button" onclick="closeModal()" class="btn btn-ghost btn-sm !p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-blue-600 mt-0.5 text-sm"></i>
                        <div class="text-xs text-blue-800">
                            <strong class="block mb-1">Hướng dẫn:</strong>
                            <ul class="list-disc list-inside space-y-0.5 text-xs">
                                <li><strong>Câu hỏi:</strong> Hiển thị trong chat widget</li>
                                <li><strong>Câu trả lời:</strong> Tự động trả lời khi khách chọn</li>
                                <li><strong>Danh mục:</strong> Phân loại (shipping, return, product, payment)</li>
                                <li><strong>Icon:</strong> Emoji để làm nổi bật (VD: 🚚, 💳, 📦)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Câu hỏi (hiển thị cho khách hàng) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="question" id="tool_question" required maxlength="500"
                        placeholder="VD: Phí ship bao nhiêu?"
                        class="form-control">
                    <p class="text-xs text-slate-500 mt-1">Câu hỏi ngắn gọn, dễ hiểu</p>
                </div>

                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Câu trả lời <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer" id="tool_answer" rows="4" required
                        placeholder="VD: Phí vận chuyển:&#10;- Miễn phí ship cho đơn từ 500.000₫&#10;- Phí ship 30.000₫ cho đơn dưới 500.000₫"
                        class="form-control resize-none"></textarea>
                    <p class="text-xs text-slate-500 mt-1">Câu trả lời chi tiết, có thể dùng markdown</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Danh mục <span class="text-red-500">*</span>
                        </label>
                        <select name="category" id="tool_category" required
                            class="form-control">
                            <option value="">-- Chọn danh mục --</option>
                            <option value="shipping">Vận chuyển (Shipping)</option>
                            <option value="return">Đổi trả (Return)</option>
                            <option value="product">Sản phẩm (Product)</option>
                            <option value="payment">Thanh toán (Payment)</option>
                            <option value="general">Chung (General)</option>
                        </select>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Thứ tự hiển thị
                        </label>
                        <input type="number" name="order" id="tool_order" min="0" value="0"
                            placeholder="0"
                            class="form-control">
                        <p class="text-xs text-slate-500 mt-1">Số nhỏ hơn sẽ hiển thị trước</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Icon/Emoji (tùy chọn)
                        </label>
                        <input type="text" name="icon" id="tool_icon" maxlength="20"
                            placeholder="VD: 🚚, 💳, 📦"
                            class="form-control text-xl text-center">
                        <p class="text-xs text-slate-500 mt-1">Emoji để làm nổi bật</p>
                    </div>

                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Tên hiển thị (cho admin) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="display_name" id="tool_display_name" required
                            placeholder="VD: Phí vận chuyển"
                            class="form-control">
                    </div>
                </div>

                
                <div class="border-t border-slate-200 pt-3">
                    <details class="group">
                        <summary class="cursor-pointer text-xs font-semibold text-slate-700 flex items-center gap-2 py-1">
                            <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform text-xs"></i>
                            Các trường kỹ thuật (Tùy chọn)
                        </summary>
                        <div class="mt-3 space-y-3 pl-5">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">
                                    Tên kỹ thuật (name)
                                </label>
                                <input type="text" name="name" id="tool_name"
                                    placeholder="VD: shipping_fee"
                                    class="form-control font-mono text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">
                                    Mô tả
                                </label>
                                <textarea name="description" id="tool_description" rows="2"
                                    placeholder="Mô tả ngắn gọn"
                                    class="form-control text-xs resize-none"></textarea>
                            </div>
                        </div>
                    </details>
                </div>

                
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="tool_is_active" value="1" checked
                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="ml-2 text-xs font-semibold text-slate-700">Kích hoạt câu hỏi này</span>
                    </label>
                </div>
            </div>

            
            <div class="flex gap-2 px-4 py-3 border-t">
                <button type="submit" class="flex-1 btn btn-primary btn-sm" style="background: #e11d48 !important; border-color: #e11d48 !important; color: #fff !important;">
                    <i class="fa-solid fa-save"></i> Lưu
                </button>
                <button type="button" onclick="closeModal()" class="btn btn-outline btn-sm">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('tool_id').value = '';
    document.getElementById('tool_question').value = '';
    document.getElementById('tool_answer').value = '';
    document.getElementById('tool_category').value = '';
    document.getElementById('tool_order').value = '0';
    document.getElementById('tool_icon').value = '';
    document.getElementById('tool_display_name').value = '';
    document.getElementById('tool_name').value = '';
    document.getElementById('tool_description').value = '';
    document.getElementById('tool_is_active').checked = true;
    document.getElementById('modal_title').textContent = 'Thêm câu hỏi tự động';
}

function openModal() {
    resetForm();
    document.getElementById('toolModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.querySelector('#toolModal .overflow-y-auto').scrollTop = 0;
}

function closeModal() {
    document.getElementById('toolModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function editTool(tool) {
    if (typeof tool === 'string') {
        try {
            tool = JSON.parse(tool);
        } catch (e) {
            console.error('Failed to parse tool:', e);
            return;
        }
    }

    document.getElementById('tool_id').value = tool.id || '';
    document.getElementById('tool_question').value = tool.question || '';
    document.getElementById('tool_answer').value = tool.answer || '';
    document.getElementById('tool_category').value = tool.category || '';
    document.getElementById('tool_order').value = tool.order ?? 0;
    document.getElementById('tool_icon').value = tool.icon || '';
    document.getElementById('tool_display_name').value = tool.display_name || '';
    document.getElementById('tool_name').value = tool.name || '';
    document.getElementById('tool_description').value = tool.description || '';
    document.getElementById('tool_is_active').checked = tool.is_active !== undefined ? tool.is_active : true;
    
    document.getElementById('modal_title').textContent = 'Sửa câu hỏi tự động';

    document.getElementById('toolModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.querySelector('#toolModal .overflow-y-auto').scrollTop = 0;
}

function confirmDelete(question) {
    return confirm(`Bạn có chắc chắn muốn xóa câu hỏi "${question}"?\n\nHành động này không thể hoàn tác!`);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

document.getElementById('toolModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/tools.blade.php ENDPATH**/ ?>