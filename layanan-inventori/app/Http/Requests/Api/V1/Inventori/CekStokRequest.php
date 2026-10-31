<?php

namespace App\Http\Requests\Api\V1\Inventori;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CekStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.kode_menu' => [
                'required',
                'string',
                'distinct',
            ],
            'items.*.jumlah' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}
