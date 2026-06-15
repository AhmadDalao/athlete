<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApiTokenStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $tokenName = $this->input('token_name', $this->input('device_name'));

        $this->merge([
            'email' => is_string($this->input('email')) ? trim($this->input('email')) : null,
            'token_name' => is_string($tokenName) ? trim($tokenName) : null,
            'abilities' => is_array($this->input('abilities')) ? array_values(array_unique($this->input('abilities'))) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:50'],
        ];
    }
}
