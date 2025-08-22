@extends('layouts.app')
@section('title','Sổ địa chỉ | Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6 grid md:grid-cols-12 gap-6">
    <aside class="md:col-span-3 space-y-2">
        @include('account._menu', ['active' => 'addresses'])
    </aside>

    <main class="md:col-span-9">
        <div class="bg-white border border-rose-100 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold">Sổ địa chỉ</h1>
                <button class="px-3 py-2 bg-brand-600 text-white rounded-md">Thêm địa chỉ</button>
            </div>

            <div class="grid sm:grid-cols-2 gap-3 mt-4">
                @forelse($addresses as $ad)
                <div class="border border-rose-100 rounded-xl p-3">
                    <div class="font-medium">{{ $ad->full_name }}</div>
                    <div class="text-sm text-ink/70">{{ $ad->phone }}</div>
                    <div class="text-sm text-ink/80 mt-1 whitespace-pre-line">{{ $ad->full_address }}</div>
                    <div class="mt-2 flex gap-2">
                        <button class="px-3 py-1.5 border border-rose-200 rounded-md">Sửa</button>
                        <button class="px-3 py-1.5 border border-rose-200 rounded-md">Xóa</button>
                    </div>
                </div>
                @empty
                <x-empty text="Chưa có địa chỉ nào." />
                @endforelse
            </div>
        </div>
    </main>
</section>
@endsection