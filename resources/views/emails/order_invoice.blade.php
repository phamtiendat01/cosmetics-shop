@php
// ===== Helpers =====
$vnd = function ($n) {
return number_format((int)($n ?? 0), 0, ',', '.') . ' ₫';
};
$lineTotal = function ($item) {
$qty = (int)($item['qty'] ?? 0);
$price = (int)($item['price'] ?? 0);
return $qty * $price;
};

// ===== Inputs =====
$code = $order['code'] ?? 'ORDER';
$createdAt = $order['created_at'] ?? null;
$cart = $order['cart'] ?? [];
$subtotal = $order['subtotal'] ?? 0;
$shipping = $order['shipping'] ?? 0;
$discount = $order['discount'] ?? 0;
$grand = $order['total'] ?? ($subtotal + $shipping - $discount);
$cust = $order['customer'] ?? [];
$pmRaw = strtoupper((string)($order['payment_method'] ?? ''));

$methodLabels = [
'COD' => 'Thanh toán khi nhận (COD)',
'MOMO' => 'MoMo',
'VNPAY' => 'VNPay',
'VIETQR' => 'VietQR',
];
$pmLabel = $methodLabels[$pmRaw] ?? $pmRaw;

$shopName = config('mail.from.name') ?? config('app.name') ?? 'Cosme House';
$shopEmail = config('mail.from.address') ?? 'no-reply@example.com';
$appUrl = config('app.url') ?? url('/');
$orderUrl = $order['order_url'] ?? (rtrim($appUrl, '/') . '/orders/' . urlencode($code));

// Màu
$brand = '#111827';
$accent = '#2E90FA';
$muted = '#6B7280';
$bgSoft = '#F6F7F9';
@endphp
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Hóa đơn #{{ $code }} – {{ $shopName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tránh Blade hiểu nhầm @media là directive --}}
    @verbatim
    <style>
        @media (max-width: 640px) {
            .container {
                width: 100% !important;
            }

            .px-24 {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .py-24 {
                padding-top: 16px !important;
                padding-bottom: 16px !important;
            }

            .hidden-sm {
                display: none !important;
            }
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #0B0B0B !important;
            }

            .card,
            .main,
            .container {
                background: #111318 !important;
                color: #E5E7EB !important;
            }

            .muted {
                color: #9CA3AF !important;
            }

            .th {
                background: #1F2937 !important;
                color: #E5E7EB !important;
            }

            .td {
                border-color: #1F2937 !important;
            }

            .pill-paid {
                background: #064E3B !important;
                color: #D1FAE5 !important;
                border-color: #065F46 !important;
            }
        }
    </style>
    @endverbatim
</head>

