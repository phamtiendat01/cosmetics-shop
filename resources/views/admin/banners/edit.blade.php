@extends('admin.layouts.app')
@section('title','Sửa banner')

@section('content')
@if(session('ok')) <div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger mb-3">{{ $errors->first() }}</div> @endif

<form method="post" action="{{ route('admin.banners.update',$banner) }}" enctype="multipart/form-data" class="space-y-3">
    @csrf @method('PUT')
    <div class="card p-3">
        <div class="form-row-3">
            <div>
                <label class="label">Tiêu đề</label>
                <input name="title" value="{{ old('title',$banner->title) }}" class="form-control" required>
            </div>
            <div>
                <label class="label">Vị trí</label>
                <select name="position" class="form-control">
                    @foreach($positions as $k=>$v)
                    <option value="{{ $k }}" @selected(old('position',$banner->position)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Thiết bị</label>
                <select name="device" class="form-control">
                    @foreach($devices as $k=>$v)
                    <option value="{{ $k }}" @selected(old('device',$banner->device)===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Ảnh</label>
                <input type="file" name="image" class="form-control">
                <div class="help mt-1">Ảnh hiện tại:</div>
                <img class="mt-1 w-28 h-16 object-cover rounded" src="{{ asset('storage/'.$banner->image) }}" alt="">
            </div>
            <div>
                <label class="label">Ảnh Mobile</label>
                <input type="file" name="mobile_image" class="form-control">
                <div class="help mt-1">Hiện tại:</div>
                <img class="mt-1 w-28 h-16 object-cover rounded" src="{{ $banner->mobile_image ? asset('storage/'.$banner->mobile_image) : 'https://placehold.co/120x64?text=—' }}" alt="">
            </div>
            <div>
                <label class="label">Liên kết</label>
                <input name="url" class="form-control" value="{{ old('url',$banner->url) }}" placeholder="https://...">
            </div>

            <div>
                <label class="label">Mở tab mới?</label>
                <select name="open_in_new_tab" class="form-control">
                    <option value="0" @selected(old('open_in_new_tab',(int)$banner->open_in_new_tab)===0)>Không</option>
                    <option value="1" @selected(old('open_in_new_tab',(int)$banner->open_in_new_tab)===1)>Có</option>
                </select>
            </div>
            <div>
                <label class="label">Thứ tự</label>
                <input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order',$banner->sort_order) }}">
            </div>
            <div>
                <label class="label">Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" @selected(old('is_active',(int)$banner->is_active)===1)>Bật</option>
                    <option value="0" @selected(old('is_active',(int)$banner->is_active)===0)>Tắt</option>
                </select>
            </div>

            <div>
                <label class="label">Bắt đầu</label>
                <input name="starts_at" id="startsAt" value="{{ old('starts_at', optional($banner->starts_at)->format('Y-m-d H:i')) }}" class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>
            <div>
                <label class="label">Kết thúc</label>
                <input name="ends_at" id="endsAt" value="{{ old('ends_at', optional($banner->ends_at)->format('Y-m-d H:i')) }}" class="form-control" placeholder="YYYY-MM-DD HH:mm">
            </div>
        </div>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-outline">← Danh sách</a>
        <div class="flex gap-2">
            <button class="btn btn-primary">Lưu thay đổi</button>
            <form method="post" action="{{ route('admin.banners.destroy',$banner) }}" onsubmit="return confirm('Xoá banner này?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">Xoá</button>
            </form>
        </div>
    </div>
</form>

@push('scripts')
<script>
    if (window.flatpickr) {
        flatpickr('#startsAt', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
        flatpickr('#endsAt', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
    }
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.classList.add('alert--hide');
            setTimeout(() => el.remove(), 350)
        }, +el.dataset.autoDismiss || 3000)
    });
</script>
@endpush
@endsection