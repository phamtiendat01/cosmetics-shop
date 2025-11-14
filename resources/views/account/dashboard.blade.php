@extends('layouts.app')
@section('title', 'Tổng quan tài khoản')

@section('content')
<style>
    :root {
        --rose: #f43f5e;
        /* rose-500 */
        --pink: #ec4899;
        /* pink-500 */
        --violet: #8b5cf6;
        /* violet-500 */
        --teal: #06b6d4;
        /* teal-500 */
        --ink: #0f172a;
        /* slate-900 */
        --muted: #64748b;
        /* slate-500 */
    }

    /* Aurora background */
    .dashboard-ambient {
        position: relative;
        isolation: isolate;
    }

    .dashboard-ambient:before,
    .dashboard-ambient:after {
        content: "";
        position: absolute;
        inset: -40px -20px auto -20px;
        height: 360px;
        z-index: -1;
        background:
            radial-gradient(55% 80% at 15% 30%, rgba(236, 72, 153, .22), transparent 60%),
            radial-gradient(50% 60% at 75% 30%, rgba(139, 92, 246, .24), transparent 60%),
            radial-gradient(40% 70% at 50% 70%, rgba(244, 63, 94, .20), transparent 65%);
        filter: blur(30px) saturate(120%);
    }

    /* Glass & glow border */
    .glass {
        background: rgba(255, 255, 255, .72);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        border: 1px solid rgba(244, 114, 182, .18);
    }

    .glow-border {
        border: 2px solid transparent;
        border-radius: 16px;
        background: linear-gradient(#fff, #fff) padding-box,
            linear-gradient(135deg, var(--rose), var(--violet)) border-box;
        box-shadow: 0 8px 30px rgba(244, 63, 94, .10);
    }

    /* Lift on hover */
    .lift {
        transition: transform .28s cubic-bezier(.22, .61, .36, 1), box-shadow .28s;
    }

    .lift:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .10)
    }

    /* Subtle shine */
    .shine {
        position: relative;
        overflow: hidden
    }

    .shine:after {
        content: "";
        position: absolute;
        inset: -120% -60% auto auto;
        height: 300%;
        width: 60%;
        background: linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, .34) 45%, transparent 60%);
        transform: rotate(8deg);
        transition: transform .9s;
    }

    .shine:hover:after {
        transform: translateX(-40%) rotate(8deg)
    }

    /* Text gradient */
    .t-gradient {
        background: linear-gradient(90deg, var(--rose), var(--pink), var(--violet));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
</style>

<section class="dashboard-ambient max-w-7xl mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-extrabold t-gradient leading-tight">
                Xin chào, {{ $user->name ?? $user->email }}
            </h1>
            <p class="text-slate-600 mt-1">Ảnh tổng quan hoạt động & chi tiêu tại <b>Cosme House</b>.</p>
        </div>
        <a href="{{ route('account.orders.index') }}"
            class="shine inline-flex items-center gap-2 rounded-xl glow-border px-4 py-2 bg-white text-ink">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M3 3h2l.4 2M7 13h10l3-7H6.4" />
                <circle cx="9" cy="20" r="1.5" />
                <circle cx="17" cy="20" r="1.5" />
            </svg>
            Lịch sử đơn hàng
        </a>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        @php
        $kpis = [
        ['label'=>'Tổng đơn','val'=>$stats['total_orders'],'icon'=>'M3 3h2l.4 2M7 13h10l3-7H6.4','hint'=>'Mọi thời gian'],
        ['label'=>'Đang xử lý','val'=>$stats['open_orders'],'icon'=>'M5 12h14M5 6h14M5 18h8','hint'=>'Pending • Processing • Confirmed'],
        ['label'=>'Hoàn tất','val'=>$stats['completed_orders'],'icon'=>'M20 6L9 17l-5-5','hint'=>'Đã giao & hoàn tất'],
        ['label'=>'Chi tiêu','val'=>'₫'.number_format($stats['total_spent']),'icon'=>'M3 10h11a4 4 0 0 1 0 8H7','hint'=>'Đã thanh toán'],
        ];
        @endphp
        @foreach($kpis as $i=>$k)
        <div class="glass rounded-2xl p-4 lift">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white"
                    style="background:linear-gradient(135deg,var(--rose),var(--violet))">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="{{ $k['icon'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <span class="text-xs text-slate-500">{{ $k['hint'] }}</span>
            </div>
            <div class="text-xs uppercase tracking-wide text-slate-500 mt-2">{{ $k['label'] }}</div>
            <div class="text-[28px] font-extrabold mt-1">{{ is_numeric($k['val'])?number_format($k['val']):$k['val'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid lg:grid-cols-3 gap-4 mt-6">
        <div class="lg:col-span-2 bg-white rounded-2xl glow-border p-4 lift">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold">Xu hướng 6 tháng gần nhất</div>
                <div class="text-xs text-slate-500">Đơn hàng &amp; Chi tiêu</div>
            </div>
            <canvas id="chartTrend" height="110"></canvas>
        </div>

        <div class="bg-white rounded-2xl glow-border p-4 lift">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold">Tỉ trọng trạng thái đơn</div>
                <div class="text-xs text-slate-500">Tổng hợp</div>
            </div>
            @if(empty($charts['pieLabels']))
            <div class="text-sm text-slate-500">Chưa có dữ liệu.</div>
            @else
            <canvas id="chartStatus" height="160"></canvas>
            @endif
        </div>
    </div>

    {{-- Charts Row 2 + Quick links --}}
    <div class="grid lg:grid-cols-3 gap-4 mt-4">
        <div class="lg:col-span-2 bg-white rounded-2xl glow-border p-4 lift">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold">{{ $charts['topTitle'] ?? 'Sản phẩm mua nhiều' }}</div>
                <div class="text-xs text-slate-500">Top 5</div>
            </div>
            @if(empty($charts['topLabels']))
            <div class="text-sm text-slate-500">Chưa có dữ liệu.</div>
            @else
            <canvas id="chartTop" height="110"></canvas>
            @endif
        </div>

        <div class="glass rounded-2xl p-4 lift">
            <div class="font-semibold mb-2">Lối tắt</div>
            <div class="grid grid-cols-2 gap-3">
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white" href="{{ route('account.profile') }}">
                    <div class="font-medium text-sm">Trang cá nhân</div>
                    <div class="text-slate-500 text-xs">Cập nhật thông tin</div>
                </a>
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white" href="{{ route('account.addresses.index') }}">
                    <div class="font-medium text-sm">Địa chỉ</div>
                    <div class="text-slate-500 text-xs">Quản lý giao hàng</div>
                </a>
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white" href="{{ route('account.coupons') }}">
                    <div class="font-medium text-sm">Mã giảm giá</div>
                    <div class="text-slate-500 text-xs">Đã dùng: {{ number_format($redeemedCoupons) }}</div>
                </a>
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white" href="{{ route('account.reviews') }}">
                    <div class="font-medium text-sm">Đánh giá</div>
                    <div class="text-slate-500 text-xs">Đã viết: {{ number_format($reviewCount) }}</div>
                </a>
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white" href="{{ route('account.wishlist') }}">
                    <div class="font-medium text-sm">Yêu thích</div>
                    <div class="text-slate-500 text-xs">SP: {{ number_format($wishlistCount) }}</div>
                </a>
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white" href="{{ route('account.orders.index') }}">
                    <div class="font-medium text-sm">Đơn hàng</div>
                    <div class="text-slate-500 text-xs">Xem lịch sử</div>
                </a>
                <a class="shine rounded-xl border px-3 py-3 hover:bg-white bg-gradient-to-br from-brand-50 to-rose-50 border-brand-200" href="{{ route('blockchain.verify') }}">
                    <div class="font-medium text-sm flex items-center gap-2">
                        <i class="fa-solid fa-qrcode text-brand-600"></i>
                        Xác thực CosmeChain
                    </div>
                    <div class="text-slate-500 text-xs">Quét QR code</div>
                </a>
            </div>
        </div>
    </div>

    {{-- Addresses --}}
    @if($shipping || $billing)
    <div class="bg-white rounded-2xl glow-border p-4 mt-6 lift">
        <div class="flex items-center justify-between">
            <div class="font-semibold">Địa chỉ mặc định</div>
            <a href="{{ route('account.addresses.index') }}" class="text-sm text-rose-600 hover:underline">Quản lý địa chỉ</a>
        </div>
        <div class="grid md:grid-cols-2 gap-4 mt-3">
            @if($shipping)
            <div class="glass rounded-xl p-3">
                <div class="font-medium mb-1">Giao hàng</div>
                <div class="text-slate-600 text-sm leading-6">
                    {{ $shipping['name'] ?? '' }}<br>
                    {{ $shipping['phone'] ?? '' }}<br>
                    {{ $shipping['line1'] ?? '' }} {{ $shipping['line2'] ?? '' }}<br>
                    {{ $shipping['ward'] ?? '' }}{{ !empty($shipping['district']) ? ', '.$shipping['district'] : '' }}{{ !empty($shipping['province']) ? ', '.$shipping['province'] : '' }}
                </div>
            </div>
            @endif

            @if($billing)
            <div class="glass rounded-xl p-3">
                <div class="font-medium mb-1">Thanh toán</div>
                <div class="text-slate-600 text-sm leading-6">
                    {{ $billing['name'] ?? '' }}<br>
                    {{ $billing['phone'] ?? '' }}<br>
                    {{ $billing['line1'] ?? '' }} {{ $billing['line2'] ?? '' }}<br>
                    {{ $billing['ward'] ?? '' }}{{ !empty($billing['district']) ? ', '.$billing['district'] : '' }}{{ !empty($billing['province']) ? ', '.$billing['province'] : '' }}
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Recent orders --}}
    <div class="bg-white rounded-2xl glow-border mt-6 lift">
        <div class="p-4 flex items-center justify-between">
            <div class="font-semibold">Đơn hàng gần đây</div>
            <a href="{{ route('account.orders.index') }}" class="text-sm text-rose-600 hover:underline">Xem tất cả</a>
        </div>
        @if($recentOrders->isEmpty())
        <div class="p-4 text-slate-500">Bạn chưa có đơn hàng nào.</div>
        @else
        <div class="divide-y">
            @foreach($recentOrders as $o)
            <a href="{{ route('account.orders.show', $o->id) }}" class="flex items-center justify-between p-4 hover:bg-rose-50/50">
                <div>
                    <div class="font-medium">#{{ $o->code }}</div>
                    <div class="text-xs text-slate-500">{{ optional($o->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm">{{ ucfirst(str_replace('_',' ', $o->status)) }}</div>
                    <div class="text-xs text-slate-500">{{ ucfirst(str_replace('_',' ', $o->payment_status)) }}</div>
                    <div class="font-semibold mt-1">₫{{ number_format($o->grand_total) }}</div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- Chart.js CDN (nếu layout đã có thì bỏ dòng này) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data from controller
    const C = {
        labels: @json($charts['labels'] ?? []),
        seriesOrders: @json($charts['seriesOrders'] ?? []),
        seriesAmount: @json($charts['seriesAmount'] ?? []),
        pieLabels: @json($charts['pieLabels'] ?? []),
        pieValues: @json($charts['pieValues'] ?? []),
        topLabels: @json($charts['topLabels'] ?? []),
        topValues: @json($charts['topValues'] ?? [])
    };

    Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial';
    Chart.defaults.color = '#334155';

    // Trend (Line+Bar with gradients)
    (function() {
        const el = document.getElementById('chartTrend');
        if (!el) return;
        const ctx = el.getContext('2d');
        const gradLine = ctx.createLinearGradient(0, 0, 0, 240);
        gradLine.addColorStop(0, 'rgba(244,63,94,.45)');
        gradLine.addColorStop(1, 'rgba(244,63,94,0)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: C.labels,
                datasets: [{
                        type: 'line',
                        label: 'Chi tiêu (₫)',
                        yAxisID: 'y2',
                        data: C.seriesAmount,
                        borderColor: 'var(--rose)',
                        backgroundColor: gradLine,
                        fill: true,
                        tension: .35,
                        borderWidth: 2
                    },
                    {
                        type: 'bar',
                        label: 'Số đơn',
                        yAxisID: 'y1',
                        data: C.seriesOrders,
                        backgroundColor: 'rgba(6,182,212,.35)',
                        borderColor: '#06b6d4',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#334155'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const v = ctx.parsed.y;
                                if (ctx.dataset.yAxisID === 'y2') return ' Chi tiêu: ' + (v || 0).toLocaleString('vi-VN') + '₫';
                                return ' Số đơn: ' + (v || 0).toLocaleString('vi-VN');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(100,116,139,.12)'
                        }
                    },
                    y1: {
                        position: 'left',
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(100,116,139,.1)'
                        }
                    },
                    y2: {
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    })();

    // Status doughnut
    (function() {
        if (!C.pieLabels.length) return;
        const ctx = document.getElementById('chartStatus').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: C.pieLabels,
                datasets: [{
                    data: C.pieValues,
                    backgroundColor: ['#f43f5e', '#ec4899', '#8b5cf6', '#06b6d4', '#f59e0b', '#10b981', '#94a3b8'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    })();

    // Top items/categories
    (function() {
        if (!C.topLabels.length) return;
        const ctx = document.getElementById('chartTop').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: C.topLabels,
                datasets: [{
                    label: 'Số lượng',
                    data: C.topValues,
                    backgroundColor: 'rgba(139,92,246,.35)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1.2,
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    })();
</script>
@endsection
