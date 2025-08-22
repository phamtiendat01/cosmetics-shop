<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage shipping');
    }
    public function rules(): array
    {
        return [
            'carrier_id' => 'required|exists:shipping_carriers,id',
            'zone_id'    => 'nullable|exists:shipping_zones,id',
            'name'       => 'nullable|string|max:100',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|gte:min_weight',
            'min_total'  => 'nullable|integer|min:0',
            'max_total'  => 'nullable|integer|gte:min_total',
            'base_fee'   => 'required|integer|min:0',
            'per_kg_fee' => 'required|integer|min:0',
            'etd_min_days' => 'nullable|integer|min:1|max:30',
            'etd_max_days' => 'nullable|integer|gte:etd_min_days|max:60',
            'enabled'    => 'nullable|boolean',
        ];
    }
}
