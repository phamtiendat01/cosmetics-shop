{{-- resources/views/components/setting/input.blade.php --}}
@props([
'label' => '',
'name' => '',
'type' => 'text',
'value' => '',
'placeholder' => '',
'required' => false,
'min' => null,
'max' => null,
'step' => null,
])

@php
// a[b][c] -> a.b.c để @error hoạt động
$dotName = str_replace(['[',']'], ['.',''], $name);
@endphp

<div>
    <label class="block text-sm font-medium mb-1">
        {{ $label }} @if($required)<span class="text-red-600">*</span>@endif
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($dotName, $value) }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if(!is_null($min)) min="{{ $min }}" @endif
        @if(!is_null($max)) max="{{ $max }}" @endif
        @if(!is_null($step)) step="{{ $step }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded border-slate-300']) }}>

    @error($dotName)
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>