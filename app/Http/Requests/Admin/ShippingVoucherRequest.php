<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShippingVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage shipping vouchers') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('shipvoucher')?->id;
        return [
            'code'           => 'required|string|max:64|unique:shipping_vouchers,code,' . ($id ?? 'null') . ',id',
            'title'          => 'nullable|string|max:255',
            'discount_type'  => 'required|in:fixed,percent',
            'amount'         => 'required|integer|min:1',
            'max_discount'   => 'nullable|integer|min:1',
            'min_order'      => 'nullable|integer|min:0',
            'start_at'       => 'nullable|date',
            'end_at'         => 'nullable|date|after_or_equal:start_at',
            'usage_limit'    => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'is_active'      => 'boolean',
            'note'           => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Mã đã tồn tại.',
            'end_at.after_or_equal' => 'Thời gian kết thúc phải sau hoặc bằng thời gian bắt đầu.',
        ];
    }
}
