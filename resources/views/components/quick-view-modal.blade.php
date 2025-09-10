<div x-data x-show="$store.qv.open" x-transition.opacity
    class="fixed inset-0 z-[80] bg-black/50" style="display:none"
    @keydown.escape.window="$store.qv.open=false" @click.self="$store.qv.open=false">

    <div x-show="$store.qv.open" x-transition
        class="absolute inset-0 m-auto w-full max-w-3xl bg-white rounded-2xl shadow-card border border-rose-100 p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold" x-text="$store.qv.data.name || 'Xem nhanh'"></h3>
            <button class="w-9 h-9 grid place-items-center rounded-full hover:bg-rose-50"
                @click="$store.qv.open=false"><i class="fa-regular fa-xmark"></i></button>
        </div>

        <div class="grid md:grid-cols-12 gap-4">
            {{-- Ảnh --}}
            <div class="md:col-span-5">
                <div class="aspect-square rounded-xl overflow-hidden border border-rose-100 bg-white">
                    <img :src="$store.qv.data.image || 'https://placehold.co/800x800?text=IMG'"
                        class="w-full h-full object-cover" alt="">
                </div>
            </div>

            {{-- Thông tin --}}
            <div class="md:col-span-7">
                <a :href="$store.qv.data.url" class="text-sm text-brand-600 hover:underline">Xem chi tiết</a>
                <h1 class="text-xl font-bold mt-1" x-text="$store.qv.data.name"></h1>

                {{-- Giá --}}
                <template x-if="$store.qv.data.sale">
                    <div class="mt-2 flex items-baseline gap-2">
                        <div class="text-2xl text-brand-600 font-semibold" x-text="$store.qv.data.price_fmt"></div>
                        <div class="text-sm line-through text-ink/50" x-text="$store.qv.data.compare_fmt"></div>
                        <span class="text-xs px-2 py-1 rounded-full bg-rose-600 text-white" x-text="'-'+$store.qv.data.sale+'%'"></span>
                    </div>
                </template>
                <template x-if="!$store.qv.data.sale">
                    <div class="mt-2 text-2xl text-brand-600 font-semibold" x-text="$store.qv.data.price_fmt || 'Đang cập nhật'"></div>
                </template>

                {{-- Variants --}}
                <template x-if="($store.qv.data.variants || []).length">
                    <div class="mt-4">
                        <div class="text-sm font-medium mb-2">Phiên bản</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="v in $store.qv.data.variants" :key="v.id">
                                <label class="cursor-pointer">
                                    <input type="radio" name="qv_variant" class="peer sr-only" :value="v.id" :checked="v._first">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full border border-rose-200 bg-white text-sm
                               peer-checked:bg-brand-600 peer-checked:text-white"
                                        x-text="v.name + ' — ' + v.price_fmt"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Qty + CTA --}}
                <div class="mt-5 flex items-center gap-3">
                    <x-qty-stepper name="qv_qty" class="bg-white" />
                    <button class="px-4 py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700"
                        @click="
                    const vid = (document.querySelector('input[name=qv_variant]:checked')||{}).value || null;
                    const qty = parseInt((document.querySelector('input[name=qv_qty]')||{}).value||1);
                    $dispatch('cart:add', {
                      product_id: $store.qv.data.id,
                      variant_id: vid,
                      qty: qty
                    });
                    $store.qv.open=false;
                  ">
                        Thêm vào giỏ
                    </button>
                    <a :href="$store.qv.data.url" class="px-4 py-3 border border-rose-200 rounded-xl hover:bg-rose-50">Xem chi tiết</a>
                </div>

                {{-- Mô tả ngắn --}}
                <template x-if="$store.qv.data.short">
                    <div class="mt-4 text-sm text-ink/80 line-clamp-4" x-text="$store.qv.data.short"></div>
                </template>
            </div>
        </div>
    </div>
</div>