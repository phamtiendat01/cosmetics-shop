<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền tạo đã do Policy kiểm soát ở Controller
    }

    public function rules(): array
    {
        return [
            'rating'  => ['required', 'integer', 'between:1,5'],
            'title'   => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Vui lòng chọn số sao.',
            'rating.between'  => 'Điểm sao phải từ 1 đến 5.',
            'content.required' => 'Vui lòng nhập nội dung đánh giá.',
            'content.min'     => 'Nội dung quá ngắn (tối thiểu 10 ký tự).',
        ];
    }
}
