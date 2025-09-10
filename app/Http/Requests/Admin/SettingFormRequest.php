<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage settings') ?? false;
    }

    // điền giá trị mặc định cho checkbox (unchecked => 0)
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment' => array_replace_recursive([
                'cod'  => ['enabled' => 0, 'fee_percent' => null],
                'vnpay' => ['enabled' => 0, 'tmn_code' => null, 'hash_secret' => null, 'sandbox' => 1],
                'momo' => ['enabled' => 0, 'partner_code' => null, 'access_key' => null, 'secret_key' => null, 'sandbox' => 1],
            ], $this->input('payment', [])),

            'checkout' => array_replace_recursive([
                'allow_guest' => 0,
            ], $this->input('checkout', [])),
        ]);
    }

    public function rules(): array
    {
        return [
            // Cửa hàng
            'store.name'      => 'required|string|max:150',
            'store.logo'      => 'nullable|string|max:255',     // URL/logo path
            'store.favicon'   => 'nullable|string|max:255',
            'store.hotline'   => 'nullable|string|max:50',
            'store.email'     => 'nullable|email',
            'store.address'   => 'nullable|string|max:255',
            'store.currency'  => 'required|in:VND,USD',
            'store.locale'    => 'required|in:vi,en',
            'store.timezone'  => 'required|timezone',

            // SEO & Tracking
            'seo.default_title'       => 'nullable|string|max:70',
            'seo.default_description' => 'nullable|string|max:200',
            'seo.og_image'            => 'nullable|string|max:255',
            'tracking.gtag_id'        => 'nullable|string|max:50',
            'tracking.fb_pixel_id'    => 'nullable|string|max:50',

            // Thanh toán
            'payment.cod.enabled'     => 'boolean',
            'payment.cod.fee_percent' => 'nullable|numeric|min:0|max:100',

            'payment.vnpay.enabled'   => 'boolean',
            'payment.vnpay.tmn_code'  => 'nullable|string|max:100',
            'payment.vnpay.hash_secret' => 'nullable|string|max:255',
            'payment.vnpay.sandbox'   => 'boolean',

            'payment.momo.enabled'    => 'boolean',
            'payment.momo.partner_code' => 'nullable|string|max:100',
            'payment.momo.access_key' => 'nullable|string|max:100',
            'payment.momo.secret_key' => 'nullable|string|max:255',
            'payment.momo.sandbox'    => 'boolean',

            // Order/Checkout
            'checkout.allow_guest'      => 'boolean',
            'order.min_total'           => 'nullable|integer|min:0',
            'order.auto_cancel_minutes' => 'nullable|integer|min:0',

            // Shipping
            'shipping.freeship_threshold' => 'nullable|integer|min:0',
            'shipping.unit.weight'        => 'required|in:kg,g',
            'shipping.unit.dimension'     => 'required|in:cm',

            // Email
            'mail.from_name'      => 'nullable|string|max:100',
            'mail.from_address'   => 'nullable|email',
            'smtp.host'           => 'nullable|string|max:150',
            'smtp.port'           => 'nullable|integer|min:1',
            'smtp.username'       => 'nullable|string|max:150',
            'smtp.password'       => 'nullable|string|max:255',
            'smtp.encryption'     => 'nullable|in:tls,ssl',

            // Policy
            'policy.return_days'       => 'nullable|integer|min:0|max:365',
            'policy.return_conditions' => 'nullable|string',

            // Pages (HTML)
            'pages.about'    => 'nullable|string',
            'pages.privacy'  => 'nullable|string',
            'pages.terms'    => 'nullable|string',
        ];
    }
}
