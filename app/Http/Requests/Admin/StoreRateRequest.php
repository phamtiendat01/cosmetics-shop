<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier_id'   => 'required|exists:shipping_carriers,id',
            'zone_id'      => 'required|exists:shipping_zones,id',
            'name'         => 'required|string|max:255',
            'min_weight'   => 'nullable|integer|min:0',
            'max_weight'   => 'nullable|integer|min:0',
            'min_total'    => 'nullable|integer|min:0',
            'max_total'    => 'nullable|integer|min:0',
            'base_fee'     => 'required|integer|min:0',
            'per_kg_fee'   => 'required|integer|min:0',
            'etd_min_days' => 'nullable|integer|min:0',
            'etd_max_days' => 'nullable|integer|min:0',
            'enabled'      => 'sometimes|boolean',
        ];
    }
}
