<?php

namespace App\Http\Requests\Ews;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimulasiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'wilayah_id' => ['required', 'integer', Rule::exists('ews_wilayah', 'id')->whereNull('deleted_at')],
            'kelembapan_tanah' => ['nullable', 'numeric', 'between:0,100'],
            'curah_hujan' => ['nullable', 'numeric', 'between:0,1000'],
            'kemiringan' => ['nullable', 'numeric', 'between:-90,90'],
            'kelembapan_udara' => ['nullable', 'numeric', 'between:0,100'],
            'kabel_putus' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, float|null>
     */
    public function bacaan(): array
    {
        return collect(['kelembapan_tanah', 'curah_hujan', 'kemiringan', 'kelembapan_udara'])
            ->mapWithKeys(fn (string $s) => [$s => $this->validated($s) === null ? null : (float) $this->validated($s)])
            ->all();
    }
}
