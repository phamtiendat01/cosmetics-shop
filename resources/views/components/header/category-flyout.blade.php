{{-- Flyout “Danh mục”: trái = cha, phải = con --}}
<div class="relative"
    x-data="{ open:false, active: {{ optional(($megaTree ?? collect())->first())->id ?? 'null' }} }"
    @mouseenter="open=true" @mouseleave="open=false">

    <button type="button" class="py-3 font-medium flex items-center gap-2 hover:text-brand-600">
        <i class="fa-solid fa-bars"></i>
        Danh mục
    </button>

    <div x-show="open" x-transition
        class="absolute left-0 top-full mt-1 w-[920px] bg-white border border-rose-100 rounded-2xl shadow-card p-4 grid grid-cols-12 gap-4 z-50">

        {{-- Cột trái: danh mục cha --}}
        <div class="col-span-4 border-r pr-2 max-h-[420px] overflow-auto">
            @forelse(($megaTree ?? collect()) as $p)
            <a href="{{ route('category.show',$p->slug) }}"
                @mouseenter="active={{ $p->id }}"
                class="group flex items-center justify-between px-3 py-2 rounded-md hover:bg-rose-50">
                <span class="font-medium">{{ $p->name }}</span>
                <i class="fa-solid fa-angle-right text-xs text-ink/50 group-hover:text-ink"></i>
            </a>
            @empty
            <div class="text-sm text-ink/50 px-3 py-2">Chưa có danh mục.</div>
            @endforelse
        </div>

        {{-- Cột phải: danh mục con của cha đang active --}}
        <div class="col-span-8 pl-1">
            @foreach(($megaTree ?? collect()) as $p)
            <div x-show="active == {{ $p->id }}" x-transition.opacity.duration.120ms>
                <div class="grid grid-cols-2 gap-2">
                    @forelse($p->children as $c)
                    <a href="{{ route('category.show',$c->slug) }}"
                        class="px-3 py-2 rounded-md hover:bg-rose-50">{{ $c->name }}</a>
                    @empty
                    <div class="text-sm text-ink/50 px-3 py-2">Danh mục này chưa có mục con.</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>