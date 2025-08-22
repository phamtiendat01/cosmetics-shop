<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /** Role coi là khách (không xuất hiện ở màn Quản trị viên) */
    protected array $customerRoleNames = ['customer'];

    /** Danh sách role dành cho admin (mọi role trừ customer) */
    protected function adminRoleNames(): array
    {
        return Role::pluck('name')
            ->reject(fn($n) => in_array($n, $this->customerRoleNames, true))
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        $q      = $request->input('q');
        $role   = $request->input('role');
        $status = $request->input('status');

        $adminRoles = $this->adminRoleNames();

        $users = User::query()
            ->with('roles')
            ->whereHas('roles', fn($qr) => $qr->whereIn('name', $adminRoles))
            ->when($q, fn($qr) => $qr->where(
                fn($s) =>
                $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")
            ))
            ->when(
                $role && in_array($role, $adminRoles, true),
                fn($qr) => $qr->whereHas('roles', fn($r) => $r->where('name', $role))
            )
            ->when($status === 'active', fn($qr) => $qr->where('is_active', 1))
            ->when($status === 'inactive', fn($qr) => $qr->where('is_active', 0))
            ->orderByDesc('id')
            ->paginate(12)->withQueryString();

        $roles = Role::whereNotIn('name', $this->customerRoleNames)->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $roles = Role::whereNotIn('name', $this->customerRoleNames)->orderBy('name')->get();
        return view('admin.users.create', [
            'roles' => $roles,
            'customerRoleNames' => $this->customerRoleNames,
        ]);
    }

    public function store(Request $request)
    {
        $adminRoles = $this->adminRoleNames();

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'min:6'],
            'role'      => ['required', Rule::in($adminRoles)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $u = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'], // đã cast 'hashed' trong model User
            'is_active' => $request->boolean('is_active', true),
        ]);

        $u->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('ok', 'Đã tạo quản trị viên.');
    }

    public function edit(User $user)
    {
        $adminRoles = $this->adminRoleNames();
        abort_unless($user->hasAnyRole($adminRoles), 404);

        $roles = Role::whereNotIn('name', $this->customerRoleNames)->orderBy('name')->get();

        return view('admin.users.edit', [
            'user'  => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $adminRoles = $this->adminRoleNames();
        abort_unless($user->hasAnyRole($adminRoles), 404);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => ['nullable', 'min:6'],
            'role'      => ['required', Rule::in($adminRoles)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Không được "giáng cấp" Super Admin cuối cùng
        $newRole = $data['role'];
        $isDemoteSuper = $user->hasRole('super-admin') && $newRole !== 'super-admin' && User::role('super-admin')->count() <= 1;
        if ($isDemoteSuper) {
            return back()->with('err', 'Không thể đổi vai trò của Super Admin cuối cùng.')->withInput();
        }
        // Không được tắt hoạt động Super Admin cuối cùng
        if ($user->hasRole('super-admin') && !$request->boolean('is_active') && User::role('super-admin')->count() <= 1) {
            return back()->with('err', 'Không thể khoá Super Admin cuối cùng.')->withInput();
        }

        $user->name      = $data['name'];
        $user->email     = $data['email'];
        $user->is_active = $request->boolean('is_active');
        if (!empty($data['password'])) $user->password = $data['password']; // cast 'hashed'
        $user->save();

        $user->syncRoles([$newRole]);

        return redirect()->route('admin.users.index')->with('ok', 'Đã cập nhật.');
    }

    public function destroy(User $user)
    {
        $adminRoles = $this->adminRoleNames();
        abort_unless($user->hasAnyRole($adminRoles), 404);

        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            return back()->with('err', 'Không thể xoá Super Admin cuối cùng.');
        }
        if (auth()->id() === $user->id) {
            return back()->with('err', 'Không thể xoá chính bạn.');
        }

        $user->delete();
        return back()->with('ok', 'Đã xoá quản trị viên.');
    }
}
