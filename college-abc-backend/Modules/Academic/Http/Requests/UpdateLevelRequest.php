<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLevelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implement role-based authorization
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $levelId = $this->route('id'); // Get ID from route parameter

        return [
            'cycle_id' => 'sometimes|required|exists:cycles,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|nullable|string|max:50|unique:levels,code,' . $levelId,
            'description' => 'sometimes|nullable|string',
            'order' => 'sometimes|nullable|integer|min:0',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cycle_id.exists' => 'Le cycle sélectionné est invalide.',
            'name.required' => 'Le nom du niveau est obligatoire.',
            'code.unique' => 'Ce code de niveau existe déjà.',
        ];
    }
}
