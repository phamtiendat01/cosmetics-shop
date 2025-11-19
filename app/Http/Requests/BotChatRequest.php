<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BotChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message'    => ['nullable', 'string', 'max:1000'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'tool_id'    => ['nullable', 'integer', 'exists:bot_tools,id'],
        ];
    }
}
