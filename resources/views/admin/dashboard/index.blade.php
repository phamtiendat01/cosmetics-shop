@extends('admin.layouts.app')
@section('title','Tổng quan')

@section('content')
{{-- KPI --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="card p-4">
        <div class="text-slate-500 text-sm">Doanh thu hôm nay</div>
        <div class="text-2xl font-bold mt-1">{{ number_format($todayRevenue ?? 0) }}₫</div>
    </div>
    <div class="card p-4">
        <div class="text-slate-500 text-sm">Doanh thu tháng</div>
        <div class="text-2xl font-bold mt-1">{{ number_format($monthRevenue ?? 0) }}₫</div>
    </div>
    <div class="card p-4">
        <div class="text-slate-500 text-sm">Số đơn tháng</div>
        <div class="text-2xl font-bold mt-1">{{ number_format($ordersCount ?? 0) }}</div>
    </div>
    <div class="card p-4">
        <div class="text-slate-500 text-sm">AOV (giá trị TB)</div>
        <div class="text-2xl font-bold mt-1">{{ number_format($aov ?? 0) }}₫</div>
    </div>
</div>

{{-- Charts row 1 --}}
<div class="grid lg:grid-cols-3 gap-4 mt-4">
    <div class="card p-4 lg:col-span-2 min-w-0">
        <div class="flex items-center justify-between">
            <div class="font-semibold">Doanh thu 14 ngày</div>
            <input id="dateRange" class="rounded-md border border-slate-200 px-2 py-1 text-sm" placeholder="Chọn khoảng ngày" />
        </div>
        <div class="relative mt-4 h-[320px]">
            <canvas id="revChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
    </div>

    <div class="card p-4 min-w-0">
        <div class="font-semibold">Tỉ lệ trạng thái đơn</div>
        <div class="relative mt-4 h-[260px]">
            <canvas id="statusChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
    </div>
</div>

{{-- Charts row 2 --}}
<div class="grid lg:grid-cols-3 gap-4 mt-4">
    <div class="card p-4 min-w-0">
        <div class="font-semibold">Kênh thanh toán</div>
        <div class="relative mt-4 h-[260px]">
            <canvas id="payChart" class="absolute inset-0 w-full h-full"></canvas>
        </div>
    </div>

    <div class="card p-4 lg:col-span-2 min-w-0">
        <div class="font-semibold mb-2">Top sản phẩm bán chạy</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">Sản phẩm</th>
                        <th class="w-20">SL</th>
                        <th class="w-32">Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($topProducts ?? []) as $row)
                    <tr class="border-t">
                        <td class="py-2">{{ $row->product_name_snapshot ?? $row->product_name ?? '—' }}</td>
                        <td>{{ number_format($row->qty ?? 0) }}</td>
                        <td>{{ number_format($row->total ?? 0) }}₫</td>
                    </tr>
                    @empty
                    <tr>
                        <td class="py-3 text-slate-500" colspan="3">Chưa có dữ liệu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Low stock --}}
<div class="card p-4 mt-4">
    <div class="font-semibold mb-1">
        Cảnh báo tồn kho thấp (≤ {{ $lowStockThreshold }})
    </div>
    <p class="text-sm text-amber-600">
        {{ $lowStock }} biến thể đang thấp hơn ngưỡng.
    </p>

    @if($lowStockItems->count())
    <div class="mt-3 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2">Sản phẩm</th>
                    <th>SKU</th>
                    <th>SL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowStockItems as $it)
                <tr class="border-t">
                    <td class="py-2">{{ $it->product_name }}</td>
                    <td>{{ $it->sku }}</td>
                    <td class="text-red-600 font-medium">{{ $it->qty }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    // Date range (nếu có flatpickr trong layout)
    if (window.flatpickr) {
        flatpickr('#dateRange', {
            mode: 'range',
            dateFormat: 'd/m/Y'
        });
    }

    // ===== DATA: KHÔNG dùng '->' trong Blade =====
    const labels = @json($labels ?? []);
    const series = @json($series ?? []);
    const status = @json($statusAgg ?? []);
    const pay = @json($payAgg ?? []);

    // ===== Charts =====
    // Revenue
    (function() {
        const el = document.getElementById('revChart');
        if (!el || !window.Chart) return;
        if (window._revChart) window._revChart.destroy();

        window._revChart = new Chart(el.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: series,
                    tension: .35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    })();

    // Status donut
    (function() {
        const el = document.getElementById('statusChart');
        if (!el || !window.Chart) return;
        if (window._stChart) window._stChart.destroy();

        window._stChart = new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(status),
                datasets: [{
                    data: Object.values(status)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '65%'
            }
        });
    })();

    // Payment bar
    (function() {
        const el = document.getElementById('payChart');
        if (!el || !window.Chart) return;
        if (window._payChart) window._payChart.destroy();

        window._payChart = new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(pay),
                datasets: [{
                    label: 'Đơn',
                    data: Object.values(pay)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    })();
</script>
@endpush