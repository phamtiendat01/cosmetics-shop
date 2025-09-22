@extends('admin.layouts.app')
@section('title','Sửa mã vận chuyển')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger mb-3">
    <div class="font-semibold mb-1">Vui lòng kiểm tra lại:</div>
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Sửa mã vận chuyển <span class="text-slate-500">#{{ $voucher->code }}</span></div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.shipvouchers.index') }}" class="btn btn-outline btn-sm">Quay lại</a>
    </div>
</div>

<form action="{{ route('admin.shipvouchers.update',$voucher) }}" method="post" class="grid md:grid-cols-3 gap-4">
    @csrf @method('PUT')

    <div class="md:col-span-2 space-y-4">
        <div class="card p-4">
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Mã</label>
                    <input name="code" class="form-control" value="{{ old('code',$voucher->code) }}">
                </div>
                <div>
                    <label class="form-label">Tên hiển thị</label>
                    <input name="title" class="form-control" value="{{ old('title',$voucher->title) }}">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" rows="2" class="form-control">{{ old('description',$voucher->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="grid md:grid-cols-3 gap-3">
                <div>
                    <label class="form-label">Loại giảm</label>
                    <select name="discount_type" class="form-control">
                        <option value="fixed" @selected(old('discount_type',$voucher->discount_type)==='fixed')>Số tiền cố định</option>
                        <option value="percent" @selected(old('discount_type',$voucher->discount_type)==='percent')>% theo phí VC</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Giá trị</label>
                    <input name="amount" type="number" min="0" class="form-control" value="{{ old('amount',$voucher->amount) }}">
                </div>
                <div>
                    <label class="form-label">Tối đa (nếu %)</label>
                    <input name="max_discount" type="number" min="0" class="form-control" value="{{ old('max_discount',$voucher->max_discount) }}">
                </div>

                <div>
                    <label class="form-label">Đơn tối thiểu</label>
                    <input name="min_order" type="number" min="0" class="form-control" value="{{ old('min_order',$voucher->min_order) }}">
                </div>
                <div>
                    <label class="form-label">Giới hạn tổng</label>
                    <input name="usage_limit" type="number" min="0" class="form-control" value="{{ old('usage_limit',$voucher->usage_limit) }}">
                </div>
                <div>
                    <label class="form-label">Giới hạn / user</label>
                    <input name="per_user_limit" type="number" min="0" class="form-control" value="{{ old('per_user_limit',$voucher->per_user_limit) }}">
                </div>

                <div>
                    <label class="form-label">Bắt đầu</label>
                    <input name="start_at" type="datetime-local" class="form-control"
                        value="{{ old('start_at', optional($voucher->start_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label class="form-label">Kết thúc</label>
                    <input name="end_at" type="datetime-local" class="form-control"
                        value="{{ old('end_at', optional($voucher->end_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label class="form-label">Loại mã</label>
                    <select class="form-control" disabled>
                        <option>Mã vận chuyển</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Hãng vận chuyển áp dụng</label>
                    @php
                    $currentCarriers = (array) old('carriers', (array)$voucher->carriers);
                    @endphp
                    @if(!empty($carrierOptions))
                    <select name="carriers[]" class="form-control" multiple size="5">
                        @foreach($carrierOptions as $code => $name)
                        <option value="{{ $code }}" @selected(in_array($code,$currentCarriers))>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                    <div class="text-xs text-slate-500 mt-1">Giữ Ctrl/Cmd để chọn nhiều.</div>
                    @else
                    <input name="carriers" class="form-control"
                        value="{{ old('carriers', is_array($voucher->carriers) ? implode(',',$voucher->carriers) : $voucher->carriers) }}"
                        placeholder="VD: GHN,GHTK,J&T">
                    @endif
                </div>

                <div>
                    <label class="form-label">Khu vực (tuỳ chọn)</label>
                    <input name="regions" class="form-control"
                        value="{{ old('regions', is_array($voucher->regions) ? implode(',',$voucher->regions) : $voucher->regions) }}"
                        placeholder="VD: HN,HCM">
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-4">
            <label class="form-label">Trạng thái</label>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" class="form-checkbox"
                    @checked(old('is_active', (bool)$voucher->is_active))>
                <span>Bật</span>
            </div>
        </div>

        <div class="card p-4">
            <button class="btn btn-primary w-full">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Cập nhật
            </button>
        </div>
    </div>
</form>
@endsection