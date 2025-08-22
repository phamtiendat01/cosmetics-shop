@extends('layouts.app')
@section('title','Ví voucher | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6 grid md:grid-cols-12 gap-6">
    <aside class="md:col-span-3 space-y-2">
        @include('account._menu', ['active' => 'coupons'])
    </aside>

    <main class="md:col-span-9">
        <div class="bg-white border border-rose-100 rounded-2xl p-4">
            <h1 class="text-lg font-bold mb-3">Ví voucher</h1>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse($coupons as $cp)
                <div class="border border-rose-100 rounded-xl p-4">
                    <div class="text-sm text-ink/60">Mã</div>
                    <div class="text-lg font-bold tracking-wide">{{ $cp->code }}</div>
                    <div class="text-sm mt-2">
                        @if($cp->discount_type==='percent')
                        Giảm {{ $cp->discount_value }}%
                        @else
                        Giảm {{ number_format($cp->discount_value) }}₫
                        @endif
                    </div>
                    @if($cp->ends_at)
                    <div class="text-xs text-ink/60 mt-1">HSD: {{ \Carbon\Carbon::parse($cp->ends_at)->format('d/m/Y H:i') }}</div>
                    @endif
                    <button class="mt-3 w-full px-3 py-2 bg-brand-600 text-white rounded-md">Sao chép mã</button>
                </div>
                @empty
                <x-empty text="Chưa có voucher nào." />
                @endforelse
            </div>

            <div class="mt-4">
                {{ $coupons->onEachSide(1)->links('shared.pagination') }}
            </div>
        </div>
    </main>
</section>
@endsection