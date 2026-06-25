<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'target_audience' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'max_length' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'max_hashtags' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'rules' => ['sometimes', 'nullable', 'array'],
            'rules.*' => ['string'],
        ];
    }
}
