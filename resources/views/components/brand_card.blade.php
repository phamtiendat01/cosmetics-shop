@props(['brand'])

<a href="{{ route('brand.show',$brand->slug) }}"
    class="group flex items-center gap-3 p-3 bg-white border border-rose-100 rounded-xl hover:shadow-card transition">
    @if($brand->logo)
    <img src="{{ asset('storage/'.$brand->logo) }}" alt="{{ $brand->name }}"
        class="w-12 h-12 object-contain rounded-md bg-white">
    @else
    <div class="w-12 h-12 rounded-md bg-rose-50 grid place-items-center text-brand-600 font-bold">
        {{ Str::substr($brand->name,0,1) }}
    </div>
    @endif
    <div class="font-medium group-hover:text-brand-600">{{ $brand->name }}</div>
</a>