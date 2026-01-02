<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create-payments');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date|before_or_equal:today',
            'payment_method' => 'required|in:especes,cheque,virement,mobile_money,carte',
            'reference' => 'nullable|string|max:255',
            'payer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:en_attente,valide',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'élève',
            'fee_type_id' => 'type de frais',
            'academic_year_id' => 'année académique',
            'amount' => 'montant',
            'payment_date' => 'date de paiement',
            'payment_method' => 'méthode de paiement',
            'reference' => 'référence',
            'payer_name' => 'nom du payeur',
            'notes' => 'notes',
            'status' => 'statut',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.in' => 'La méthode de paiement doit être : espèces, chèque, virement, mobile money ou carte.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'payment_date.before_or_equal' => 'La date de paiement ne peut pas être dans le futur.',
        ];
    }
}
