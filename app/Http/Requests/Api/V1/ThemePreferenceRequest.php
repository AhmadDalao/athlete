<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class ThemePreferenceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::in(['system', 'dark', 'light'])],
        ];
    }
}
