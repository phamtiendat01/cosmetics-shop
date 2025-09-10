<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'slug'       => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'parent_id'  => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => 'Tên danh mục',
            'slug'       => 'Slug',
            'parent_id'  => 'Danh mục cha',
            'sort_order' => 'Thứ tự',
            'is_active'  => 'Trạng thái',
        ];
    }
}
