@extends('admin.layouts.app')
@section('title','Cài đặt')

@php use App\Models\Setting; @endphp

@section('content')
@if(session('ok'))
<div class="alert alert-success mb-4" data-auto-dismiss="2500">{{ session('ok') }}</div>
@endif

@if($errors->any())
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 p-3">
    <div class="font-medium mb-1">Có lỗi xảy ra, vui lòng kiểm tra lại:</div>
    <ul class="list-disc pl-6 text-sm space-y-1">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
</div>
@endif

@php
// tab hiện tại: ưu tiên query ?tab=..., fallback session hoặc 'general'
$tab = request('tab') ?? (session('settings.tab') ?? 'general');
@endphp

<form method="POST" action="{{ route('admin.settings.store', ['tab' => $tab]) }}" class="space-y-6">
    @csrf
    <input type="hidden" name="tab" value="{{ $tab }}"><!-- để controller có thể flash về đúng tab -->

    {{-- Sticky header actions (chỉ 1 nút Lưu) --}}
    <div class="sticky top-0 z-10 -mx-6 px-6 py-3 bg-white/90 backdrop-blur border-b border-slate-200 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">Cài đặt hệ thống</h1>
            <p class="text-slate-500 text-sm">Quản lý cấu hình cửa hàng, SEO, thanh toán, đơn hàng, vận chuyển, email, chính sách và trang tĩnh.</p>
        </div>
        <button class="px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700 shadow-sm">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Lưu cài đặt
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- NAV LEFT --}}
        <aside class="col-span-12 lg:col-span-3">
            <nav class="rounded-xl border border-slate-200 bg-white p-2 text-sm">
                @foreach([
                ['general','Chung','fa-sliders'],
                ['seo','SEO / Tracking','fa-magnifying-glass'],
                ['payment','Thanh toán','fa-credit-card'],
                ['order','Đơn hàng & Checkout','fa-receipt'],
                ['shipping','Vận chuyển','fa-truck'],
                ['email','Email','fa-envelope'],
                ['policy','Trả hàng / Hoàn tiền','fa-rotate-left'],
                ['pages','Trang tĩnh','fa-file-lines'],
                ] as [$key,$label,$icon])
                <a href="{{ route('admin.settings.index', ['tab'=>$key]) }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-50
                    {{ $tab===$key ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'text-slate-700' }}">
                    <i class="fa-solid {{$icon}} w-4 text-center"></i>
                    <span>{{ $label }}</span>
                </a>
                @endforeach
            </nav>
        </aside>

        {{-- PANELS RIGHT --}}
        <section class="col-span-12 lg:col-span-9 space-y-6">

            {{-- GENERAL --}}
            <div class="{{ $tab==='general' ? '' : 'hidden' }}">
                <x-card title="Cửa hàng" desc="Tên, logo, liên hệ và định dạng hiển thị.">
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input label="Tên cửa hàng" name="store[name]" :value="old('store.name', Setting::get('store.name'))" required />
                        <x-setting.input label="Hotline" name="store[hotline]" :value="old('store.hotline', Setting::get('store.hotline'))" />
                        <x-setting.input type="email" label="Email" name="store[email]" :value="old('store.email', Setting::get('store.email'))" />
                        <x-setting.input label="Địa chỉ" name="store[address]" :value="old('store.address', Setting::get('store.address'))" />
                        <x-setting.input label="Logo (URL/đường dẫn)" name="store[logo]" :value="old('store.logo', Setting::get('store.logo'))" />
                        <x-setting.input label="Favicon (URL/đường dẫn)" name="store[favicon]" :value="old('store.favicon', Setting::get('store.favicon'))" />
                        <div>
                            <label class="block text-sm font-medium mb-1">Tiền tệ</label>
                            <select name="store[currency]" class="w-full rounded border-slate-300">
                                @foreach (['VND'=>'VND','USD'=>'USD'] as $k=>$v)
                                <option value="{{ $k }}" @selected(old('store.currency', Setting::get('store.currency','VND'))==$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Ngôn ngữ</label>
                            <select name="store[locale]" class="w-full rounded border-slate-300">
                                @foreach (['vi'=>'Tiếng Việt','en'=>'English'] as $k=>$v)
                                <option value="{{ $k }}" @selected(old('store.locale', Setting::get('store.locale','vi'))==$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Múi giờ</label>
                            <input class="w-full rounded border-slate-300"
                                name="store[timezone]"
                                value="{{ old('store.timezone', Setting::get('store.timezone','Asia/Ho_Chi_Minh')) }}">
                            <p class="text-xs text-slate-500 mt-1">Ví dụ: <code>Asia/Ho_Chi_Minh</code></p>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- SEO --}}
            <div class="{{ $tab==='seo' ? '' : 'hidden' }}">
                <x-card title="SEO & Tracking" desc="Tiêu đề/mô tả mặc định và mã theo dõi.">
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input label="Tiêu đề mặc định" name="seo[default_title]" :value="old('seo.default_title', Setting::get('seo.default_title'))" />
                        <x-setting.input label="Ảnh OG (URL)" name="seo[og_image]" :value="old('seo.og_image', Setting::get('seo.og_image'))" />
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Mô tả mặc định</label>
                            <textarea name="seo[default_description]" rows="3" class="w-full rounded border-slate-300">{{ old('seo.default_description', Setting::get('seo.default_description')) }}</textarea>
                        </div>
                        <x-setting.input label="Google Tag (G-XXXX)" name="tracking[gtag_id]" :value="old('tracking.gtag_id', Setting::get('tracking.gtag_id'))" />
                        <x-setting.input label="Facebook Pixel ID" name="tracking[fb_pixel_id]" :value="old('tracking.fb_pixel_id', Setting::get('tracking.fb_pixel_id'))" />
                    </div>
                </x-card>
            </div>

            {{-- PAYMENT --}}
            <div class="{{ $tab==='payment' ? '' : 'hidden' }}">
                <x-card title="COD" desc="Thanh toán khi nhận hàng.">
                    <div class="flex items-center justify-end mb-4">
                        <x-switch name="payment[cod][enabled]" :checked="old('payment.cod.enabled', Setting::get('payment.cod.enabled'))" />
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input type="number" step="0.01" min="0" max="100"
                            label="Phí (%)" name="payment[cod][fee_percent]"
                            :value="old('payment.cod.fee_percent', Setting::get('payment.cod.fee_percent'))"
                            placeholder="VD 0 hoặc 1.5" />
                    </div>
                </x-card>

                <x-card title="VNPAY" desc="Kết nối cổng VNPAY.">
                    <div class="flex items-center justify-end mb-4">
                        <x-switch name="payment[vnpay][enabled]" :checked="old('payment.vnpay.enabled', Setting::get('payment.vnpay.enabled'))" />
                    </div>
                    <div class="grid md:grid-cols-3 gap-5">
                        <x-setting.input label="TMN Code" name="payment[vnpay][tmn_code]" :value="old('payment.vnpay.tmn_code', Setting::get('payment.vnpay.tmn_code'))" />
                        <x-setting.input label="Hash Secret" name="payment[vnpay][hash_secret]" :value="old('payment.vnpay.hash_secret', Setting::get('payment.vnpay.hash_secret'))" />
                        <x-switch name="payment[vnpay][sandbox]" label="Sandbox" :right="true" :checked="old('payment.vnpay.sandbox', Setting::get('payment.vnpay.sandbox', 1))" />
                    </div>
                </x-card>

                <x-card title="MoMo" desc="Kết nối ví MoMo.">
                    <div class="flex items-center justify-end mb-4">
                        <x-switch name="payment[momo][enabled]" :checked="old('payment.momo.enabled', Setting::get('payment.momo.enabled'))" />
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input label="Partner Code" name="payment[momo][partner_code]" :value="old('payment.momo.partner_code', Setting::get('payment.momo.partner_code'))" />
                        <x-setting.input label="Access Key" name="payment[momo][access_key]" :value="old('payment.momo.access_key', Setting::get('payment.momo.access_key'))" />
                        <x-setting.input label="Secret Key" name="payment[momo][secret_key]" :value="old('payment.momo.secret_key', Setting::get('payment.momo.secret_key'))" />
                        <x-switch name="payment[momo][sandbox]" label="Sandbox" :right="true" :checked="old('payment.momo.sandbox', Setting::get('payment.momo.sandbox',1))" />
                    </div>
                </x-card>
            </div>

            {{-- ORDER --}}
            <div class="{{ $tab==='order' ? '' : 'hidden' }}">
                <x-card title="Đơn hàng & Checkout" desc="Quy tắc đặt hàng và thanh toán.">
                    <div class="grid md:grid-cols-2 gap-5 items-start">
                        <x-switch name="checkout[allow_guest]" label="Cho phép mua không cần đăng ký"
                            :checked="old('checkout.allow_guest', Setting::get('checkout.allow_guest'))" />
                        <x-setting.input type="number" min="0" step="1000" label="Đơn tối thiểu (VND)"
                            name="order[min_total]" :value="old('order.min_total', Setting::get('order.min_total'))" />
                        <x-setting.input type="number" min="0" step="1" label="Tự hủy đơn chờ thanh toán (phút)"
                            name="order[auto_cancel_minutes]" :value="old('order.auto_cancel_minutes', Setting::get('order.auto_cancel_minutes'))" />
                    </div>
                </x-card>
            </div>

            {{-- SHIPPING --}}
            <div class="{{ $tab==='shipping' ? '' : 'hidden' }}">
                <x-card title="Vận chuyển" desc="Đơn vị và ngưỡng miễn phí.">
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input type="number" min="0" step="1000" label="Ngưỡng miễn phí vận chuyển (VND)"
                            name="shipping[freeship_threshold]" :value="old('shipping.freeship_threshold', Setting::get('shipping.freeship_threshold'))" />
                        <div>
                            <label class="block text-sm font-medium mb-1">Đơn vị khối lượng</label>
                            <select name="shipping[unit][weight]" class="w-full rounded border-slate-300">
                                @foreach (['kg'=>'kg','g'=>'g'] as $k=>$v)
                                <option value="{{ $k }}" @selected(old('shipping.unit.weight', Setting::get('shipping.unit.weight','kg'))==$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Đơn vị kích thước</label>
                            <select name="shipping[unit][dimension]" class="w-full rounded border-slate-300">
                                <option value="cm" @selected(old('shipping.unit.dimension', Setting::get('shipping.unit.dimension','cm'))=='cm' )>cm</option>
                            </select>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- EMAIL --}}
            <div class="{{ $tab==='email' ? '' : 'hidden' }}">
                <x-card title="Email" desc="Thông tin người gửi & SMTP.">
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input label="From name" name="mail[from_name]" :value="old('mail.from_name', Setting::get('mail.from_name'))" />
                        <x-setting.input type="email" label="From address" name="mail[from_address]" :value="old('mail.from_address', Setting::get('mail.from_address'))" />
                        <x-setting.input label="SMTP Host" name="smtp[host]" :value="old('smtp.host', Setting::get('smtp.host'))" />
                        <x-setting.input type="number" label="SMTP Port" name="smtp[port]" :value="old('smtp.port', Setting::get('smtp.port'))" />
                        <x-setting.input label="SMTP Username" name="smtp[username]" :value="old('smtp.username', Setting::get('smtp.username'))" />
                        <x-setting.input label="SMTP Password" name="smtp[password]" :value="old('smtp.password', Setting::get('smtp.password'))" />
                        <div>
                            <label class="block text-sm font-medium mb-1">Encryption</label>
                            <select name="smtp[encryption]" class="w-full rounded border-slate-300">
                                @foreach ([''=>'(none)','tls'=>'TLS','ssl'=>'SSL'] as $k=>$v)
                                <option value="{{ $k }}" @selected(old('smtp.encryption', Setting::get('smtp.encryption'))===$k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- POLICY --}}
            <div class="{{ $tab==='policy' ? '' : 'hidden' }}">
                <x-card title="Trả hàng / Hoàn tiền" desc="Chính sách áp dụng cho khách hàng.">
                    <div class="grid md:grid-cols-2 gap-5">
                        <x-setting.input type="number" min="0" step="1" label="Số ngày cho phép đổi trả"
                            name="policy[return_days]" :value="old('policy.return_days', Setting::get('policy.return_days'))" />
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Điều kiện đổi trả (text)</label>
                            <textarea name="policy[return_conditions]" rows="6" class="w-full rounded border-slate-300">{{ old('policy.return_conditions', Setting::get('policy.return_conditions')) }}</textarea>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- PAGES --}}
            <div class="{{ $tab==='pages' ? '' : 'hidden' }}">
                <x-card title="Trang tĩnh" desc="Nội dung HTML cho các trang thông tin.">
                    <div class="grid gap-6">
                        @foreach (['about'=>'Giới thiệu','privacy'=>'Chính sách bảo mật','terms'=>'Điều khoản sử dụng'] as $k=>$label)
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $label }}</label>
                            <textarea name="pages[{{ $k }}]" rows="10" class="w-full rounded border-slate-300">{{ old("pages.$k", Setting::get("pages.$k")) }}</textarea>
                            <p class="text-xs text-slate-500 mt-1">Có thể thay bằng WYSIWYG (Quill/TinyMCE) khi cần.</p>
                        </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </section>
    </div>
</form>
@endsection