<?php $__env->startSection('title', 'CosmeBot - Quản lý Câu hỏi Tự động'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-comments text-rose-600"></i>
            Quản lý Câu hỏi Tự động
        </h1>
        <p class="text-slate-600 mt-1">Thiết lập các câu hỏi và câu trả lời tự động cho chatbot. Những câu hỏi này sẽ hiển thị trong chat widget để khách hàng chọn.</p>
        <div class="mt-2 text-sm text-amber-600 bg-amber-50 px-3 py-2 rounded-lg inline-block">
            <i class="fa-solid fa-lightbulb mr-1"></i>
            <strong>Lưu ý:</strong> Câu hỏi sẽ hiển thị trong chat widget, khi khách hàng chọn sẽ tự động trả lời bằng câu trả lời bạn đã thiết lập.
        </div>
    </div>
    <button onclick="openModal()"
        class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition shadow-sm hover:shadow-md flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> Thêm câu hỏi mới
    </button>
</div>


<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="text-2xl font-bold text-slate-900 mb-1"><?php echo e($tools->total()); ?></div>
        <div class="text-sm text-slate-600">Tổng câu hỏi</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="text-2xl font-bold text-green-600 mb-1"><?php echo e($tools->where('is_active', true)->count()); ?></div>
        <div class="text-sm text-slate-600">Đang hoạt động</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="text-2xl font-bold text-slate-500 mb-1"><?php echo e($tools->where('is_active', false)->count()); ?></div>
        <div class="text-sm text-slate-600">Đã tắt</div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="text-2xl font-bold text-rose-600 mb-1"><?php echo e($tools->pluck('category')->unique()->count()); ?></div>
        <div class="text-sm text-slate-600">Danh mục</div>
    </div>
</div>


<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Câu hỏi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Câu trả lời</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Danh mục</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Thứ tự</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Trạng thái</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php $__empty_1 = true; $__currentLoopData = $tools->sortBy('order'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tool): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <?php if($tool->icon): ?>
                            <span class="text-lg"><?php echo e($tool->icon); ?></span>
                            <?php endif; ?>
                            <div>
                                <div class="font-medium text-slate-900"><?php echo e($tool->question ?? 'Chưa có câu hỏi'); ?></div>
                                <div class="text-xs text-slate-500 font-mono mt-1"><?php echo e($tool->name); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-700 max-w-md line-clamp-2"><?php echo e(Str::limit($tool->answer ?? 'Chưa có câu trả lời', 100)); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $categoryColors = [
                            'shipping' => 'bg-blue-100 text-blue-700',
                            'return' => 'bg-orange-100 text-orange-700',
                            'product' => 'bg-purple-100 text-purple-700',
                            'payment' => 'bg-green-100 text-green-700',
                            'general' => 'bg-gray-100 text-gray-700',
                        ];
                        $color = $categoryColors[$tool->category ?? 'general'] ?? 'bg-gray-100 text-gray-700';
                        ?>
                        <span class="px-2 py-1 <?php echo e($color); ?> rounded text-xs font-semibold">
                            <?php echo e(ucfirst($tool->category ?? 'general')); ?>

                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-slate-600"><?php echo e($tool->order ?? 0); ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if($tool->is_active): ?>
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold flex items-center gap-1 w-fit">
                            <i class="fa-solid fa-check-circle text-xs"></i> Hoạt động
                        </span>
                        <?php else: ?>
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold flex items-center gap-1 w-fit">
                            <i class="fa-solid fa-pause-circle text-xs"></i> Tắt
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="editTool(<?php echo e(json_encode($tool)); ?>)"
                                class="text-rose-600 hover:text-rose-700 text-sm font-medium flex items-center gap-1.5 px-3 py-1.5 rounded-lg hover:bg-rose-50 transition">
                                <i class="fa-solid fa-edit text-xs"></i> Sửa
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-slate-400 mb-3">
                            <i class="fa-solid fa-inbox text-5xl"></i>
                        </div>
                        <p class="text-slate-600 font-medium text-lg mb-1">Chưa có câu hỏi tự động nào</p>
                        <p class="text-sm text-slate-500 mb-4">Hãy thêm câu hỏi đầu tiên để bot tự động trả lời khách hàng!</p>
                        <button onclick="openModal()" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Thêm câu hỏi đầu tiên
                        </button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div class="mt-4">
    <?php echo e($tools->links()); ?>

</div>


