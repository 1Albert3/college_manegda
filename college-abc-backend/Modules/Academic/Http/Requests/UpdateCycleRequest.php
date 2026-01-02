<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCycleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implémenter la vérification des permissions
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $cycleId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('cycles', 'name')->ignore($cycleId),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('cycles', 'slug')->ignore($cycleId),
            ],
            'description' => 'sometimes|nullable|string',
            'order' => 'sometimes|nullable|integer|min:0',
            'is_active' => 'sometimes|nullable|boolean',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du cycle est requis.',
            'name.unique' => 'Ce nom de cycle existe déjà.',
            'slug.unique' => 'Ce slug existe déjà.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'description' => 'description',
            'order' => 'ordre',
            'is_active' => 'statut actif',
        ];
    }
}