<body style="margin:0; padding:0; background: {{ $bgSoft }}; font-family: Arial, Helvetica, sans-serif; color: {{ $brand }};">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background: {{ $bgSoft }};">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table class="container" role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:640px; max-width:640px; background:#ffffff; border-radius: 12px; overflow:hidden; border:1px solid #E5E7EB;">
                    <tr>
                        <td style="padding: 18px 24px; background: {{ $brand }}; color:#ffffff;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td align="left" style="font-size: 18px; font-weight:700; letter-spacing:.2px;">
                                        {{ $shopName }}
                                    </td>
                                    <td align="right" class="hidden-sm" style="font-size:12px; opacity:.9;">
                                        Mã đơn: <strong>#{{ $code }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-24 py-24" style="padding: 22px 24px;">
                            <h1 style="margin: 0 0 6px; font-size: 18px; font-weight: 700;">
                                {{ $paid ? 'Thanh toán thành công' : 'Xác nhận đơn hàng' }}
                            </h1>
                            <p class="muted" style="margin: 0 0 14px; font-size: 13px; color: {{ $muted }};">
                                Mã đơn <strong>#{{ $code }}</strong>@if($createdAt) · đặt lúc {{ $createdAt }}@endif
                            </p>

                            <p style="margin: 0 0 12px; font-size: 14px; line-height: 1.6;">
                                Chào {{ $cust['name'] ?? 'Quý khách' }},<br>
                                @if($paid)
                                Cảm ơn bạn! Thanh toán cho đơn <strong>#{{ $code }}</strong> đã <strong>được xác nhận</strong> qua <strong>{{ $pmLabel ?: '—' }}</strong>.
                                Chúng tôi sẽ sớm xử lý và giao hàng.
                                @else
                                Cảm ơn bạn đã đặt hàng tại <strong>{{ $shopName }}</strong>. Vui lòng giữ liên lạc để xác nhận & giao hàng.
                                @endif
                            </p>

                            <div style="margin:12px 0 0;">
                                <span class="pill-paid" style="display:inline-block; font-size:12px; padding:6px 10px; border-radius:999px; background:{{ $paid ? '#ECFDF5' : '#F3F4F6' }}; color:{{ $paid ? '#065F46' : '#374151' }}; border:1px solid {{ $paid ? '#A7F3D0' : '#E5E7EB' }}; font-weight:600;">
                                    {{ $paid ? 'ĐÃ THANH TOÁN' : 'CHỜ THANH TOÁN' }}
                                </span>
                                @if($pmLabel)
                                <span style="display:inline-block; font-size:12px; padding:6px 10px; border-radius:999px; background:#EEF2FF; color:#3730A3; border:1px solid #E0E7FF; font-weight:600; margin-left:6px;">
                                    {{ $pmLabel }}
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-24" style="padding: 0 24px 6px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th class="th" align="left" style="background:#F9FAFB; color:#111827; font-size:13px; font-weight:600; padding:10px 12px; border-bottom:1px solid #F1F5F9;">Sản phẩm</th>
                                        <th class="th" align="right" style="background:#F9FAFB; color:#111827; font-size:13px; font-weight:600; padding:10px 12px; border-bottom:1px solid #F1F5F9;">SL</th>
                                        <th class="th" align="right" style="background:#F9FAFB; color:#111827; font-size:13px; font-weight:600; padding:10px 12px; border-bottom:1px solid #F1F5F9;">Đơn giá</th>
                                        <th class="th" align="right" style="background:#F9FAFB; color:#111827; font-size:13px; font-weight:600; padding:10px 12px; border-bottom:1px solid #F1F5F9;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cart as $item)
                                    <tr>
                                        <td class="td" align="left" style="font-size:13px; padding:10px 12px; border-bottom:1px solid #F1F5F9;">
                                            {{ $item['name'] ?? 'Sản phẩm' }}
                                        </td>
                                        <td class="td" align="right" style="font-size:13px; padding:10px 12px; border-bottom:1px solid #F1F5F9;">
                                            {{ (int)($item['qty'] ?? 0) }}
                                        </td>
                                        <td class="td" align="right" style="font-size:13px; padding:10px 12px; border-bottom:1px solid #F1F5F9;">
                                            {{ $vnd($item['price'] ?? 0) }}
                                        </td>
                                        <td class="td" align="right" style="font-size:13px; padding:10px 12px; border-bottom:1px solid #F1F5F9;">
                                            {{ $vnd($lineTotal($item)) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="td" colspan="4" align="left" style="font-size:13px; padding:12px; color:{{ $muted }}; border-bottom:1px solid #F1F5F9;">
                                            (Chưa có dòng sản phẩm)
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-24" style="padding: 8px 24px 4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="right" style="width:55%;"></td>
                                    <td align="right" style="width:45%;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
                                            <tr>
                                                <td align="left" style="font-size:14px; padding:6px 0;">Tạm tính</td>
                                                <td align="right" style="font-size:14px; padding:6px 0;">{{ $vnd($subtotal) }}</td>
                                            </tr>
                                            @if((int)$discount > 0)
                                            <tr>
                                                <td align="left" style="font-size:14px; padding:6px 0;">Giảm giá</td>
                                                <td align="right" style="font-size:14px; padding:6px 0;">- {{ $vnd($discount) }}</td>
                                            </tr>
                                            @endif
                                            @if((int)$shipping > 0)
                                            <tr>
                                                <td align="left" style="font-size:14px; padding:6px 0;">Phí vận chuyển</td>
                                                <td align="right" style="font-size:14px; padding:6px 0;">{{ $vnd($shipping) }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td align="left" style="font-size:15px; padding:10px 0; font-weight:700; border-top:1px dashed #E5E7EB;">Tổng thanh toán</td>
                                                <td align="right" style="font-size:15px; padding:10px 0; font-weight:700; border-top:1px dashed #E5E7EB;">{{ $vnd($grand) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-24" style="padding: 6px 24px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="card" style="border:1px solid #E5E7EB; border-radius:10px; padding:14px; vertical-align:top;">
                                        <div style="font-size:12px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Thông tin nhận hàng</div>
                                        <div style="font-size:14px; font-weight:700; margin-bottom:4px;">{{ $cust['name'] ?? '—' }}</div>
                                        <div style="font-size:13px; color:#374151; margin-bottom:2px;">Điện thoại: {{ $cust['phone'] ?? '—' }}</div>
                                        <div style="font-size:13px; color:#374151; margin-bottom:6px;">Email: {{ $cust['email'] ?? '—' }}</div>
                                        <div style="font-size:14px; color:#111827;">{{ $cust['addr'] ?? '—' }}</div>
                                        @if(!empty($cust['note']))
                                        <div style="margin-top:10px; background:#F9FAFB; border:1px dashed #E5E7EB; border-radius:8px; padding:10px 12px; font-size:13px; color:#374151;">
                                            <strong>Ghi chú:</strong> {{ $cust['note'] }}
                                        </div>
                                        @endif
                                    </td>

                                    <td style="width:12px;"></td>

                                    <td class="card" style="border:1px solid #E5E7EB; border-radius:10px; padding:14px; vertical-align:top;">
                                        <div style="font-size:12px; color:{{ $muted }}; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Thông tin thanh toán</div>
                                        <div style="font-size:14px; margin-bottom:6px;">Phương thức: <strong>{{ $pmLabel ?: '—' }}</strong></div>
                                        <div style="font-size:14px;">Trạng thái:
                                            <span style="display:inline-block; font-size:12px; padding:4px 8px; border-radius:999px; background:{{ $paid ? '#ECFDF5' : '#F3F4F6' }}; color:{{ $paid ? '#065F46' : '#374151' }}; border:1px solid {{ $paid ? '#A7F3D0' : '#E5E7EB' }}; font-weight:600; margin-left:4px;">
                                                {{ $paid ? 'ĐÃ THANH TOÁN' : 'CHỜ THANH TOÁN' }}
                                            </span>
                                        </div>
                                        @if($createdAt)
                                        <div style="font-size:13px; color:#374151; margin-top:8px;">Ngày tạo: {{ $createdAt }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-24" style="padding: 18px 24px 6px; text-align:center;">
                            <a href="{{ $orderUrl }}" target="_blank" rel="noopener"
                                style="display:inline-block; padding:12px 18px; background: {{ $accent }}; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700; font-size:14px;">
                                Xem đơn hàng
                            </a>
                            <div style="margin-top:8px; font-size:12px; color: {{ $muted }};">
                                Nếu nút không bấm được, hãy mở liên kết: <br>
                                <a href="{{ $orderUrl }}" target="_blank" style="color: {{ $accent }}; text-decoration: underline;">{{ $orderUrl }}</a>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="px-24" style="padding: 8px 24px 18px;">
                            <div style="font-size:13px; color: {{ $muted }}; text-align:center;">
                                Cần hỗ trợ? Hãy trả lời email này hoặc liên hệ <a href="mailto:{{ $shopEmail }}" style="color: {{ $accent }}; text-decoration: underline;">{{ $shopEmail }}</a>.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 16px 24px; background:#F9FAFB; text-align:center; color: {{ $muted }}; font-size:12px;">
                            © {{ date('Y') }} {{ $shopName }}. Đây là email tự động, vui lòng bỏ qua nếu bạn nhận được nhầm.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>