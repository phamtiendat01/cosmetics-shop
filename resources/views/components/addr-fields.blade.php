@props(['addr'=>null])

<div>
    <label class="text-sm font-medium">Người nhận</label>
    <input name="name" value="{{ old('name', $addr->name ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>
<div>
    <label class="text-sm font-medium">Số điện thoại</label>
    <input name="phone" value="{{ old('phone', $addr->phone ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>
<div class="md:col-span-2">
    <label class="text-sm font-medium">Địa chỉ</label>
    <input name="line1" value="{{ old('line1', $addr->line1 ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300" placeholder="Số nhà, đường...">
</div>
<div class="md:col-span-2">
    <label class="text-sm font-medium">Địa chỉ bổ sung (không bắt buộc)</label>
    <input name="line2" value="{{ old('line2', $addr->line2 ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>
<div>
    <label class="text-sm font-medium">Phường/Xã</label>
    <input name="ward" value="{{ old('ward', $addr->ward ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>
<div>
    <label class="text-sm font-medium">Quận/Huyện</label>
    <input name="district" value="{{ old('district', $addr->district ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>
<div>
    <label class="text-sm font-medium">Tỉnh/Thành phố</label>
    <input name="province" value="{{ old('province', $addr->province ?? '') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>
<div>
    <label class="text-sm font-medium">Quốc gia</label>
    <input name="country" value="{{ old('country', $addr->country ?? 'Việt Nam') }}"
        class="mt-1 w-full px-3 py-2 rounded border border-rose-200 outline-none focus:ring-2 focus:ring-brand-300">
</div>