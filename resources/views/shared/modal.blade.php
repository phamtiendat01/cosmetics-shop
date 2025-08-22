@props([
'id' => 'modal',
'title' => null,
])

<div x-data="{ open: false }" x-show="open"
    id="{{ $id }}"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    style="display: none;">
    <div @click.away="open=false"
        class="bg-white rounded-2xl shadow-card w-full max-w-lg p-6 relative">
        {{-- Close --}}
        <button type="button" @click="open=false"
            class="absolute top-3 right-3 text-ink/60 hover:text-ink">
            <i class="fa-solid fa-xmark"></i>
        </button>

        {{-- Tiêu đề modal --}}
        @if($title)
        <h3 class="text-lg font-semibold mb-4">{{ $title }}</h3>
        @endif

        {{-- Nội dung modal --}}
        <div class="text-sm text-ink">
            {{ $slot }}
        </div>
    </div>
</div>