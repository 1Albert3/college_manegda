<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'period' => 'required|in:annuel,trimestriel_1,trimestriel_2,trimestriel_3,mensuel',
            'due_date' => 'nullable|date|after:today',
            'issue_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
            'auto_issue' => 'nullable|boolean',
            'fee_types' => 'nullable|array',
            'fee_types.*.fee_type_id' => 'required_with:fee_types|exists:fee_types,id',
            'fee_types.*.quantity' => 'nullable|integer|min:1',
            'fee_types.*.discount' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'élève',
            'academic_year_id' => 'année académique',
            'period' => 'période',
            'due_date' => 'date d\'échéance',
            'issue_date' => 'date d\'émission',
            'notes' => 'notes',
            'auto_issue' => 'émission automatique',
            'fee_types' => 'types de frais',
            'fee_types.*.fee_type_id' => 'type de frais',
            'fee_types.*.quantity' => 'quantité',
            'fee_types.*.discount' => 'réduction',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'period.in' => 'La période doit être : annuel, trimestriel_1, trimestriel_2, trimestriel_3 ou mensuel.',
            'due_date.after' => 'La date d\'échéance doit être dans le futur.',
            'issue_date.before_or_equal' => 'La date d\'émission ne peut pas être dans le futur.',
        ];
    }
}
