{{-- components/header/mega-menu.blade.php --}}
@php
// Nhận dữ liệu từ include
$roots = $tree ?? collect();
$roots = $roots instanceof \Illuminate\Support\Collection
? $roots->values()->take(6)
: collect($roots)->values()->take(6);
@endphp

<ul class="flex items-center gap-6 whitespace-nowrap">
    @foreach($roots as $cat)
    {{-- pb-2 = hover bridge để không bị “rơi” hover khi di chuyển xuống panel --}}
    <li class="relative group pb-2">
        <a href="{{ route('category.show', $cat->slug) }}"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-rose-50">
            {{ $cat->name }}
            @if(!empty($cat->children) && count($cat->children))
            <i class="fa-solid fa-chevron-down text-xs opacity-60"></i>
            @endif
        </a>

        {{-- MEGA DROPDOWN --}}
        @if(!empty($cat->children) && count($cat->children))
        <div
            class="absolute left-0 top-full w-[min(960px,calc(100vw-2rem))] bg-white border border-rose-100
                 rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,.08)] p-5
                 opacity-0 invisible translate-y-1 transition
                 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0
                 z-[80]">
            <div class="grid grid-cols-3 gap-6">
                @foreach($cat->children as $child)
                <div>
                    <a href="{{ route('category.show', $child->slug) }}"
                        class="font-medium hover:text-rose-600">{{ $child->name }}</a>

                    @if(!empty($child->children) && count($child->children))
                    <ul class="mt-2 space-y-1">
                        @foreach($child->children as $gchild)
                        <li>
                            <a href="{{ route('category.show', $gchild->slug) }}"
                                class="text-sm text-gray-600 hover:text-gray-900">{{ $gchild->name }}</a>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </li>
    @endforeach
</ul>