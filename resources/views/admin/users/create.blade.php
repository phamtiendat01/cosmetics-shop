@extends('admin.layouts.app')
@section('title','Thêm quản trị viên')

@section('content')
@if ($errors->any())
<div class="alert alert-danger mb-3">
    @foreach ($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Thêm quản trị viên</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">← Quay lại</a>
    </div>
</div>

<div class="card p-4">
    <form action="{{ route('admin.users.store') }}" method="POST" class="grid md:grid-cols-2 gap-4">
        @csrf

        <div>
            <label class="form-label">Họ tên</label>
            <input name="name" value="{{ old('name') }}" class="form-control" required>
        </div>

        <div>
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
        </div>

        <div>
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
            <div class="text-xs text-slate-500 mt-1">Tối thiểu 6 ký tự.</div>
        </div>

        <div>
            <label class="form-label">Vai trò</label>
            <select name="role" class="form-control" required>
                @foreach($roles as $r)
                <option value="{{ $r->name }}" @selected(old('role')===$r->name)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>

        <label class="inline-flex items-center gap-2 md:col-span-2">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active',1) ? 'checked' : '' }}> Hoạt động
        </label>

        <div class="md:col-span-2 flex gap-2">
            <button class="btn btn-primary">Lưu</button>
            <a class="btn btn-outline" href="{{ route('admin.users.index') }}">Huỷ</a>
        </div>
    </form>
</div>
@endsection