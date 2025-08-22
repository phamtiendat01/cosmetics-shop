@props(['icon' => 'fa-regular fa-box-open', 'text' => 'Chưa có dữ liệu.'])

<div class="col-span-full text-center py-10">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-50 text-brand-600 mb-2">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="text-sm text-ink/60">{{ $text }}</div>
</div>