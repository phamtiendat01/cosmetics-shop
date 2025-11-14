@php
$selectedBrands = (array) request()->input('brand_ids', []);
$minReq = (int) request('min', 0);
$maxReq = (int) request('max', 0);
$ratingReq = (int) request('rating', 0);
$inStock = request()->boolean('in_stock');
@endphp

<div x-data="{
    min: {{ $minReq ?: 0 }},
    max: {{ $maxReq ?: 0 }},
    minInit: 0, maxInit: 0, // có thể bind range thực tế nếu bạn truyền từ controller
    clamp(){
      if(this.max && this.min && this.max < this.min){ const t = this.min; this.min=this.max; this.max=t; }
    }
  }" class="bg-white border border-rose-100 rounded-2xl p-3">

    {{-- Chips: đang lọc --}}
    @php $active = collect(); @endphp
    @if($minReq) @php $active->push(['key'=>'min','label'=>'Giá từ '.number_format($minReq).'₫']); @endphp @endif
    @if($maxReq) @php $active->push(['key'=>'max','label'=>'Giá đến '.number_format($maxReq).'₫']); @endphp @endif
    @foreach($selectedBrands as $bid)
    @php $bname = optional(\App\Models\Brand::find($bid))->name; if($bname) $active->push(['key'=>"brand_ids[]",'val'=>$bid,'label'=>"Brand: $bname"]); @endphp
    @endforeach
    @if($ratingReq) @php $active->push(['key'=>'rating','val'=>$ratingReq,'label'=>"Từ $ratingReq★"]); @endphp @endif
    @if($inStock) @php $active->push(['key'=>'in_stock','val'=>1,'label'=>"Còn hàng"]); @endphp @endif

    @if($active->count())
    <div class="mb-3 flex items-center gap-2 flex-wrap">
        @foreach($active as $chip)
        @php
        $params = request()->all();
        if(isset($chip['val'])) {
        // xoá param mảng/param cụ thể
        if($chip['key']==='brand_ids[]'){
        $params['brand_ids'] = array_values(array_filter((array)($params['brand_ids'] ?? []), fn($v)=>$v != $chip['val']));
        if(empty($params['brand_ids'])) unset($params['brand_ids']);
        } else {
        unset($params[$chip['key']]);
        }
        } else {
        unset($params[$chip['key']]);
        }
        $clearUrl = url()->current().'?'.http_build_query($params);
        @endphp
        <a href="{{ $clearUrl }}" class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full border border-rose-200 text-sm">
            {{ $chip['label'] }}
            <i class="fa-regular fa-xmark text-ink/50"></i>
        </a>
        @endforeach

        {{-- Clear all --}}
        <a href="{{ url()->current() }}" class="text-sm text-rose-600 hover:underline">Xoá tất cả</a>
    </div>
    @endif

    <form method="get" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        {{-- Giá: slider đôi đơn giản bằng 2 input number (dễ server-side) --}}
        <div>
            <label class="block text-sm font-medium mb-1">Khoảng giá (₫)</label>
            <div class="flex items-center gap-2">
                <input type="number" x-model.number="min" @input="clamp()" name="min"
                    class="w-full px-3 py-2 border border-rose-200 rounded-md" placeholder="Từ">
                <span class="text-ink/40">—</span>
                <input type="number" x-model.number="max" @input="clamp()" name="max"
                    class="w-full px-3 py-2 border border-rose-200 rounded-md" placeholder="Đến">
            </div>
        </div>

        {{-- Brand: nhiều lựa chọn --}}
        <div>
            <label class="block text-sm font-medium mb-1">Thương hiệu</label>
            <div class="relative" x-data="{open:false}" @click.outside="open=false">
                <button type="button" @click="open=!open"
                    class="w-full px-3 py-2 border border-rose-200 rounded-md text-left flex items-center justify-between">
                    <span class="truncate">
                        @if(count($selectedBrands))
                        {{ count($selectedBrands) }} thương hiệu đã chọn
                        @else
                        Chọn thương hiệu
                        @endif
                    </span>
                    <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
                </button>
                <div x-show="open" x-transition class="absolute z-10 mt-1 w-full bg-white border border-rose-100 rounded-xl p-2 max-h-64 overflow-auto">
                    @foreach(\App\Models\Brand::orderBy('name')->get(['id','name']) as $b)
                    <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-rose-50">
                        <input type="checkbox" name="brand_ids[]" value="{{ $b->id }}" {{ in_array($b->id, $selectedBrands) ? 'checked' : '' }}>
                        <span class="text-sm">{{ $b->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Rating --}}
        <div>
            <label class="block text-sm font-medium mb-1">Đánh giá</label>
            <select name="rating" class="w-full px-3 py-2 border border-rose-200 rounded-md">
                <option value="">Bất kỳ</option>
                <option value="4" @selected($ratingReq===4)>Từ 4★</option>
                <option value="3" @selected($ratingReq===3)>Từ 3★</option>
                <option value="2" @selected($ratingReq===2)>Từ 2★</option>
            </select>
        </div>

        {{-- Kho --}}
        <div class="flex items-center gap-2">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="in_stock" value="1" @checked($inStock)>
                <span class="text-sm">Chỉ hiển thị hàng còn</span>
            </label>
            <button class="ml-auto px-3 py-2 bg-brand-600 text-white rounded-md">Áp dụng</button>
        </div>

        {{-- giữ các query khác khi submit --}}
        @foreach(request()->except(['min','max','brand_ids','rating','in_stock','page']) as $k=>$v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
    </form>
</div>