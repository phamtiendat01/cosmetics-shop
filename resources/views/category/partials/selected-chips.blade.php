@php
$chips = collect();
if(($v=request('min'))) $chips->push(['key'=>'min','label'=>'Giá từ '.number_format($v).'₫']);
if(($v=request('max'))) $chips->push(['key'=>'max','label'=>'Giá đến '.number_format($v).'₫']);
foreach((array)request('brand_ids',[]) as $bid){
$n = optional(\App\Models\Brand::find($bid))->name; if($n) $chips->push(['key'=>'brand_ids[]','val'=>$bid,'label'=>"Brand: $n"]);
}
if(($v=request('rating'))) $chips->push(['key'=>'rating','val'=>$v,'label'=>"Từ $v★"]);
if(request()->boolean('in_stock')) $chips->push(['key'=>'in_stock','val'=>1,'label'=>'Còn hàng']);
@endphp

@if($chips->count())
<div class="flex items-center gap-2 flex-wrap">
    @foreach($chips as $chip)
    @php
    $params = request()->all();
    if(isset($chip['val'])){
    if($chip['key']==='brand_ids[]'){
    $params['brand_ids'] = array_values(array_filter((array)($params['brand_ids'] ?? []), fn($v)=>$v != $chip['val']));
    if(empty($params['brand_ids'])) unset($params['brand_ids']);
    } else unset($params[$chip['key']]);
    } else unset($params[$chip['key']]);
    $url = url()->current().'?'.http_build_query($params);
    @endphp
    <a href="{{ $url }}" class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full border border-rose-200 text-sm">
        {{ $chip['label'] }} <i class="fa-regular fa-xmark text-ink/50"></i>
    </a>
    @endforeach
    <a href="{{ url()->current() }}" class="text-sm text-rose-600 hover:underline">Xoá tất cả</a>
</div>
@endif