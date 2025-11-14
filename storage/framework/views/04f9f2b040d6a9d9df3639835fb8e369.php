<?php $__env->startSection('title', 'Xác thực sản phẩm - CosmeChain'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .scanner-container {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        background: #000;
        border-radius: 16px;
        overflow: hidden;
        isolation: isolate;
    }

    #qr-reader {
        width: 100%;
        height: 400px;
    }

    .scanner-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 250px;
        height: 250px;
        border: 3px solid #f43f5e;
        border-radius: 16px;
        pointer-events: none;
        z-index: 10;
        box-sizing: border-box;
    }

    .scanner-container::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
        pointer-events: none;
        border-radius: 16px;
    }

    .scanner-container::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 250px;
        height: 250px;
        background: transparent;
        z-index: 2;
        pointer-events: none;
        border-radius: 16px;
    }

    .scanner-overlay::before,
    .scanner-overlay::after {
        content: '';
        position: absolute;
        width: 30px;
        height: 30px;
        border: 4px solid #f43f5e;
    }

    .scanner-overlay::before {
        top: -4px;
        left: -4px;
        border-right: none;
        border-bottom: none;
        border-top-left-radius: 12px;
    }

    .scanner-overlay::after {
        bottom: -4px;
        right: -4px;
        border-left: none;
        border-top: none;
        border-bottom-right-radius: 12px;
    }

    .pulse-ring {
        position: relative;
        overflow: hidden;
    }

    .pulse-ring::before {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        border: 2px solid currentColor;
        opacity: 0;
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse-ring {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    .result-card {
        animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gradient-to-br from-rose-50 via-white to-pink-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            
            <div class="text-center mb-8">

                <h1 class="text-4xl md:text-5xl font-bold text-ink mb-3">
                    Xác thực sản phẩm CosmeChain
                </h1>
                <p class="text-lg text-ink/70 max-w-2xl mx-auto">
                    Quét QR code hoặc nhập mã để xác thực sản phẩm chính hãng.
                    Mỗi sản phẩm có một mã QR code duy nhất, không thể làm giả.
                </p>
            </div>

            
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6" x-data="{ activeTab: 'scan' }" x-init="
                $watch('activeTab', (value) => {
                    if (value === 'scan' && !window.isScanning) {
                        setTimeout(() => window.startScanner(), 300);
                    } else if (value === 'manual' && window.isScanning) {
                        window.stopScanner();
                    }
                });
            ">
                <div class="flex gap-2 mb-6 border-b border-gray-200">
                    <button
                        @click="activeTab = 'scan'"
                        :class="activeTab === 'scan' ? 'border-b-2 border-brand-500 text-brand-600 font-semibold' : 'text-gray-600 hover:text-brand-600'"
                        class="px-6 py-3 transition-colors"
                    >
                        <i class="fas fa-camera mr-2"></i>
                        Quét QR Code
                    </button>
                    <button
                        @click="activeTab = 'manual'"
                        :class="activeTab === 'manual' ? 'border-b-2 border-brand-500 text-brand-600 font-semibold' : 'text-gray-600 hover:text-brand-600'"
                        class="px-6 py-3 transition-colors"
                    >
                        <i class="fas fa-keyboard mr-2"></i>
                        Nhập mã thủ công
                    </button>
                </div>

                
                <div x-show="activeTab === 'scan'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="text-center mb-4">
                        <p class="text-sm text-gray-600 mb-4">
                            Đặt QR code vào khung quét. Hệ thống sẽ tự động nhận diện.
                        </p>
                    </div>

                    <div class="scanner-container mb-4" id="scanner-container">
                        <div id="qr-reader" style="width: 100%; position: relative; z-index: 0;"></div>
                        <div class="scanner-overlay"></div>
                    </div>

                    <div class="text-center">
                        <button
                            id="stop-scanner-btn"
                            class="hidden px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                        >
                            <i class="fas fa-stop mr-2"></i>Dừng quét
                        </button>
                    </div>
                </div>

                
                <div x-show="activeTab === 'manual'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <form action="<?php echo e(route('blockchain.verify.submit')); ?>" method="POST" id="verifyForm">
                        <?php echo csrf_field(); ?>
                        <div class="mb-4">
                            <label for="qr_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-qrcode mr-2 text-brand-600"></i>
                                Mã QR Code
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="qr_code"
                                    name="qr_code"
                                    value="<?php echo e(old('qr_code', $qr_code ?? '')); ?>"
                                    class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-base font-mono transition-all"
                                    placeholder="Nhập mã QR code của sản phẩm..."
                                    required
                                    autofocus
                                >
                                <button
                                    type="button"
                                    onclick="pasteFromClipboard()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 px-3 py-1 text-sm text-brand-600 hover:text-brand-700 hover:bg-brand-50 rounded-lg transition"
                                    title="Dán từ clipboard"
                                >
                                    <i class="fas fa-paste"></i>
                                </button>
                            </div>
                            <?php $__errorArgs = ['qr_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo e($message); ?>

                                </p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button
                            type="submit"
                            class="w-full bg-gradient-to-r from-brand-500 to-brand-600 text-white py-4 px-6 rounded-xl hover:from-brand-600 hover:to-brand-700 transition shadow-lg hover:shadow-xl font-semibold text-lg"
                        >
                            <i class="fas fa-search mr-2"></i>Xác thực ngay
                        </button>
                    </form>
                </div>
            </div>

            
            <?php if(isset($result)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-8 result-card">
                    <?php if(isset($result['authentic']) && $result['authentic'] && ($result['result'] === 'authentic')): ?>
                        
                        <div class="text-center mb-8 pb-8 border-b border-gray-200">
                            <div class="inline-flex items-center justify-center w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-brand-500 to-brand-600 mb-6 shadow-lg overflow-hidden relative">
                                <i class="fas fa-check text-white text-3xl md:text-4xl relative z-10"></i>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-brand-600 mb-3">
                                Sản phẩm chính hãng
                            </h2>
                            <p class="text-lg text-gray-600 mb-4">
                                Sản phẩm của bạn đã được xác thực trên hệ thống CosmeChain
                            </p>

                            
                            <?php if(isset($result['is_suspicious']) && $result['is_suspicious']): ?>
                            <div class="bg-amber-50 border-2 border-amber-200 rounded-xl p-4 max-w-md mx-auto text-left">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-exclamation-triangle text-amber-600 text-xl mt-0.5"></i>
                                    <div>
                                        <p class="font-semibold text-amber-900 mb-1">⚠️ Cảnh báo bảo mật</p>
                                        <p class="text-sm text-amber-800">
                                            QR code này đã được verify <strong><?php echo e($result['verification_count'] ?? 0); ?> lần</strong>.
                                            <?php if(isset($result['remaining_verifications']) && $result['remaining_verifications'] > 0): ?>
                                                Còn <strong><?php echo e($result['remaining_verifications']); ?> lần</strong> nữa sẽ bị khóa vĩnh viễn.
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-amber-700 mt-2">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Nếu bạn là chủ sở hữu hợp pháp, vui lòng liên hệ hỗ trợ để bảo vệ QR code của bạn.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if(isset($result['certificate'])): ?>
                            <div class="mb-8">
                                <h3 class="text-2xl font-bold mb-6 flex items-center text-gray-800">
                                    <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-certificate text-brand-600"></i>
                                    </div>
                                    Thông tin chứng chỉ
                                </h3>
                                <div class="bg-gradient-to-br from-brand-50 to-rose-50 rounded-xl p-6 space-y-4">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div class="bg-white rounded-lg p-4 shadow-sm">
                                            <div class="text-sm text-gray-600 mb-1">Sản phẩm</div>
                                            <div class="font-semibold text-gray-900"><?php echo e($result['certificate']['metadata']['product_name'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-sm">
                                            <div class="text-sm text-gray-600 mb-1">SKU</div>
                                            <div class="font-semibold text-gray-900"><?php echo e($result['certificate']['metadata']['sku'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-sm">
                                            <div class="text-sm text-gray-600 mb-1">Thương hiệu</div>
                                            <div class="font-semibold text-gray-900"><?php echo e($result['certificate']['metadata']['brand'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow-sm">
                                            <div class="text-sm text-gray-600 mb-1">Batch Number</div>
                                            <div class="font-semibold text-gray-900"><?php echo e($result['certificate']['metadata']['batch_number'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="text-sm text-gray-600 mb-2">Certificate Hash</div>
                                        <code class="block text-xs bg-gray-50 p-3 rounded-lg break-all font-mono"><?php echo e($result['certificate']['hash']); ?></code>
                                    </div>
                                    <?php if(isset($result['certificate']['ipfs_url'])): ?>
                                        <a href="<?php echo e($result['certificate']['ipfs_url']); ?>" target="_blank"
                                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-lg hover:from-brand-600 hover:to-brand-700 transition shadow-md hover:shadow-lg">
                                            <i class="fas fa-external-link-alt mr-2"></i>
                                            Xem chứng chỉ trên IPFS
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($result['history']) && count($result['history']) > 0): ?>
                            <div>
                                <h3 class="text-2xl font-bold mb-6 flex items-center text-gray-800">
                                    <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-route text-brand-600"></i>
                                    </div>
                                    Lịch sử chuỗi cung ứng
                                </h3>
                                <div class="space-y-3">
                                    <?php $__currentLoopData = $result['history']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $movement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-start gap-4 p-5 bg-gradient-to-r from-gray-50 to-white rounded-xl hover:shadow-md transition border border-gray-100">
                                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 flex items-center justify-center text-xl shadow-sm">
                                                <?php if($movement['type'] === 'manufacture'): ?>
                                                    🏭
                                                <?php elseif($movement['type'] === 'warehouse_in'): ?>
                                                    📦
                                                <?php elseif($movement['type'] === 'warehouse_out'): ?>
                                                    🚚
                                                <?php elseif($movement['type'] === 'sale'): ?>
                                                    🛒
                                                <?php elseif($movement['type'] === 'return'): ?>
                                                    ↩️
                                                <?php else: ?>
                                                    📍
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1">
                                                <p class="font-semibold text-gray-900 capitalize mb-1">
                                                    <?php echo e(str_replace('_', ' ', $movement['type'])); ?>

                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    <?php if($movement['from']): ?>
                                                        <span class="font-medium"><?php echo e($movement['from']); ?></span>
                                                        <i class="fas fa-arrow-right mx-2 text-xs"></i>
                                                    <?php endif; ?>
                                                    <span class="font-medium"><?php echo e($movement['to']); ?></span>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    <i class="far fa-clock mr-1"></i><?php echo e($movement['date']); ?>

                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        
                        <div class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-red-400 to-red-600 mb-6 shadow-lg overflow-hidden">
                                <?php if(isset($result['result']) && $result['result'] === 'suspicious'): ?>
                                    <i class="fas fa-exclamation-triangle text-white text-3xl md:text-4xl"></i>
                                <?php else: ?>
                                    <i class="fas fa-times text-white text-3xl md:text-4xl"></i>
                                <?php endif; ?>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-red-600 mb-3">
                                <?php echo e($result['message'] ?? 'QR code không tồn tại'); ?>

                            </h2>
                            <p class="text-lg text-gray-600 mb-6">
                                <?php if(isset($result['result']) && $result['result'] === 'suspicious'): ?>
                                    QR code đã bị đánh dấu nghi ngờ hoặc đã được verify quá nhiều lần.
                                <?php else: ?>
                                    Sản phẩm có thể là hàng giả hoặc mã QR code không hợp lệ.
                                <?php endif; ?>
                            </p>
                            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6 max-w-md mx-auto text-left">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-info-circle text-red-600 text-xl mt-1"></i>
                                    <div>
                                        <p class="font-semibold text-red-900 mb-2">Lưu ý quan trọng:</p>
                                        <p class="text-sm text-red-800 leading-relaxed">
                                            Nếu bạn đã mua sản phẩm này từ CosmeChain, vui lòng:
                                        </p>
                                        <ul class="text-sm text-red-800 mt-2 space-y-1 list-disc list-inside">
                                            <li>Kiểm tra lại mã QR code</li>
                                            <li>Liên hệ bộ phận hỗ trợ khách hàng</li>
                                            <li>Gửi ảnh sản phẩm để được hỗ trợ</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="mt-8 bg-gradient-to-br from-brand-50 to-rose-50 rounded-2xl p-8 shadow-lg">
                <h3 class="text-2xl font-bold mb-4 flex items-center text-gray-800">
                    <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center mr-3">
                        <i class="fas fa-info-circle text-brand-600"></i>
                    </div>
                    Về CosmeChain
                </h3>
                <div class="grid md:grid-cols-2 gap-6 text-gray-700">
                    <div>
                        <h4 class="font-semibold mb-2 text-gray-900">🔒 Bảo mật cao</h4>
                        <p class="text-sm leading-relaxed">
                            Dữ liệu được lưu trữ trên IPFS (InterPlanetary File System) - hệ thống phân tán, không thể sửa đổi hay làm giả.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2 text-gray-900">🔍 Truy xuất nguồn gốc</h4>
                        <p class="text-sm leading-relaxed">
                            Xem toàn bộ lịch sử di chuyển của sản phẩm từ nhà sản xuất đến tay bạn, đảm bảo tính minh bạch.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2 text-gray-900">✅ Chống hàng giả</h4>
                        <p class="text-sm leading-relaxed">
                            Mỗi sản phẩm có một mã QR code duy nhất, không thể sao chép hay làm giả.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-2 text-gray-900">📱 Dễ sử dụng</h4>
                        <p class="text-sm leading-relaxed">
                            Chỉ cần quét QR code bằng camera điện thoại, kết quả xác thực hiển thị ngay lập tức.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let html5QrCode = null;
let isScanning = false;
window.isScanning = false; // Make it global for Alpine

window.startScanner = function() {
    if (isScanning) return;

    html5QrCode = new Html5Qrcode("qr-reader");
    const qrCodeSuccessCallback = (decodedText, decodedResult) => {
        // Stop scanner
        window.stopScanner();

        // Submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo e(route("blockchain.verify.submit")); ?>';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '<?php echo e(csrf_token()); ?>';
        form.appendChild(csrfInput);

        const qrInput = document.createElement('input');
        qrInput.type = 'hidden';
        qrInput.name = 'qr_code';
        qrInput.value = decodedText;
        form.appendChild(qrInput);

        document.body.appendChild(form);
        form.submit();
    };

    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };

    html5QrCode.start(
        { facingMode: "environment" }, // Use back camera
        config,
        qrCodeSuccessCallback,
        (errorMessage) => {
            // Ignore errors
        }
    ).then(() => {
        isScanning = true;
        window.isScanning = true;
        const btn = document.getElementById('stop-scanner-btn');
        if (btn) btn.classList.remove('hidden');
    }).catch((err) => {
        console.error("Unable to start scanning", err);
        alert('Không thể truy cập camera. Vui lòng kiểm tra quyền truy cập camera hoặc dùng chế độ nhập thủ công.');
    });
}

window.stopScanner = function() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            isScanning = false;
            window.isScanning = false;
            const btn = document.getElementById('stop-scanner-btn');
            if (btn) btn.classList.add('hidden');
        }).catch((err) => {
            console.error("Unable to stop scanning", err);
        });
    }
};

// Stop scanner button
document.getElementById('stop-scanner-btn')?.addEventListener('click', () => {
    window.stopScanner();
});

// Paste from clipboard
async function pasteFromClipboard() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('qr_code').value = text;
    } catch (err) {
        alert('Không thể đọc clipboard. Vui lòng nhập thủ công.');
    }
}

// Auto-start scanner when page loads (if on scan tab)
document.addEventListener('DOMContentLoaded', () => {
    // Wait for Alpine to initialize
    setTimeout(() => {
        const scanTab = document.querySelector('[x-show*="scan"]');
        if (scanTab && scanTab.offsetParent !== null) {
            // Scan tab is visible
            setTimeout(() => {
                window.startScanner();
            }, 500);
        }
    }, 1000);
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/blockchain/verify.blade.php ENDPATH**/ ?>