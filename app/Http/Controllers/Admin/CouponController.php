<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CouponController extends Controller
{
    /**
     * Danh sách mã giảm giá + lọc cơ bản.
     */
    public function index(Request $r)
    {
        $q = Coupon::query()
            ->when($r->keyword, function ($qq) use ($r) {
                $kw = trim($r->keyword);
                $qq->where(function ($x) use ($kw) {
                    $x->where('code', 'like', "%{$kw}%")
                        ->orWhere('name', 'like', "%{$kw}%");
                });
            });

        // status: active|inactive|ongoing|expired
        if ($r->filled('status')) {
            $now = now();
            switch ($r->status) {
                case 'active':
                    $q->where('is_active', 1);
                    break;
                case 'inactive':
                    $q->where('is_active', 0);
                    break;
                case 'ongoing':
                    $q->where('is_active', 1)
                        ->where(function ($w) use ($now) {
                            $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                        })
                        ->where(function ($w) use ($now) {
                            $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                        });
                    break;
                case 'expired':
                    $q->whereNotNull('ends_at')->where('ends_at', '<', $now);
                    break;
            }
        }

        $coupons = $q->orderByDesc('id')->paginate(12)->withQueryString();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'filters' => $r->only('keyword', 'status'),
        ]);
    }

    /**
     * Form tạo.
     */
    public function create()
    {
        // mảng id preselect cho TomSelect (trống khi tạo mới)
        $preselected = [];
        return view('admin.coupons.create', compact('preselected'));
    }

    /**
     * Lưu mới.
     */
    public function store(Request $r)
    {
        $rules = [
            'code'               => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'name'               => ['nullable', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'is_active'          => ['required', 'boolean'],

            // form dùng: type (percent|fixed), value
            'type'               => ['required', Rule::in(['percent', 'fixed'])],
            'value'              => ['required', 'numeric', 'min:0'],
            'max_discount'       => ['nullable', 'numeric', 'min:0'],
            'min_order_value'    => ['nullable', 'numeric', 'min:0'],

            'usage_limit'        => ['nullable', 'integer', 'min:0'],
            'per_customer_limit' => ['nullable', 'integer', 'min:0'],

            'applies_to'         => ['required', Rule::in(['order', 'category', 'brand', 'product'])],
            'applies_to_ids'     => ['array'],
            'applies_to_ids.*'   => ['string'],

            'start_at'           => ['nullable', 'date'],
            'end_at'             => ['nullable', 'date', 'after_or_equal:start_at'],
        ];
        if ($r->input('type') === 'percent') {
            $rules['value'][] = 'max:100';
        }

        $data = $r->validate($rules);

        $payload = [
            'code'                   => $data['code'],
            'name'                   => $data['name'] ?? null,
            'description'            => $data['description'] ?? null,
            'is_active'              => (bool) $data['is_active'],

            'discount_type'          => $data['type'],
            'discount_value'         => (float) $data['value'],
            'max_discount'           => $data['max_discount'] ?? null,
            'min_order_total'        => $data['min_order_value'] ?? 0,

            'usage_limit'            => $data['usage_limit'] ?? null,
            'usage_limit_per_user'   => $data['per_customer_limit'] ?? null,

            'applied_to'             => $data['applies_to'],
            'applies_to_ids'         => array_values(array_map('strval', $data['applies_to_ids'] ?? [])),

            'starts_at'              => !empty($data['start_at']) ? Carbon::parse($data['start_at']) : null,
            'ends_at'                => !empty($data['end_at'])   ? Carbon::parse($data['end_at'])   : null,
        ];

        // Nếu áp dụng toàn đơn, không cần lưu applies_to_ids
        if ($payload['applied_to'] === 'order') {
            $payload['applies_to_ids'] = [];
        }

        Coupon::create($payload);

        return redirect()->route('admin.coupons.index')->with('ok', 'Tạo mã giảm giá thành công!');
    }

    /**
     * Form sửa.
     */
    public function edit(Coupon $coupon)
    {
        $preselected = collect(old('applies_to_ids', $coupon->applies_to_ids ?? []))
            ->map(fn($id) => (string) $id)->values()->all();

        return view('admin.coupons.edit', compact('coupon', 'preselected'));
    }

    /**
     * Cập nhật.
     */
    public function update(Request $r, Coupon $coupon)
    {
        $rules = [
            'code'               => ['required', 'string', 'max:64', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            'name'               => ['nullable', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'is_active'          => ['required', 'boolean'],

            'type'               => ['required', Rule::in(['percent', 'fixed'])],
            'value'              => ['required', 'numeric', 'min:0'],
            'max_discount'       => ['nullable', 'numeric', 'min:0'],
            'min_order_value'    => ['nullable', 'numeric', 'min:0'],

            'usage_limit'        => ['nullable', 'integer', 'min:0'],
            'per_customer_limit' => ['nullable', 'integer', 'min:0'],

            'applies_to'         => ['required', Rule::in(['order', 'category', 'brand', 'product'])],
            'applies_to_ids'     => ['array'],
            'applies_to_ids.*'   => ['string'],

            'start_at'           => ['nullable', 'date'],
            'end_at'             => ['nullable', 'date', 'after_or_equal:start_at'],
        ];
        if ($r->input('type') === 'percent') {
            $rules['value'][] = 'max:100';
        }

        $data = $r->validate($rules);

        $payload = [
            'code'                   => $data['code'],
            'name'                   => $data['name'] ?? null,
            'description'            => $data['description'] ?? null,
            'is_active'              => (bool) $data['is_active'],

            'discount_type'          => $data['type'],
            'discount_value'         => (float) $data['value'],
            'max_discount'           => $data['max_discount'] ?? null,
            'min_order_total'        => $data['min_order_value'] ?? 0,

            'usage_limit'            => $data['usage_limit'] ?? null,
            'usage_limit_per_user'   => $data['per_customer_limit'] ?? null,

            'applied_to'             => $data['applies_to'],
            'applies_to_ids'         => array_values(array_map('strval', $data['applies_to_ids'] ?? [])),

            'starts_at'              => !empty($data['start_at']) ? Carbon::parse($data['start_at']) : null,
            'ends_at'                => !empty($data['end_at'])   ? Carbon::parse($data['end_at'])   : null,
        ];

        if ($payload['applied_to'] === 'order') {
            $payload['applies_to_ids'] = [];
        }

        $coupon->update($payload);

        return redirect()->route('admin.coupons.edit', $coupon)->with('ok', 'Cập nhật mã giảm giá thành công!');
    }

    /**
     * Xoá.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('ok', 'Đã xoá mã giảm giá.');
    }

    /**
     * API cho TomSelect: lấy options theo type (brand|category|product)
     * - q: từ khoá
     * - page, per: phân trang đơn giản
     * - ids[]: preload các id cụ thể (khi edit)
     */
    public function targets(Request $r)
    {
        $type = $r->get('type');
        abort_unless(in_array($type, ['brand', 'category', 'product']), 422, 'Loại không hợp lệ');

        $ids  = (array) $r->input('ids', []);
        $q    = trim((string) $r->get('q', ''));
        $per  = max(1, min((int) $r->get('per', 20), 50));
        $page = max(1, (int) $r->get('page', 1));

        $builder = match ($type) {
            'brand'    => Brand::query()->select('id', 'name'),
            'category' => Category::query()->select('id', 'name'),
            'product'  => Product::query()->select('id', 'name'),
        };

        // preload theo ids (khi mở form edit)
        if (!empty($ids)) {
            $items = $builder->whereIn('id', $ids)->limit(200)->get();
            return response()->json([
                'results' => $items->map(fn($m) => [
                    'value' => (string) $m->id,
                    'text'  => $m->name,
                ])->values(),
            ]);
        }

        // search theo q
        if ($q !== '') {
            $builder->where('name', 'like', "%{$q}%");
        } else {
            // không có q thì trả rỗng (tránh tải nặng)
            return response()->json(['results' => []]);
        }

        $items = $builder->orderBy('name')->forPage($page, $per)->get();

        return response()->json([
            'results' => $items->map(fn($m) => [
                'value' => (string) $m->id,
                'text'  => $m->name,
            ])->values(),
        ]);
    }
}
