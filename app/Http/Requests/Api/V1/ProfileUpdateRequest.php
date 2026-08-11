<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1500'],
            'primary_goal' => ['sometimes', 'nullable', 'string', 'max:500'],
            'theme' => ['sometimes', Rule::in(['system', 'dark', 'light'])],
        ];
    }
}
