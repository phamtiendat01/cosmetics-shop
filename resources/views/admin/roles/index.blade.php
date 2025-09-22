@extends('admin.layouts.app')
@section('title','Vai trò & Quyền')

@section('content')
@php
// Chọn role hiện tại theo ?role=ID, nếu không có lấy cái đầu tiên
$currentRole = $roles->firstWhere('id', (int)request('role')) ?? $roles->first();
@endphp

{{-- Alerts --}}
@if (session('ok'))
<div class="alert mb-4 rounded-md border border-green-200 bg-green-50 text-green-700 px-4 py-3" data-auto-dismiss="3000">
    {{ session('ok') }}
</div>
@endif
@if (session('err'))
<div class="alert mb-4 rounded-md border border-red-200 bg-red-50 text-red-700 px-4 py-3">
    {{ session('err') }}
</div>
@endif
@if ($errors->any())
<div class="alert mb-4 rounded-md border border-red-200 bg-red-50 text-red-700 px-4 py-3">
    @foreach ($errors->all() as $e)
    <div>• {{ $e }}</div>
    @endforeach
</div>
@endif

<div class="grid grid-cols-12 gap-6">
    {{-- Sidebar: Danh sách vai trò --}}
    <aside class="col-span-12 lg:col-span-4">
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold">Vai trò</h2>
                <button id="btnOpenCreateRole" class="text-sm px-3 py-1.5 rounded-md bg-rose-600 text-white hover:bg-rose-700">
                    + Thêm
                </button>
            </div>

            {{-- Tạo vai trò (inline) --}}
            <div id="createRoleForm" class="hidden px-4 py-3 border-b border-slate-200 bg-slate-50">
                <form action="{{ route('admin.roles.store') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <input name="name" required placeholder="Tên vai trò (vd: staff)"
                        class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-md outline-none focus:ring-2 focus:ring-rose-300">
                    <button class="px-3 py-2 text-sm rounded-md border">Huỷ</button>
                    <button class="px-3 py-2 text-sm rounded-md bg-rose-600 text-white hover:bg-rose-700">Tạo</button>
                </form>
                <div class="mt-2 text-xs text-slate-500">Gợi ý: <code>super-admin</code>, <code>admin</code>, <code>staff</code></div>
            </div>

            {{-- Danh sách --}}
            <ul class="max-h-[520px] overflow-y-auto divide-y divide-slate-100">
                @forelse ($roles as $r)
                @php
                $isActive = $currentRole && $currentRole->id === $r->id;
                $isProtected = in_array($r->name, ['super-admin']);
                $usersCount = method_exists($r, 'users') ? $r->users()->count() : null;
                @endphp
                <li class="hover:bg-slate-50">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.roles.index', ['role' => $r->id]) }}"
                            class="block flex-1 px-4 py-3 {{ $isActive ? 'bg-rose-50/50 font-medium' : '' }}">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-rose-100 text-rose-700 text-xs uppercase">
                                    {{ strtoupper(substr($r->name,0,2)) }}
                                </span>
                                <div>
                                    <div class="text-sm">{{ $r->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $r->permissions->count() }} quyền
                                        @if(!is_null($usersCount)) • {{ $usersCount }} người dùng
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>

                        {{-- Xoá vai trò --}}
                        <div class="px-3">
                            <form action="{{ route('admin.roles.destroy', $r) }}" method="POST"
                                onsubmit="return confirm('Xoá vai trò {{ $r->name }}?')">
                                @csrf @method('DELETE')
                                <button class="p-2 text-slate-500 hover:text-rose-600"
                                    {{ $isProtected ? 'disabled' : '' }}
                                    title="{{ $isProtected ? 'Vai trò hệ thống, không thể xoá' : 'Xoá vai trò' }}">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-6 text-sm text-slate-500">Chưa có vai trò nào.</li>
                @endforelse
            </ul>
        </div>

        <div class="mt-3 text-xs text-slate-500">
            * <b>Gợi ý chuẩn sàn:</b> Chỉ “super-admin” mới được toàn quyền (đã cấu hình bypass qua <code>Gate::before</code>).
        </div>
    </aside>

    {{-- Content: Matrix quyền theo vai trò --}}
    <section class="col-span-12 lg:col-span-8">
        <div class="bg-white border border-slate-200 rounded-xl">
            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Quyền của vai trò</h2>
                    @if($currentRole)
                    <div class="text-sm text-slate-600">
                        Đang chỉnh: <span class="font-medium text-rose-700">{{ $currentRole->name }}</span>
                        @if(in_array($currentRole->name, ['super-admin']))
                        <span class="ml-2 text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Super-admin: có toàn bộ quyền
                        </span>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="btnCheckAll"
                        class="px-3 py-1.5 text-sm rounded-md border hover:bg-slate-50">
                        Chọn tất cả
                    </button>
                    <button type="button" id="btnUncheckAll"
                        class="px-3 py-1.5 text-sm rounded-md border hover:bg-slate-50">
                        Bỏ chọn
                    </button>
                </div>
            </div>

            @if ($currentRole)
            <form action="{{ route('admin.roles.update', $currentRole) }}" method="POST" class="p-4">
                @csrf @method('PUT')

                {{-- Nhóm quyền theo prefix (manage/view/...) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse ($perms as $group => $items)
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div class="text-sm font-medium capitalize">
                                {{ $group === '' ? 'Khác' : str_replace('-', ' ', $group) }}
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" class="text-rose-700 hover:underline group-check"
                                    data-action="check" data-target="grp-{{ \Illuminate\Support\Str::slug($group) }}">
                                    Chọn nhóm
                                </button>
                                <span class="text-slate-300">•</span>
                                <button type="button" class="hover:underline text-slate-600 group-check"
                                    data-action="uncheck" data-target="grp-{{ \Illuminate\Support\Str::slug($group) }}">
                                    Bỏ nhóm
                                </button>
                            </div>
                        </div>
                        <div class="p-3 grid grid-cols-1 gap-2" id="grp-{{ \Illuminate\Support\Str::slug($group) }}">
                            @foreach ($items as $perm)
                            @php $checked = $currentRole->hasPermissionTo($perm->name); @endphp
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                    class="perm-checkbox rounded border-slate-300"
                                    {{ $checked ? 'checked' : '' }}>
                                <span class="select-none">{{ $perm->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="text-sm text-slate-500 px-2">Chưa có permission nào.</div>
                    @endforelse
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button class="px-4 py-2 rounded-md bg-rose-600 text-white hover:bg-rose-700">
                        Lưu thay đổi
                    </button>
                    <a href="{{ route('admin.roles.index', ['role' => $currentRole->id]) }}"
                        class="px-4 py-2 rounded-md border hover:bg-slate-50">Hoàn tác</a>
                </div>
            </form>
            @else
            <div class="p-6 text-sm text-slate-600">
                Vui lòng tạo ít nhất một vai trò để cấu hình quyền.
            </div>
            @endif
        </div>

        {{-- Hướng dẫn nhanh (UX như sàn lớn) --}}
        <div class="mt-4 text-xs text-slate-500 leading-relaxed">
            <p class="mb-1">• Mô hình <b>RBAC</b>: người dùng ⇢ gán <b>vai trò</b>; vai trò ⇢ sở hữu <b>quyền</b>.</p>
            <p class="mb-1">• Nên giới hạn số vai trò (3–5 loại) để dễ vận hành: <code>super-admin</code>, <code>admin</code>, <code>staff</code>.</p>
            <p>• Bảo vệ: Không xoá/giáng cấp <b>Super Admin cuối cùng</b>; Staff thường không được “Phân quyền”.</p>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    // Toggle form tạo vai trò
    (function() {
        const btnOpen = document.getElementById('btnOpenCreateRole');
        const box = document.getElementById('createRoleForm');
        if (btnOpen && box) {
            btnOpen.addEventListener('click', () => box.classList.toggle('hidden'));
            // nút "Huỷ" là button nằm trong form tạo
            const cancelBtn = box.querySelector('button[type="button"], button:not([type])');
            if (cancelBtn) cancelBtn.addEventListener('click', (e) => {
                e.preventDefault();
                box.classList.add('hidden');
            });
        }
    })();

    // Chọn/Bỏ chọn tất cả permission
    const checkAllBtn = document.getElementById('btnCheckAll');
    const uncheckAllBtn = document.getElementById('btnUncheckAll');
    const allCheckboxes = () => document.querySelectorAll('input.perm-checkbox');

    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', () => {
            allCheckboxes().forEach(cb => cb.checked = true);
        });
    }
    if (uncheckAllBtn) {
        uncheckAllBtn.addEventListener('click', () => {
            allCheckboxes().forEach(cb => cb.checked = false);
        });
    }

    // Chọn/Bỏ theo từng nhóm
    document.querySelectorAll('.group-check').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            const action = btn.dataset.action;
            if (!target) return;
            target.querySelectorAll('input.perm-checkbox').forEach(cb => {
                cb.checked = action === 'check';
            });
        });
    });
</script>
@endpush