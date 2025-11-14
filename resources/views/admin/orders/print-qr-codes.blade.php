<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In QR Codes - Đơn hàng {{ $order->code }}</title>
    @php
    use Illuminate\Support\Facades\Storage;
    use App\Models\Setting;
    $storeName = Setting::get('store.name', config('app.name', 'Cosme House'));
    @endphp
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        /* ========== PREVIEW MODE (không in) ========== */
        .preview-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .preview-header {
            background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .preview-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .preview-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .preview-actions {
            padding: 24px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #f43f5e;
            color: white;
        }

        .btn-primary:hover {
            background: #e11d48;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .preview-info {
            padding: 24px 30px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #111827;
            font-weight: 600;
        }

        .preview-qr-grid {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
        }

        .preview-qr-item {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s;
        }

        .preview-qr-item:hover {
            border-color: #f43f5e;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.1);
        }

        .preview-qr-item img {
            width: 100%;
            max-width: 200px;
            height: auto;
            margin-bottom: 12px;
            border-radius: 8px;
        }

        .preview-qr-item .product-name {
            font-weight: 600;
            font-size: 14px;
            color: #111827;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .preview-qr-item .qr-code-text {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            color: #6b7280;
            word-break: break-all;
            margin-top: 8px;
            padding: 8px;
            background: #f9fafb;
            border-radius: 4px;
        }

        .empty-state {
            padding: 60px 30px;
            text-align: center;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* ========== PRINT MODE (chỉ in QR + tên cửa hàng) ========== */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            /* Mỗi QR code là một sticker/tem riêng */
            .print-qr-sticker {
                width: 60mm; /* Kích thước tem dán chuẩn */
                height: 60mm;
                border: 1px solid #ddd;
                padding: 8mm;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                page-break-inside: avoid;
                margin: 0;
                box-sizing: border-box;
            }

            .print-qr-sticker img {
                width: 100%;
                max-width: 40mm;
                height: auto;
                margin-bottom: 4mm;
            }

            .print-qr-sticker .store-name {
                font-size: 10pt;
                font-weight: 700;
                color: #f43f5e;
                text-align: center;
                margin-top: 2mm;
                letter-spacing: 0.5px;
            }

            /* Layout in: 4 cột trên mỗi trang A4 */
            .print-container {
                display: grid;
                grid-template-columns: repeat(4, 60mm);
                grid-template-rows: repeat(4, 60mm);
                gap: 0;
                justify-content: center;
                padding: 10mm;
                page-break-after: always;
            }

            /* Ẩn tất cả preview elements */
            .preview-container,
            .preview-header,
            .preview-actions,
            .preview-info,
            .preview-qr-grid,
            .preview-qr-item {
                display: none !important;
            }
        }

        /* Hiển thị print layout khi preview */
        @media screen {
            .print-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    {{-- ========== PREVIEW MODE (hiển thị trên màn hình) ========== --}}
    <div class="preview-container no-print">
        <div class="preview-header">
            <h1><i class="fas fa-qrcode"></i> In QR Codes CosmeChain</h1>
            <p>Đơn hàng: <strong>{{ $order->code }}</strong></p>
        </div>

        <div class="preview-actions">
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> In QR Codes
            </button>
        </div>

        <div class="preview-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Khách hàng</span>
                    <span class="info-value">{{ $order->customer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Điện thoại</span>
                    <span class="info-value">{{ $order->customer_phone }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ngày đặt</span>
                    <span class="info-value">{{ $order->placed_at ? $order->placed_at->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tổng số QR codes</span>
                    <span class="info-value">{{ $qrCodes->count() }} tem</span>
                </div>
            </div>
        </div>

        @if($qrCodes->isEmpty())
        <div class="empty-state">
            <i class="fas fa-qrcode"></i>
            <p style="font-size: 18px; margin-bottom: 8px;">Chưa có QR codes cho đơn hàng này</p>
            <p style="font-size: 14px;">QR codes sẽ được tự động tạo khi đơn hàng được xác nhận hoặc đang xử lý.</p>
        </div>
        @else
        <div class="preview-qr-grid">
            @foreach($qrCodes as $qr)
            <div class="preview-qr-item">
                @if($qr->qr_image_path)
                <img src="{{ Storage::disk('public')->url($qr->qr_image_path) }}" alt="QR Code">
                @elseif($qr->qr_image_url)
                <img src="{{ $qr->qr_image_url }}" alt="QR Code">
                @else
                <div style="padding: 40px; background: #f3f4f6; color: #999; border-radius: 8px;">
                    <i class="fas fa-qrcode" style="font-size: 48px;"></i>
                </div>
                @endif
                <div class="product-name">
                    {{ $qr->productVariant->product->name ?? 'N/A' }}
                </div>
                <div class="qr-code-text">
                    {{ $qr->qr_code }}
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ========== PRINT MODE (chỉ hiển thị khi in) ========== --}}
    @if($qrCodes->isNotEmpty())
    <div class="print-container">
        @foreach($qrCodes as $qr)
        <div class="print-qr-sticker">
            @if($qr->qr_image_path)
            <img src="{{ Storage::disk('public')->url($qr->qr_image_path) }}" alt="QR Code">
            @elseif($qr->qr_image_url)
            <img src="{{ $qr->qr_image_url }}" alt="QR Code">
            @endif
            <div class="store-name">{{ $storeName }}</div>
        </div>
        @endforeach
    </div>
    @endif
</body>
</html>

