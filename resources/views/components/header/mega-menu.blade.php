{{-- Mega menu đơn giản: danh mục cha + con --}}
@props(['tree' => $megaTree ?? collect()])
<div class="absolute left-0 top-full mt-1 w-[920px] bg-white border border-rose-100 rounded-2xl shadow-card p-4 grid grid-cols-12 gap-4 z-50">
    <div class="col-span-4 border-r pr-2 max-h-[420px] overflow-auto">
        @foreach($tree as $p)
        <div class="px-3 py-2 font-medium">{{ $p->name }}</div>
        @endforeach
    </div>
    <div class="col-span-8 pl-1">
        <div class="grid grid-cols-2 gap-2">
            @foreach(($tree->first()->children ?? collect()) as $c)
            <a href="{{ route('category.show',$c->slug) }}" class="px-3 py-2 rounded-md hover:bg-rose-50">{{ $c->name }}</a>
            @endforeach
        </div>
    </div>
</div>