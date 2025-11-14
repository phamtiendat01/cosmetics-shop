
<?php $__env->startSection('title','Sửa sản phẩm'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-3" data-auto-dismiss="3000"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<?php if($errors->any()): ?>
<div class="alert alert-danger mb-3" data-auto-dismiss="3000">
    <b>Lỗi:</b>
    <ul class="list-disc pl-5 mt-1">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($msg); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" action="<?php echo e(route('admin.products.update', $product)); ?>" enctype="multipart/form-data" class="space-y-4">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

    <div class="card p-3">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="label">Tên sản phẩm</label>
                <input name="name" value="<?php echo e(old('name', $product->name)); ?>" class="form-control" required>
            </div>

            <div>
                <label class="label">Slug</label>
                <input name="slug" value="<?php echo e(old('slug', $product->slug)); ?>" class="form-control">
            </div>

            <div>
                <label class="label">Danh mục</label>
                <select name="category_id" id="catSelect" class="form-control">
                    <option value="">-- Chọn --</option>
                    <?php $__currentLoopData = $categoryGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parentName => $children): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <optgroup label="<?php echo e($parentName); ?>">
                        <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c['id']); ?>" <?php if(old('category_id', $product->category_id)==$c['id']): echo 'selected'; endif; ?>><?php echo e($c['name']); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </optgroup>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <div class="help mt-1 text-xs text-slate-500">Chỉ liệt kê danh mục <b>con</b>.</div>
            </div>

            <div>
                <label class="label">Thương hiệu</label>
                <select name="brand_id" id="brandSelect" class="form-control">
                    <option value="">-- Chọn --</option>
                    <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($b->id); ?>" <?php if(old('brand_id',$product->brand_id)==$b->id): echo 'selected'; endif; ?>><?php echo e($b->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="label">Ảnh đại diện</label>
                <input type="file" name="thumbnail" class="form-control">
                <div class="help mt-1">Ảnh hiện tại:</div>
                <img class="mt-1 w-24 h-24 rounded object-cover"
                    src="<?php echo e($product->thumbnail ? asset('storage/'.$product->thumbnail) : 'https://placehold.co/120x120?text=IMG'); ?>"
                    alt="thumb">
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả ngắn</label>
                <textarea name="short_desc" rows="3" class="form-control"><?php echo e(old('short_desc', $product->short_desc)); ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="label">Mô tả chi tiết</label>
                <div class="editor-card">
                    <textarea id="long_desc" name="long_desc" rows="12" class="form-control"><?php echo e(old('long_desc', $product->long_desc)); ?></textarea>
                </div>
                <div class="help mt-1 text-xs text-slate-500">
                    Soạn thảo có định dạng. Dùng <b>Heading 2</b> cho mục lớn, <b>Heading 3</b> cho mục con.
                </div>
            </div>
        </div>
    </div>

    
    <div class="card p-3">
        <div class="toolbar mb-4">
            <div class="font-semibold text-sm flex items-center gap-2">
                <i class="fa-solid fa-robot text-rose-600"></i>
                Thông tin cho Chatbot (CosmeBot)
            </div>
            <div class="text-xs text-slate-500">Các thông tin này giúp bot tư vấn chính xác hơn cho khách hàng</div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            
            <div>
                <label class="label">Loại da phù hợp <span class="text-red-500">*</span></label>
                <div class="space-y-2">
                    <?php
                    $skinTypes = [
                        'oily' => 'Da dầu',
                        'dry' => 'Da khô',
                        'combination' => 'Da hỗn hợp',
                        'sensitive' => 'Da nhạy cảm',
                        'normal' => 'Da thường',
                    ];
                    $oldSkinTypes = old('skin_types', $product->skin_types ?? []);
                    ?>
                    <?php $__currentLoopData = $skinTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="skin_types[]" value="<?php echo e($key); ?>" 
                            <?php echo e(in_array($key, $oldSkinTypes) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm"><?php echo e($label); ?></span>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            
            <div>
                <label class="label">Vấn đề da giải quyết</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                    <?php
                    $concerns = [
                        'acne' => 'Mụn',
                        'blackheads' => 'Đầu đen',
                        'dark_spots' => 'Thâm',
                        'melasma' => 'Nám',
                        'freckles' => 'Tàn nhang',
                        'pores' => 'Lỗ chân lông',
                        'aging' => 'Lão hóa',
                        'hydration' => 'Dưỡng ẩm',
                        'sunburn' => 'Cháy nắng',
                    ];
                    $oldConcerns = old('concerns', $product->concerns ?? []);
                    ?>
                    <?php $__currentLoopData = $concerns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="concerns[]" value="<?php echo e($key); ?>" 
                            <?php echo e(in_array($key, $oldConcerns) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm"><?php echo e($label); ?></span>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            
            <div>
                <label class="label">Thành phần chính</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                    <?php
                    $ingredients = [
                        'hyaluronic_acid' => 'Hyaluronic Acid',
                        'niacinamide' => 'Niacinamide',
                        'retinol' => 'Retinol',
                        'vitamin_c' => 'Vitamin C',
                        'salicylic_acid' => 'Salicylic Acid',
                        'glycolic_acid' => 'Glycolic Acid',
                        'peptides' => 'Peptides',
                        'ceramides' => 'Ceramides',
                        'snail_mucin' => 'Snail Mucin',
                        'centella' => 'Centella Asiatica',
                        'tea_tree' => 'Tea Tree Oil',
                        'aloe_vera' => 'Aloe Vera',
                    ];
                    $oldIngredients = old('ingredients', $product->ingredients ?? []);
                    ?>
                    <?php $__currentLoopData = $ingredients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="ingredients[]" value="<?php echo e($key); ?>" 
                            <?php echo e(in_array($key, $oldIngredients) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm"><?php echo e($label); ?></span>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            
            <div>
                <label class="label">Công dụng chính</label>
                <textarea name="benefits" rows="4" class="form-control" 
                    placeholder="VD: Dưỡng ẩm sâu, làm mờ thâm, se khít lỗ chân lông..."><?php echo e(old('benefits', $product->benefits)); ?></textarea>
            </div>

            
            <div>
                <label class="label">Hướng dẫn sử dụng</label>
                <textarea name="usage_instructions" rows="4" class="form-control" 
                    placeholder="VD: Thoa đều lên mặt sau khi làm sạch, dùng buổi tối..."><?php echo e(old('usage_instructions', $product->usage_instructions)); ?></textarea>
            </div>

            
            <div>
                <label class="label">Độ tuổi phù hợp</label>
                <select name="age_range" class="form-control">
                    <option value="">-- Chọn --</option>
                    <option value="teen" <?php echo e(old('age_range', $product->age_range) === 'teen' ? 'selected' : ''); ?>>Thiếu niên (13-18)</option>
                    <option value="adult" <?php echo e(old('age_range', $product->age_range) === 'adult' ? 'selected' : ''); ?>>Người trẻ (19-35)</option>
                    <option value="mature" <?php echo e(old('age_range', $product->age_range) === 'mature' ? 'selected' : ''); ?>>Trung niên (36+)</option>
                    <option value="all" <?php echo e(old('age_range', $product->age_range) === 'all' ? 'selected' : ''); ?>>Mọi độ tuổi</option>
                </select>
            </div>

            
            <div>
                <label class="label">Giới tính</label>
                <select name="gender" class="form-control">
                    <option value="unisex" <?php echo e(old('gender', $product->gender ?? 'unisex') === 'unisex' ? 'selected' : ''); ?>>Unisex (Cả nam và nữ)</option>
                    <option value="female" <?php echo e(old('gender', $product->gender) === 'female' ? 'selected' : ''); ?>>Nữ</option>
                    <option value="male" <?php echo e(old('gender', $product->gender) === 'male' ? 'selected' : ''); ?>>Nam</option>
                </select>
            </div>

            
            <div>
                <label class="label">Loại sản phẩm</label>
                <select name="product_type" class="form-control">
                    <option value="">-- Chọn --</option>
                    <option value="serum" <?php echo e(old('product_type', $product->product_type) === 'serum' ? 'selected' : ''); ?>>Serum</option>
                    <option value="cream" <?php echo e(old('product_type', $product->product_type) === 'cream' ? 'selected' : ''); ?>>Kem</option>
                    <option value="toner" <?php echo e(old('product_type', $product->product_type) === 'toner' ? 'selected' : ''); ?>>Toner</option>
                    <option value="cleanser" <?php echo e(old('product_type', $product->product_type) === 'cleanser' ? 'selected' : ''); ?>>Sữa rửa mặt</option>
                    <option value="moisturizer" <?php echo e(old('product_type', $product->product_type) === 'moisturizer' ? 'selected' : ''); ?>>Kem dưỡng ẩm</option>
                    <option value="sunscreen" <?php echo e(old('product_type', $product->product_type) === 'sunscreen' ? 'selected' : ''); ?>>Kem chống nắng</option>
                    <option value="mask" <?php echo e(old('product_type', $product->product_type) === 'mask' ? 'selected' : ''); ?>>Mặt nạ</option>
                    <option value="essence" <?php echo e(old('product_type', $product->product_type) === 'essence' ? 'selected' : ''); ?>>Essence</option>
                    <option value="eye_cream" <?php echo e(old('product_type', $product->product_type) === 'eye_cream' ? 'selected' : ''); ?>>Kem mắt</option>
                    <option value="other" <?php echo e(old('product_type', $product->product_type) === 'other' ? 'selected' : ''); ?>>Khác</option>
                </select>
            </div>

            
            <div>
                <label class="label">Kết cấu</label>
                <select name="texture" class="form-control">
                    <option value="">-- Chọn --</option>
                    <option value="gel" <?php echo e(old('texture', $product->texture) === 'gel' ? 'selected' : ''); ?>>Gel</option>
                    <option value="cream" <?php echo e(old('texture', $product->texture) === 'cream' ? 'selected' : ''); ?>>Cream</option>
                    <option value="liquid" <?php echo e(old('texture', $product->texture) === 'liquid' ? 'selected' : ''); ?>>Liquid</option>
                    <option value="foam" <?php echo e(old('texture', $product->texture) === 'foam' ? 'selected' : ''); ?>>Foam</option>
                    <option value="oil" <?php echo e(old('texture', $product->texture) === 'oil' ? 'selected' : ''); ?>>Oil</option>
                    <option value="balm" <?php echo e(old('texture', $product->texture) === 'balm' ? 'selected' : ''); ?>>Balm</option>
                    <option value="powder" <?php echo e(old('texture', $product->texture) === 'powder' ? 'selected' : ''); ?>>Powder</option>
                    <option value="spray" <?php echo e(old('texture', $product->texture) === 'spray' ? 'selected' : ''); ?>>Spray</option>
                </select>
            </div>

            
            <div>
                <label class="label">Chỉ số SPF (chỉ cho kem chống nắng)</label>
                <input type="number" name="spf" min="0" max="100" class="form-control" 
                    placeholder="VD: 30, 50" value="<?php echo e(old('spf', $product->spf)); ?>">
            </div>

            
            <div class="md:col-span-2">
                <label class="label">Đặc điểm</label>
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="fragrance_free" value="1" 
                            <?php echo e(old('fragrance_free', $product->fragrance_free) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">Không mùi</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="cruelty_free" value="1" 
                            <?php echo e(old('cruelty_free', $product->cruelty_free) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">Không test trên động vật</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="vegan" value="1" 
                            <?php echo e(old('vegan', $product->vegan) ? 'checked' : ''); ?>

                            class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                        <span class="text-sm">Thuần chay</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card p-3">
        <div class="toolbar mb-2">
            <div class="font-semibold text-sm">Biến thể & Giá</div>
            <button type="button" onclick="addVariantRow()" class="btn btn-outline btn-sm">+ Thêm biến thể</button>
        </div>

        <div class="variants-header">
            <div>Tên biến thể</div>
            <div>SKU</div>
            <div>Giá</div>
            <div>Giá gốc</div>
            <div>Tồn kho</div>
            <div>Cảnh báo</div>
        </div>

        <div id="variantList" class="space-y-2">
            <?php $oldVars = old('variants'); ?>

            
            <?php if(is_array($oldVars)): ?>
            <?php $__currentLoopData = $oldVars; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="variant-row">
                <?php if(!empty($v['id'])): ?>
                <input type="hidden" name="variants[<?php echo e($i); ?>][id]" value="<?php echo e($v['id']); ?>">
                <?php endif; ?>

                <input name="variants[<?php echo e($i); ?>][name]" class="form-control" placeholder="VD: 30ml" value="<?php echo e($v['name'] ?? ''); ?>">
                <input name="variants[<?php echo e($i); ?>][sku]" class="form-control" placeholder="SKU" value="<?php echo e($v['sku'] ?? ''); ?>">
                <input name="variants[<?php echo e($i); ?>][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá" value="<?php echo e($v['price'] ?? ''); ?>">
                <input name="variants[<?php echo e($i); ?>][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc" value="<?php echo e($v['compare_at_price'] ?? ''); ?>">

                
                <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">
                        <?php echo e($v['qty_in_stock'] ?? 0); ?>

                    </span>
                    <?php if(!empty($v['id'])): ?>
                    <button type="button" class="btn btn-soft btn-sm" data-open="#inv-modal-<?php echo e($v['id']); ?>">Điều chỉnh</button>
                    <?php endif; ?>
                </div>

                <div class="row-actions">
                    <input name="variants[<?php echo e($i); ?>][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo" value="<?php echo e($v['low_stock_threshold'] ?? 0); ?>">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <?php else: ?>
            <?php $__empty_1 = true; $__currentLoopData = $product->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $inv = $v->inventory; ?>
            <div class="variant-row">
                <input type="hidden" name="variants[<?php echo e($idx); ?>][id]" value="<?php echo e($v->id); ?>">
                <input name="variants[<?php echo e($idx); ?>][name]" value="<?php echo e($v->name); ?>" class="form-control" placeholder="VD: 30ml">
                <input name="variants[<?php echo e($idx); ?>][sku]" value="<?php echo e($v->sku); ?>" class="form-control" placeholder="SKU">
                <input name="variants[<?php echo e($idx); ?>][price]" value="<?php echo e($v->price); ?>" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
                <input name="variants[<?php echo e($idx); ?>][compare_at_price]" value="<?php echo e($v->compare_at_price); ?>" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">

                
                <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">
                        <?php echo e($inv->qty_in_stock ?? 0); ?>

                    </span>
                    <button type="button" class="btn btn-soft btn-sm" data-open="#inv-modal-<?php echo e($v->id); ?>">Điều chỉnh</button>
                </div>

                <div class="row-actions">
                    <input name="variants[<?php echo e($idx); ?>][low_stock_threshold]" value="<?php echo e($inv->low_stock_threshold ?? 0); ?>" class="form-control" type="number" min="0" placeholder="Cảnh báo">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>

            
            <?php $__env->startPush('modals'); ?>
            <div id="inv-modal-<?php echo e($v->id); ?>" class="modal hidden js-inv-modal" aria-hidden="true">
                <div class="modal-card max-w-[560px] w-[92vw] p-0 overflow-hidden rounded-2xl" role="dialog" aria-labelledby="inv-title-<?php echo e($v->id); ?>">
                    
                    <div class="px-6 py-4 border-b bg-white flex items-start justify-between">
                        <div>
                            <div id="inv-title-<?php echo e($v->id); ?>" class="text-base font-semibold">
                                Điều chỉnh kho — <?php echo e($v->name); ?> <a class="text-brand-600 hover:underline">(<?php echo e($v->sku); ?>)</a>
                            </div>
                            <div class="mt-1 text-xs text-slate-600 space-x-2">
                                <span>Hiện có</span>
                                <span class="badge"><?php echo e((int)($inv->qty_in_stock ?? 0)); ?></span>
                                <span>→ Sau lưu</span>
                                <span class="badge badge-live js-stock-result"><?php echo e((int)($inv->qty_in_stock ?? 0)); ?></span>
                                <span class="hidden js-stock-current" data-cur="<?php echo e((int)($inv->qty_in_stock ?? 0)); ?>"></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm !px-2" title="Đóng" data-close="#inv-modal-<?php echo e($v->id); ?>">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    
                    <form method="POST" action="<?php echo e(route('admin.variants.inventory.adjust', $v)); ?>" class="p-6 space-y-5 bg-white">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="mode" class="js-mode-input" value="delta">

                        
                        <div class="grid grid-cols-2 bg-slate-50 p-1 rounded-lg border border-slate-200">
                            <button type="button" class="tab-btn is-active js-mode" data-mode="delta">
                                <i class="fa-solid fa-plus-minus"></i> Cộng/Trừ
                            </button>
                            <button type="button" class="tab-btn js-mode" data-mode="set">
                                <i class="fa-regular fa-pen-to-square"></i> Đặt bằng
                            </button>
                        </div>

                        <div class="grid md:grid-cols-2 gap-5">
                            
                            <div class="js-delta-wrap">
                                <label class="text-xs text-slate-500">+ / − số lượng</label>
                                <input class="form-control js-delta" name="delta" type="number" step="1" inputmode="numeric" placeholder="+100 (nhập) hoặc -5 (hỏng)">
                                <div class="chips mt-2">
                                    <button type="button" class="chip js-quick" data-val="+10">+10</button>
                                    <button type="button" class="chip js-quick" data-val="+50">+50</button>
                                    <button type="button" class="chip js-quick" data-val="+100">+100</button>
                                    <button type="button" class="chip js-quick" data-val="-1">-1</button>
                                    <button type="button" class="chip js-quick" data-val="-5">-5</button>
                                </div>
                            </div>

                            
                            <div class="js-set-wrap opacity-50 pointer-events-none">
                                <label class="text-xs text-slate-500">Đặt tồn kho bằng</label>
                                <input class="form-control js-set" name="qty" type="number" min="0" placeholder="<?php echo e((int)($inv->qty_in_stock ?? 0)); ?>">
                                <div class="text-xs text-slate-400 mt-1">Nhập giá trị tuyệt đối muốn đặt.</div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-slate-500">Lý do</label>
                                <select name="reason" class="form-control">
                                    <option value="">-- Chọn lý do --</option>
                                    <option value="restock">Nhập hàng</option>
                                    <option value="damage">Hỏng/lỗi</option>
                                    <option value="stock_take">Kiểm kho</option>
                                    <option value="manual-set">Đặt bằng (thủ công)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500">Ghi chú</label>
                                <input name="note" class="form-control" placeholder="Ví dụ: nhập lô 09/2025">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn btn-outline btn-sm" data-close="#inv-modal-<?php echo e($v->id); ?>">Hủy</button>
                            <button class="btn btn-primary btn-sm">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php $__env->stopPush(); ?>
            
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            
            <div class="variant-row">
                <input name="variants[0][name]" class="form-control" placeholder="VD: 30ml">
                <input name="variants[0][sku]" class="form-control" placeholder="SKU">
                <input name="variants[0][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
                <input name="variants[0][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">
                <div class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">0</span>
                </div>
                <div class="row-actions">
                    <input name="variants[0][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo">
                    <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="<?php echo e(route('admin.products.index')); ?>" class="btn btn-outline">← Danh sách</a>
        <button type="submit" class="btn btn-primary !text-black">Lưu thay đổi</button>
    </div>
</form>


<?php echo $__env->yieldPushContent('modals'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* ✨ Glass editor card */
    .editor-card {
        border-radius: 16px;
        overflow: hidden;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: saturate(140%) blur(6px);
        border: 1px solid #eef2f7;
        box-shadow: 0 12px 34px rgba(2, 6, 23, .06)
    }

    .editor-card .ck-editor__top {
        background: #fff;
        border-bottom: 1px solid #f1f5f9 !important;
        position: sticky;
        top: 0;
        z-index: 20
    }

    .editor-card .ck-toolbar {
        border: 0 !important;
        box-shadow: none !important
    }

    .editor-card .ck-editor__editable {
        min-height: 360px;
        padding: 18px 20px !important;
        border: 0 !important;
        box-shadow: none !important;
        font-size: 15px;
        line-height: 1.75;
        color: #0f172a
    }

    .editor-card .ck-editor__editable.ck-focused {
        box-shadow: 0 0 0 3px rgba(244, 63, 94, .16) !important;
        outline: 1px solid #fb7185 !important
    }

    /* Badge nhỏ (Hiện có / Sau lưu) */
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 9999px;
        background: #e2e8f0;
        color: #0f172a;
        font-weight: 700;
        font-size: 12px
    }

    .badge-live {
        background: #dcfce7;
        color: #166534
    }

    /* Tab button cho 2 chế độ */
    .tab-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 12px;
        border-radius: 10px;
        font-weight: 700;
        color: #334155
    }

    .tab-btn.is-active {
        background: #111827;
        color: #fff
    }

    /* Quick chips */
    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px
    }

    .chip {
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        background: #fff;
        color: #111827
    }

    .chip:hover {
        background: #111827;
        color: #fff
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>

<script>
    if (document.getElementById('catSelect')) new TomSelect('#catSelect', {
        create: false,
        maxOptions: 500
    });
    if (document.getElementById('brandSelect')) new TomSelect('#brandSelect', {
        create: false,
        maxOptions: 500
    });

    function removeRow(btn) {
        btn.closest('.variant-row')?.remove();
    }

    function addVariantRow() {
        const list = document.getElementById('variantList');
        const idx = list.querySelectorAll('.variant-row').length;
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.innerHTML = `
          <input name="variants[${idx}][name]"  class="form-control" placeholder="VD: 30ml">
          <input name="variants[${idx}][sku]"   class="form-control" placeholder="SKU">
          <input name="variants[${idx}][price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá">
          <input name="variants[${idx}][compare_at_price]" class="form-control" type="number" step="0.01" min="0" placeholder="Giá gốc">
          <div class="inline-flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-20 h-9 rounded border bg-gray-50 select-none">0</span>
          </div>
          <div class="row-actions">
            <input name="variants[${idx}][low_stock_threshold]" class="form-control" type="number" min="0" placeholder="Cảnh báo">
            <button type="button" class="btn btn-outline btn-sm" onclick="removeRow(this)">X</button>
          </div>`;
        list.appendChild(row);
    }

    // Toggle modal mở/đóng (dùng chung)
    document.addEventListener('click', function(e) {
        const openBtn = e.target.closest('[data-open]');
        const closeBtn = e.target.closest('[data-close]');
        if (openBtn) {
            const sel = openBtn.getAttribute('data-open');
            const m = document.querySelector(sel);
            if (m) {
                m.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }
        if (closeBtn) {
            const sel = closeBtn.getAttribute('data-close');
            const m = document.querySelector(sel);
            if (m) {
                m.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }
    });
    document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) {
                m.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });

    // ===== Logic UI cho popup điều chỉnh kho =====
    document.querySelectorAll('.js-inv-modal').forEach(function(modal) {
        const curEl = modal.querySelector('.js-stock-current');
        const resEl = modal.querySelector('.js-stock-result');
        const modeInput = modal.querySelector('.js-mode-input');
        const btns = modal.querySelectorAll('.js-mode');
        const deltaWrap = modal.querySelector('.js-delta-wrap');
        const setWrap = modal.querySelector('.js-set-wrap');
        const deltaEl = modal.querySelector('.js-delta');
        const setEl = modal.querySelector('.js-set');
        const cur = Number(curEl?.dataset.cur || 0);

        function clamp(n) {
            return Math.max(0, n | 0);
        }

        function setMode(mode) {
            modeInput.value = mode;
            btns.forEach(b => b.classList.toggle('is-active', b.dataset.mode === mode));
            if (mode === 'delta') {
                deltaWrap.classList.remove('opacity-50', 'pointer-events-none');
                setWrap.classList.add('opacity-50', 'pointer-events-none');
            } else {
                setWrap.classList.remove('opacity-50', 'pointer-events-none');
                deltaWrap.classList.add('opacity-50', 'pointer-events-none');
            }
            updatePreview();
        }

        function updatePreview() {
            const mode = modeInput.value;
            const delta = Number(deltaEl?.value || 0);
            const set = Number(setEl?.value || 0);
            const next = mode === 'delta' ? clamp(cur + (isNaN(delta) ? 0 : delta)) : clamp(isNaN(set) ? cur : set);
            resEl.textContent = next;
        }

        btns.forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
        deltaEl && deltaEl.addEventListener('input', updatePreview);
        setEl && setEl.addEventListener('input', updatePreview);

        modal.querySelectorAll('.js-quick').forEach(ch => {
            ch.addEventListener('click', () => {
                const v = Number(ch.dataset.val || 0);
                deltaEl.value = (Number(deltaEl.value || 0) + v) || v;
                setMode('delta'); // về chế độ cộng/trừ khi bấm chip
            });
        });

        // init
        setMode('delta');
    });

    // Tự ẩn alert
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, +el.dataset.autoDismiss || 3000);
    });
</script>

<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#long_desc'), {
        placeholder: 'Viết mô tả chi tiết… (H2 cho mục lớn, H3 cho mục con, gạch đầu dòng để rõ ràng) ✨',
        toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
        heading: {
            options: [{
                    model: 'paragraph',
                    title: 'Đoạn văn',
                    class: 'ck-heading_paragraph'
                },
                {
                    model: 'heading2',
                    view: 'h2',
                    title: 'Tiêu đề (H2)',
                    class: 'ck-heading_heading2'
                },
                {
                    model: 'heading3',
                    view: 'h3',
                    title: 'Tiêu đề nhỏ (H3)',
                    class: 'ck-heading_heading3'
                }
            ]
        },
        list: {
            properties: {
                styles: true,
                startIndex: true,
                reversed: true
            }
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        }
    }).catch(console.error);
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/products/edit.blade.php ENDPATH**/ ?>