<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_type_id' => 'sometimes|exists:issue_types,id',
            'status_id' => 'sometimes|exists:issue_statuses,id',
            'summary' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'assignee_id' => 'sometimes|nullable|exists:users,id',
            'priority' => 'sometimes|in:lowest,low,medium,high,highest',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after:start_date',
            'story_points' => 'nullable|integer|min:0',
            'custom_fields' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'summary.max' => 'Заголовок не может быть длиннее 500 символов',
            'due_date.after' => 'Дата окончания должна быть после даты начала',
        ];
    }
}
