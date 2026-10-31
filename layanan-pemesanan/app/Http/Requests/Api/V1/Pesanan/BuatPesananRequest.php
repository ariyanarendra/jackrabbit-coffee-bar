<?php

namespace App\Http\Requests\Api\V1\Pesanan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BuatPesananRequest extends FormRequest
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
            'kanal' => ['required', 'string'],
            'nama_pelanggan' => ['required', 'string', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.kode_menu' => ['required', 'string'],
            'items.*.nama_menu' => ['required', 'string'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan' => ['required', 'integer', 'min:0'],
        ];
    }
}
