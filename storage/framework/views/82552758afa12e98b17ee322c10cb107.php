
<?php $__env->startSection('title','Giỏ hàng | Cosme House'); ?>

<?php $__env->startSection('content'); ?>
<section class="max-w-7xl mx-auto px-4 mt-6" x-data>
    <h1 class="text-2xl font-bold mb-4">Giỏ hàng</h1>

    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <div class="lg:col-span-8">
            <div class="bg-white border border-rose-100 rounded-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-rose-100 flex items-center gap-3">
                    <input id="selAll" type="checkbox" class="w-4 h-4 rounded border-rose-300">
                    <label for="selAll" class="text-sm">Chọn tất cả</label>
                    <button id="btnRemoveSelected" class="ml-auto text-sm text-rose-600 hover:underline hidden">Xoá mục đã chọn</button>
                </div>
                <div id="cartList" class="divide-y divide-rose-100"></div>
            </div>
        </div>

        
        <div class="lg:col-span-4">
            <div class="bg-white border border-rose-100 rounded-2xl p-4 space-y-4">
                
                <div>
                    <div class="text-sm font-medium mb-2">Mã giảm giá</div>

                    <div id="couponWrap" class="relative">
                        <div id="couponRow" class="flex gap-2">
                            <input id="couponInput" class="flex-1 px-3 py-2 rounded-md border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300" placeholder="Nhập mã">
                            <button id="btnApply" class="px-3 py-2 bg-brand-600 text-white rounded-md">Áp dụng</button>
                            
                            <button id="btnShowCoupons" type="button"
                                class="px-3 py-2 rounded-md border border-rose-200 text-ink/70 hover:bg-rose-50"
                                title="Chọn mã của bạn">
                                <i class="fa-solid fa-caret-down"></i>
                            </button>
                        </div>

                        
                        <div id="couponMenu"
                            class="hidden absolute z-30 mt-1 left-0 right-0 bg-white border border-rose-100 rounded-md shadow max-h-60 overflow-auto">
                            
                        </div>
                    </div>

                    <div id="couponApplied" class="hidden mt-2 flex items-center gap-2">
                        <span class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded text-sm">
                            Đã áp dụng: <span id="cpCode" class="font-semibold"></span>
                        </span>
                        <button id="btnRemoveCoupon" class="text-sm text-rose-600 hover:underline">Huỷ</button>
                    </div>
                    <div id="cpMsg" class="text-xs text-rose-600 mt-1"></div>
                </div>

                
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span>Tạm tính</span><span id="subSel">0₫</span></div>
                    <div class="flex justify-between text-emerald-700"><span>Giảm mã</span><span id="cpDiscount">0₫</span></div>
                    <div class="flex justify-between text-ink/60"><span>Phí vận chuyển</span><span>Miễn phí 499k</span></div>
                    <div class="border-t border-rose-100 pt-2 text-base font-semibold flex justify-between">
                        <span>Tổng</span><span id="grandTotal">0₫</span>
                    </div>
                </div>

                <a id="btnCheckout" href="<?php echo e(route('checkout.index')); ?>"
                    class="block text-center w-full px-4 py-3 rounded-xl bg-brand-600 text-white font-medium pointer-events-none opacity-50">Thanh toán</a>
            </div>
        </div>
    </div>
</section>


