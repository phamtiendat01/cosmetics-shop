@extends('admin.layouts.app')
@section('title','Sửa quản trị viên')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif
@if ($errors->any())
<div class="alert alert-danger mb-3">
    @foreach ($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Sửa quản trị viên</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">← Quay lại</a>
    </div>
</div>

<div class="card p-4">
    <form action="{{ route('admin.users.update',$user) }}" method="POST" class="grid md:grid-cols-2 gap-4">
        @csrf @method('PUT')

        <div>
            <label class="form-label">Họ tên</label>
            <input name="name" value="{{ old('name',$user->name) }}" class="form-control" required>
        </div>

        <div>
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email',$user->email) }}" class="form-control" required>
        </div>

        <div>
            <label class="form-label">Mật khẩu mới (để trống nếu giữ nguyên)</label>
            <input type="password" name="password" class="form-control" minlength="6">
        </div>

        <div>
            <label class="form-label">Vai trò</label>
            <select name="role" class="form-control" required>
                @foreach($roles as $r)
                <option value="{{ $r->name }}" @selected(old('role',$user->roles->first()->name ?? '')===$r->name)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>

        <label class="inline-flex items-center gap-2 md:col-span-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active',$user->is_active))> Hoạt động
        </label>

        <div class="md:col-span-2 flex gap-2">
            <button class="btn btn-primary">Cập nhật</button>
            <a class="btn btn-outline" href="{{ route('admin.users.index') }}">Huỷ</a>
        </div>
    </form>
</div>
@endsection