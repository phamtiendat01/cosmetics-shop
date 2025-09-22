<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'   => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', 'unique:coupons,code'],
            'name'   => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'discount_type'  => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attr, $val, $fail) {
                    if (request('discount_type') === 'percent' && ($val <= 0 || $val > 100)) $fail('Phần trăm phải trong (0,100]');
                }
            ],
            'max_discount'   => ['nullable', 'numeric', 'min:0'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],

            'applied_to'     => ['required', Rule::in(['order', 'category', 'brand', 'product'])],
            'applies_to_ids' => ['nullable', 'array'],
            'applies_to_ids.*' => ['integer'],

            'is_stackable'       => ['boolean'],
            'first_order_only'   => ['boolean'],
            'is_active'          => ['boolean'],

            'usage_limit'            => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user'   => ['nullable', 'integer', 'min:1'],

            'starts_at' => ['nullable', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper((string)$this->code)]);
        if ($this->applied_to === 'order') $this->merge(['applies_to_ids' => null]);
    }
}
