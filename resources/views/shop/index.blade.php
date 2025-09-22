@extends('layouts.app')
@section('title', ($q ? "Tìm: $q" : 'Cửa hàng').' | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6">
    {{-- breadcrumb --}}
    <div class="text-sm text-ink/60 mb-3">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Trang chủ</a> /
        <span class="text-ink">{{ $q ? "Kết quả cho \"$q\"" : "Cửa hàng" }}</span>
    </div>

    <div class="flex items-end justify-between gap-3">
        <h1 class="text-xl font-bold">
            {{ $q ? "Kết quả cho \"$q\"" : "Tất cả sản phẩm" }}
        </h1>

        {{-- Sort (giống category) --}}
        <form method="get" class="flex items-center gap-2">
            @foreach(request()->except('sort') as $k=>$v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <select name="sort" class="form-control border border-rose-200 rounded-md px-2 py-1.5 text-sm"
                onchange="this.form.submit()">
                <option value="">Mới nhất</option>
                <option value="price_asc" @selected(request('sort')==='price_asc' )>Giá ↑</option>
                <option value="price_desc" @selected(request('sort')==='price_desc' )>Giá ↓</option>
            </select>
        </form>
    </div>

    {{-- Filters nhanh: giá & brand (tuỳ chọn) --}}
    <div class="mt-3">
        <form method="get" class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="number" name="min" value="{{ request('min') }}" class="form-control border border-rose-200 rounded-md px-3 py-2" placeholder="Giá từ">
            <input type="number" name="max" value="{{ request('max') }}" class="form-control border border-rose-200 rounded-md px-3 py-2" placeholder="Giá đến">
            <select name="brand_id" class="form-control border border-rose-200 rounded-md px-3 py-2">
                <option value="">Thương hiệu</option>
                @foreach(\App\Models\Brand::orderBy('name')->get(['id','name']) as $b)
                <option value="{{ $b->id }}" @selected(request('brand_id')==$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-soft px-3 py-2 rounded-md border border-rose-200">Lọc</button>
        </form>
    </div>

    {{-- Grid kết quả --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mt-4">
        @forelse($products as $p)
        <x-product-card :product="$p" />
        @empty
        <x-empty text="Không tìm thấy sản phẩm phù hợp." />
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $products->onEachSide(1)->links('shared.pagination') }}
    </div>
</section>
@endsection