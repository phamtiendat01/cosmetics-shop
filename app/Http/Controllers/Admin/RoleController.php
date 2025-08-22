<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    protected array $protectedRoles = ['super-admin'];

    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $perms = Permission::orderBy('name')->get()->groupBy(function ($p) {
            // nhóm bằng prefix đầu tiên trước dấu cách: "manage products" => "manage"
            return explode(' ', $p->name)[0] ?? 'other';
        });
        return view('admin.roles.index', compact('roles', 'perms'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'            => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions'     => ['array'],
            'permissions.*'   => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', 'Đã tạo vai trò.');
    }

    public function update(Request $r, Role $role)
    {
        $data = $r->validate([
            'name'          => ['nullable', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // Nếu có gửi 'name' thì mới đổi tên (và không cho đổi tên role bảo vệ)
        if (isset($data['name']) && $data['name'] !== $role->name) {
            if (in_array($role->name, $this->protectedRoles)) {
                return back()->with('err', 'Không được đổi tên vai trò bảo vệ.');
            }
            $role->name = $data['name'];
            $role->save();
        }

        // Cập nhật quyền
        $role->syncPermissions($data['permissions'] ?? []);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', 'Đã cập nhật quyền cho vai trò ' . $role->name);
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, $this->protectedRoles)) {
            return back()->with('err', 'Không thể xoá vai trò bảo vệ.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', 'Đã xoá vai trò.');
    }
}
