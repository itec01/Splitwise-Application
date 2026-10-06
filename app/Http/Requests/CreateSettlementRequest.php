<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_by' => ['sometimes', 'required', 'string'],
            'paid_to' => ['required', 'string', 'different:paid_by'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $paidBy = $this->input('paid_by');
            $paidTo = $this->input('paid_to');

            if (
                $paidBy !== null
                && $paidTo !== null
                && (string) $paidBy === (string) $paidTo
            ) {
                $validator->errors()->add(
                    'paid_to',
                    'Payer and receiver cannot be the same user.'
                );
            }
        });
    }
}