<div id="toolModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <form method="POST" action="<?php echo e(route('admin.bot.tools.store')); ?>" class="p-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="tool_id">

            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
                <h3 class="text-2xl font-bold text-slate-900">Thêm/Sửa câu hỏi tự động</h3>
                <button type="button" onclick="closeModal()"
                    class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="space-y-5">
                
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-info-circle text-blue-600 mt-0.5 text-lg"></i>
                        <div class="text-sm text-blue-800">
                            <strong class="block mb-1">Hướng dẫn sử dụng:</strong>
                            <ul class="list-disc list-inside space-y-1 text-xs">
                                <li><strong>Câu hỏi:</strong> Sẽ hiển thị trong chat widget để khách hàng chọn</li>
                                <li><strong>Câu trả lời:</strong> Sẽ tự động trả lời khi khách hàng chọn câu hỏi này</li>
                                <li><strong>Danh mục:</strong> Phân loại câu hỏi (VD: shipping, return, product, payment)</li>
                                <li><strong>Icon:</strong> Emoji hoặc icon để làm nổi bật câu hỏi (VD: 🚚, 💳, 📦)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Câu hỏi (hiển thị cho khách hàng) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="question" id="tool_question" required maxlength="500"
                        placeholder="VD: Phí ship bao nhiêu?"
                        class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-base">
                    <p class="text-xs text-slate-500 mt-1">Câu hỏi ngắn gọn, dễ hiểu sẽ hiển thị trong chat widget</p>
                </div>

                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Câu trả lời <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer" id="tool_answer" rows="5" required
                        placeholder="VD: Phí vận chuyển:&#10;- Miễn phí ship cho đơn từ 500.000₫&#10;- Phí ship 30.000₫ cho đơn dưới 500.000₫&#10;- Giao hàng toàn quốc trong 2-5 ngày làm việc"
                        class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-base"></textarea>
                    <p class="text-xs text-slate-500 mt-1">Câu trả lời chi tiết, có thể dùng markdown (**bold**, *italic*)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Danh mục <span class="text-red-500">*</span>
                        </label>
                        <select name="category" id="tool_category" required
                            class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-base">
                            <option value="">-- Chọn danh mục --</option>
                            <option value="shipping">Vận chuyển (Shipping)</option>
                            <option value="return">Đổi trả (Return)</option>
                            <option value="product">Sản phẩm (Product)</option>
                            <option value="payment">Thanh toán (Payment)</option>
                            <option value="general">Chung (General)</option>
                        </select>
                    </div>

                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Thứ tự hiển thị
                        </label>
                        <input type="number" name="order" id="tool_order" min="0" value="0"
                            placeholder="0"
                            class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-base">
                        <p class="text-xs text-slate-500 mt-1">Số nhỏ hơn sẽ hiển thị trước</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Icon/Emoji (tùy chọn)
                        </label>
                        <input type="text" name="icon" id="tool_icon" maxlength="20"
                            placeholder="VD: 🚚, 💳, 📦, ⚡"
                            class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-2xl text-center">
                        <p class="text-xs text-slate-500 mt-1">Emoji hoặc icon để làm nổi bật</p>
                    </div>

                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Tên hiển thị (cho admin) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="display_name" id="tool_display_name" required
                            placeholder="VD: Phí vận chuyển"
                            class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 text-base">
                    </div>
                </div>

                
                <div class="border-t border-slate-200 pt-4">
                    <details class="group">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700 flex items-center gap-2">
                            <i class="fa-solid fa-chevron-down group-open:rotate-180 transition-transform"></i>
                            Các trường kỹ thuật (Tùy chọn - chỉ dành cho developer)
                        </summary>
                        <div class="mt-4 space-y-4 pl-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Tên kỹ thuật (name)
                                </label>
                                <input type="text" name="name" id="tool_name"
                                    placeholder="VD: shipping_fee"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Mô tả
                                </label>
                                <textarea name="description" id="tool_description" rows="2"
                                    placeholder="Mô tả ngắn gọn"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                            </div>
                        </div>
                    </details>
                </div>

                
                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-lg">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" id="tool_is_active" value="1" checked
                            class="w-5 h-5 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="ml-3 text-sm font-medium text-slate-700">Kích hoạt câu hỏi này</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="flex-1 px-6 py-3 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition shadow-sm hover:shadow-md flex items-center justify-center gap-2 font-semibold">
                    <i class="fa-solid fa-save"></i> Lưu câu hỏi
                </button>
                <button type="button" onclick="closeModal()"
                    class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition font-semibold">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('toolModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    // Reset form
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
}

function closeModal() {
    document.getElementById('toolModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function editTool(tool) {
    // Parse nếu là string
    if (typeof tool === 'string') {
        try {
            tool = JSON.parse(tool);
        } catch (e) {
            console.error('Failed to parse tool:', e);
            return;
        }
    }

    // Fill form với dữ liệu tool
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

    // Mở modal
    openModal();

    // Scroll to top của modal
    document.querySelector('#toolModal .bg-white').scrollTop = 0;
}

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/tools.blade.php ENDPATH**/ ?>