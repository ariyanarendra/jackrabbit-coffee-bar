<?php

namespace App\Http\Requests\Api\V1\Operasional;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerbaruiStatusOperasionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_operasional' => [
                'required',
                'string',
                Rule::in([
                    'menunggu_diproses',
                    'sedang_diproses',
                    'siap_diambil',
                    'selesai',
                ]),
            ],
        ];
    }
}
