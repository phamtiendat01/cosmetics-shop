<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingVoucherRequest;
use App\Models\ShippingVoucher;
use App\Models\ShippingVoucherUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\ShippingCarrier;
use App\Models\ShippingZone;


class ShippingVoucherController extends Controller
{
    public function __construct()
    {
        // đảm bảo chỉ admin (hoặc ai có quyền) mới truy cập
        $this->middleware('can:manage shipping vouchers');
    }

    /**
     * Danh sách + bộ lọc
     */
    public function index(Request $r)
    {
        $q = ShippingVoucher::query();

        // Từ khoá
        if ($kw = trim((string) $r->input('q'))) {
            $q->where(function ($qq) use ($kw) {
                $qq->where('code', 'like', "%{$kw}%")
                    ->orWhere('title', 'like', "%{$kw}%");
            });
        }

        // Trạng thái
        if ($r->filled('status')) {
            $now = now();

            switch ($r->status) {
                case 'running':
                    $q->where('is_active', 1)
                        ->where(fn($qq) => $qq->whereNull('start_at')->orWhere('start_at', '<=', $now))
                        ->where(fn($qq) => $qq->whereNull('end_at')->orWhere('end_at', '>=', $now));
                    break;

                case 'expired':
                    $q->whereNotNull('end_at')->where('end_at', '<', $now);
                    break;

                case 'active':
                    $q->where('is_active', 1);
                    break;

                case 'inactive':
                    $q->where('is_active', 0);
                    break;
            }
        }

        // Đếm cho chip
        $base = ShippingVoucher::query();
        $counts = [
            'all'      => (clone $base)->count(),
            'running'  => (clone $base)->where('is_active', 1)
                ->where(fn($qq) => $qq->whereNull('start_at')->orWhere('start_at', '<=', now()))
                ->where(fn($qq) => $qq->whereNull('end_at')->orWhere('end_at', '>=', now()))
                ->count(),
            'expired'  => (clone $base)->whereNotNull('end_at')->where('end_at', '<', now())->count(),
            'active'   => (clone $base)->where('is_active', 1)->count(),
            'inactive' => (clone $base)->where('is_active', 0)->count(),
        ];

        $items = $q->orderByDesc('id')->paginate(15)->withQueryString();

        // Đếm lượt dùng (nếu có bảng usage)
        $usageCounts = [];
        if (Schema::hasTable('shipping_voucher_usages')) {
            $usageCounts = ShippingVoucherUsage::selectRaw('shipping_voucher_id, COUNT(*) as c')
                ->groupBy('shipping_voucher_id')
                ->pluck('c', 'shipping_voucher_id')->all();
        }

        return view('admin.shipvouchers.index', compact('items', 'counts', 'usageCounts'));
    }

    /**
     * Trang tạo
     */
    public function create()
    {
        $voucher = new ShippingVoucher(['discount_type' => 'fixed', 'is_active' => true]);
        $carrierOptions = ShippingCarrier::orderBy('name')->pluck('name', 'code');   // code => name
        $zoneOptions    = ShippingZone::where('enabled', 1)->orderBy('name')->pluck('name', 'id');
        return view('admin.shipvouchers.create', compact('voucher', 'carrierOptions', 'zoneOptions'));
    }

    /**
     * Lưu mới
     */
    public function store(ShippingVoucherRequest $req)
    {
        $data = $this->payload($req);
        $data['type'] = 'shipping';

        ShippingVoucher::create($data);

        return redirect()
            ->route('admin.shipvouchers.index')
            ->with('ok', 'Đã tạo mã vận chuyển.');
    }

    /**
     * Trang sửa
     * Route model binding: {shipvoucher}
     */
    public function edit(ShippingVoucher $shipvoucher)
    {
        $carrierOptions = ShippingCarrier::orderBy('name')->pluck('name', 'code');
        $zoneOptions    = ShippingZone::where('enabled', 1)->orderBy('name')->pluck('name', 'id');
        return view('admin.shipvouchers.edit', [
            'voucher' => $shipvoucher,
            'carrierOptions' => $carrierOptions,
            'zoneOptions'    => $zoneOptions,
        ]);
    }

    /**
     * Cập nhật
     */
    public function update(ShippingVoucherRequest $req, ShippingVoucher $shipvoucher)
    {
        $shipvoucher->update($this->payload($req));

        return back()->with('ok', 'Đã cập nhật.');
    }

    /**
     * Xoá
     */
    public function destroy(ShippingVoucher $shipvoucher)
    {
        $shipvoucher->delete();

        return back()->with('ok', 'Đã xoá.');
    }

    /**
     * Bật/tắt nhanh
     */
    public function toggle(ShippingVoucher $shipvoucher)
    {
        $shipvoucher->update(['is_active' => ! $shipvoucher->is_active]);

        return back()->with('ok', $shipvoucher->is_active ? 'Đã bật mã.' : 'Đã tắt mã.');
    }

    /**
     * Chuẩn hoá dữ liệu từ FormRequest
     */
    private function payload(ShippingVoucherRequest $req): array
    {
        $data = $req->validated();

        foreach (['start_at', 'end_at'] as $k) {
            if (!empty($data[$k])) {
                $data[$k] = Carbon::parse($data[$k]);
            }
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
