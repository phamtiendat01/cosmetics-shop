
<?php $__env->startSection('title','Cài đặt'); ?>

<?php use App\Models\Setting; ?>

<?php $__env->startSection('content'); ?>
<?php if(session('ok')): ?>
<div class="alert alert-success mb-4" data-auto-dismiss="2500"><?php echo e(session('ok')); ?></div>
<?php endif; ?>

<?php if($errors->any()): ?>
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 p-3">
    <div class="font-medium mb-1">Có lỗi xảy ra, vui lòng kiểm tra lại:</div>
    <ul class="list-disc pl-6 text-sm space-y-1">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li><?php echo e($e); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<?php
// tab hiện tại: ưu tiên query ?tab=..., fallback session hoặc 'general'
$tab = request('tab') ?? (session('settings.tab') ?? 'general');
?>

<form method="POST" action="<?php echo e(route('admin.settings.store', ['tab' => $tab])); ?>" class="space-y-6">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="tab" value="<?php echo e($tab); ?>"><!-- để controller có thể flash về đúng tab -->

    
    <div class="sticky top-0 z-10 -mx-6 px-6 py-3 bg-white/90 backdrop-blur border-b border-slate-200 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Cài đặt hệ thống</h1>
            <p class="text-slate-500 text-sm">Quản lý cấu hình cửa hàng, SEO, thanh toán, đơn hàng, vận chuyển, email, chính sách và trang tĩnh.</p>
        </div>
        <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700 shadow-sm">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Lưu cài đặt
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">
        
        <aside class="col-span-12 lg:col-span-3">
            <nav class="rounded-xl border border-slate-200 bg-white p-2 text-sm">
                <?php $__currentLoopData = [
                ['general','Chung','fa-sliders'],
                ['seo','SEO / Tracking','fa-magnifying-glass'],
                ['payment','Thanh toán','fa-credit-card'],
                ['order','Đơn hàng & Checkout','fa-receipt'],
                ['shipping','Vận chuyển','fa-truck'],
                ['email','Email','fa-envelope'],
                ['policy','Trả hàng / Hoàn tiền','fa-rotate-left'],
                ['pages','Trang tĩnh','fa-file-lines'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$key,$label,$icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('admin.settings.index', ['tab'=>$key])); ?>"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50
                    <?php echo e($tab===$key ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'text-slate-700'); ?>">
                    <i class="fa-solid <?php echo e($icon); ?> w-4 text-center"></i>
                    <span><?php echo e($label); ?></span>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>
        </aside>

        
        <section class="col-span-12 lg:col-span-9 space-y-6">

            
            <div class="<?php echo e($tab==='general' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'Cửa hàng','desc' => 'Tên, logo, liên hệ và định dạng hiển thị.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Cửa hàng','desc' => 'Tên, logo, liên hệ và định dạng hiển thị.']); ?>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Tên cửa hàng','name' => 'store[name]','value' => old('store.name', Setting::get('store.name')),'required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Tên cửa hàng','name' => 'store[name]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store.name', Setting::get('store.name'))),'required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Hotline','name' => 'store[hotline]','value' => old('store.hotline', Setting::get('store.hotline'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Hotline','name' => 'store[hotline]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store.hotline', Setting::get('store.hotline')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'email','label' => 'Email','name' => 'store[email]','value' => old('store.email', Setting::get('store.email'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'email','label' => 'Email','name' => 'store[email]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store.email', Setting::get('store.email')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Địa chỉ','name' => 'store[address]','value' => old('store.address', Setting::get('store.address'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Địa chỉ','name' => 'store[address]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store.address', Setting::get('store.address')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Logo (URL/đường dẫn)','name' => 'store[logo]','value' => old('store.logo', Setting::get('store.logo'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Logo (URL/đường dẫn)','name' => 'store[logo]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store.logo', Setting::get('store.logo')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Favicon (URL/đường dẫn)','name' => 'store[favicon]','value' => old('store.favicon', Setting::get('store.favicon'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Favicon (URL/đường dẫn)','name' => 'store[favicon]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('store.favicon', Setting::get('store.favicon')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tiền tệ</label>
                            <select name="store[currency]" class="w-full rounded border-slate-300">
                                <?php $__currentLoopData = ['VND'=>'VND','USD'=>'USD']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($k); ?>" <?php if(old('store.currency', Setting::get('store.currency','VND'))==$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Ngôn ngữ</label>
                            <select name="store[locale]" class="w-full rounded border-slate-300">
                                <?php $__currentLoopData = ['vi'=>'Tiếng Việt','en'=>'English']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($k); ?>" <?php if(old('store.locale', Setting::get('store.locale','vi'))==$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Múi giờ</label>
                            <input class="w-full rounded border-slate-300"
                                name="store[timezone]"
                                value="<?php echo e(old('store.timezone', Setting::get('store.timezone','Asia/Ho_Chi_Minh'))); ?>">
                            <p class="text-xs text-slate-500 mt-1">Ví dụ: <code>Asia/Ho_Chi_Minh</code></p>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='seo' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'SEO & Tracking','desc' => 'Tiêu đề/mô tả mặc định và mã theo dõi.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'SEO & Tracking','desc' => 'Tiêu đề/mô tả mặc định và mã theo dõi.']); ?>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Tiêu đề mặc định','name' => 'seo[default_title]','value' => old('seo.default_title', Setting::get('seo.default_title'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Tiêu đề mặc định','name' => 'seo[default_title]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('seo.default_title', Setting::get('seo.default_title')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Ảnh OG (URL)','name' => 'seo[og_image]','value' => old('seo.og_image', Setting::get('seo.og_image'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Ảnh OG (URL)','name' => 'seo[og_image]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('seo.og_image', Setting::get('seo.og_image')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Mô tả mặc định</label>
                            <textarea name="seo[default_description]" rows="3" class="w-full rounded border-slate-300"><?php echo e(old('seo.default_description', Setting::get('seo.default_description'))); ?></textarea>
                        </div>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Google Tag (G-XXXX)','name' => 'tracking[gtag_id]','value' => old('tracking.gtag_id', Setting::get('tracking.gtag_id'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Google Tag (G-XXXX)','name' => 'tracking[gtag_id]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('tracking.gtag_id', Setting::get('tracking.gtag_id')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Facebook Pixel ID','name' => 'tracking[fb_pixel_id]','value' => old('tracking.fb_pixel_id', Setting::get('tracking.fb_pixel_id'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Facebook Pixel ID','name' => 'tracking[fb_pixel_id]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('tracking.fb_pixel_id', Setting::get('tracking.fb_pixel_id')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='payment' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'COD','desc' => 'Thanh toán khi nhận hàng.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'COD','desc' => 'Thanh toán khi nhận hàng.']); ?>
                    <div class="flex items-center justify-end mb-4">
                        <?php if (isset($component)) { $__componentOriginal026c544cfb81717be117829402d47b5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal026c544cfb81717be117829402d47b5a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switch','data' => ['name' => 'payment[cod][enabled]','checked' => old('payment.cod.enabled', Setting::get('payment.cod.enabled'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'payment[cod][enabled]','checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.cod.enabled', Setting::get('payment.cod.enabled')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $attributes = $__attributesOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__attributesOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $component = $__componentOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__componentOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'number','step' => '0.01','min' => '0','max' => '100','label' => 'Phí (%)','name' => 'payment[cod][fee_percent]','value' => old('payment.cod.fee_percent', Setting::get('payment.cod.fee_percent')),'placeholder' => 'VD 0 hoặc 1.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','step' => '0.01','min' => '0','max' => '100','label' => 'Phí (%)','name' => 'payment[cod][fee_percent]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.cod.fee_percent', Setting::get('payment.cod.fee_percent'))),'placeholder' => 'VD 0 hoặc 1.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'VNPAY','desc' => 'Kết nối cổng VNPAY.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'VNPAY','desc' => 'Kết nối cổng VNPAY.']); ?>
                    <div class="flex items-center justify-end mb-4">
                        <?php if (isset($component)) { $__componentOriginal026c544cfb81717be117829402d47b5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal026c544cfb81717be117829402d47b5a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switch','data' => ['name' => 'payment[vnpay][enabled]','checked' => old('payment.vnpay.enabled', Setting::get('payment.vnpay.enabled'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'payment[vnpay][enabled]','checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.vnpay.enabled', Setting::get('payment.vnpay.enabled')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $attributes = $__attributesOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__attributesOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $component = $__componentOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__componentOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
                    </div>
                    <div class="grid md:grid-cols-3 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'TMN Code','name' => 'payment[vnpay][tmn_code]','value' => old('payment.vnpay.tmn_code', Setting::get('payment.vnpay.tmn_code'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'TMN Code','name' => 'payment[vnpay][tmn_code]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.vnpay.tmn_code', Setting::get('payment.vnpay.tmn_code')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Hash Secret','name' => 'payment[vnpay][hash_secret]','value' => old('payment.vnpay.hash_secret', Setting::get('payment.vnpay.hash_secret'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Hash Secret','name' => 'payment[vnpay][hash_secret]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.vnpay.hash_secret', Setting::get('payment.vnpay.hash_secret')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal026c544cfb81717be117829402d47b5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal026c544cfb81717be117829402d47b5a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switch','data' => ['name' => 'payment[vnpay][sandbox]','label' => 'Sandbox','right' => true,'checked' => old('payment.vnpay.sandbox', Setting::get('payment.vnpay.sandbox', 1))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'payment[vnpay][sandbox]','label' => 'Sandbox','right' => true,'checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.vnpay.sandbox', Setting::get('payment.vnpay.sandbox', 1)))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $attributes = $__attributesOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__attributesOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $component = $__componentOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__componentOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'MoMo','desc' => 'Kết nối ví MoMo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'MoMo','desc' => 'Kết nối ví MoMo.']); ?>
                    <div class="flex items-center justify-end mb-4">
                        <?php if (isset($component)) { $__componentOriginal026c544cfb81717be117829402d47b5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal026c544cfb81717be117829402d47b5a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switch','data' => ['name' => 'payment[momo][enabled]','checked' => old('payment.momo.enabled', Setting::get('payment.momo.enabled'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'payment[momo][enabled]','checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.momo.enabled', Setting::get('payment.momo.enabled')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $attributes = $__attributesOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__attributesOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $component = $__componentOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__componentOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Partner Code','name' => 'payment[momo][partner_code]','value' => old('payment.momo.partner_code', Setting::get('payment.momo.partner_code'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Partner Code','name' => 'payment[momo][partner_code]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.momo.partner_code', Setting::get('payment.momo.partner_code')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Access Key','name' => 'payment[momo][access_key]','value' => old('payment.momo.access_key', Setting::get('payment.momo.access_key'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Access Key','name' => 'payment[momo][access_key]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.momo.access_key', Setting::get('payment.momo.access_key')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'Secret Key','name' => 'payment[momo][secret_key]','value' => old('payment.momo.secret_key', Setting::get('payment.momo.secret_key'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Secret Key','name' => 'payment[momo][secret_key]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.momo.secret_key', Setting::get('payment.momo.secret_key')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal026c544cfb81717be117829402d47b5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal026c544cfb81717be117829402d47b5a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switch','data' => ['name' => 'payment[momo][sandbox]','label' => 'Sandbox','right' => true,'checked' => old('payment.momo.sandbox', Setting::get('payment.momo.sandbox',1))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'payment[momo][sandbox]','label' => 'Sandbox','right' => true,'checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('payment.momo.sandbox', Setting::get('payment.momo.sandbox',1)))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $attributes = $__attributesOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__attributesOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $component = $__componentOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__componentOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='order' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'Đơn hàng & Checkout','desc' => 'Quy tắc đặt hàng và thanh toán.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Đơn hàng & Checkout','desc' => 'Quy tắc đặt hàng và thanh toán.']); ?>
                    <div class="grid md:grid-cols-2 gap-5 items-start">
                        <?php if (isset($component)) { $__componentOriginal026c544cfb81717be117829402d47b5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal026c544cfb81717be117829402d47b5a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switch','data' => ['name' => 'checkout[allow_guest]','label' => 'Cho phép mua không cần đăng ký','checked' => old('checkout.allow_guest', Setting::get('checkout.allow_guest'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switch'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'checkout[allow_guest]','label' => 'Cho phép mua không cần đăng ký','checked' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('checkout.allow_guest', Setting::get('checkout.allow_guest')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $attributes = $__attributesOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__attributesOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal026c544cfb81717be117829402d47b5a)): ?>
<?php $component = $__componentOriginal026c544cfb81717be117829402d47b5a; ?>
<?php unset($__componentOriginal026c544cfb81717be117829402d47b5a); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'number','min' => '0','step' => '1000','label' => 'Đơn tối thiểu (VND)','name' => 'order[min_total]','value' => old('order.min_total', Setting::get('order.min_total'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','min' => '0','step' => '1000','label' => 'Đơn tối thiểu (VND)','name' => 'order[min_total]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('order.min_total', Setting::get('order.min_total')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'number','min' => '0','step' => '1','label' => 'Tự hủy đơn chờ thanh toán (phút)','name' => 'order[auto_cancel_minutes]','value' => old('order.auto_cancel_minutes', Setting::get('order.auto_cancel_minutes'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','min' => '0','step' => '1','label' => 'Tự hủy đơn chờ thanh toán (phút)','name' => 'order[auto_cancel_minutes]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('order.auto_cancel_minutes', Setting::get('order.auto_cancel_minutes')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='shipping' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'Vận chuyển','desc' => 'Đơn vị và ngưỡng miễn phí.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Vận chuyển','desc' => 'Đơn vị và ngưỡng miễn phí.']); ?>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'number','min' => '0','step' => '1000','label' => 'Ngưỡng miễn phí vận chuyển (VND)','name' => 'shipping[freeship_threshold]','value' => old('shipping.freeship_threshold', Setting::get('shipping.freeship_threshold'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','min' => '0','step' => '1000','label' => 'Ngưỡng miễn phí vận chuyển (VND)','name' => 'shipping[freeship_threshold]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('shipping.freeship_threshold', Setting::get('shipping.freeship_threshold')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium mb-1">Đơn vị khối lượng</label>
                            <select name="shipping[unit][weight]" class="w-full rounded border-slate-300">
                                <?php $__currentLoopData = ['kg'=>'kg','g'=>'g']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($k); ?>" <?php if(old('shipping.unit.weight', Setting::get('shipping.unit.weight','kg'))==$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Đơn vị kích thước</label>
                            <select name="shipping[unit][dimension]" class="w-full rounded border-slate-300">
                                <option value="cm" <?php if(old('shipping.unit.dimension', Setting::get('shipping.unit.dimension','cm'))=='cm' ): echo 'selected'; endif; ?>>cm</option>
                            </select>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='email' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'Email','desc' => 'Thông tin người gửi & SMTP.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Email','desc' => 'Thông tin người gửi & SMTP.']); ?>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'From name','name' => 'mail[from_name]','value' => old('mail.from_name', Setting::get('mail.from_name'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'From name','name' => 'mail[from_name]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('mail.from_name', Setting::get('mail.from_name')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'email','label' => 'From address','name' => 'mail[from_address]','value' => old('mail.from_address', Setting::get('mail.from_address'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'email','label' => 'From address','name' => 'mail[from_address]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('mail.from_address', Setting::get('mail.from_address')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'SMTP Host','name' => 'smtp[host]','value' => old('smtp.host', Setting::get('smtp.host'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'SMTP Host','name' => 'smtp[host]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('smtp.host', Setting::get('smtp.host')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'number','label' => 'SMTP Port','name' => 'smtp[port]','value' => old('smtp.port', Setting::get('smtp.port'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','label' => 'SMTP Port','name' => 'smtp[port]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('smtp.port', Setting::get('smtp.port')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'SMTP Username','name' => 'smtp[username]','value' => old('smtp.username', Setting::get('smtp.username'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'SMTP Username','name' => 'smtp[username]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('smtp.username', Setting::get('smtp.username')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['label' => 'SMTP Password','name' => 'smtp[password]','value' => old('smtp.password', Setting::get('smtp.password'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'SMTP Password','name' => 'smtp[password]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('smtp.password', Setting::get('smtp.password')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <div>
                            <label class="block text-sm font-medium mb-1">Encryption</label>
                            <select name="smtp[encryption]" class="w-full rounded border-slate-300">
                                <?php $__currentLoopData = [''=>'(none)','tls'=>'TLS','ssl'=>'SSL']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($k); ?>" <?php if(old('smtp.encryption', Setting::get('smtp.encryption'))===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='policy' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'Trả hàng / Hoàn tiền','desc' => 'Chính sách áp dụng cho khách hàng.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Trả hàng / Hoàn tiền','desc' => 'Chính sách áp dụng cho khách hàng.']); ?>
                    <div class="grid md:grid-cols-2 gap-5">
                        <?php if (isset($component)) { $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.setting.input','data' => ['type' => 'number','min' => '0','step' => '1','label' => 'Số ngày cho phép đổi trả','name' => 'policy[return_days]','value' => old('policy.return_days', Setting::get('policy.return_days'))]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('setting.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'number','min' => '0','step' => '1','label' => 'Số ngày cho phép đổi trả','name' => 'policy[return_days]','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('policy.return_days', Setting::get('policy.return_days')))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $attributes = $__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__attributesOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae)): ?>
<?php $component = $__componentOriginal5866be7dc1a27c9c231e63d73ce76dae; ?>
<?php unset($__componentOriginal5866be7dc1a27c9c231e63d73ce76dae); ?>
<?php endif; ?>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Điều kiện đổi trả (text)</label>
                            <textarea name="policy[return_conditions]" rows="6" class="w-full rounded border-slate-300"><?php echo e(old('policy.return_conditions', Setting::get('policy.return_conditions'))); ?></textarea>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>

            
            <div class="<?php echo e($tab==='pages' ? '' : 'hidden'); ?>">
                <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'Trang tĩnh','desc' => 'Nội dung HTML cho các trang thông tin.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Trang tĩnh','desc' => 'Nội dung HTML cho các trang thông tin.']); ?>
                    <div class="grid gap-6">
                        <?php $__currentLoopData = ['about'=>'Giới thiệu','privacy'=>'Chính sách bảo mật','terms'=>'Điều khoản sử dụng']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <label class="block text-sm font-medium mb-1"><?php echo e($label); ?></label>
                            <textarea name="pages[<?php echo e($k); ?>]" rows="10" class="w-full rounded border-slate-300"><?php echo e(old("pages.$k", Setting::get("pages.$k"))); ?></textarea>
                            <p class="text-xs text-slate-500 mt-1">Có thể thay bằng WYSIWYG (Quill/TinyMCE) khi cần.</p>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
            </div>
        </section>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/admin/settings/index.blade.php ENDPATH**/ ?>