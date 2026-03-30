<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RagAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:1', 'max:2000'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:100'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }

    public function defaults(): array
    {
        return [
            'top_k' => 10,
            'min_score' => 0.75,
        ];
    }
}
