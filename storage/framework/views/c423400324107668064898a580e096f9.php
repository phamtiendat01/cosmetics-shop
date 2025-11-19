
<?php $__env->startSection('title', 'CosmeBot - Quản lý Chủ đề (Intents)'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-title">Quản lý Chủ đề (Intents)</div>
    <div class="toolbar-actions">
        <button onclick="openModal()" class="btn btn-primary btn-sm">+ Thêm</button>
    </div>
</div>


<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.1s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($intents->total()); ?></div>
        <div class="text-xs text-slate-500">Tổng chủ đề</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.2s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($intents->where('is_active', true)->count()); ?></div>
        <div class="text-xs text-slate-500">Đang hoạt động</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.3s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($intents->where('is_active', false)->count()); ?></div>
        <div class="text-xs text-slate-500">Đã tắt</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.4s backwards;">
        <div class="text-2xl font-bold mb-0.5"><?php echo e($intents->sum(fn($i) => count($i->examples ?? []))); ?></div>
        <div class="text-xs text-slate-500">Tổng examples</div>
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
                <th>Tên chủ đề</th>
                <th>Examples</th>
                <th>Tools</th>
                <th>Độ ưu tiên</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $intents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $intent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr style="animation-delay: <?php echo e(0.5 + ($index * 0.03)); ?>s;">
                <td>
                    <div class="font-medium mb-0.5"><?php echo e($intent->display_name); ?></div>
                    <div class="text-xs text-slate-500 font-mono bg-slate-50 px-1.5 py-0.5 rounded inline-block"><?php echo e($intent->name); ?></div>
                    <?php if($intent->description): ?>
                    <div class="text-xs text-slate-600 mt-1 max-w-md line-clamp-1"><?php echo e(Str::limit($intent->description, 60)); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge"><?php echo e(count($intent->examples ?? [])); ?> câu</span>
                </td>
                <td>
                    <?php
                    $tools = $intent->config['tools'] ?? [];
                    ?>
                    <?php if(!empty($tools)): ?>
                    <div class="flex flex-wrap gap-1">
                        <?php $__currentLoopData = array_slice($tools, 0, 2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $toolName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="badge"><?php echo e($toolName); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if(count($tools) > 2): ?>
                        <span class="badge">+<?php echo e(count($tools) - 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-xs text-slate-400">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="text-xs font-medium"><?php echo e($intent->priority ?? 0); ?></span>
                </td>
                <td>
                    <?php if($intent->is_active): ?>
                    <span class="badge badge-green"><span class="badge-dot"></span>Hoạt động</span>
                    <?php else: ?>
                    <span class="badge badge-red"><span class="badge-dot"></span>Tắt</span>
                    <?php endif; ?>
                </td>
                <td class="col-actions">
                    <button onclick="editIntent(<?php echo e(json_encode($intent)); ?>)" class="btn btn-table btn-outline">Sửa</button>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="py-6 text-center text-slate-500">Chưa có chủ đề nào.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php if($intents->hasPages()): ?>
<div class="pagination mt-3">
    <?php echo e($intents->onEachSide(1)->links('pagination::tailwind')); ?>

</div>
<?php endif; ?>


<div id="intentModal" class="modal hidden">
    <div class="modal-card max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <form method="POST" action="<?php echo e(route('admin.bot.intents.store')); ?>" class="flex flex-col h-full" id="intentForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="intent_id">

            
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="font-semibold" id="modal_title">Thêm chủ đề mới</h3>
                <button type="button" onclick="closeModal()" class="btn btn-ghost btn-sm !p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            
            <div class="flex border-b border-slate-200 bg-white px-5">
                <button type="button" onclick="switchTab(1)" id="tab-btn-1"
                    class="px-4 py-3 text-sm font-semibold border-b-2 border-rose-600 text-rose-600 transition">
                    <i class="fa-solid fa-info-circle mr-1.5"></i> Thông tin cơ bản
                </button>
                <button type="button" onclick="switchTab(2)" id="tab-btn-2"
                    class="px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-600 hover:text-rose-600 transition">
                    <i class="fa-solid fa-list mr-1.5"></i> Examples
                </button>
                <button type="button" onclick="switchTab(3)" id="tab-btn-3"
                    class="px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-600 hover:text-rose-600 transition">
                    <i class="fa-solid fa-file-lines mr-1.5"></i> Response Template
                </button>
                <button type="button" onclick="switchTab(4)" id="tab-btn-4"
                    class="px-4 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-600 hover:text-rose-600 transition">
                    <i class="fa-solid fa-cog mr-1.5"></i> Configuration
                </button>
            </div>

            
            <div class="flex-1 overflow-hidden p-5 flex flex-col min-h-0">
                
                <div id="tab-content-1" class="tab-content space-y-4 overflow-y-auto max-h-[calc(90vh-250px)] pr-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Tên hiển thị <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="display_name" id="intent_display_name" required
                            placeholder="VD: Tìm sản phẩm"
                            class="form-control">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Tên kỹ thuật (name) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="intent_name" required
                            placeholder="VD: product_search"
                            class="form-control font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mô tả</label>
                        <textarea name="description" id="intent_description" rows="3"
                            placeholder="VD: Khách hàng muốn tìm kiếm sản phẩm theo tiêu chí..."
                            class="form-control"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Độ ưu tiên (0-1000)</label>
                            <input type="number" name="priority" id="intent_priority" value="0" min="0" max="1000"
                                class="form-control">
                        </div>
                        <div class="flex items-center pt-7">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" id="intent_is_active" value="1" checked
                                    class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                                <span class="ml-2 text-xs font-semibold text-slate-700">Kích hoạt</span>
                            </label>
                        </div>
                    </div>
                </div>

                
                <div id="tab-content-2" class="tab-content hidden flex flex-col h-full max-h-[calc(90vh-250px)]">
                    <div class="flex items-center justify-between mb-3 flex-shrink-0">
                        <h4 class="text-sm font-semibold text-slate-900">Examples</h4>
                        <button type="button" onclick="addExample()"
                            class="px-3 py-1.5 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-plus text-xs"></i> Thêm example
                        </button>
                    </div>

                    <div id="examples-container" class="space-y-2 overflow-y-auto flex-1 min-h-0 pr-2">
                        <!-- Examples sẽ được thêm vào đây bằng JS -->
                    </div>
                </div>

                
                <div id="tab-content-3" class="tab-content hidden space-y-4 overflow-y-auto max-h-[calc(90vh-250px)] pr-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Template câu trả lời
                        </label>
                        <textarea name="response_template" id="intent_response_template" rows="12"
                            placeholder="{greeting} Mình hiểu bạn đang {intent_description}!

{if_has_entities}
Dựa vào thông tin bạn cung cấp:
- Loại da: {skin_types}
- Ngân sách: {budget}
{endif}

{if_has_products}
Mình gợi ý cho bạn {product_count} sản phẩm:
{products_list}
{endif}

{if_no_products}
Để mình tư vấn chính xác hơn, bạn có thể cho mình biết:
{follow_up_questions}
{endif}"
                            class="form-control font-mono text-xs resize-none"></textarea>
                        <p class="text-xs text-slate-500 mt-1.5">
                            Variables: <code class="bg-slate-100 px-1 py-0.5 rounded">{skin_types}</code>, 
                            <code class="bg-slate-100 px-1 py-0.5 rounded">{budget}</code>, 
                            <code class="bg-slate-100 px-1 py-0.5 rounded">{products_list}</code>
                        </p>
                    </div>
                </div>

                
                <div id="tab-content-4" class="tab-content hidden space-y-4 overflow-y-auto max-h-[calc(90vh-250px)] pr-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Required Entities</label>
                            <div class="space-y-2">
                                <?php
                                $entityOptions = ['skin_types', 'budget', 'product_type', 'concerns', 'ingredients'];
                                ?>
                                <?php $__currentLoopData = $entityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="required_entities[]" value="<?php echo e($entity); ?>"
                                        class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                                    <span class="text-xs text-slate-700"><?php echo e($entity); ?></span>
                                </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Optional Entities</label>
                            <div class="space-y-2">
                                <?php $__currentLoopData = $entityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="optional_entities[]" value="<?php echo e($entity); ?>"
                                        class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                                    <span class="text-xs text-slate-700"><?php echo e($entity); ?></span>
                                </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tools Mapping</label>
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 max-h-48 overflow-y-auto">
                            <div class="space-y-2">
                                <?php $__currentLoopData = $availableTools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tool): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex items-center gap-2 cursor-pointer hover:bg-white p-2 rounded transition">
                                    <input type="checkbox" name="tools[]" value="<?php echo e($tool->name); ?>"
                                        class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                                    <div class="flex-1">
                                        <div class="text-xs font-semibold text-slate-900"><?php echo e($tool->display_name); ?></div>
                                        <div class="text-xs text-slate-500 font-mono"><?php echo e($tool->name); ?></div>
                                    </div>
                                </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Follow-up Questions</label>
                        <div id="follow-up-questions-container" class="space-y-2">
                            <!-- Follow-up questions sẽ được thêm vào đây bằng JS -->
                        </div>
                        <button type="button" onclick="addFollowUpQuestion()"
                            class="mt-2 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition text-xs font-semibold">
                            <i class="fa-solid fa-plus text-xs mr-1"></i> Thêm câu hỏi
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confidence Threshold (0.0 - 1.0)</label>
                        <input type="number" name="confidence_threshold" id="intent_confidence_threshold" 
                            value="0.7" min="0" max="1" step="0.1"
                            class="form-control">
                    </div>
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
let currentTab = 1;
let exampleCount = 0;
let followUpCount = 0;

function switchTab(tabNum) {
    for (let i = 1; i <= 4; i++) {
        document.getElementById(`tab-content-${i}`).classList.add('hidden');
        const btn = document.getElementById(`tab-btn-${i}`);
        btn.classList.remove('border-rose-600', 'text-rose-600');
        btn.classList.add('border-transparent', 'text-slate-600');
    }
    
    document.getElementById(`tab-content-${tabNum}`).classList.remove('hidden');
    const btn = document.getElementById(`tab-btn-${tabNum}`);
    btn.classList.remove('border-transparent', 'text-slate-600');
    btn.classList.add('border-rose-600', 'text-rose-600');
    
    currentTab = tabNum;
}

function resetForm() {
    document.getElementById('intent_id').value = '';
    document.getElementById('intent_display_name').value = '';
    document.getElementById('intent_name').value = '';
    document.getElementById('intent_description').value = '';
    document.getElementById('intent_priority').value = '0';
    document.getElementById('intent_is_active').checked = true;
    document.getElementById('intent_response_template').value = '';
    document.getElementById('intent_confidence_threshold').value = '0.7';
    document.getElementById('modal_title').textContent = 'Thêm chủ đề mới';
    
    document.getElementById('examples-container').innerHTML = '';
    exampleCount = 0;
    
    document.getElementById('follow-up-questions-container').innerHTML = '';
    followUpCount = 0;
    
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('intent_is_active').checked = true;
    
    switchTab(1);
}

function openModal() {
    resetForm();
    document.getElementById('intentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('intentModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function editIntent(intent) {
    if (typeof intent === 'string') {
        try {
            intent = JSON.parse(intent);
        } catch (e) {
            console.error('Failed to parse intent:', e);
            return;
        }
    }
    
    document.getElementById('intent_id').value = intent.id || '';
    document.getElementById('intent_display_name').value = intent.display_name || '';
    document.getElementById('intent_name').value = intent.name || '';
    document.getElementById('intent_description').value = intent.description || '';
    document.getElementById('intent_priority').value = intent.priority ?? 0;
    document.getElementById('intent_is_active').checked = intent.is_active !== undefined ? intent.is_active : true;
    document.getElementById('modal_title').textContent = 'Sửa chủ đề: ' + (intent.display_name || '');
    
    const examples = intent.examples || [];
    document.getElementById('examples-container').innerHTML = '';
    exampleCount = 0;
    examples.forEach(example => {
        addExample(example);
    });
    
    document.getElementById('intent_response_template').value = intent.config?.response_template || '';
    
    const config = intent.config || {};
    
    document.querySelectorAll('input[name="required_entities[]"]').forEach(cb => {
        cb.checked = (config.required_entities || []).includes(cb.value);
    });
    
    document.querySelectorAll('input[name="optional_entities[]"]').forEach(cb => {
        cb.checked = (config.optional_entities || []).includes(cb.value);
    });
    
    document.querySelectorAll('input[name="tools[]"]').forEach(cb => {
        cb.checked = (config.tools || []).includes(cb.value);
    });
    
    const followUps = config.follow_up_questions || [];
    document.getElementById('follow-up-questions-container').innerHTML = '';
    followUpCount = 0;
    followUps.forEach(question => {
        addFollowUpQuestion(question);
    });
    
    document.getElementById('intent_confidence_threshold').value = config.confidence_threshold ?? 0.7;
    
    document.getElementById('intentModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    switchTab(1);
}

function addExample(value = '') {
    exampleCount++;
    const container = document.getElementById('examples-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2';
    div.innerHTML = `
        <input type="text" name="examples[]" value="${value.replace(/"/g, '&quot;')}" 
            placeholder="VD: tìm serum cho da dầu"
            class="form-control">
        <button type="button" onclick="this.parentElement.remove()"
            class="px-2.5 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
            <i class="fa-solid fa-trash text-xs"></i>
        </button>
    `;
    container.appendChild(div);
}

function addFollowUpQuestion(value = '') {
    followUpCount++;
    const container = document.getElementById('follow-up-questions-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2';
    div.innerHTML = `
        <input type="text" name="follow_up_questions[]" value="${value.replace(/"/g, '&quot;')}" 
            placeholder="VD: Bạn có thể cho mình biết loại da của bạn không?"
            class="form-control">
        <button type="button" onclick="this.parentElement.remove()"
            class="px-2.5 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
            <i class="fa-solid fa-trash text-xs"></i>
        </button>
    `;
    container.appendChild(div);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

document.getElementById('intentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/bot/intents.blade.php ENDPATH**/ ?>