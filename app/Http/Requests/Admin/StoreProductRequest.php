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
            'image'       => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string'],

            'variants'                       => ['required', 'array', 'min:1'],
            'variants.*.name'                => ['nullable', 'string', 'max:255'],
            'variants.*.sku'                 => ['nullable', 'string', 'max:100', 'distinct', Rule::unique('product_variants', 'sku')],
            'variants.*.price'               => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price'    => ['nullable', 'numeric', 'lt:variants.*.price'],
            'variants.*.qty_in_stock'        => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }

    // lọc dòng biến thể rỗng giá
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
            'slug.unique'    => 'Slug đã tồn tại, hãy đổi slug hoặc tên sản phẩm.',
            'image.image'    => 'Ảnh đại diện không hợp lệ.',
            'image.max'      => 'Ảnh tối đa 2MB.',

            'variants.required' => 'Vui lòng thêm ít nhất 1 biến thể.',
            'variants.*.price.required' => 'Giá bán là bắt buộc.',
            'variants.*.price.min'      => 'Giá bán phải ≥ 0.',
            'variants.*.compare_at_price.gte' => 'Giá gốc phải < Giá bán.',
            'variants.*.qty_in_stock.required' => 'Vui lòng nhập tồn kho.',
            'variants.*.qty_in_stock.min'      => 'Tồn kho không được âm.',
            'variants.*.sku.distinct'          => 'SKU các biến thể không được trùng nhau.',
            'variants.*.sku.unique'            => 'SKU này đã tồn tại.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                         => 'tên sản phẩm',
            'slug'                         => 'slug',
            'variants.*.name'              => 'tên biến thể',
            'variants.*.sku'               => 'SKU',
            'variants.*.price'             => 'giá bán',
            'variants.*.compare_at_price'  => 'giá gốc',
            'variants.*.qty_in_stock'      => 'tồn kho',
            'variants.*.low_stock_threshold' => 'ngưỡng cảnh báo',
        ];
    }
}
