@props(['products' => collect()])

<div class="relative">
    <div class="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory pb-2">
        @forelse($products as $p)
        <div class="min-w-[180px] max-w-[180px] snap-start">
            <x-product-card :product="$p" />
        </div>
        @empty
        @for($i=0;$i<10;$i++)
            <div class="min-w-[180px] max-w-[180px] snap-start bg-white border border-rose-100 rounded-xl h-[280px]">
    </div>
    @endfor
    @endforelse
</div>
</div>