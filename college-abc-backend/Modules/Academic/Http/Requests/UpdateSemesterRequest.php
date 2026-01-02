<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-academic');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|required|in:trimester,semester',
            'number' => 'sometimes|required|integer|min:1|max:3',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'is_current' => 'sometimes|nullable|boolean',
            'description' => 'sometimes|nullable|string',
        ];
    }
}
