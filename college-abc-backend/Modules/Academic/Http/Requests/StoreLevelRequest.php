<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLevelRequest extends FormRequest
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
        return [
            'cycle_id' => 'required|exists:cycles,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:levels,code',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cycle_id.required' => 'Le cycle est obligatoire.',
            'cycle_id.exists' => 'Le cycle sélectionné est invalide.',
            'name.required' => 'Le nom du niveau est obligatoire.',
            'name.max' => 'Le nom du niveau ne doit pas dépasser 255 caractères.',
            'code.unique' => 'Ce code de niveau existe déjà.',
            'code.max' => 'Le code ne doit pas dépasser 50 caractères.',
        ];
    }
}
