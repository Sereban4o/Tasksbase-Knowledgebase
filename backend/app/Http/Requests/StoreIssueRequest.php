<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_key' => 'required|string|exists:projects,key',
            'issue_type_id' => 'nullable|exists:issue_types,id',
            'status_id' => 'nullable|exists:issue_statuses,id',
            'summary' => 'required|string|max:500',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:issues,id',
            'epic_id' => 'nullable|exists:issues,id',
            'priority' => 'nullable|in:lowest,low,medium,high,highest',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after:start_date',
            'story_points' => 'nullable|integer|min:0',
            'custom_fields' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'project_key.required' => 'Ключ проекта обязателен',
            'project_key.exists' => 'Проект не найден',
            'summary.required' => 'Заголовок обязателен',
            'summary.max' => 'Заголовок не может быть длиннее 500 символов',
            'due_date.after' => 'Дата окончания должна быть после даты начала',
        ];
    }
}