<script>
    // Endpoints
    window.R = {
        cartJson: <?php echo json_encode(route('cart.json'), 15, 512) ?>,
        cartBase: <?php echo json_encode(url('/cart'), 15, 512) ?>, // PATCH/DELETE: `${cartBase}/${key}`
        couponApply: <?php echo json_encode(route('coupon.apply'), 15, 512) ?>,
        couponRemove: <?php echo json_encode(route('coupon.remove'), 15, 512) ?>,
        couponMine: <?php echo json_encode(route('coupon.mine'), 15, 512) ?>,
    };

    // State
    const state = {
        items: [], // [{key, product_id, name, price, qty, img, variant_name, ...}]
        selected: new Set(), // keys được tick
        coupon: null, // {code, amount}
    };

    // Helpers
    const fmt = n => Number(n || 0).toLocaleString('vi-VN') + '₫';
    const $ = sel => document.querySelector(sel);

    // ====== DROPDOWN MÃ CỦA TÔI ======
    let myCoupons = [];

    function renderCouponMenu() {
        const box = $('#couponMenu');
        if (!box) return;

        if (!myCoupons.length) {
            box.innerHTML = `<div class="p-3 text-sm text-ink/60">Bạn chưa có mã nào.</div>`;
            return;
        }

        box.innerHTML = myCoupons.map(it => {
            const disabled = !it.usable;
            return `
        <button type="button"
            class="w-full text-left px-3 py-2 ${disabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-rose-50'}"
            ${disabled ? 'disabled' : ''} data-code="${it.code}">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="font-semibold truncate">${it.code}</div>
                    <div class="text-xs text-ink/60 truncate">
                        ${it.label}${it.min ? ` • ĐH tối thiểu ${fmt(it.min)}` : ''}${it.expires ? ` • HSD ${it.expires}` : ''}
                    </div>
                    ${it.reason ? `<div class="text-xs text-rose-600 mt-0.5">${it.reason}</div>` : ''}
                </div>
                ${it.times > 1 ? `<span class="text-xs text-ink/50 shrink-0">x${it.times}</span>` : ''}
            </div>
        </button>`;
        }).join('');

        box.querySelectorAll('button[data-code]').forEach(btn => {
            btn.addEventListener('click', () => {
                const code = btn.dataset.code;
                if (!code || btn.disabled) return;
                $('#couponInput').value = code;
                applyCoupon(code);
                hideCouponMenu();
            });
        });
    }
    async function loadMyCoupons() {
        try {
            const r = await fetch(R.couponMine, {
                credentials: 'same-origin'
            });
            const j = await r.json();
            const src = Array.isArray(j?.data) ? j.data : (Array.isArray(j?.items) ? j.items : []);
            // Chuẩn hoá field về định dạng FE đang dùng
            myCoupons = src.map(x => ({
                code: x.code,
                label: x.discount_text || x.label || '',
                min: (typeof x.min_order_total === 'number') ? x.min_order_total : (x.min ?? 0),
                expires: x.expires_at ?? x.expires ?? null,
                times: (typeof x.left === 'number') ? x.left : (x.times ?? null),
                usable: (x.usable !== false),
                reason: x.reason ?? null
            }));
        } catch (e) {
            myCoupons = [];
        }
        renderCouponMenu();
    }



    function showCouponMenu() {
        $('#couponMenu').classList.remove('hidden');
    }

    function hideCouponMenu() {
        $('#couponMenu').classList.add('hidden');
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#couponWrap')) hideCouponMenu();
    });
    $('#btnShowCoupons')?.addEventListener('click', (e) => {
        e.preventDefault();
        const box = $('#couponMenu');
        box.classList.contains('hidden') ? showCouponMenu() : hideCouponMenu();
    });

    // ====== Render 1 item row ======
    function rowTpl(it) {
        const line = it.price * it.qty;
        return `
      <div class="p-4 flex items-center gap-3" data-key="${it.key}">
        <input type="checkbox" class="w-4 h-4 rounded border-rose-300 item-check" ${state.selected.has(it.key) ? 'checked' : ''}>
        <img src="${it.img}"
             onerror="this.src='https://placehold.co/80x80?text=IMG'"
             class="w-16 h-16 object-contain bg-white rounded border border-rose-100"
             alt="">
        <div class="flex-1 min-w-0">
          <div class="font-medium line-clamp-1">${it.name}</div>
          ${it.variant_name ? `<div class="text-xs text-ink/60 mt-0.5">${it.variant_name}</div>` : ''}
          <div class="mt-1 text-rose-600 font-semibold">${fmt(it.price)}</div>
        </div>
        <div class="flex items-center rounded-lg border border-rose-200 overflow-hidden">
          <button class="w-8 h-8 grid place-items-center text-ink/70 hover:bg-rose-50 btn-dec">−</button>
          <input value="${it.qty}" inputmode="numeric" class="w-12 h-8 text-center outline-none border-x border-rose-100 qty-input">
          <button class="w-8 h-8 grid place-items-center text-ink/70 hover:bg-rose-50 btn-inc">+</button>
        </div>
        <div class="w-24 text-right font-semibold">${fmt(line)}</div>
        <button class="w-8 h-8 text-rose-600 hover:bg-rose-50 rounded-md btn-del"><i class="fa-regular fa-trash-can"></i></button>
      </div>
    `;
    }

    // ====== Load cart ======
    async function loadCart() {
        const res = await fetch(R.cartJson, {
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await res.json();

        // Chuẩn hoá key & img
        const raw = data.items || [];
        state.items = raw.map(i => ({
            ...i,
            key: i.key ?? i.rowId ?? i.id ?? i.sku ?? String(i.product_id),
            img: i.img ?? i.image ?? i.image_url ?? i.thumbnail ?? ''
        }));

        // Mặc định chọn tất cả nếu chưa có lựa chọn
        if (state.selected.size === 0) {
            state.items.forEach(i => state.selected.add(i.key));
        }

        // Render
        const list = $('#cartList');
        list.innerHTML = state.items.length ?
            state.items.map(rowTpl).join('') :
            `<div class="p-6 text-center text-ink/60">Giỏ hàng trống.</div>`;

        // Bind row events
        list.querySelectorAll('.item-check').forEach(ck => {
            ck.addEventListener('change', e => {
                const key = e.target.closest('[data-key]').dataset.key;
                e.target.checked ? state.selected.add(key) : state.selected.delete(key);
                syncSelAll();
                renderTotals();
            });
        });

        list.querySelectorAll('.btn-dec').forEach(btn => {
            btn.onclick = async (e) => {
                const row = e.target.closest('[data-key]');
                const key = row.dataset.key;
                const qtyEl = row.querySelector('.qty-input');
                const newQty = Math.max(1, (parseInt(qtyEl.value || '1') || 1) - 1);
                await updateQty(key, newQty);
                qtyEl.value = newQty;
                await loadCart();
            };
        });

        list.querySelectorAll('.btn-inc').forEach(btn => {
            btn.onclick = async (e) => {
                const row = e.target.closest('[data-key]');
                const key = row.dataset.key;
                const qtyEl = row.querySelector('.qty-input');
                const newQty = (parseInt(qtyEl.value || '1') || 1) + 1;
                await updateQty(key, newQty);
                qtyEl.value = newQty;
                await loadCart();
            };
        });

        list.querySelectorAll('.qty-input').forEach(inp => {
            inp.oninput = () => {
                inp.value = inp.value.replace(/[^0-9]/g, '');
            };
            inp.onchange = async () => {
                const row = inp.closest('[data-key]');
                const key = row.dataset.key;
                const newQty = Math.max(1, parseInt(inp.value || '1') || 1);
                await updateQty(key, newQty);
                await loadCart();
            };
        });

        list.querySelectorAll('.btn-del').forEach(btn => {
            btn.onclick = async (e) => {
                const key = e.target.closest('[data-key]').dataset.key;
                await fetch(`${R.cartBase}/${encodeURIComponent(key)}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                state.selected.delete(key);
                await loadCart();
            };
        });

        // Top controls
        syncSelAll();
        $('#btnRemoveSelected').classList.toggle('hidden', state.selected.size === 0);
        $('#btnRemoveSelected').onclick = async () => {
            const arr = [...state.selected];
            for (const k of arr) {
                await fetch(`${R.cartBase}/${encodeURIComponent(k)}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
            }
            state.selected.clear();
            await loadCart();
        };

        await renderTotals();
    }

    // Update qty API
    async function updateQty(key, qty) {
        await fetch(`${R.cartBase}/${encodeURIComponent(key)}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                qty
            })
        });
    }

    // Select all logic
    function syncSelAll() {
        const allChecked = state.items.length > 0 && state.items.every(i => state.selected.has(i.key));
        $('#selAll').checked = allChecked;
    }
    $('#selAll').addEventListener('change', e => {
        if (e.target.checked) state.items.forEach(i => state.selected.add(i.key));
        else state.selected.clear();
        $('#btnRemoveSelected').classList.toggle('hidden', state.selected.size === 0);
        renderTotals();
        document.querySelectorAll('#cartList .item-check').forEach(ck => ck.checked = e.target.checked);
    });

    // Totals + coupon
    async function renderTotals() {
        const selItems = state.items.filter(i => state.selected.has(i.key));
        const subtotal = selItems.reduce((s, i) => s + i.price * i.qty, 0);
        $('#subSel').textContent = fmt(subtotal);

        // Re-apply coupon theo mục đã chọn / qty mới
        let discount = 0;
        if (state.coupon?.code) {
            const re = await applyCoupon(state.coupon.code, /*silent*/ true);
            discount = re?.discount || 0;
        }
        $('#cpDiscount').textContent = fmt(discount);
        $('#grandTotal').textContent = fmt(Math.max(0, subtotal - discount));

        // Khoá nút Checkout khi chưa chọn item nào
        const disabled = selItems.length === 0;
        const btn = $('#btnCheckout');
        btn.classList.toggle('pointer-events-none', disabled);
        btn.classList.toggle('opacity-50', disabled);
        $('#btnRemoveSelected').classList.toggle('hidden', state.selected.size === 0);
    }

    // Apply / Remove coupon
    async function applyCoupon(code, silent = false) {
        if (!code) {
            if (!silent) $('#cpMsg').textContent = 'Vui lòng nhập mã.';
            return null;
        }

        const keys = [...state.selected];
        const payload = {
            code
        };
        if (keys.length) payload.keys = keys;

        const r = await fetch(R.couponApply, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await r.json().catch(() => null);

        if (!r.ok || !data || data.ok === false) {
            if (!silent) $('#cpMsg').textContent = data?.message || 'Mã không hợp lệ.';
            $('#couponRow').classList.remove('hidden');
            $('#couponApplied').classList.add('hidden');
            $('#cpDiscount').textContent = fmt(0);
            state.coupon = null;
            return null;
        }

        // Thành công
        state.coupon = {
            code: data.code ?? code,
            amount: data.discount || 0
        };

        $('#couponRow').classList.add('hidden');
        $('#couponApplied').classList.remove('hidden');
        $('#cpCode').textContent = state.coupon.code;
        if (!silent) $('#cpMsg').textContent = data.message || '';

        // Cập nhật totals theo mục đã chọn
        const sub = state.items.filter(i => state.selected.has(i.key))
            .reduce((s, i) => s + i.price * i.qty, 0);

        $('#cpDiscount').textContent = fmt(data.discount || 0);
        $('#grandTotal').textContent = fmt(Math.max(0, sub - (data.discount || 0)));

        return data;
    }

    $('#btnApply').onclick = () => applyCoupon($('#couponInput').value.trim());

    $('#btnRemoveCoupon').onclick = async () => {
        await fetch(R.couponRemove, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            }
        });
        state.coupon = null;
        $('#couponRow').classList.remove('hidden');
        $('#couponApplied').classList.add('hidden');
        $('#cpMsg').textContent = '';
        renderTotals();
    };

    // Init
    loadCart();
    loadMyCoupons();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/cart/index.blade.php ENDPATH**/ ?>