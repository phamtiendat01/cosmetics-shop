<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage shipping');
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'province_codes' => 'nullable|array',
            'province_codes.*' => 'string|max:10',
            'enabled' => 'nullable|boolean',
        ];
    }
}
