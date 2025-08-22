@php
$selectedBrands = (array) request()->input('brand_ids', []);
$minReq = (int) request('min', 0);
$maxReq = (int) request('max', 0);
$ratingReq = (int) request('rating', 0);
$inStock = request()->boolean('in_stock');
@endphp

<div class="space-y-3">
    {{-- Chips --}}
    @includeWhen(true, 'category.partials.selected-chips')

    <form method="get" class="space-y-3">
        <x-accordion title="Khoảng giá (₫)" :open="true">
            <div class="flex items-center gap-2">
                <input type="number" name="min" value="{{ $minReq }}" class="w-full px-3 py-2 border border-rose-200 rounded-md" placeholder="Từ">
                <span class="text-ink/40">—</span>
                <input type="number" name="max" value="{{ $maxReq }}" class="w-full px-3 py-2 border border-rose-200 rounded-md" placeholder="Đến">
            </div>
        </x-accordion>

        <x-accordion title="Thương hiệu">
            <div class="max-h-64 overflow-auto pr-1 space-y-1">
                @foreach(\App\Models\Brand::orderBy('name')->get(['id','name']) as $b)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="brand_ids[]" value="{{ $b->id }}" {{ in_array($b->id, $selectedBrands) ? 'checked' : '' }}>
                    <span>{{ $b->name }}</span>
                </label>
                @endforeach
            </div>
        </x-accordion>

        <x-accordion title="Đánh giá">
            <select name="rating" class="w-full px-3 py-2 border border-rose-200 rounded-md">
                <option value="">Bất kỳ</option>
                <option value="4" @selected($ratingReq===4)>Từ 4★</option>
                <option value="3" @selected($ratingReq===3)>Từ 3★</option>
                <option value="2" @selected($ratingReq===2)>Từ 2★</option>
            </select>
        </x-accordion>

        <x-accordion title="Tình trạng">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="in_stock" value="1" @checked($inStock)>
                <span>Chỉ hiển thị hàng còn</span>
            </label>
        </x-accordion>

        {{-- giữ các query khác khi submit --}}
        @foreach(request()->except(['min','max','brand_ids','rating','in_stock','page']) as $k=>$v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach

        <button class="w-full px-3 py-2 bg-brand-600 text-white rounded-md">Áp dụng</button>
    </form>
</div>