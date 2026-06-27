<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assignee_id' => 'sometimes|nullable|exists:users,id',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}