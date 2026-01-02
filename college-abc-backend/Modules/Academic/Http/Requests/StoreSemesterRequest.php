<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterRequest extends FormRequest
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
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:trimester,semester',
            'number' => 'required|integer|min:1|max:3',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];
    }
}
