<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $q = User::query()
            ->select('users.*')
            ->withCount('orders')
            ->whereDoesntHave('roles', fn($q) => $q->whereNotIn('name', ['customer']))
            ->selectSub(
                Order::selectRaw('COALESCE(SUM(grand_total),0)')
                    ->whereColumn('orders.user_id', 'users.id'),
                'total_spent'
            )
            ->selectSub(
                Order::selectRaw('MAX(placed_at)')
                    ->whereColumn('orders.user_id', 'users.id'),
                'last_order_at_sub'
            )
            ->when($r->keyword, function ($qq) use ($r) {
                $kw = $r->keyword;
                $qq->where(function ($w) use ($kw) {
                    $w->where('name', 'like', "%$kw%")
                        ->orWhere('email', 'like', "%$kw%")
                        ->orWhere('phone', 'like', "%$kw%");
                });
            })
            ->when($r->status === 'active', fn($qq) => $qq->where('is_active', 1))
            ->when($r->status === 'inactive', fn($qq) => $qq->where('is_active', 0))
            ->when($r->verified === 'yes', fn($qq) => $qq->whereNotNull('email_verified_at'))
            ->when($r->verified === 'no',  fn($qq) => $qq->whereNull('email_verified_at'))
            ->when($r->date_from, fn($qq) => $qq->whereDate('users.created_at', '>=', $r->date_from))
            ->when($r->date_to,   fn($qq) => $qq->whereDate('users.created_at', '<=', $r->date_to))
            ->orderByDesc('id');

        // sắp xếp tuỳ chọn
        if ($r->sort === 'total_spent') $q->orderByDesc('total_spent');
        if ($r->sort === 'orders')      $q->orderByDesc('orders_count');
        if ($r->sort === 'created_at')  $q->orderByDesc('users.created_at');

        $customers = $q->paginate(15)->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'filters'   => $r->only('keyword', 'status', 'verified', 'date_from', 'date_to', 'sort'),
        ]);
    }

    public function create()
    {
        // các dữ liệu khác cho form (nếu có)...
        $orders = collect(); // trang tạo mới chưa có đơn nào
        return view('admin.customers.create', compact('orders'));
    }


    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();

        $user = new User();
        $user->name      = $data['name'];
        $user->email     = $data['email'];
        $user->phone     = $data['phone'] ?? null;
        $user->gender    = $data['gender'] ?? null;
        $user->dob       = $data['dob'] ?? null;
        $user->is_active = (bool)($data['is_active'] ?? true);
        $user->password  = Hash::make($data['password']);
        if (!empty($data['shipping_address'])) {
            $user->default_shipping_address = $data['shipping_address'];
        }
        $user->save();

        return redirect()->route('admin.customers.index')->with('ok', 'Tạo khách hàng thành công!');
    }

    public function edit(User $customer)
    {
        // kèm thống kê
        $customer->loadCount('orders');
        $stats = [
            'total_spent' => Order::where('user_id', $customer->id)->sum('grand_total'),
            'last_order'  => Order::where('user_id', $customer->id)->max('placed_at'),
        ];

        return view('admin.customers.edit', compact('customer', 'stats'));
    }

    public function update(UpdateCustomerRequest $request, User $customer)
    {
        $data = $request->validated();

        $payload = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'gender'    => $data['gender'] ?? null,
            'dob'       => $data['dob'] ?? null,
            'is_active' => (bool)($data['is_active'] ?? false),
        ];
        if (!empty($data['shipping_address'])) {
            $payload['default_shipping_address'] = $data['shipping_address'];
        }
        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $customer->update($payload);

        return redirect()->route('admin.customers.edit', $customer)->with('ok', 'Cập nhật khách hàng thành công!');
    }

    public function show(User $customer)
    {
        $customer->loadCount('orders');
        $orders = Order::where('user_id', $customer->id)
            ->orderByDesc('id')->paginate(10);

        $stats = [
            'total_spent' => Order::where('user_id', $customer->id)->sum('grand_total'),
            'last_order'  => Order::where('user_id', $customer->id)->max('placed_at'),
        ];

        return view('admin.customers.show', compact('customer', 'orders', 'stats'));
    }

    public function toggle(User $customer)
    {
        $customer->is_active = !$customer->is_active;
        $customer->save();

        return back()->with('ok', $customer->is_active ? 'Đã bật hoạt động.' : 'Đã khoá tài khoản.');
    }

    public function destroy(User $customer)
    {
        $customer->loadCount('orders');
        if ($customer->orders_count > 0) {
            return back()->withErrors('Không thể xoá khách hàng đã có đơn hàng.');
        }
        $customer->delete();

        return back()->with('ok', 'Đã xoá khách hàng.');
    }

    public function bulk(Request $r)
    {
        $data = $r->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
            'act'   => ['required', 'in:activate,deactivate,delete'],
        ], [], ['ids' => 'Danh sách chọn', 'act' => 'Hành động']);

        $ids = $data['ids'];
        if ($data['act'] !== 'delete') {
            User::whereIn('id', $ids)->update(['is_active' => $data['act'] === 'activate' ? 1 : 0]);
            return back()->with('ok', 'Đã cập nhật trạng thái cho ' . count($ids) . ' khách hàng.');
        }

        // delete: chỉ xoá user chưa có orders
        $canDelete = User::whereIn('id', $ids)->withCount('orders')->get()
            ->filter(fn($u) => $u->orders_count == 0);
        foreach ($canDelete as $u) $u->delete();

        $deleted = $canDelete->count();
        $blocked = count($ids) - $deleted;
        $msg = "Đã xoá {$deleted} khách hàng.";
        if ($blocked > 0) $msg .= " {$blocked} khách hàng bị chặn do đã có đơn hàng.";
        return back()->with('ok', $msg);
    }
}
