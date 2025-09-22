<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'position'        => ['required', Rule::in(array_keys(\App\Models\Banner::POSITIONS))],
            'device'          => ['required', Rule::in(['all', 'desktop', 'mobile'])],
            'image'           => ['nullable', 'image', 'max:4096'],
            'mobile_image'    => ['nullable', 'image', 'max:4096'],
            'url'             => ['nullable', 'url', 'max:2048'],
            'open_in_new_tab' => ['nullable', 'boolean'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'is_active'       => ['required', 'boolean'],
            'starts_at'       => ['nullable', 'date'],
            'ends_at'         => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
