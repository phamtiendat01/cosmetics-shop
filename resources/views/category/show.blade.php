@extends('layouts.app')
@section('title', $cat->name.' | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6">
    {{-- breadcrumb --}}
    <div class="text-sm text-ink/60 mb-3">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Trang chủ</a> /
        @if($cat->parent)
        <a href="{{ route('category.show',$cat->parent->slug) }}" class="hover:text-brand-600">{{ $cat->parent->name }}</a> /
        @endif
        <span class="text-ink">{{ $cat->name }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- SIDEBAR --}}
        <aside class="lg:col-span-3">
            <div class="sticky top-[84px]">
                @include('category.partials.filters-sidebar')
            </div>
        </aside>

        {{-- MAIN --}}
        <main class="lg:col-span-9">
            <div class="flex items-end justify-between gap-3">
                <h1 class="text-xl font-bold">{{ $cat->name }}</h1>
                @include('category.partials.sort')
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mt-4">
                @forelse($products as $p)
                <x-product-card :product="$p" />
                @empty
                <x-empty text="Chưa có sản phẩm." />
                @endforelse
            </div>

            <div class="mt-6">
                {{ $products->onEachSide(1)->links('shared.pagination') }}
            </div>
        </main>
    </div>
</section>
@endsection