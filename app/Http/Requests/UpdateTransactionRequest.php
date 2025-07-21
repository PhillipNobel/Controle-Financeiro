<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item' => 'required|string|max:255',
            'date' => 'required|date',
            'quantity' => 'required|numeric|min:0',
            'value' => 'required|numeric',
            'wallet_id' => 'required|exists:wallets,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item.required' => 'O campo item é obrigatório.',
            'item.string' => 'O campo item deve ser um texto.',
            'item.max' => 'O campo item não pode ter mais de 255 caracteres.',
            'date.required' => 'O campo data é obrigatório.',
            'date.date' => 'O campo data deve ser uma data válida.',
            'quantity.required' => 'O campo quantidade é obrigatório.',
            'quantity.numeric' => 'O campo quantidade deve ser um número.',
            'quantity.min' => 'O campo quantidade deve ser maior ou igual a zero.',
            'value.required' => 'O campo valor é obrigatório.',
            'value.numeric' => 'O campo valor deve ser um número.',
            'wallet_id.required' => 'O campo carteira é obrigatório.',
            'wallet_id.exists' => 'A carteira selecionada não existe.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Dados de validação falharam',
            'errors' => $validator->errors()
        ], 422));
    }
}
