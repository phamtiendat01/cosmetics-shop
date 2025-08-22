@extends('layouts.app')
@section('title', ($brand->name ?? 'Thương hiệu').' | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6">
    {{-- breadcrumb --}}
    <div class="text-sm text-ink/60 mb-3">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Trang chủ</a> /
        <span class="text-ink">{{ $brand->name }}</span>
    </div>

    <div class="bg-white border border-rose-100 rounded-2xl p-4 flex items-center gap-4">
        @if($brand->logo)
        <img src="{{ asset('storage/'.$brand->logo) }}" class="w-16 h-16 object-contain rounded-md" alt="{{ $brand->name }}">
        @else
        <div class="w-16 h-16 rounded-md bg-rose-50 grid place-items-center text-2xl">🅱</div>
        @endif
        <div>
            <h1 class="text-xl font-bold">{{ $brand->name }}</h1>
            @if($brand->website)
            <a href="{{ $brand->website }}" target="_blank" class="text-sm text-brand-600 hover:underline">{{ $brand->website }}</a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @forelse($products as $p)
        <x-product-card :product="$p" />
        @empty
        <x-empty text="Thương hiệu này chưa có sản phẩm." />
        @endforelse
    </div>

    <div class="mt-6">
        {{ $products->onEachSide(1)->links('pagination::tailwind') }}
    </div>
</section>
@endsection