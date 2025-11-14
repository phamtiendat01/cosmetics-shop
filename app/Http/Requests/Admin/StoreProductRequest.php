<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')],
            'brand_id'    => ['nullable', 'exists:brands,id'],
            'category_id' => ['nullable', 'exists:categories,id'],

            // 👇 mô tả đúng trường
            'short_desc'  => ['nullable', 'string'],
            'long_desc'   => ['nullable', 'string'],

            // ảnh: chấp nhận thumbnail hoặc image để tương thích
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

            // biến thể
            'variants'                       => ['required', 'array', 'min:1'],
            'variants.*.name'                => ['nullable', 'string', 'max:255'],
            'variants.*.sku'                 => ['nullable', 'string', 'max:100', 'distinct', Rule::unique('product_variants', 'sku')],
            'variants.*.price'               => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price'    => ['nullable', 'numeric', 'lt:variants.*.price'],
            'variants.*.qty_in_stock'        => ['required', 'integer', 'min:0'],
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

            'variants.required' => 'Vui lòng thêm ít nhất 1 biến thể.',
            'variants.*.price.required' => 'Giá bán là bắt buộc.',
            'variants.*.price.min'      => 'Giá bán phải ≥ 0.',
            'variants.*.compare_at_price.lt' => 'Giá gốc phải > giá bán.',
            'variants.*.qty_in_stock.required' => 'Vui lòng nhập tồn kho.',
            'variants.*.qty_in_stock.min'      => 'Tồn kho không được âm.',
            'variants.*.sku.distinct'          => 'SKU các biến thể không được trùng nhau.',
            'variants.*.sku.unique'            => 'SKU này đã tồn tại.',
        ];
    }
}
