<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'data' => null,
            'meta' => (object) [],
            'links' => (object) [],
            'error' => [
                'code' => 'validation_failed',
                'message' => 'The submitted data is invalid.',
                'fields' => $validator->errors(),
            ],
        ], 422));
    }
}
