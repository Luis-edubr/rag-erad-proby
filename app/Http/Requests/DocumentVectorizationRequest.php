<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentVectorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:txt,pdf', 'max:10240'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
        ];
    }
}
