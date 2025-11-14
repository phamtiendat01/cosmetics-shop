<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'slug'       => ['nullable', 'string', 'max:255', 'unique:brands,slug'],
            'website'    => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
            'logo'       => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Tên thương hiệu',
            'slug' => 'Slug',
            'logo' => 'Logo',
        ];
    }
}
