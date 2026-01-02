<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeeTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implémenter la vérification des permissions
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'frequency' => 'sometimes|required|in:mensuel,trimestriel,annuel,unique',
            'cycle_id' => 'nullable|exists:cycles,id',
            'level_id' => 'nullable|exists:levels,id',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
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
            'amount' => 'montant',
            'frequency' => 'fréquence',
            'cycle_id' => 'cycle',
            'level_id' => 'niveau',
            'is_mandatory' => 'obligatoire',
            'is_active' => 'actif',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'frequency.in' => 'La fréquence doit être : mensuel, trimestriel, annuel ou unique.',
            'amount.min' => 'Le montant doit être supérieur ou égal à 0.',
        ];
    }
}
