@props(['status'])

@php
$map = [
'pending' => ['Chờ xác nhận','bg-amber-50 text-amber-700 border-amber-200'],
'confirmed' => ['Đã xác nhận','bg-emerald-50 text-emerald-700 border-emerald-200'],
'processing' => ['Đang xử lý','bg-blue-50 text-blue-700 border-blue-200'],
'shipping' => ['Đang giao','bg-indigo-50 text-indigo-700 border-indigo-200'],
'completed' => ['Hoàn tất','bg-emerald-50 text-emerald-700 border-emerald-200'],
'cancelled' => ['Đã hủy','bg-rose-50 text-rose-700 border-rose-200'],
'refunded' => ['Đã hoàn tiền','bg-slate-50 text-slate-700 border-slate-200'],
];
[$label,$cls] = $map[$status] ?? ['Không rõ','bg-slate-50 text-slate-700 border-slate-200'];
@endphp

<span {{ $attributes->merge([
  'class'=>"inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border $cls"
]) }}>
  <i class="fa-solid fa-circle text-[6px]"></i> {{ $label }}
</span>