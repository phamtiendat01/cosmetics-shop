<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product?->id)],
            'brand_id'    => ['nullable', 'exists:brands,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'short_desc'  => ['nullable', 'string'],
            'long_desc'   => ['nullable', 'string'],
            'thumbnail'   => ['nullable', 'image', 'max:2048'],
            'image'       => ['nullable', 'image', 'max:2048'],

            // Bot fields
            'skin_types'         => ['nullable', 'array'],
            'skin_types.*'       => ['string', Rule::in(['oily', 'dry', 'combination', 'sensitive', 'normal'])],
            'concerns'           => ['nullable', 'array'],
            'concerns.*'         => ['string'],
            'ingredients'        => ['nullable', 'array'],
            'ingredients.*'      => ['string'],
            'benefits'           => ['nullable', 'string', 'max:1000'],
            'usage_instructions' => ['nullable', 'string', 'max:1000'],
            'age_range'          => ['nullable', 'string', Rule::in(['teen', 'adult', 'mature', 'all'])],
            'gender'             => ['nullable', 'string', Rule::in(['male', 'female', 'unisex'])],
            'product_type'       => ['nullable', 'string', Rule::in(['serum', 'cream', 'toner', 'cleanser', 'moisturizer', 'sunscreen', 'mask', 'essence', 'eye_cream', 'other'])],
            'texture'            => ['nullable', 'string', Rule::in(['gel', 'cream', 'liquid', 'foam', 'oil', 'balm', 'powder', 'spray'])],
            'spf'                => ['nullable', 'integer', 'min:0', 'max:100'],
            'fragrance_free'     => ['nullable', 'boolean'],
            'cruelty_free'       => ['nullable', 'boolean'],
            'vegan'              => ['nullable', 'boolean'],

            'variants'                    => ['required', 'array', 'min:1'],
            'variants.*.id'               => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.name'             => ['nullable', 'string', 'max:255'],
            'variants.*.sku'              => [
                'nullable',
                'string',
                'max:100',
                'distinct',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    preg_match('/^variants\.(\d+)\./', $attribute, $m);
                    $i  = $m[1] ?? null;
                    $id = $i !== null ? $this->input("variants.$i.id") : null;
                    $exists = \App\Models\ProductVariant::where('sku', $value)
                        ->when($id, fn($q) => $q->where('id', '!=', $id))
                        ->exists();
                    if ($exists) $fail('SKU này đã tồn tại.');
                },
            ],
            'variants.*.price'            => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            // 🚫 Không cho nhận tồn kho từ form Edit (tránh reset 0 khi bấm Lưu)
            'variants.*.qty_in_stock'     => ['prohibited'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $variants = collect($this->input('variants', []))
            ->filter(fn($v) => isset($v['price']) && $v['price'] !== '' && $v['price'] !== null)
            ->values()->all();
        $this->merge(['variants' => $variants]);
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Vui lòng nhập tên sản phẩm.',
            'slug.unique'    => 'Slug đã tồn tại.',
            'thumbnail.image' => 'Ảnh đại diện không hợp lệ.',
            'thumbnail.max'  => 'Ảnh tối đa 2MB.',
            'image.image'    => 'Ảnh đại diện không hợp lệ.',
            'image.max'      => 'Ảnh tối đa 2MB.',

            'variants.required'          => 'Vui lòng giữ ít nhất 1 biến thể.',
            'variants.*.price.required'  => 'Giá bán là bắt buộc.',
            'variants.*.price.min'       => 'Giá bán phải ≥ 0.',
            'variants.*.sku.distinct'    => 'SKU các biến thể không được trùng nhau.',
            // (không còn thông báo bắt nhập tồn kho)
        ];
    }
}
