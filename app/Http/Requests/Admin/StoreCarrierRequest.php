<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarrierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage shipping');
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:shipping_carriers,code,' . ($this->carrier->id ?? 'NULL') . ',id',
            'logo' => 'nullable|string|max:1000',
            'supports_cod' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
