@extends('layouts.app')

@section('title','Yêu thích')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Sản phẩm yêu thích</h1>
        <a href="{{ route('shop.index') }}" class="text-rose-600 hover:underline">Tiếp tục mua sắm</a>
    </div>

    @if(($products ?? collect())->isEmpty())
    <div class="p-8 bg-white border border-rose-100 rounded-xl text-ink/70">
        Bạn chưa thêm sản phẩm nào vào danh sách yêu thích.
    </div>
    @else
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" data-wave=".js-card">
        @foreach($products as $product)
        <x-product-card :product="$product" />
        @endforeach
    </div>
    @endif
</div>
@endsection