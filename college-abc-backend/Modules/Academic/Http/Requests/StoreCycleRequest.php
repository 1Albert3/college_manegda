<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCycleRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|unique:cycles,name',
            'slug' => 'nullable|string|max:255|unique:cycles,slug',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
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
