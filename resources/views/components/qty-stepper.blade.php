@props(['name' => 'qty', 'min' => 1, 'max' => 99, 'value' => 1, 'class' => ''])
<div x-data="{v: {{ (int)$value }}, min: {{ (int)$min }}, max: {{ (int)$max }} }"
    class="inline-flex items-stretch border border-rose-200 rounded-lg overflow-hidden {{ $class }}">
    <button type="button" class="px-3 hover:bg-rose-50" @click="v=Math.max(min, v-1)"><i class="fa-solid fa-minus"></i></button>
    <input type="number" :value="v" @input="v = Math.max(min, Math.min(max, +$event.target.value||min))"
        class="w-14 text-center outline-none" name="{{ $name }}" min="{{ $min }}" max="{{ $max }}">
    <button type="button" class="px-3 hover:bg-rose-50" @click="v=Math.min(max, v+1)"><i class="fa-solid fa-plus"></i></button>
</div>