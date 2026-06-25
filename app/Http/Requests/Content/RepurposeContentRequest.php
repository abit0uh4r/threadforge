<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class RepurposeContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'content' => ['required', 'string', 'min:10', 'max:50000'],
            'source_type' => ['sometimes', 'string', 'in:text,markdown,readme'],
        ];
    }
}