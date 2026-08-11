<?php

namespace App\Http\Requests\Api\V1;

class MessageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('messages.send') === true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
