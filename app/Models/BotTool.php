<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotTool extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'question', // Câu hỏi hiển thị cho user
        'answer', // Câu trả lời
        'category', // Phân loại
        'order', // Thứ tự hiển thị
        'icon', // Icon/emoji
        'description',
        'parameters_schema',
        'handler_class',
        'is_active',
        'config',
    ];

    protected $casts = [
        'parameters_schema' => 'array',
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get function declaration for LLM
     */
    public function toFunctionDeclaration(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters_schema,
        ];
    }
}
