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
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240', 'required_without:body'],
        ];
    }
}
