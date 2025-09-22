@props(['icon'=>'fa-solid fa-box','value'=>0,'label'=>''])
<div class="p-4 rounded-xl border border-rose-100 bg-white">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-rose-50 grid place-items-center text-rose-600">
            <i class="{{ $icon }}"></i>
        </div>
        <div>
            <div class="text-xl font-bold">{{ $value }}</div>
            <div class="text-sm text-ink/60">{{ $label }}</div>
        </div>
    </div>
</div>