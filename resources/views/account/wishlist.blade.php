@extends('layouts.app')
@section('title','Danh sách yêu thích | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6 grid md:grid-cols-12 gap-6">
    <aside class="md:col-span-3 space-y-2">
        @include('account._menu', ['active' => 'wishlist'])
    </aside>

    <main class="md:col-span-9">
        <div class="bg-white border border-rose-100 rounded-2xl p-4">
            <h1 class="text-lg font-bold mb-3">Yêu thích</h1>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @forelse($wishlistProducts as $p)
                <x-product-card :product="$p" />
                @empty
                <x-empty text="Danh sách yêu thích trống." />
                @endforelse
            </div>

            <div class="mt-4">
                {{ $wishlistProducts->onEachSide(1)->links('shared.pagination') }}
            </div>
        </div>
    </main>
</section>
@endsection