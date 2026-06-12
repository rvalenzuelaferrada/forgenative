<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreForgeCredentialRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('forge_credentials', 'name'),
            ],
            'token' => ['required', 'string', 'min:20', 'max:16384'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('forge.validation.name_required'),
            'name.unique' => trans('forge.validation.name_unique'),
            'token.required' => trans('forge.validation.token_required'),
            'token.min' => trans('forge.validation.token_min'),
            'token.max' => trans('forge.validation.token_max'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->trim()->toString(),
            'token' => $this->string('token')->trim()->toString(),
        ]);
    }
}
