@extends('admin.layouts.app')
@section('title','Cấu hình Trang chủ')

@section('content')
@if(session('ok'))
<div class="mb-3 rounded bg-green-50 text-green-700 px-4 py-2">{{ session('ok') }}</div>
@endif

<form method="POST" action="{{ route('admin.homepage.store') }}" class="space-y-6">
    @csrf

    {{-- Banners --}}
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <div class="font-semibold">Banners (Hero)</div>
            <button type="button" onclick="addBanner()" class="text-sm px-3 py-1 rounded border">Thêm banner</button>
        </div>
        <div id="bannerList" class="mt-3 space-y-3">
            @foreach($values['banners'] as $i => $b)
            <div class="grid md:grid-cols-4 gap-2 items-center banner-row">
                <input name="banners[{{$i}}][image]" class="border rounded px-2 py-1" placeholder="URL ảnh" value="{{ $b['image'] ?? '' }}">
                <input name="banners[{{$i}}][title]" class="border rounded px-2 py-1" placeholder="Tiêu đề" value="{{ $b['title'] ?? '' }}">
                <input name="banners[{{$i}}][subtitle]" class="border rounded px-2 py-1" placeholder="Mô tả ngắn" value="{{ $b['subtitle'] ?? '' }}">
                <div class="flex gap-2">
                    <input name="banners[{{$i}}][link]" class="border rounded px-2 py-1 flex-1" placeholder="Link (tùy chọn)" value="{{ $b['link'] ?? '' }}">
                    <button type="button" class="px-2 py-1 border rounded" onclick="this.closest('.banner-row').remove()">X</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Featured Categories --}}
    <div class="card p-4">
        <div class="font-semibold mb-2">Danh mục nổi bật</div>
        <select id="catSelect" name="featured_category_ids[]" multiple>
            @foreach($allCategories as $c)
            <option value="{{ $c->id }}" @selected(in_array($c->id,$values['featured_category_ids']))>{{ $c->name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-2">Chọn 6 danh mục để hiển thị ngoài trang chủ.</p>
    </div>

    {{-- Featured Brands --}}
    <div class="card p-4">
        <div class="font-semibold mb-2">Thương hiệu nổi bật</div>
        <select id="brandSelect" name="featured_brand_ids[]" multiple>
            @foreach($allBrands as $b)
            <option value="{{ $b->id }}" @selected(in_array($b->id,$values['featured_brand_ids']))>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Deal Hot (chọn sản phẩm) --}}
    <div class="card p-4">
        <div class="font-semibold mb-2">Deal hot hôm nay</div>
        <select id="dealSelect" name="deal_product_ids[]" multiple>
            @foreach($allProducts as $p)
            <option value="{{ $p->id }}" @selected(in_array($p->id,$values['deal_product_ids']))>{{ $p->name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-2">Chọn tối đa ~8 sản phẩm.</p>
    </div>

    <div>
        <button class="px-5 py-2 rounded bg-rose-600 text-white">Lưu trang chủ</button>
    </div>
</form>

@push('scripts')
<script>
    // Tom Select (multi-select đẹp)
    new TomSelect("#catSelect", {
        plugins: ['remove_button'],
        maxItems: 6,
        create: false,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });
    new TomSelect("#brandSelect", {
        plugins: ['remove_button'],
        maxItems: 10,
        create: false
    });
    new TomSelect("#dealSelect", {
        plugins: ['remove_button'],
        maxItems: 8,
        create: false
    });

    // Thêm dòng banner
    function addBanner() {
        const list = document.getElementById('bannerList');
        const idx = list.querySelectorAll('.banner-row').length;
        const row = document.createElement('div');
        row.className = 'grid md:grid-cols-4 gap-2 items-center banner-row';
        row.innerHTML = `
      <input name="banners[${idx}][image]" class="border rounded px-2 py-1" placeholder="URL ảnh">
      <input name="banners[${idx}][title]" class="border rounded px-2 py-1" placeholder="Tiêu đề">
      <input name="banners[${idx}][subtitle]" class="border rounded px-2 py-1" placeholder="Mô tả ngắn">
      <div class="flex gap-2">
        <input name="banners[${idx}][link]" class="border rounded px-2 py-1 flex-1" placeholder="Link (tùy chọn)">
        <button type="button" class="px-2 py-1 border rounded" onclick="this.closest('.banner-row').remove()">X</button>
      </div>`;
        list.appendChild(row);
    }
</script>
@endpush
@endsection