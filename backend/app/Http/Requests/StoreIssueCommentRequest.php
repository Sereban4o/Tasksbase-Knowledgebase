<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Текст комментария обязателен',
        ];
    }
}
