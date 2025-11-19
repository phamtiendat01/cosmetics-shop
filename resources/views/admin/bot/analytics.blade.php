@extends('admin.layouts.app')
@section('title', 'CosmeBot - Analytics')

@section('content')
<div class="toolbar">
    <div class="toolbar-title">Analytics</div>
    <div class="toolbar-actions"></div>
</div>

{{-- Filters --}}
<div class="card p-3 mb-3">
    <form method="GET" class="grid md:grid-cols-3 gap-2 items-end">
        <div>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-soft btn-sm">Lọc</button>
            <a href="{{ route('admin.bot.analytics') }}" class="btn btn-outline btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.1s backwards;">
        <div class="text-2xl font-bold mb-0.5">{{ number_format($stats['total_interactions']) }}</div>
        <div class="text-xs text-slate-500">Tổng tương tác</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.2s backwards;">
        <div class="text-2xl font-bold mb-0.5">{{ number_format($stats['intent_detections']) }}</div>
        <div class="text-xs text-slate-500">Intent detections</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.3s backwards;">
        <div class="text-2xl font-bold mb-0.5">{{ number_format($stats['tool_calls']) }}</div>
        <div class="text-xs text-slate-500">Tool calls</div>
    </div>
    <div class="card p-3" style="animation: fadeInUp 0.4s ease-out 0.4s backwards;">
        <div class="text-2xl font-bold mb-0.5">{{ number_format($stats['avg_latency'], 0) }}ms</div>
        <div class="text-xs text-slate-500">Avg latency</div>
    </div>
</div>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="grid lg:grid-cols-2 gap-3">
    {{-- Intent Performance --}}
    <div class="card p-4">
        <div class="font-semibold mb-3">Intent Performance</div>
        <div class="space-y-2">
            @forelse($intentStats as $index => $stat)
            <div class="flex items-center justify-between p-2 bg-slate-50 rounded hover:bg-slate-100 transition-colors"
                 style="animation: fadeInUp 0.3s ease-out {{ 0.5 + ($index * 0.05) }}s backwards;">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ $stat->intent }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $stat->count }} lần • Confidence: {{ number_format($stat->avg_confidence * 100, 1) }}%
                    </div>
                </div>
                <div class="text-sm font-semibold ml-2">{{ $stat->count }}</div>
            </div>
            @empty
            <div class="text-center py-8 text-sm text-slate-500">Chưa có dữ liệu</div>
            @endforelse
        </div>
    </div>

    {{-- Tool Usage --}}
    <div class="card p-4">
        <div class="font-semibold mb-3">Tool Usage</div>
        <div class="space-y-2">
            @forelse($toolStats as $index => $stat)
            <div class="flex items-center justify-between p-2 bg-slate-50 rounded hover:bg-slate-100 transition-colors"
                 style="animation: fadeInUp 0.3s ease-out {{ 0.5 + ($index * 0.05) }}s backwards;">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ $stat->tool ?? 'N/A' }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ $stat->count }} lần sử dụng</div>
                </div>
                <div class="text-sm font-semibold ml-2">{{ $stat->count }}</div>
            </div>
            @empty
            <div class="text-center py-8 text-sm text-slate-500">Chưa có dữ liệu</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

