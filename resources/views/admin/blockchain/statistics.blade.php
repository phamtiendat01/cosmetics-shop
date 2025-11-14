@extends('admin.layouts.app')
@section('title','CosmeChain - Statistics')

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-3" data-auto-dismiss="3000">{{ session('ok') }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-title">Thống kê CosmeChain</div>
    <div class="toolbar-actions">
        <a href="{{ route('admin.blockchain.certificates') }}" class="btn btn-outline btn-sm">Certificates</a>
        <a href="{{ route('admin.blockchain.qr-codes') }}" class="btn btn-outline btn-sm">QR Codes</a>
        <a href="{{ route('admin.blockchain.verifications') }}" class="btn btn-outline btn-sm">Verifications</a>
    </div>
</div>

{{-- Overall Stats --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng Certificates</div>
                <div class="text-3xl font-bold text-slate-900">{{ number_format($totalCertificates) }}</div>
            </div>
            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-certificate text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng QR Codes</div>
                <div class="text-3xl font-bold text-slate-900">{{ number_format($totalQRCodes) }}</div>
            </div>
            <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center">
                <i class="fas fa-qrcode text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Tổng Verifications</div>
                <div class="text-3xl font-bold text-slate-900">{{ number_format($totalVerifications) }}</div>
            </div>
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Verification Results --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Chính hãng</div>
                <div class="text-3xl font-bold text-green-600">{{ number_format($verificationStats['authentic']) }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $totalVerifications > 0 ? number_format(($verificationStats['authentic'] / $totalVerifications) * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-check text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Hàng giả</div>
                <div class="text-3xl font-bold text-red-600">{{ number_format($verificationStats['fake']) }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $totalVerifications > 0 ? number_format(($verificationStats['fake'] / $totalVerifications) * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-times text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đáng nghi</div>
                <div class="text-3xl font-bold text-amber-600">{{ number_format($verificationStats['suspicious']) }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $totalVerifications > 0 ? number_format(($verificationStats['suspicious'] / $totalVerifications) * 100, 1) : 0 }}%
                </div>
            </div>
            <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-amber-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- QR Code Stats --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đã xác thực</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($qrStats['verified']) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Khả nghi</div>
                <div class="text-2xl font-bold text-amber-600">{{ number_format($qrStats['flagged']) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                <i class="fas fa-flag text-amber-600"></i>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-slate-600 mb-1">Đã khóa</div>
                <div class="text-2xl font-bold text-red-600">{{ number_format($qrStats['blocked']) }}</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-ban text-red-600"></i>
            </div>
        </div>
    </div>
</div>

{{-- Fraud Detection Stats --}}
<div class="card p-6 mb-6">
    <h3 class="text-xl font-bold mb-4 flex items-center">
        <i class="fas fa-shield-alt text-red-600 mr-2"></i>
        Phát hiện gian lận
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-amber-50 rounded-lg p-4">
            <div class="text-sm text-amber-700 mb-1">QR Codes khả nghi</div>
            <div class="text-2xl font-bold text-amber-800">{{ number_format($fraudStats['suspicious_qr']) }}</div>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
            <div class="text-sm text-red-700 mb-1">QR Codes đã khóa</div>
            <div class="text-2xl font-bold text-red-800">{{ number_format($fraudStats['blocked_qr']) }}</div>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
            <div class="text-sm text-orange-700 mb-1">IP đáng nghi</div>
            <div class="text-2xl font-bold text-orange-800">{{ number_format($fraudStats['high_risk_ips']) }}</div>
        </div>
    </div>
</div>

{{-- Top Products --}}
@if($topProducts->isNotEmpty())
<div class="card p-6 mb-6">
    <h3 class="text-xl font-bold mb-4 flex items-center">
        <i class="fas fa-trophy text-amber-600 mr-2"></i>
        Top 10 sản phẩm được verify nhiều nhất
    </h3>
    <div class="table-wrap">
        <table class="table-admin w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sản phẩm</th>
                    <th>Số lần verify</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $index => $item)
                @php
                $product = $item->productVariant->product ?? null;
                @endphp
                <tr>
                    <td class="text-right pr-3">{{ $index + 1 }}</td>
                    <td>
                        @if($product)
                        <div class="font-medium">{{ $product->name }}</div>
                        <div class="text-xs text-slate-500">SKU: {{ $item->productVariant->sku ?? 'N/A' }}</div>
                        @else
                        <span class="text-slate-400">N/A</span>
                        @endif
                    </td>
                    <td>
                        <span class="font-semibold text-brand-600">{{ number_format($item->verify_count) }}</span>
                    </td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product->id ?? '#') }}" class="btn btn-xs btn-soft">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Daily Verification Chart --}}
<div class="card p-6">
    <h3 class="text-xl font-bold mb-4 flex items-center">
        <i class="fas fa-chart-line text-blue-600 mr-2"></i>
        Xu hướng Verification (30 ngày gần nhất)
    </h3>
    <canvas id="verificationChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('verificationChart');
if (ctx) {
    const dailyData = @json($dailyVerifications);
    
    // Format labels: "2025-11-13" -> "13/11"
    const labels = Object.keys(dailyData).map(date => {
        const [year, month, day] = date.split('-');
        return `${day}/${month}`;
    });
    
    const data = Object.values(dailyData);
    
    // Tìm max value để set stepSize phù hợp
    const maxValue = Math.max(...data, 1);
    const stepSize = maxValue <= 10 ? 1 : maxValue <= 50 ? 5 : maxValue <= 100 ? 10 : 20;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Số lần verify',
                data: data,
                borderColor: 'rgb(244, 63, 94)',
                backgroundColor: 'rgba(244, 63, 94, 0.1)',
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(244, 63, 94)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        title: function(context) {
                            // Hiển thị full date trong tooltip
                            const index = context[0].dataIndex;
                            const dates = Object.keys(dailyData);
                            const fullDate = dates[index];
                            const [year, month, day] = fullDate.split('-');
                            return `${day}/${month}/${year}`;
                        },
                        label: function(context) {
                            return `Số lần verify: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: stepSize,
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
}
</script>

@endsection

