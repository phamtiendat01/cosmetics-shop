@php
/**
* Biến $headerCats phải là danh sách danh mục CHA có children,
* ví dụ mỗi item: { id, name, slug, children: [ {id,name,slug}, ... ] }
*/
$parents = $headerCats ?? collect();
@endphp

<div x-data="catFlyout()" class="relative">
    <!-- Nút mở flyout -->
    <button type="button"
        @mouseenter="open()"
        @click="toggle()"
        class="flex items-center gap-2 py-3 px-3 rounded-lg hover:bg-rose-50">
        <i class="fa-solid fa-bars-staggered"></i>
        <span class="font-medium">Danh mục</span>
        <i class="fa-solid fa-chevron-down text-xs text-ink/60"></i>
    </button>

    <!-- Flyout -->
    <div x-show="isOpen"
        x-transition.opacity
        @mouseleave="close()"
        @keydown.escape.window="close()"
        class="absolute left-0 top-full mt-2 w-[720px] bg-white border border-rose-100 rounded-2xl shadow-card overflow-hidden z-[300]"
        x-cloak>
        <div class="grid grid-cols-12">
            <!-- Cột trái: danh mục cha -->
            <div class="col-span-5 max-h-[360px] overflow-auto bg-rose-50/40">
                @foreach($parents as $idx => $p)
                <button type="button"
                    @mouseenter="active={{ $idx }}"
                    :class="active==={{ $idx }} ? 'bg-white text-brand-700' : 'hover:bg-white/70'"
                    class="w-full text-left px-4 py-3 border-b border-rose-100 flex items-center justify-between">
                    <span class="truncate">{{ $p->name }}</span>
                    <i class="fa-solid fa-chevron-right text-xs opacity-60"></i>
                </button>
                @endforeach
            </div>

            <!-- Cột phải: con của danh mục đang active -->
            <div class="col-span-7 p-4">
                @foreach($parents as $idx => $p)
                <div x-show="active==={{ $idx }}" x-transition.opacity>
                    @if(($p->children ?? collect())->count())
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($p->children as $c)
                        <a href="{{ route('category.show', $c->slug) }}"
                            class="px-3 py-2 rounded-lg border border-rose-100 hover:border-brand-500 hover:bg-rose-50/60">
                            {{ $c->name }}
                        </a>
                        @endforeach
                    </div>
                    @else
                    <div class="text-sm text-ink/60">Chưa có danh mục con.</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function catFlyout() {
        return {
            isOpen: false,
            active: 0,
            open() {
                this.isOpen = true
            },
            close() {
                this.isOpen = false
            },
            toggle() {
                this.isOpen = !this.isOpen
            }
        }
    }
</script>
@endpush