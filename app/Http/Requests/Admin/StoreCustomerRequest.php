<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'gender'   => ['nullable', Rule::in(['male', 'female', 'other'])],
            'dob'      => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],

            // địa chỉ mặc định (optional)
            'shipping_address.line1'   => ['nullable', 'string', 'max:255'],
            'shipping_address.city'    => ['nullable', 'string', 'max:100'],
            'shipping_address.district' => ['nullable', 'string', 'max:100'],
            'shipping_address.province' => ['nullable', 'string', 'max:100'],
            'shipping_address.postal'  => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Điện thoại', 'password' => 'Mật khẩu'];
    }
}
